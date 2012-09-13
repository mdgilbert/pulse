<?php

ini_set('display_errors', 'On');

// Set up the database connection
$kona_mycnf = parse_ini_file(".my.wikiproj.cnf");
$uw_db = mysql_connect($kona_mycnf['uw_host'] . ":" . $kona_mycnf['uw_port'], $kona_mycnf['uw_user'], $kona_mycnf['uw_password']);
if (! $uw_db) {
  wpLog("Failed to connect to UW DB: " . mysql_error());
  exit;
}
unset($kona_mycnf);
mysql_select_db('wikiproj', $uw_db);

$ns_lookup = array(0 => 'Article', 1 => 'Talk', 2 => 'User', 3 => 'User_talk', 4 => 'Wikipedia', 5 => 'Wikipedia_talk',
    6 => 'File', 7 => 'File_talk', 8 => 'Mediawiki', 9 => 'Mediawiki_talk', 10 => 'Template', 11 => 'Template_talk',
    12 => 'Help', 13 => 'Help_talk', 14 => 'Category', 15 => 'Category_talk', 100 => 'Portal', 101 => 'Portal_talk',
    108 => 'Book', 109 => 'Book_talk');

// Get all the projects
$projects = utility_getActiveProjects(array('page' => 1));


function utility_convertDateToWikiMonth($date) {
  // Given a date like '20110101' will calculate the number of months between 20010101 and that date
  $s = date_create_from_format('Ymd', '20010101');
  $e = date_create_from_format('Ymd', $date);
  $diff = $s->diff($e);
  // Need to add years to the month diff
  $m = ($diff->format('%y') * 12) + $diff->format('%m');
  return $m;
}

function utility_convertWikiMonthToDate($month) {
  return date('Ymd', strtotime("20010101 +" . $month . " month"));
}

function utility_getRandomPageForProject($pid) {
  global $uw_db;
  $table = 'project_pages';
  $query = sprintf("SELECT FLOOR(RAND() * COUNT(*)) AS `offset` FROM `%s` WHERE pp_projectid = %d",
    mysql_real_escape_string($table),
    mysql_real_escape_string($pid));
  $result = mysql_query($query);
  if (! $result) {
    return array("message" => "Failed to determine offset: " . mysql_error(), "errorstatus" => "fail");
  }
  $offset_row = mysql_fetch_object($result);
  $offset = $offset_row->offset;
  $query = sprintf("SELECT * FROM `%s` LIMIT %d, 1",
    mysql_real_escape_string($table),
    mysql_real_escape_string($offset));
  $result = mysql_query($query);
  if (!$result) {
    return array("message" => "Failed to fetch random row: " . mysql_error(), "errorstatus" => "fail");
  }
  return mysql_fetch_assoc($result);
}

function utility_getFirstPageForProject($pid, $offset = 0) {
  global $uw_db;
  $query = sprintf("SELECT * FROM project_pages WHERE pp_projectid = %d LIMIT %d, 1",
    mysql_real_escape_string($pid),
    mysql_real_escape_string($offset));
  $result = mysql_query($query, $uw_db);
  //echo "QUERY: $query<br/>";
  if (!$result) {
    return array("message" => "Failed to fetch first row: " . mysql_error(), "errorstatus" => "fail");
  }
  while ($row = mysql_fetch_assoc($result)) {
    return $row;
  }
  return null;
}

function utility_getProjectsForNav() {
  global $uw_db;
  $query = sprintf("SELECT * FROM project ORDER BY p_articlecount DESC LIMIT 10");
  $result  = mysql_query($query, $uw_db);
  if (!$result) {
    return array("message" => "Failed to query for nav projects" . mysql_error(), "errorstatus" => "fail");
  }
  $json = array();
  while ($row = mysql_fetch_assoc($result)) {
    $json[] = array('title' => $row['p_aka'], 'link' => $row['p_title']);
  }
  //$json['errorstatus'] = 'success';
  return $json;
}

