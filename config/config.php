<?php
// Modify it before upload for more security.
$installerPassword = "";

// Your game name
$gameName = rawurldecode("NWE%20Dev");

// Web base directory of your game
$webBaseDir = "";

$demoEngine = false;

// Main template
$template = "plain";

// Database host
$dbhost = "localhost";
// Database username
$dbuser = "nwe-dev";
// Database password
$dbpass = "nwe-dev";
// Database name
$dbname = "nwe-dev";

// Default module to load once logged in
$defaultModule = "home";

// Default module for non-logged users
$defaultPublic = "welcome";
// Default template for non-logged users
$publicTemplate = "public_plain";

// Default language for the game
$language = "en";

// Config values stored of file system
$storeXmlConfig = false;

// If set to true, the error details will be display for all even non-admins.
$alwaysShowErrorDetails = false;

// If set to true the hook file will be cached.
$hookCache = false;
