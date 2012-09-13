<?php

// This page will handle project specific requests, viewing project details and pages owned by that project
// * utility.php - Utility functions, database setup.
// * elements.php - Page display functions (header, menus, etc).

include_once('utility.php');
include_once('elements.php');

// Include the proper localized string file
$lang = '';
if (isset($_REQUEST['lang'])) {
  include_once('i18n/' . $_REQUEST['lang'] . '.php');
} else {
  include_once('i18n/en.php');
}

// Not handling rest requests here

// Draw the page
elements_drawHeader();
elements_drawProjectBody($_REQUEST);
elements_drawFooter();