function utility_getActiveProjects($request) {
  global $uw_db;
  // To get projects with the most edits to the project page for the last month (the month with full data):
  $last_month = (int) utility_convertDateToWikiMonth(date('Ymd'));
  $last_month--;
  // Get the page (determines offset), show 15 projects per page
  $page = isset($request['page']) ? $request['page'] : 1;
  $floor = $page == 1 ? 0 : 15 * ($page - 1);
  $query = sprintf("SELECT p_id, pue_month, p_title, p_aka, count(pue_project_edits) AS 'count' FROM project JOIN project_user_edits ON pue_projectid = p_id WHERE pue_month = %d GROUP BY p_title ORDER BY count DESC",
    mysql_real_escape_string($last_month),
    mysql_real_escape_string($floor));
  $result = mysql_query($query, $uw_db);
  if (!$result) {
    return array("message" => "Failed to query for active projects: " . mysql_error(), "errorstatus" => "false");
  }
  unset($json); $json = array();
  while ($row = mysql_fetch_array($result)) {
    echo "Checking project: " . $row['p_aka'];
    $img = utility_getProjectImage(array('pid' => $row['p_id']));
    if (isset($img['errorstatus']) && $img['errorstatus'] == 'fail') { $json['errors'][] = $img; continue; }
    $json[] = array(
      'p_id' => $row['p_id'], 'p_title' => $row['p_title'], 'p_aka' => $row['p_aka'], 'count' => $row['count'],
      'href' => $img['href'], 'width' => $img['width'], 'height' => $img['height'], 'pi_id' => $img['pi_id'], 
      'piv_vote' => $img['piv_vote'], 'pi_img' => $img['pi_img'],
    );
    if (! isset($img['href'])) {
      echo "ERROR: No image found!!!\n";
      var_dump($img);
      exit;
    }
    echo " - " . $img['href'] . "\n";
  }
  $json['errorstatus'] = 'success';
  $json['offset'] = "Floor: $floor, COUNT: " . count($json);
  return $json;
}

// Changes to this should also be made to the javascript scale function (temp: will remove that once projects /aren't/ fetched via json)
function utility_scaleImage($width, $height) {
  $mult = 0; $col = 1;
  $mult = 304 / $width;
  return array("width" => (int) $width * $mult, "height" => (int) $height * $mult, $col => $col);
}

// function to return a client's IP address
function getIP() { 
  $ip; 
  if (getenv("HTTP_CLIENT_IP")) 
    $ip = getenv("HTTP_CLIENT_IP"); 
  else if(getenv("HTTP_X_FORWARDED_FOR")) 
    $ip = getenv("HTTP_X_FORWARDED_FOR"); 
  else if(getenv("REMOTE_ADDR")) 
    $ip = getenv("REMOTE_ADDR"); 
  else 
    $ip = "UNKNOWN";
  return $ip; 
}

// function to handle up votes on a page image
function utility_upVoteImage($request) {

  $request['piv_vote'] = 1;
  $res = utility_insertVote($request);

  return $res;
}

// function to handle down votes on a page image
function utility_downVoteImage($request) {

  $request['piv_vote'] = -1;
  $res = utility_insertVote($request);

  // Fetch a new image for the user
  $img = utility_refreshImage($request);
  $img['errorstatus'] = $res['errorstatus'];
  $img['message'] = $res['message'];

  return $img;
}

// Function to actually insert a vote
function utility_insertVote($request) {
  global $uw_db;

  $pi_id = $request['pi_id'];
  $p_id = $request['p_id'];
  $ip = getIP();
  $vote = $request['piv_vote'];

  // Make sure this user hasn't already up-voted this image
  $query = sprintf("SELECT piv_id FROM project_image_votes WHERE piv_imageid = %d AND piv_voter_ip = %d",
    mysql_real_escape_string($pi_id),
    mysql_real_escape_string($ip));
  $result = mysql_query($query, $uw_db);
  if (!$result) {
    return array("message" => "Failed to query for current votes: " . mysql_error(), "errorstatus" => "fail");
  }
  while ($row = mysql_fetch_assoc($result)) {
    return array("message" => "IP has already voted on image.", "errorstatus" => "fail");
  }

  // Create a vote for the image
  $query = sprintf("INSERT INTO project_image_votes (piv_imageid, piv_voter_ip, piv_vote, piv_timestamp) VALUES (%d, '%s', %d, %d)",
    mysql_real_escape_string($pi_id),
    mysql_real_escape_string($ip),
    mysql_real_escape_string($vote),
    mysql_real_escape_string(time()));
  $result = mysql_query($query, $uw_db);
  if (!$result) {
    return array("message" => "Failed to insert vote: " . mysql_error(), "errorstatus" => "fail");
  }
  return array("message" => "Successfully inserted vote - ($vote).", "errorstatus" => "success");
}

// function to fetch and return new image
function utility_refreshImage($request) {
  global $uw_db;

  $pi_id = $request['pi_id'];
  $p_id = $request['p_id'];

  // Fetch a new image for this page
  $img = utility_getProjectImage(array('pid' => $p_id));
  $s = utility_scaleImage($img['width'], $img['height']);
  $img['width'] = $s['width'] . "px"; $img['height'] = $s['height'] . "px";

  // Return image data
  return $img;
}

