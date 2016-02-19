<?php
//required for php >= 5.3
date_default_timezone_set('UTC');

//stack tracer
//xdebug_disable();

//standard libraries
require_once("lib/common.inc.php");
require_once("lib/site.inc.php");
require_once("lib/model.inc.php");

//external libraries
require_once("vendor/Mobile_Detect.php");
require_once("vendor/autoload.php");

//headers
header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );

?>