<?php
// $Author: slavik $ $Rev: 58 $
// $Id: admin.php 58 2009-03-13 07:34:12Z slavik $
//$mod_name="$web_client_header";
//$mod_name="Managment mode interface";
global $tstart;
include("inc/version.php");
include("inc/functions.php");

//include("functions.php");
require_once("inc/auth.php");
require("inc/mysql.php");
$mode=$_GET['mode'];
if (isset($_POST['mode'])) {$mode=$_POST['mode'];};
$link=db_connect();

$uid=getuid($link);
if (777>(int)$perm) {echo "manager mode restricted";redirect($link,"user.php?mode=err&rsn=perm");};

// ------------ Просмотр обычной статистики ----------------------------------
list($month, $year) = get_month_year();
$page_descr="Managment mode interface";
//какойто глюк
$origin=option('origin', $link);
$mod_name="manager";
show_head($uservname, $sess_id, $login);
if (!isset($_POST['DG_ajaxid'])) 
{
	show_amenu($mode);
	echo " </td>
		 <td valign=\"top\" class=\"content\" colspan=\"2\">
		    <!--Контент-->";
};
if (!isset($mode)) {$mode="list";};
$id=(int)$_GET['id'];
switch ($mode) {
	case "list": {users_list($link);break;}; // menu, list users
	case "shist": {user_shist($link, $id);break;}; // menu, show user sessions history
	case "auth": {auths_list($link);break;}; // menu, list auth
	case "ea": {auth_show($link, $id);break;}; // edit selected auth
	case "pa": {pay_show($link, $id, 0);break;}; // show form to add payment	
	case "pe": {$pid=$_GET['pid'];pay_show($link, $id, $pid);break;}; // show form to add payment
	case "pc": {$vtext=$_GET['vtext'];$val=$_GET['val'];pay_create($link, $id, $vtext, $val);redirect($link,"admin.php?mode=pays");break;}; // show form to add payment	
	case "ca": {auth_create($link, $id);redirect($link,"admin.php?mode=auth");break;}; // create auth
	case "da": {auth_del($link, $id);redirect($link,"admin.php?mode=auth");break;}; // del auth
	case "tarif": {tarif_list($link);break;}; // menu, list tariffs
	case "eu": {user_show($link, $id);break;}; // edit selected user
	case "show": {user_info($link, $id);show_days($link, $id);break;}; // show selected user 
	case "cu": {user_create($link, $id);redirect($link,"admin.php?mode=list");break;}; // create user
	case "du": {user_del($link, $id);redirect($link,"admin.php?mode=list");break;}; // delete selected user
	case "useradd": {user_show($link, $id);break;}; //  show form modify/add user 
	case "pays": {pays_list($link);break;}; // menu, list payments
	case "sess": {session_list($link);break;}; // menu, list session
	case "usess": {$accid=$_GET['aid'];session_list($link,$accid);break;}; // menu, list session
	case "svc": {echo "services - not implemented";break;}; // menu, list services
	case "as": {session_show($link,$id);break;}; // show form modify/add session
	case "cs": {session_create($link, $id);redirect($link,"admin.php?mode=sess");break;}; // crate session
	case "ct": {tarif_create($link, $id);redirect($link,"admin.php?mode=tarif");break;}; // crate tarif
	case "prf": {opt_show($link);break;}; // menu, show system options
	case "sessdet": {$page=(int)$_GET['page'];session_detail($link, $id, $page);break;}; // show session details
	case "sessdet2": {$ip=$_GET['ip'];session_detail2($link, $id, $ip);break;}; // show session details level 2
	case "sessdet3": {$ip=$_GET['ip'];$p1=$_GET['p1'];session_detail3($link, $id, $ip,$p1);break;}; // show session details level 3
	case "sessdet4": {$ip=$_GET['ip'];$p1=$_GET['p1'];$p2=$_GET['p2'];session_detail3($link, $id, $ip,$p1, $p2);break;}; // show session details level 4
	case "et": {tarif_show($link, $id);break;}; // show form modify/add tariff
	case "sesskil": {session_stop($link,$id);redirect($link,"admin.php?mode=sess");break;}; // close session
	case "svcmonth": {svc_month($link);break;}; // close session
	case "opted": {opt_save($link);redirect($link,"admin.php?mode=prf");break;}; // alter options
	case "dt": {echo "tarif deletion - not implemented";break;}; // menu, list payments
}

echo "</tr></table><!--//Контент-->";
show_tail();
@mysql_close($link);

?>