function utility_getProjectImage($request, $offset = 0, $page_offset = 0) {
  global $uw_db, $ns_lookup;

  $pid = $request['pid'];
  $json = array('pid' => $pid);
  $ip = getIP();

  // Before we check the wp api for images, look in the local db (returns null if none found)
  $img = utility_getLocalProjectImage($pid);
  if (isset($img['errorstatus']) && $img['errorstatus'] == 'fail') { return $img; }
  if ($img != null) {
    return $img;
  }

  // Skip some common images that don't have anything to do with the project
  $skip_imgs = array(
    'File:Ambox_content.png',
    'File:BSicon_.svg',
    'File:Commons-logo.svg',
    'File:Cmbox_content.png',
    'File:Cscr-featured.svg',
    'File:Crystal_Clear_app_kedit.svg',
    'File:Disambig_gray.svg',
    'File:Edit-clear.svg',
    'File:Featured_article_star.svg',
    'File:Folder_Hexagonal_Icon.svg',
    'File:Imbox_notice.png',
    'File:Increase2.svg',
    'File:Media-cdrom.svg',
    'File:Portal.svg',
    'File:Portal-puzzle.svg',
    'File:Question_book-new.svg',
    'File:Symbol_book_class2.svg',
    'File:Template-info.png',
    'File:Text_document_with_red_question_mark.svg',
    'File:Wiki_letter_s.svg',
    'File:Wiki_letter_w.svg',
  );

  $page = utility_getFirstPageForProject($pid, $offset);
  if ($page == null) {
    echo "\n  WARNING: No pages found for project within offset: $offset!!! Setting default image:";
    $json['href'] = "img/question.jpg";
    $json['width'] = 300;
    $json['height'] = 300;
    $json['pi_id'] = 0;
    $json['piv_vote'] = null;
    $json['pi_img'] = null;
    return $json;
  }
  $url = sprintf("http://en.wikipedia.org/w/api.php?action=query&prop=images&pageids=%d&format=json",
    urlencode($page['pp_pageid']));
  $p_json = json_decode(http_response($url));
  $imgs = property_exists($p_json->query->pages->$page['pp_pageid'], 'images') ? $p_json->query->pages->$page['pp_pageid']->images : array();
  $page_images = count($imgs);
  //$page_ns = $p_json->query->pages->$page['pp_pageid']->ns == 0 ? '' : $ns_lookup[$p_json->query->pages->$page['pp_pageid']->ns] . ":";
  $page_title = $p_json->query->pages->$page['pp_pageid']->title;
  $page_url = "http://en.wikipedia.org/wiki/" . $page_title;
  while ($img == null && $page_offset < $page_images) {
    $img = isset($imgs[$page_offset]) ? $imgs[$page_offset]->title : null;
    $img = preg_replace("/ /", "_", $img);
    $img = in_array($img, $skip_imgs) ? null : $img;
    $page_offset++;
  }

  // If we still don't have an image, try the next page
  if ($img == null) {
    return utility_getProjectImage($request, $offset+1, 0);
  }

  echo " - $img";

  if ($img != null) {
    // If we /did/ find an image, we'll need to fetch the actual image url and cache it in the local db
    $imgurl = "http://en.wikipedia.org/wiki/$img";
    $out = http_response($imgurl);
    // Now we need to traverse the dom to find the link to the full image
    // Don't display errors when loading the html, php may choke on malformed text
    ini_set('display_errors', 'Off');
    $doc = new DOMDocument();
    @$doc->loadHTML($out);
    // Turn errors back on
    ini_set('display_errors', 'On');

    $xpath = new DOMXPath($doc);
    $href = $xpath->query('//div[@id="mw-content-text"]/div[@class="fullImageLink"]/a[1]')->item(0);
    $href = $href ? $href->getAttribute('href') : null;
    if (!$href) {
      $href = $xpath->query('//div[@id="mw-content-text"]/div[@class="fullImageLink"]/div/div/img[1]')->item(0);
      $href = $href ? $href->getAttribute('src') : null;
    }
    if (!$href) {
      echo "\nERROR: Failed to find image link ($imgurl)!!  Trying again...\n";
      return utility_getProjectImage($request, $offset + 1);
    }

    // We should now /for sure/ have a url.  Ensure proper format
    if (! preg_match("/^http:/", $href)) {
      $href = 'http:' . $href;
    }

    // Get image dimensions
    $size = array();
    if (preg_match("/\.svg$/", $href)) {
      $xmlget = simplexml_load_file($href);
      $xmlattr = $xmlget->attributes();
      $size = array((string) $xmlattr->width, (string) $xmlattr->height);
    } else {
      $size = getimagesize($href);
    }
    $json['width'] = $size[0];
    $json['height'] = $size[1];
    $json['href'] = $href;
    $json['url'] = $page_url;
    $json['pi_img'] = $img;

    // Cache image data in local db if we found an image
    if (isset($json['width']) && isset($json['height']) && $json['width'] != 0 && $json['height'] != 0) {
      $res = utility_saveLocalProjectImage($json);
      if (isset($res['errorstatus']) && $res['errorstatus'] == 'fail') { return $res; }
      $json['pi_id'] = $res['pi_id'];
      $json['piv_vote'] = null;
    } else {
      echo "\nERROR: Project image not found or not valid!!  Trying again...\n";
      return utility_getProjectImage($request, $offset + 1);
    }
  }
  return $json;
}

