<?php

// This main index will route requests properly based on the URL passed in.  Functionality is split along the following:
// * utility.php - Utility functions, database setup.
// * elements.php - Page display functions (header, menus, etc).
// * projects.php - Project specific functions.

include_once('utility.php');
include_once('elements.php');
//include_once('projects.php');

// Include the proper localized string file
$lang = '';
if (isset($_REQUEST['lang'])) {
  include_once('i18n/' . $_REQUEST['lang'] . '.php');
} else {
  include_once('i18n/en.php');
}

// Handle rest requests
if (isset($_REQUEST['a']) && $_REQUEST['a']) {
  $result = json_encode($_REQUEST['a']($_REQUEST));
  if ($result && $result != 'null') {
    echo $result;
    return;
  }
}

// Draw the page
elements_drawHeader();
elements_drawBody();
elements_drawFooter();


