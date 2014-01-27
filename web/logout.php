<?php
// $Author: slavik $ $Rev: 35 $
// $Id: logout.php 35 2008-12-23 05:24:14Z slavik $
$mod_name="$web_client_header";
global $tstart;
include("inc/version.php");
include("inc/functions.php");
//include("functions.php");
require_once("inc/auth.php");
require("inc/mysql.php");
$mode="user";
$link=db_connect();
$uid=getuid($link);
session_stop($link, $sess_id);
if (1==option("DEBUG",$link)) {echo "closed session $sid";exit;};
@mysql_close($link);
Header("Location: login.php?url=user.php");
exit;
?>