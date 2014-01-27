<?php
// $Author: slavik $ $Rev: 35 $
// $Id: user.php 35 2008-12-23 05:24:14Z slavik $
$mod_name="$web_client_header";
global $tstart;
include("inc/version.php");
include("inc/functions.php");
//include("functions.php");
require_once("inc/auth.php");
require("inc/mysql.php");
//$mode="user";
$mode=$_GET['mode'];
$link=db_connect();
$uid=getuid($link);

// ------------ Просмотр обычной статистики ----------------------------------
	list($month, $year) = get_month_year();
	$page_descr="$web_client_your_stat $month $year";
	show_head($uservname, $sess_id, $login);
	show_menu($mode);
echo " </td>
		 <td valign=\"top\" class=\"content\" colspan=\"2\">
		    <!--Контент-->";
if (!isset($mode)) {$mode="csess";};
$id=(int)$_GET['id'];
switch ($mode) {
	case "show": {user_info($link, $uid);break;};
	case "csess": {show_days($link, $uid, $sess_id);break;};
	case "sess": {session_list($link, $uid);break;};
	case "as": {session_show($link,$id, $uid);break;}; // show form modify/add session
	case "cs": {session_create($link, $id, $uid);redirect($link,"?mode=sess");break;}; // crate session
	case "sesskil": {session_stop($link,$id, $uid);redirect($link,"?mode=sess");break;}; // close session
};
echo "</tr></table><!--//Контент-->";
	show_tail();
	@mysql_close($link);

?>
