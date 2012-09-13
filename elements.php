<?php


function elements_drawHeader() {
  echo '
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Wikiproject Pulse</title>

    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
    <link href="css/pulse.css" rel="stylesheet" type="text/css"/>

    <script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
    <script src="js/bootstrap.min.js" type="text/javascript"></script>
    <script src="js/jquery.infinitescroll.js" type="text/javascript"></script>
    <script src="js/jquery.masonry.min.js" type="text/javascript"></script>
    <script src="js/jquery-ui-1.8.21.custom.min.js" type="text/javascript"></script>
    <script src="js/pulse.js" type="text/javascript"></script>

  </head>
  <body>
  ';

  // The header will also include the Pulse logo title bar, and browsable menu
  elements_drawTitleBar();
  elements_drawNavBar();
}

function elements_drawBody() {
  global $i18n;

  $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
  $projects = utility_getActiveProjects(array('page' => $page));
  if ($projects['errorstatus'] == 'fail') {
    echo "Failed to get projects: " . $projects['message'] . "<br/>";
  }
  echo "<div class='content'>";
  echo "  <div class='main_desc'>" . $i18n['MAIN_DESC'] . "</div>";
  echo "  <div id='main_projects'>";

  foreach ($projects as $p) {
    if (isset($p['p_id']) && (! preg_match("/^\d+$/", $p['p_id']))) { continue; }
    $p_id = $p['p_id']; $count = $p['count']; $p_aka = $p['p_aka']; $p_title = $p['p_title']; $href = $p['href']; 
    $pi_id = $p['pi_id']; $piv_vote = $p['piv_vote']; $pi_img = addslashes($p['pi_img']);
    $s = utility_scaleImage($p['width'], $p['height']);
    $w = $s['width'] . "px"; $h = $s['height'] . "px";
    echo "
      <div id='$p_id' class='pulse_activeProject'>
        <div id='" . $p_id . "_image_div' class='pulse_activeProjectImage' style='background-color: #eee;' >
          <img id='" . $p_id . "_image' src='$href' width='$w' height='$h' pi_id='$pi_id' piv_vote='$piv_vote' pi_img=\"$pi_img\" />
        </div>
        <div style='clear: left;'></div>
        <div class='pulse_activeProjectDetails'>$p_aka</div>
        <div class='pulse_activeProjectEditsNumber'>$count</div>
        <div class='pulse_activeProjectEdits'>Edits</div>
      </div>
    ";
  }
  echo "  </div>";
  echo "</div>";

  // If we're an ajax request, print the objects and return
  /*
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $page = $_REQUEST['page'];
    $projects = utility_getActiveProjects($_REQUEST);
    // Removed project iterator shown above    
    }
  }
  echo "  </div>";
  echo "</div>";
  */

  // Draw 'next' link for infinitescroll
  $next = $page + 1;
  echo "
    <nav id='page-nav'>
      <!-- <a href='index.php?a=utility_getActiveProjects&page=2'></a> -->
      <a href='index.php?page=$next'></a>
    </nav>";

}

function elements_drawProjectBody($request) {
  global $i18n;

  $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
  $pages = utility_getActivePages(array('page' => $page, 'p_id' => $request['p_id']));
  $project_desc = "FILLER IPSUM!!!";
  if (isset($pages['errorstatus']) && $pages['errorstatus'] == 'fail') {
    echo "Failed to get pages: " . $pages['message'] . "<br/>";
  }
  echo "<div class='content'>";
  echo "  <div class='main_desc'>" . $project_desc . "</div>";
  echo "  <div id='main_projects'>";

  foreach ($pages as $p) {
    if (isset($p['p_id']) && (! preg_match("/^\d+$/", $p['p_id']))) { continue; }
    $p_id = $p['p_id']; $count = $p['count']; $p_aka = $p['p_aka']; $p_title = $p['p_title']; $href = $p['href'];
    $pi_id = $p['pi_id']; $piv_vote = $p['piv_vote']; $pi_img = addslashes($p['pi_img']); $pp_pageid = $p['pp_pageid'];
    $s = utility_scaleImage($p['width'], $p['height']);
    $w = $s['width'] . "px"; $h = $s['height'] . "px";
    echo "
      <div id='$pp_pageid' class='pulse_activeProject'>
        <div id='" . $pp_pageid . "_image_div' class='pulse_activeProjectImage' style='background-color: #eee;' >
          <img id='" . $pp_pageid . "_image' src='$href' width='$w' height='$h' pi_id='$pi_id' piv_vote='$piv_vote' pi_img=\"$pi_img\" />
        </div>
        <div style='clear: left;'></div>
        <div class='pulse_activeProjectDetails'>$p_aka</div>
        <div class='pulse_activeProjectEditsNumber'>$count</div>
        <div class='pulse_activeProjectEdits'>Edits</div>
      </div>
    ";
  }

  echo "  </div>";
  echo "</div>";

  // Draw the 'next' link for infinitescroll
  $next = $page + 1;
  echo "
    <nav id='page-nav'>
      <a href='projects.php?page=$next'></a>
    </nav>
  ";
}

function elements_drawFooter() {
  echo "
  </body>
</html>
  ";
}

function elements_drawTitleBar() {
  echo "<div class='title_bar span8'>";
  echo "  <div class='title_ellipsis'>...</div>";
  echo "  <div class='title_logo'><img src='img/PulseLogo.png' /></div>";
  echo "  <div class='title_ellipsis'>...</div>";
  echo "  <div class='title_name'>wiki<span style='color: #CCC;'>.</span>project</div>";
  echo "  <div class='title_name'><a href='/pulse'>pul<span style='color: #CCC;'>.</span>se</a></div>";
  echo "</div>";
}

function elements_drawNavBar() {
  global $i18n;
  $projects = utility_getProjectsForNav();
  echo "
  <div class='navbar span4 offset3 '>
    <div class='navbar-inner'>
    <div class='container'>
    <ul class='nav'>
      <li class='dropdown'>
        <a href='#' class='dropdown-toggle nav_link' data-toggle='dropdown'>
          <b class='caret'></b> " . $i18n['NAV_BROWSE'] . "
        </a>
        <ul class='dropdown-menu'>";
  if (isset($projects['errorstatus']) && $projects['errorstatus'] == 'fail') {
    echo $projects['message'];
  } else {
    foreach ($projects as $project) {
      echo "<li><a href='#' class='nav_link_inner'>" . $project['title'] . "</a></li>";
    }
    echo "<li><a href='#' class='nav_link_inner'> . . . </a></li>";
  }
  echo "
        </ul>
      </li>
      <li class='divider-vertical'></li>
      <li><a href='#' class='nav_link'><p>" . $i18n['NAV_ABOUT'] . "</p></a></li>
    </ul>
    </div>
    </div>
  </div>
  ";
}