function utility_saveLocalProjectImage($json) {
  global $uw_db;

  // Ensure the project doesn't already exist
  $query = sprintf("SELECT * FROM project_images WHERE pi_projectid = %d AND pi_href = '%s' AND pi_linked_from = '%s' AND pi_img = '%s'",
    mysql_real_escape_string($json['pid']),
    mysql_real_escape_string($json['href']),
    mysql_real_escape_string($json['url']),
    mysql_real_escape_string($json['pi_img']));
  $result = mysql_query($query);
  if (!$result) {
    return array("message" => "Failed to query for current local images: " . mysql_error(), "errorstatus" => "fail");
  }
  while ($row = mysql_fetch_assoc($result)) {
    // If we found a current image, skip the insert
    return array("message" => "Skipped project add.", "errorstatus" => "success", "pi_id" => $row['pi_id']);
  }

  $query = sprintf("INSERT INTO project_images (pi_projectid, pi_href, pi_width, pi_height, pi_linked_from, pi_img) VALUES (%d, '%s', %d, %d, '%s', '%s')",
    mysql_real_escape_string($json['pid']),
    mysql_real_escape_string($json['href']),
    mysql_real_escape_string($json['width']),
    mysql_real_escape_string($json['height']),
    mysql_real_escape_string($json['url']),
    mysql_real_escape_string($json['pi_img']));
  $result = mysql_query($query, $uw_db);
  if (!$result) {
    return array("message" => "Failed to insert project image: " . mysql_error(), "errorstatus" => "fail");
  }
  return array("message" => "Created project image with id: " . mysql_insert_id(), "errorstatus" => "success", "pi_id" => mysql_insert_id());
}

function utility_getLocalProjectImage($pid) {
  global $uw_db;

  // TODO: implement voting on images, take that into account here (join on project_image_votes)
  $ip = getIP();
  $query = sprintf("SELECT pi_id, pi_projectid, pi_href, pi_img, pi_width, pi_height, pi_linked_from, SUM(piv_vote) as 'piv_vote' FROM project_images LEFT JOIN project_image_votes ON pi_id = piv_imageid WHERE pi_projectid = %d GROUP BY pi_id ORDER BY piv_vote DESC",
    mysql_real_escape_string($pid));
  $result = mysql_query($query, $uw_db);

  if (!$result) {
    return array("message" => "Failed to query for cached project image: " . mysql_error(), "errorstatus" => "fail");
  }
  $img = null;
  while ($row = mysql_fetch_assoc($result)) {
    // Also grab the vote, if any.  If this user voted this image down, skip it.
    $v_query = sprintf("SELECT piv_vote FROM project_image_votes WHERE piv_voter_ip = '%s' AND piv_imageid = %d",
      mysql_real_escape_string($ip),
      mysql_real_escape_string($row['pi_id']));
    $v_result = mysql_query($v_query, $uw_db);
    if (!$v_result) {
      return array("message" => "Failed to query for image votes: " . mysql_error(), "errorstatus" => "fail");
    }
    $vote = null;
    while ($v_row = mysql_fetch_assoc($v_result)) {
      $vote = $v_row['piv_vote'];
    }
    if ($vote == -1) { continue; }
    $img = array(
      "pid" => $row['pi_projectid'], "href" => $row['pi_href'], "width" => $row['pi_width'], "pi_img" => $row['pi_img'],
      "height" => $row['pi_height'], "cached" => 'true', "pi_id" => $row['pi_id'], "piv_vote" => $vote,
    );
  }
  return $img;
}

// Helper function to make web requests
function http_response($url, $opts = array()) {
  $url = preg_replace('/ /', '%20', $url);
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_USERAGENT, "uw_transparancy, toolserver.org wikiproject parser");
  $output = curl_exec($ch);
  // Check for errors
  if (curl_errno($ch)) {
    wpLog("Curl error: " . curl_error($ch));
    return http_response($url);
  }

  curl_close($ch);

  return $output;
}



