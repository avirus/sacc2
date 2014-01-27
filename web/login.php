<?php
// $Author: slavik $ $Rev: 35 $
// $Id: login.php 35 2008-12-23 05:24:14Z slavik $
$mod_name="$web_client_header";
global $tstart;
include("inc/version.php");
include("inc/functions.php");
//include("functions.php");
require_once("inc/auth.php");
require("inc/mysql.php");
$mode="user";
$link=db_connect();
$user = trim($_POST['user']);
$pass    = trim($_POST['pass']);
if ( auth_cli($user, $pass, $link) )
{
$result = mysql_query("SELECT id from users where login='$user';", $link);
$uid = mysql_result( $result, 0, "id");
$srcip=sprintf("%u",ip2long($_SERVER['REMOTE_ADDR']));
$result = mysql_query("select nat_session_start($uid,$srcip,0) as sessid;", $link);
if (1==option("DEBUG",$link)) {
if ( !$result ) {
	echo "error executing query (about to start session /select nat_session_start($uid,$srcip,0) as sessid;/ )<br>"; 
	echo "<br>mysql error".mysql_errno($link).mysql_error($link); 
	echo "<br><a href=login.php>try again</a>" ; 
	ob_end_flush();
	exit(0); 
};
$sid = (int)@mysql_result( $result, 0, "sessid");
@mysql_free_result($result);
echo "opened session $sid for ".$_SERVER['REMOTE_ADDR']."($srcip)";
echo "<br><a href=user.php>proceed to user mode</a><br>";
echo "<a href=admin.php>proceed to admin mode</a>";
ob_end_flush();
exit(0);
};

redirect($link,"user.php");
@mysql_close($link);
exit;
}
else
{
if (!isset($_POST['url']))
{
	$url=$_SERVER['HTTP_REFERER'];
	//if (strstr($url, "login.php"))
};
if (!isset($_POST['user']))
{
	$welcome_message="Доступ к ресурсу $url требует авторизации.";
}
else
{
	$welcome_message="введены неправильные данные ($user|$pass)";
};
//echo "incorrect login & password ($user|$pass)";
//Header("Location: login.html");
echo "
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">

<html>
<head>
	<title>SAcc2:Вход в систему</title>
    <meta http-equiv=\"content-type\" content=\"text/html; charset=koi8-r\" />
    <meta http-equiv=\"pragma\" content=\"no-cache\">
	<link href=\"css/style.css\" rel=stylesheet type=\"text/css\" />
</head>
<body leftmargin=\"0\" topmargin=\"0\" rightmargin=\"0\" bottommargin=\"0\">
  <table width=\"100%\" height=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
    <!--Распорки-->
      <tr>
	    <td valign=\"top\" height=\"2\" width=\"3%\"></td>
		<td width=\"23%\"></td>
		<td width=\"52%\"></td>
		<td width=\"19%\"></td>
		<td width=\"3%\"></td>
	  </tr>
    <!--//Распорки-->
	  <tr>
	     <td></td>
		 <td valign=\"top\" class=\"content\"></td>
		 <td valign=\"middle\" class=\"content\">
		    <!--Контент-->
			  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
			    <tr>
				  <td valign=\"top\" align=\"center\"><img src=\"images/logo.gif\" alt=\"\" width=\"412\" height=\"127\" border=\"0\"></td>
				</tr>
				<tr>
				  <td height=\"20\"></td>
				</tr>
				<tr>
				  <td valign=\"top\" align=\"center\"><span class=\"h1\">Служба доступа в интернет</span></td>
				</tr>
				<tr>
				  <td height=\"15\"></td>
				</tr>
				<tr>
				  <td valign=\"top\" align=\"center\">
				  <FORM ACTION=\"login.php\" METHOD=post>
				    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"270\">
					  <tr>
					    <td valign=\"top\" colspan=\"2\" style=\"text-align:justify\">
						  $welcome_message
						  Введите имя пользователя и пароль и нажмите кнопку Ok или Отмена для завершения сеанса.
						</td>
					  </tr>
					  <tr>
					    <td colspan=\"2\" height=\"20\"></td>
					  </tr>
					  <tr>
				        <td valign=\"middle\" class=\"tbl_cont\">Пользователь:</td>
				        <td valign=\"middle\" width=\"100%\"><input type=\"text\" style=\"width:100%\" NAME=user></td>
				      </tr>
					  <tr>
					    <td colspan=\"2\" height=\"10\"></td>
					  </tr>
					  <tr>
				        <td valign=\"middle\" class=\"tbl_cont\">Пароль:</td>
				        <td valign=\"middle\"><input type=\"Password\" style=\"width:100%\" NAME=pass></td>
				      </tr>
					  <tr>
					    <td colspan=\"2\" height=\"10\"></td>
					  </tr>
					  <tr>
				        <td valign=\"top\" colspan=\"2\" align=\"right\"><input type=\"submit\" class=\"button\" value=\"Ok\">&nbsp; <input type=\"Button\" class=\"button\" value=\"Отмена\"></td>
				      </tr>
					</table>
					</form>
				  </td>
				</tr>
			  </table>
		    <!--//Контент-->
		 </td>
		 <td valign=\"top\" class=\"content\"></td>
		 <td></td>
	  </tr>

	<!--Футер-->
	  <tr>
	    <td valign=\"top\" height=\"2\" bgcolor=\"#0\"></td>
		<td bgcolor=\"#bebfbf\"></td>
		<td bgcolor=\"#bebfbf\"></td>
		<td bgcolor=\"#bebfbf\"></td>
		<td bgcolor=\"#bebfbf\"></td>
	  </tr>

	  <tr>
	    <td height=\"45\"></td>
		<td valign=\"middle\" align=\"center\" class=\"footer\">&copy; Cybersec ltd 2007</td>
		<td valign=\"middle\" align=\"center\"></td>
		<td valign=\"middle\" align=\"center\" class=\"footer\"><a href=\"http://sacc.cybersec.ru\" target=\"_blank\">sacc.cybersec.ru</a></td>
		<td></td>
	  </tr>
	<!--//Футер-->

  </table>
</body>
</html>

";
@mysql_close($link);
exit;
}
?>