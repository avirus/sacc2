<?php
// $Author: slavik $ $Rev: 43 $
// $Id: functions.php 43 2009-02-15 20:05:58Z slavik $
include ("inc/phpmydatagrid.class.php");

// registerglobals workaround
if (!get_cfg_var("register_globals")) {
extract($_REQUEST, EXTR_SKIP);
extract($_SERVER, EXTR_OVERWRITE);
}
//Content-Type: text/css; charset=windows-1251
header( "Content-type:text/html; charset=koi8-r");
date_default_timezone_set('Asia/Yekaterinburg');

function opt_save($link)
{
$result = mysql_query("SELECT name FROM options;", $link);
    $link2=db_connect();
    for ($i = 0; $i < mysql_numrows($result); $i++ )
    {
	$name = mysql_result( $result, $i, "name");
	$value = $_GET[$name];
	$result2 = mysql_query("update options set value='$value' where name='$name'", $link2);
	//		echo "update `options` set value='$value' where name='$name'<br>";echo mysql_error($link2);echo "<br>";
    }
    @mysql_close($link2);
    return true;
}

function opt_show($link)
{
    // форматирование
    echo "<!--блок информации -->
	       <table width=\"100%\" height=\"60\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
	         <tr>
		    <td width=\"15%\">";
    // конец форматирования
    echo "<FORM ACTION=\"admin.php\" METHOD=get><INPUT TYPE=hidden NAME=mode VALUE=\"opted\">";
    $result = mysql_query("SELECT name, value, descr, help FROM options order by descr;", $link);
    for ($i = 0; $i < mysql_numrows($result); $i++ )
    {
	$name = mysql_result( $result, $i, "name");
	$value = mysql_result( $result, $i, "value");
	$descr = mysql_result( $result, $i, "descr");
	$help = mysql_result( $result, $i, "help");
	echo "<TR>
	<TD><B>$descr</B></TD>
	<TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=$name VALUE=\"" . htmlspecialchars($value) . "\"></TD>
	<td>$help</td></TR>";
    }
    echo "<tr><td>
            <INPUT TYPE=submit CLASS=\"inputsubmit\" ID=\"bg_green\" NAME=createbutton VALUE=\"apply\">&nbsp;&nbsp;&nbsp;&nbsp;
            <INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_blue\"  VALUE=\"back\" OnClick=\"window.location = 'admin.php'\">&nbsp;&nbsp;&nbsp;&nbsp;
</td></form></table>";
}

//      Функция getuid($link) - выгребает юзерские данные по номеру сессии
//      Вход : линк на подключение к БД
//      Выход: возвращается идентификатор пользователя
// Примечание: через глобалсы передается вспомогательная инфа по юзеру, при ошибке - остановка выполнения скрипта и переброс на авторизацию
function getuid($link)
{
    global $sess_id, $uservname, $perm, $login;
    $srcip=sprintf("%u",ip2long($_SERVER['REMOTE_ADDR']));
    $result = mysql_query("SELECT u_id, id from natsess where hip=$srcip and closing=0;", $link);
    if ( mysql_num_rows($result) == 0 )
    {
	echo "не найдена сессия ".$_SERVER['REMOTE_ADDR']." $srcip ".long2ip($srcip);
	if (1==option("DEBUG",$link)) {exit;} else {Header("Location: login.php");};
	exit;
    };
    // выгребаем нужные данные по юзерам
    $uid = mysql_result( $result, 0, "u_id");
    $sess_id = mysql_result( $result, 0, "id");
    $result = mysql_query("SELECT login, vname, perm from users where id=$uid;", $link);
    $uservname = mysql_result( $result, 0, "vname");
    $perm = mysql_result( $result, 0, "perm");
    $login = mysql_result( $result, 0, "login");
    return $uid;
}

function svc_month($link)
{
    global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;	
    getclasses(0);
    $qry="select id from natsess where closing=0;";
    $result = mysql_query($qry, $link);
    if (mysql_num_rows($result)==0) if (1==option("DEBUG",$link)) {echo "while executing $qry, some shit happens, looks like no active sessions".mysql_error($link);exit(0);} 
    else {echo "<center>>no active sessions. <br><a href=?mode=svc>back</a>";return 0;} ;
    $link2=db_connect();
    for ($i = 0; $i < mysql_numrows($result); $i++ )
    {
	$sid = mysql_result( $result, $i, "id");
	$qry = "call nat_session_checkpoint($sid);";
	$res2 = mysql_query($qry, $link2);
	@mysql_free_result($res2);
    }
    
}

// set flag "stop session now"
function session_stop($link, $session_id, $uid=0)
{
    $qry="UPDATE natsess set closing=1 WHERE id=$session_id";
    if (0!=$uid) {$qry=$qry." and u_id=$uid";};
    $result = mysql_query($qry, $link);
    if (0==mysql_affected_rows($link)) {echo "no such session ($session_id) avaible for $sid !";exit(0);}
    if (1==option("DEBUG",$link)) {echo "closed session $session_id for user $sid";exit(0);};
    return 0;
}

//
//      Функция dotize($num) - расставляет точки через каждые три разряда в числе $num,
//                             начиная с самого младшего разряда
//      Вход : $num - число для преобразования
//      Выход: возвращается преобразованное число
//
// Примечание: Спасибо Greyder-у за глючную функцию. ;)
//
function dotize($num)
{
    global $delimiter;
    if (!isset($delimiter)) {$delimiter=option("delimiter", 0);};
    $num= strrev(preg_replace ("/(\d{3})/", "\\1".$delimiter, strrev($num)));
    return $num;
}

//	Функция option($name, $link)
//      Вход:
//		$name имя опции
//		$link на коннект к базе
//	Выход: значение опции
function option($rname, $link)
{
    global $settings;
    if (!isset($settings))
    {
	$result = mysql_query("SELECT name,value from options", $link);
	for ($i = 0; $i < mysql_numrows($result); $i++ )
	{
	    $value = @mysql_result( $result, $i, "value");
	    $name = @mysql_result( $result, $i, "name");
	    $settings[$name]=$value;
	}
	@mysql_free_result($result);
    }
    return $settings[$rname];
}
// log event into database
function logevent($message)
{
    global $mysql_server,$mysql_login,$mysql_passwd,$mysql_database,$PHP_AUTH_USER;
    $loglink = db_connect();
    $message=date("d.m.Y H:i:s")." $PHP_AUTH_USER ".$_SERVER['REMOTE_ADDR']." ".addslashes($message);
    mysql_query("INSERT INTO syslog (record) VALUES('$message');", $loglink);
    mysql_close($loglink);
}

//      ---- А эта хуета лежит тут по привычке, и выбросить надо бы, да лень.
//      Функция get_month_year() - возвращает текущий месяц и год
//      Вход : ничего
//      Выход: функция возвращает массив, нулевой элемент которого -
//             название месяца (по-русски), первый элемент - год
//
function get_month_year()
{
    $date = getdate();
    return array(date("F"), $date[year]);
}

// error handling routine
function debug()
{
    $debug_array = debug_backtrace();
    $counter = count($debug_array);
    for($tmp_counter = 0; $tmp_counter != $counter; ++$tmp_counter)
    {
          ?>
          <table width="558" height="116" border="0" cellpadding="0" cellspacing="0" bordercolor="#000000">
            <tr>
              <td height="38" bgcolor="#D6D7FC"><font color="#000000">function <font color="#FF3300"><?php
              echo($debug_array[$tmp_counter]["function"]);?>(</font> <font color="#2020F0"><?php
              //count how many args a there
              $args_counter = count($debug_array[$tmp_counter]["args"]);
              //print them
              for($tmp_args_counter = 0; $tmp_args_counter != $args_counter; ++$tmp_args_counter)
              {
                echo($debug_array[$tmp_counter]["args"][$tmp_args_counter]);

                if(($tmp_args_counter + 1) != $args_counter)
                {
            	echo(", ");
                }
                else
                {
            	echo(" ");
                }
              }
              ?></font><font color="#FF3300">)</font></font></td>
            </tr>
            <tr>
              <td bgcolor="#5F72FA"><font color="#FFFFFF">{</font><br>
                <font color="#FFFFFF">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;file: <?php
                echo($debug_array[$tmp_counter]["file"]);?></font><br>
                <font color="#FFFFFF">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;line: <?
                echo($debug_array[$tmp_counter]["line"]);?></font><br>
                <font color="#FFFFFF">}</font></td>
            </tr>
          </table>
          <?php
          if(($tmp_counter + 1) != $counter)
          {
            echo("<br>was called by:<br>");
          }
    }
    exit();
}

//
//      Функция user_info($id) - для заданного id'a делает выборку в
//                               таблице (база данных) и представляет
//                               полученные данные в HTML-формате
//      Вход : $id - username
//      Выход: если все удачно - true, иначе - код ошибки
//      Примечание: если программе не удается выполнить запрос к базе
//                    данных, возвращается код ошибки 3
//
function user_info($link, $id)
{
    global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;	
    getclasses(0);
    getclasses(2);
    $visualclassTD2=$visualclassTD;
    $visualclassTD2r=$visualclassTDr;
    $visualclassTR2=$visualclassTR;
    getclasses(1);
    global $megabyte_cost;
    global $lang;
    if ($lang==0) {include "inc/ru.php";};
    if ($lang==1) {include "inc/en.php";};
    // форматирование
    echo "<!--блок информации -->
	       <table width=\"50%\" height=\"60\"  class='dgTable' cellpadding='0' cellspacing='0'>
	         <tr>
		    <td width=\"30%\"></td><td width=\"20%\"></td><td width=\"10%\"></td><td></td>";
    // конец форматирования
    $res = mysql_query("SELECT u.login as login, u.balans as balans, u.overdraft as overdraft, u.bill as bill, u.email as email, u.vname as vname, t.vname as tarif FROM users u, tariffs t WHERE u.id=$id and u.t_id=t.id", $link);
    if ( !$res ) {
	echo mysql_error();
	return 3;}
	$i=0;
	if ( 0==mysql_numrows($res)) {echo "error: $web_client_nouser";
	return false;
	}

	$login  = mysql_result($res,$i,"login");
	$balans = mysql_result($res,$i,"balans");
	$avail = mysql_result($res,$i,"overdraft")+mysql_result($res,$i,"balans");
	$balans = mysql_result($res,$i,"balans");
	$over = mysql_result($res,$i,"overdraft");
	$bill = mysql_result($res,$i,"bill");
	$email = mysql_result($res,$i,"email");
	$vname = mysql_result($res,$i,"vname");
	$tarif = mysql_result($res,$i,"tarif");
	echo "
<TR $visualclassTR>
    <TD $visualclassTD><B>$view_user</B></TD>
    <TD $visualclassTD>&nbsp;$vname</TD>
</TR>
<TR $visualclassTR2>
    <TD $visualclassTD2><B>$view_login</B></TD>
    <TD $visualclassTD2>&nbsp;$login</TD>
</TR>
<TR $visualclassTR>
    <TD $visualclassTD><B>$view_email</B></TD>
    <TD $visualclassTD>&nbsp;$email</TD>
</TR>";
	//echo"</table><TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0>";
echo "
<TR $visualclassTR2>
    <TD $visualclassTD2><B>Баланс:</B></TD>
    <TD $visualclassTD2r>&nbsp;".dotize($balans)."";
	echo "</TD>
    <TD>&nbsp;уе</TD>
</TR>
<TR $visualclassTR>
    <TD $visualclassTD><B>$web_client_used</B></TD>
    <TD $visualclassTDr>&nbsp;".dotize($bill)."</TD>
    <TD>&nbsp;уе</TD>";
	echo "</TR>
<TR $visualclassTR2>
    <TD $visualclassTD2><B>Разрешенный овердрафт:</B></TD>
    <TD $visualclassTD2r>&nbsp;".dotize($over)."</TD>
    <TD>&nbsp;уе</TD>
</TR>
<TR $visualclassTR>
    <TD $visualclassTD><B>Тариф</B></TD>
    <TD $visualclassTD>&nbsp;$tarif</TD>
</TR>
</table>
";
	@mysql_free_result($res);
	return true;
}
function auth_del($link, $id)
{
    if ($id>3) {
	$res = mysql_query("delete from auth where id=$id", $link);
	if ( 0==mysql_affected_rows($link)) {echo "нет такого auth, операция не выполнена".mysql_error();return false;};
	echo "auth ($id) succesfully deleted ";
	return true;
    } // or add new user
    echo "i dont want delete default auth types";
return false;
}

// immediate delete user
// todo: надо сделать удаление в конце месяца.
function user_del($link, $id)
{
    if ($id>0) {
	$res = mysql_query("delete from users where id=$id", $link);
	if ( 0==mysql_affected_rows($link)) {echo "no such user ($id) the transaction aborted".mysql_error();return false;};
	echo "user succesfully deleted.";
	return true;
    } // or add new user
return false;
}
// redirect user to specified URL
function redirect($link, $url)
{
    if (0==option("DEBUG",$link))
    {
	Header("Location: $url");
    };
}
// create/alter user
function user_create($link, $id)
{
    global $delimiter;
    $pass1=$_GET['pass1'];
    $pass2=$_GET['pass2'];
    if ( $pass1 != $pass2 ) {echo "The passwords you entered do not match, the transaction aborted";return false;};
    if (strlen($pass1)>0) {$pwdrenew=1;};
    $login=$_GET['login'];
    $vname=$_GET['vname'];
    $email=$_GET['email'];
    $perm=(int)str_replace ( $delimiter, "",trim($_GET['perm']));
    $over=(float)str_replace ( $delimiter, "", trim($_GET['over']));
    $aid=(int)$_GET['aid'];
    $tid=(int)$_GET['tid'];
    $email=$_GET['email'];
    if ($id>0) {
	$res = mysql_query("update users set overdraft=$over, email='$email', vname='$vname', login='$login', perm=$perm, a_id=$aid, t_id=$tid where id=$id", $link);
	if (!$res) {echo "no such user ($id) the transaction aborted".mysql_error();return false;};
	//if ( 0==mysql_affected_rows($link)) {echo "no such user ($id) the transaction aborted".mysql_error();return false;};
	echo "user $login succesfully altered".mysql_error();
    } // or add new user
    else {
	$query="insert into users (overdraft, email, vname, login, perm, a_id, t_id) values ($over, '$email', '$vname', '$login', $perm, $aid, $tid);";
	$res = mysql_query($query, $link);
	$id=mysql_insert_id($link);
	if ( 0==mysql_affected_rows($link)) {echo "error: $query ".mysql_error();return false;};
	echo "user $login succesfully added ".mysql_error();
    };
    if (1==$pwdrenew)
    {
	$res = mysql_query("update users set passwd='$pass1' where id=$id", $link);
	//if ( 0==mysql_affected_rows($link)) 
	if (!$res) {echo "BUG: cant change user password".mysql_error();return false;};
    };
    return true;
}

// показ сессии
function session_show($link, $session_id, $uid=0)
{ 
    // форматирование
    echo "<!--блок информации -->
	       <table width=\"100%\" height=\"60\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
	         <tr>
		    <td width=\"15%\">";
    // конец форматирования	
    if ($session_id>0) {

	$qry="select u.id as uid, vname, tin, tout, hip, stime, t_id from natsess as n, users as u where u.id=n.u_id and n.id=$session_id";
	if (0!=$uid) {$qry=$qry."and u.id=$uid";};
	echo "<!-- $qry -->";
	$res = mysql_query($qry, $link);
	if ( !$res )
	{
	    echo mysql_error();
	    return 3;
	}
	$i=0;
	if ( 0==mysql_numrows($res))
	{
	    echo "error: нет сессии";
	    return false;
	}
	$uid = mysql_result($res,$i,"uid");
	$tid = mysql_result($res,$i,"t_id");
	$vname = mysql_result($res,$i,"vname");
	$tin = mysql_result($res,$i,"tin");
	$tout = mysql_result($res,$i,"tout");
	$hip = long2ip(mysql_result($res,$i,"hip"));
	$stime = mysql_result($res,$i,"stime");
    }  // end of session get data
    // show form
    if (0==$uid) {$pname="admin.php";} else {$pname="user.php";};
    echo "<FORM ACTION=\"$pname\" METHOD=get>
<INPUT TYPE=hidden NAME=mode VALUE=\"cs\">
<INPUT TYPE=hidden NAME=id VALUE=\"$session_id\">
<TR>
    <TD><B>логин</B></TD>";
    echo "<td><select name=uid style=\"font-weight: bold\">";
    $qry="select vname, id from users";
    if (0!=$uid) {$qry=$qry." where id=$uid";};
    echo "<!-- $qry -->";
    $result = mysql_query($qry, $link); 
    for ($i = 0; $i < mysql_numrows($result); $i++)
    {
	$id = mysql_result( $result, $i, "id");
	$vname = mysql_result( $result, $i, "vname");
	echo "<option value=\"$id\"";
	if ($uid == $id) {echo "selected";}
	echo ">$vname";
    }
    echo "</select></td>";
    //	<TD>&nbsp;$vname</TD>
    echo "</TR>
<TR>
    <TD><B>траф in</B></TD>
    <TD>".dotize($tin)."></TD>
</TR>
<TR>
    <TD><B>траф out</B></TD>
    <TD>".dotize($tout)."</TD>
</TR>
<TR>
    <TD><B>host ip</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=hip VALUE=\"" . long2ip($hip) . "\"></TD>
</TR>
<TR>
    <TD><B>host mac</B></TD>
    <TD>&nbsp;not implemented yet&nbsp;уе</td>
</TR>
<TR>
    <TD><B>session start time</B></TD>
    <TD>&nbsp;$stime&nbsp;уе</TD>
</TR>
<tr>
<td>
            <INPUT TYPE=submit CLASS=\"inputsubmit\" ID=\"bg_green\" NAME=createbutton VALUE=\"apply\">&nbsp;&nbsp;&nbsp;&nbsp;
            <INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_blue\"  VALUE=\"back\" OnClick=\"window.location = 'admin.php'\">&nbsp;&nbsp;&nbsp;&nbsp;";
    if ($session_id>0) { echo "<INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_black\"  VALUE=\"delete\" OnClick=\"window.location = '?mode=sesskil&id=$id'\">&nbsp;&nbsp;&nbsp;&nbsp;";};
    echo "</td>
</form>
</table>
";
    @mysql_free_result($res);
    return true;
}
// session  create/alter
function session_create($link, $id, $uid=0)
{
    if (0==$uid) { $uid=(int)$_GET['uid'];};
    //$tid=$_GET['tid'];
    $hip=sprintf('%u',ip2long($_GET['hip']));
    if ($id>0) {
	$res = mysql_query("update natsess set u_id=$uid where id=$id", $link);
	if ( 0==mysql_affected_rows($link)) {echo "нет такой записи, операция не выполнена".mysql_error();return false;};
	echo "данные обновлены успешно "; 
	return true;
    } // or add new
    else {
	$query="select nat_session_start($uid,$hip,0) as sid;";
	$res = mysql_query($query, $link);
	$id = mysql_result( $res, 0, "sid");
	//if ( 0==mysql_affected_rows($link)) {echo "error: $query ".mysql_error();return false;};
	echo "запись добавлена успешно $id ($hip) ".mysql_error();
	$query="update natsess set t_id=$tid where id=$id;";
	$res = mysql_query($query, $link);
    };
    return true;
}
// vtext - pay comment, val - pay value
function pay_create($link, $id=0, $vtext, $val)
{
	//TODO: do it
    $res = mysql_query("insert into payments (u_id, amount, stat, paytime, cmnt) values ($id, $val, 1, NOW(), '$vtext');", $link);
	$iid=mysql_insert_id($link);
	$res = mysql_query("update users  set balans=balans+$val where id=$id", $link);
	if ( 0==mysql_affected_rows($link)) {
		if (1==option("DEBUG",$link)) {echo "нет такой записи, операция не выполнена".mysql_error();};
		$res = mysql_query("update payments set stat=500 where id=$iid;", $link);
		return false;
	};
	$res = mysql_query("update payments set stat=0 where id=$iid;", $link);
	if (1==option("DEBUG",$link)) {echo "данные обновлены успешно ";}; 
	return true;
}


//
//      Функция user_mod($link,$id)
//      Вход : $id - userid
//      Выход: если все удачно - true, иначе - код ошибки
//      Примечание: если программе не удается выполнить запрос к базе
//                    данных, возвращается код ошибки 3
//
function user_show($link, $id)
{
    global $lang;
    // форматирование
    echo "<!--блок информации -->
	       <table width=\"100%\" height=\"60\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
	         <tr>
		    <td width=\"15%\">";
    // конец форматирования
    if ($id>0) {
	$res = mysql_query("SELECT u.login as login, u.balans as balans, u.overdraft as overdraft, u.bill as bill, u.perm as perm, u.a_id as aid, u.email as email, u.vname as vname, t.id as tid FROM users u, tariffs t WHERE u.id=$id and u.t_id=t.id", $link);
	if ( !$res ) {
	    echo mysql_error();
	    return 3;}
	    $i=0;
	    if ( 0==mysql_numrows($res)) {echo "error(user_show): so such user";
	    return false;
	    };

	    $login  = mysql_result($res,$i,"login");
	    $balans = mysql_result($res,$i,"balans");
	    $avail = mysql_result($res,$i,"overdraft")+mysql_result($res,$i,"balans");
	    $balans = mysql_result($res,$i,"balans");
	    $over = mysql_result($res,$i,"overdraft");
	    $bill = mysql_result($res,$i,"bill");
	    $email = mysql_result($res,$i,"email");
	    $vname = mysql_result($res,$i,"vname");
	    $perm = (int)mysql_result($res,$i,"perm");
	    $aid = (int)mysql_result($res,$i,"aid");
	    $tid = (int)mysql_result($res,$i,"tid");
    };
    echo "<FORM ACTION=\"admin.php\" METHOD=get>
<INPUT TYPE=hidden NAME=mode VALUE=\"cu\">
<INPUT TYPE=hidden NAME=id VALUE=\"$id\">
<TR>
    <TD><B>description</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=vname VALUE=\"" . htmlspecialchars($vname) . "\"></TD>
</TR>
<TR>
    <TD><B>login</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=login VALUE=\"" . htmlspecialchars($login) . "\"></TD>
</TR>
<TR>
	    <TD><B>password</B></TD>
            <TD><INPUT TYPE=password CLASS=\"input\" NAME=pass1 VALUE=\"\"></TD>
</TR>
<TR>
	    <TD><B>repeat password</B></TD>
            <TD><INPUT TYPE=password CLASS=\"input\" NAME=pass2 VALUE=\"\"></TD>
</TR>
<TR>
    <TD><B>email</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=email VALUE=\"" . htmlspecialchars($email) . "\"></TD>
</TR>
<TR>
    <TD><B>permissions</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=perm VALUE=\"$perm\"></TD>
</TR>
<TR>
    <TD><B>balans:</B></TD>
    <TD>&nbsp;".dotize($balans)."&nbsp;уе</td>
</TR>
<TR>
    <TD><B>billed</B></TD>
    <TD>&nbsp;".dotize($bill)."&nbsp;уе</TD>
</TR>
<TR>
    <TD><B>overdraft:</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=over VALUE=\"" . dotize($over) . "\">&nbsp;уе</TD>
</TR>
<TR>
    <TD><B>tarif</B></TD>";
    echo "<td><select name=tid style=\"font-weight: bold\">";
    $result = mysql_query("select vname, id from tariffs;", $link);
    for ($i = 0; $i < mysql_numrows($result); $i++)
    {
	$rid = mysql_result( $result, $i, "id");
	$vname = mysql_result( $result, $i, "vname");
	echo "<option value=\"$rid\"";
	if ($rid == $tid) {echo "selected";}
	echo ">$vname";
    }
    echo "</select></td>
</TR>
<TR>
    <TD><B>authentication</B></TD>";
    echo "<td><select name=aid style=\"font-weight: bold\">";
    $result = mysql_query("select type, id, sysname from auth;", $link);
    for ($i = 0; $i < mysql_numrows($result); $i++)
    {
	$rid = mysql_result( $result, $i, "id");
	$vsysname = mysql_result( $result, $i, "sysname");
	$vtype = mysql_result( $result, $i, "type");
	echo "<option value=\"$rid\"";
	if ($rid == $aid) {echo "selected";}
	echo ">$vsysname ($vtype)";
    }
    echo "</select></td>
</TR>

<tr>
<td>
            <INPUT TYPE=submit CLASS=\"inputsubmit\" ID=\"bg_green\" NAME=createbutton VALUE=\"apply\">&nbsp;&nbsp;&nbsp;&nbsp;
            <INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_blue\"  VALUE=\"back\" OnClick=\"window.location = 'admin.php'\">&nbsp;&nbsp;&nbsp;&nbsp;";
    if ($id>0) { echo "<INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_black\"  VALUE=\"delete\" OnClick=\"window.location = 'admin.php?mode=du&id=$id'\">&nbsp;&nbsp;&nbsp;&nbsp;";};
    echo "</td>
</form>
</table>
";
    @mysql_free_result($res);
    return true;
}

function pay_show($link, $id=0, $pid=0)
{
    global $lang;
    // todo
    if (0!=$pid) {    
	$res = mysql_query("select 	u_id, amount, stat, paytime, cmnt from payments where id=$pid", $link);
	if ( !$res ) {
	    echo mysql_error();
	    return 3;}
	    $i=0;
	    if ( 0==mysql_numrows($res)) {echo "error(pay_show): so such payment";
	    return false;
	    };

	    $id = mysql_result($res,$i,"u_id");
	    //$avail = mysql_result($res,$i,"overdraft")+mysql_result($res,$i,"balans");
	    $val = mysql_result($res,$i,"amount");
	    $status = mysql_result($res,$i,"stat");
	    $paytime = mysql_result($res,$i,"paytime");
	    $cmnt = mysql_result($res,$i,"cmnt");
} // select payment data
   if (0!=$id) {    
	$res = mysql_query("select login, balans, vname, bill, overdraft from users where id=$id", $link);
	if ( !$res ) {
	    echo mysql_error();
	    return 3;}
	    $i=0;
	    if ( 0==mysql_numrows($res)) {echo "error(pay_show): so such payment";
	    return false;
	    };

	    $balans = mysql_result($res,$i,"balans");
	    //$avail = mysql_result($res,$i,"overdraft")+mysql_result($res,$i,"balans");
	    $balans = mysql_result($res,$i,"balans");
	    $over = mysql_result($res,$i,"overdraft");
	    $bill = mysql_result($res,$i,"bill");
	    $vname = mysql_result($res,$i,"vname");
//	    $aid = (int)mysql_result($res,$i,"aid");
//	    $tid = (int)mysql_result($res,$i,"tid");
} // select payment data

    echo "<!--блок информации -->
	       <table width=\"100%\" height=\"60\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
	         <tr>
		    <td width=\"15%\">";
    // конец форматирования

    echo "<FORM ACTION=\"admin.php\" METHOD=get>
<INPUT TYPE=hidden NAME=mode VALUE=\"pc\">
<INPUT TYPE=hidden NAME=id VALUE=\"$id\">
<INPUT TYPE=hidden NAME=pid VALUE=\"$pid\">
<TR>
    <TD><B>payment description</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=vtext VALUE=\"" . htmlspecialchars($vtext) . "\"></TD>
</TR>
<TR>
    <TD><B>user</B></TD>
    <TD>&nbsp;$vname</TD>
</TR>
<TR>
    <TD><B>value</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=val VALUE=\"0\"></TD>
</TR>
<TR>
    <TD><B>balans:</B></TD>
    <TD>&nbsp;".dotize($balans)."&nbsp;уе</td>
</TR>
<TR>
    <TD><B>billed</B></TD>
    <TD>&nbsp;".dotize($bill)."&nbsp;уе</TD>
</TR>
<TR>
    <TD><B>overdraft:</B></TD>
    <TD>&nbsp;" . dotize($over) . "&nbsp;уе</TD>
</TR>
<tr>
<td>
            <INPUT TYPE=submit CLASS=\"inputsubmit\" ID=\"bg_green\" NAME=createbutton VALUE=\"apply\">&nbsp;&nbsp;&nbsp;&nbsp;
            <INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_blue\"  VALUE=\"back\" OnClick=\"window.location = 'admin.php'\">&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "</td>
</form>
</table>
";
    @mysql_free_result($res);
    return true;
}



function auth_create($link, $id)
{
    $atype=$_GET['type'];
    $asysname=$_GET['sysname'];
    $aname=$_GET['name'];
    $aparam1=$_GET['param1'];
    $aparam2=$_GET['param2'];
    if ($id>0) {
	$res = mysql_query("update auth set type='$atype', sysname='$asysname', name='$aname', prog='$aparam1', param='$aparam2' where id=$id", $link);
	if ( 0==mysql_affected_rows($link)) {echo "нет такого пользователя, операция не выполнена".mysql_error();return false;};
	echo "данные обновлены успешно ";
    } // or add new user
    else {
	$query="insert into auth (type, sysname, name, prog, param) values ('$atype', '$asysname', '$aname', '$aparam1', '$aparam2');";
	$res = mysql_query($query, $link);
	$id=mysql_insert_id($link);
	if ( 0==mysql_affected_rows($link)) {echo "error: $query ".mysql_error();return false;};
	echo "новая авторизация добавлена успешно ".mysql_error();
    };
    return true;
}

function auth_show($link, $id)
{
    global $lang;
    // форматирование
    echo "<!--блок информации -->
	       <table width=\"100%\" height=\"60\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
	         <tr>
		    <td width=\"15%\">";
    // конец форматирования
    if ($id>0) {
	$res = mysql_query("select 	type,prog,param,name,sysname	from auth where id=$id;", $link);
	if ( !$res ) {
	    echo mysql_error();
	    return 3;}
	    if ( 0==mysql_numrows($res)) 
	    {
		echo "auth_show: error нет такого типа авторизации";
		return false;
	    };

	    $atype = mysql_result($res,0,"type");
	    $apar1 = mysql_result($res,0,"prog");
	    $apar2 = mysql_result($res,0,"param");
	    $aname = mysql_result($res,0,"name");
	    $asysname = mysql_result($res,0,"sysname");			
	    
    };
    echo "<FORM ACTION=\"admin.php\" METHOD=get>
<INPUT TYPE=hidden NAME=mode VALUE=\"ca\">
<INPUT TYPE=hidden NAME=id VALUE=\"$id\">
<TR>
    <TD><B>shortname</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=sysname VALUE=\"" . htmlspecialchars($asysname) . "\"></TD>
</TR>
<TR>
    <TD><B>fullname</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=name VALUE=\"" . htmlspecialchars($aname) . "\"></TD>
</TR>
<TR>
    <TD><B>parameter 1</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=param1 VALUE=\"" . htmlspecialchars($apar1) . "\">file or domain name (/passwd.file or @job.local)</TD>
</TR>
<TR>
    <TD><B>parameter 2</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=param2 VALUE=\"" . htmlspecialchars($apar2) . "\">Domain Controllers list (1.1.1.1;1.1.1.2;1.1.1.3)</TD>
</TR>
<TR>
    <TD><B>auth type</B></TD>";
    echo "<td><select name=type style=\"font-weight: bold\">";
    $result = mysql_query("select distinct type from auth;", $link);
    for ($i = 0; $i < mysql_numrows($result); $i++)
    {
	$type = mysql_result( $result, $i, "type");
	echo "<option value=\"$type\"";
	if ($type == $atype) {echo "selected";}
	echo ">$type";
    }
    echo "</select></td>";
    echo "</TR>
<tr>
<td>
            <INPUT TYPE=submit CLASS=\"inputsubmit\" ID=\"bg_green\" NAME=createbutton VALUE=\"apply\">&nbsp;&nbsp;&nbsp;&nbsp;
            <INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_blue\"  VALUE=\"back\" OnClick=\"window.location = 'admin.php'\">&nbsp;&nbsp;&nbsp;&nbsp;";
    //if ($id>0) { echo "<INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_black\"  VALUE=\"delete\" OnClick=\"window.location = 'admin.php?mode=del&id=$id'\">&nbsp;&nbsp;&nbsp;&nbsp;";};
    echo "</td>
</form>
</table>
";
    @mysql_free_result($res);
    return true;
}



// f single session stat
function sess_show($link, $id)
{
    global $lang;
    // форматирование
    if ($id>0)
    {
	$res = mysql_query("select distinct UNIX_TIMESTAMP(date_format(dt,'%Y%m%d000000')) as dtd from natchng as n where n.s_id=$id order by dtd", $link);
	echo "<!--блок данных по дням --><table width=\"400px\" height=\"60\" cellpadding=\"0\" cellspacing=\"0\" border=\"1\">";
	// конец форматирования
	echo "<TR>	<TD><B>Дата</B></TD><TD>входящий</TD><TD>исходящий</TD><TD>цена</TD></TR>";
	if ( !$res )
	{
	    echo mysql_error();
	    return 3;
	}
	$i=0;
	if ( 0==mysql_numrows($res))
	{
	    echo "error: no data for this period";
	    return false;
	}
	$link2=db_connect();
	$stin=0;
	$stout=0;
	$scst=0;
	for ($i=0;$i<mysql_num_rows($res);$i++)
	{
	    $dtd  = mysql_result($res,$i,"dtd");
	    $qry="select DATE(FROM_UNIXTIME($dtd)) as dt2, sum(n.tin) as tin, sum(n.tout) as tout, sum(cost) as ct from natchng as n  where (n.dt between DATE(FROM_UNIXTIME($dtd)) and date_add(FROM_UNIXTIME($dtd), interval 1 day))  and s_id=$id";
	    echo "<!-- $qry -->";
	    $res2 = mysql_query($qry, $link2);
	    if ( !$res2 ) {
		echo mysql_error();
		return 3;}
		//   $i=0;
		$dt = mysql_result($res2,0,"dt2");
		$tin = mysql_result($res2,0,"tin");
		$tout = mysql_result($res2,0,"tout");
		$cst = mysql_result($res2,0,"ct");
		$stin=$stin+$tin;
		$stout=$stout+$tout;
		$scst=$scst+$cst;
		echo "
<TR>
    <TD><a href=?mode=day&dt1=$dtd><B>$dt</B></a></TD><TD>$tin</TD><TD>$tout</TD><TD>$cst</TD>
</TR>" ;
		// надо вывести таблицу со значениями
	}
	// конец контента
	echo "
<TR>
    <TD><B>всего</B></TD><TD>$stin</TD><TD>$stout</TD><TD>$scst</TD>
</TR>
";
	echo "</table><!--//блок данных по дням-->";

	// конец вывода данных по сессии
	return 0;
    };

    echo "<FORM ACTION=\"admin.php\" METHOD=get>
<INPUT TYPE=hidden NAME=mode VALUE=\"cs\">
<INPUT TYPE=hidden NAME=id VALUE=\"$id\">
<TR>
    <TD><B>username</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=login VALUE=\"" . htmlspecialchars($vname) . "\"></TD>
</TR>
<TR>
    <TD><B>ip</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=ip VALUE=\"" . htmlspecialchars($ip) . "\"></TD>
</TR>
<tr>
<td>
            <INPUT TYPE=submit CLASS=\"inputsubmit\" ID=\"bg_green\" NAME=createbutton VALUE=\"apply\">&nbsp;&nbsp;&nbsp;&nbsp;
            <INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_blue\"  VALUE=\"back\" OnClick=\"window.location = 'admin.php'\">&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "</td></form></table>";
    @mysql_free_result($res);
    return true;
}
// tarif create/alter
function tarif_create($link, $id)
{
    // можно использовать только до 2^31
    $usize=(int)str_replace ( $delimiter, "",trim($_GET['usize']));
    $dcost=(int)str_replace ( $delimiter, "",trim($_GET['dcost']));
    $ucost=(int)str_replace ( $delimiter, "",trim($_GET['ucost']));
    $mbonus=(int)str_replace ( $delimiter, "",trim($_GET['mbonus']));
    $sysname=$_GET['sysname'];
    $vname=$_GET['vname'];
    $act=$_GET['act'];
    if ($id>0) {
	$res = mysql_query("update tariffs set usize=$usize,dcost=$dcost,ucost=$ucost,sysname='$sysname',vname='$vname',act=$act, mbonus=$mbonus where id=$id", $link);
	if ( 0==mysql_affected_rows($link)) {echo "нет такой записи, операция не выполнена".mysql_error();return false;};
	echo "данные обновлены успешно "; 
	return true;
    } // or add new
    else {
	$query="insert into tariffs (sysname, vname, usize, ucost, dcost, mbonus, act) values ('$sysname', '$vname', $usize, $ucost, $dcost, $mbonus, $act);";
	$res = mysql_query($query, $link);		
	if ( 0==mysql_insert_id($link)) {echo "error: $query ".mysql_error();return false;};
	echo "запись добавлена успешно ".mysql_error();
    };
    return true;
}

// show form to edit/add tariff
// in: $link - mysql link
// in: $id - session id
function tarif_show($link, $id)
{
 //todo
    // форматирование
    echo "<!--блок информации -->
	       <table width=\"100%\" height=\"60\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
	         <tr>
		    <td width=\"15%\">";
    // конец форматирования	
 
    if ($id>0) {

    $qry="select usize,dcost,ucost,sysname,vname,act, mbonus from tariffs where id=$id";
	echo "<!-- $qry -->";
	$res = mysql_query($qry, $link);
	if ( !$res )
	{
	    echo mysql_error();
	    return 3;
	}
	if ( 0==mysql_numrows($res))
	{
	    echo "error: нет такого тарифа";
	    return false;
	}
	$usize = mysql_result($res,0,"usize");
	$vname = mysql_result($res,0,"vname");
	$ucost = mysql_result($res,0,"ucost");
	$dcost = mysql_result($res,0,"dcost");
	$sysname = mysql_result($res,0,"sysname");
	$act = (int)mysql_result($res,0,"act");
	$mbonus = mysql_result($res,0,"mbonus");
    }  // end of get data
    // show form

    echo "<FORM ACTION=\"admin.php\" METHOD=get>
<INPUT TYPE=hidden NAME=mode VALUE=\"ct\">
<INPUT TYPE=hidden NAME=id VALUE=\"$id\">
<TR>
Warning! changing tariff will cause recalculation of the value of all active sessions
<!-- Редактирование тарифных цен вызовет пересчет стоимости всех активных сессий -->
    <TD><B>long name (user friendly)</B></TD>
    <TD><INPUT TYPE=text CLASS=\"input\" NAME=vname VALUE=\"$vname\"></TD>
</TR>
<TR>
    <TD><B>short name (listed)</B></TD>
    <TD><INPUT TYPE=text CLASS=\"input\" NAME=sysname VALUE=\"$sysname\"></TD>
</TR>
<TR>
    <TD><B>unit size (bytes)</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=usize VALUE=\"$usize\"></TD>
</TR>
<TR>
    <TD><B>цена передачи еденицы</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=ucost VALUE=\"$ucost\"></TD>
</TR>
<TR>
    <TD><B>цена приема еденицы</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=dcost VALUE=\"$dcost\"></TD>
</TR>
<TR>
    <TD><B>добавлять едениц в начале месяца</B></TD>
    <TD>&nbsp;<INPUT TYPE=text CLASS=\"input\" NAME=mbonus VALUE=\"$mbonus\"></TD>
</TR>
<TR>
    <TD><B>avaible</B></TD>
    <TD><select name=act style=\"font-weight: bold\">
<option value=\"1\" ";
if (1==$act) {echo "selected";}; 
echo "> yes 
<option value=\"0\" ";
if (0==$act) {echo "selected";};
echo "> no
</select> 
    </td>
</TR>
<tr>
<td>
            <INPUT TYPE=submit CLASS=\"inputsubmit\" ID=\"bg_green\" NAME=createbutton VALUE=\"apply\">&nbsp;&nbsp;&nbsp;&nbsp;
            <INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_blue\"  VALUE=\"back\" OnClick=\"window.location = 'admin.php'\">&nbsp;&nbsp;&nbsp;&nbsp;";
    if ($id>0) { echo "<INPUT TYPE=button CLASS=\"inputsubmit\" ID=\"bg_black\"  VALUE=\"delete\" OnClick=\"window.location = 'admin.php?mode=dt&id=$id'\">&nbsp;&nbsp;&nbsp;&nbsp;";};
    echo "</td>
</form>
</table>
";
    @mysql_free_result($res);
    return true;
}

// show tarif list
function tarif_list($link)
{
    global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;	
    getclasses(0);
    echo "<!--блок данных по тарифам -->
    <table $visualclassTable>
    ";
    // конец форматирования
    echo "
<tr>
<a href=?mode=et>create tarif</a>
</tr>
<TR>
    <TD $visualclassTitleTD><B>tarif name</B></TD>
    <TD $visualclassTitleTD>description</TD>
    <TD $visualclassTitleTD>inbound cost</TD>
    <TD $visualclassTitleTD>outbound cost</TD>
    <TD $visualclassTitleTD>billable unit size</TD>
    <TD $visualclassTitleTD>monthly bonus</TD>
</TR>";
    $qry="select id,usize,dcost,ucost,sysname,vname,act, mbonus from tariffs";
    echo "<!-- $qry -->";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo mysql_error();
	return 3;
    }
    $i=0;
    if ( 0==mysql_numrows($res))
    {
	echo "error: no tarifs found!";
	return false;
    }
    $counter=0;
    // надо вывести таблицу со значениями
    for ($i=0;$i<mysql_num_rows($res);$i++)
    {
	$counter++;
	getclasses($counter);		
	$tid = mysql_result($res,$i,"id");
	$usize = mysql_result($res,$i,"usize");
	$vname = mysql_result($res,$i,"vname");
	$dcost = mysql_result($res,$i,"dcost");
	$ucost = mysql_result($res,$i,"ucost");
	$sysname = mysql_result($res,$i,"sysname");
	$act = (int)mysql_result($res,$i,"act");
	$mbonus = (int)mysql_result($res,$i,"mbonus");
    if (0==$act) {$closed="bgcolor=\"#7e7e7e\"";} else {$closed="";}
	//$bill = mysql_result($res,$i,"bill");
	echo "
<TR $visualclassTR>
    <TD $visualclassTD $closed><A NAME=\"$tid\"><A HREF=\"admin.php?mode=et&id=$tid\"><B>$sysname</B></a></TD>
    <TD $visualclassTD $closed>$vname</TD>
    <TD $visualclassTDr $closed>".dotize($ucost)."</TD>
    <TD $visualclassTDr $closed>".dotize($dcost)."</TD>
    <TD $visualclassTDr $closed>".dotize($usize)."</TD>
    <TD $visualclassTD $closed>$mbonus</TD>
</TR>" ;
    }
    // конец контента
    echo "</table><!--//блок данных по дням-->";
    @mysql_free_result($res);
    return true;
};

function getclasses($counter)
{
global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;
if (0==$counter)
{
    $visualclassTable="width=\"600px\" height=\"60\"  class='dgTable' cellpadding='0' cellspacing='0'";
    $visualclassTitleTD="class='dgTitles'";
    $visualclassTotalTDr="class='dgRowsTot' align='right'";
    $visualclassTotalTD="class='dgRowsTot' align='left'";
    $visualclassTotalTR="class='dgTotRowsTR' align='left'";
    return;
};
    if ($counter&1)
	{
	    $visualclassTD="class='dgRownorm' align='left'";
	    $visualclassTR="class='dgRowsnormTR' align='left'";
	    $visualclassTDr="class='dgRownorm' align='right'";
	} 
	else 
	{
	    $visualclassTD="class='dgRowalt' align='left'";
	    $visualclassTDr="class='dgRowalt' align='right'";
	    $visualclassTR="class='dgRowsaltTR' align='left'";
	};
}

// session_list вывод данных по активным сеесиям
function session_list($link, $uid=0)
{
global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;	
    getclasses(0);
    $counter=0;
    echo "<!--блок данных по активным сессиям -->
    <table $visualclassTable>
    ";
    // конец форматирования
    echo "<tr><a href=?mode=as>create session</a></tr>";
    echo "<TR>
    <TD $visualclassTitleTD><B>name</B></TD>
    <TD $visualclassTitleTD>IP</TD>
    <TD $visualclassTitleTD>inbound</TD>
    <TD $visualclassTitleTD>outbound</TD>
    <TD $visualclassTitleTD>session started</TD>
    <TD $visualclassTitleTD>cost</TD>
</TR>";
    if (0!=$uid) {$qry1="and u.id=$uid ";};
    $qry="select n.id as id, u.id as uid, vname, tin, tout, hip, closing, stime, n.bill as bill from natsess as n, users as u where u.id=n.u_id $qry1 order by stime";
    echo "<!-- $qry -->";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo mysql_error();
	return 3;
    }
    $i=0;
    if ( 0==mysql_numrows($res))
    {
	echo "error(session_list): no sessions";
	return false;
    }
    $stin=0;
    $stout=0;
    $scost=0;
    // надо вывести таблицу со значениями
    for ($i=0;$i<mysql_num_rows($res);$i++)
    {
	$counter++;
	getclasses($counter);
	
	$sid = mysql_result($res,$i,"id");
	$uid = mysql_result($res,$i,"uid");
	$vname = mysql_result($res,$i,"vname");
	$tin = mysql_result($res,$i,"tin");
	$tout = mysql_result($res,$i,"tout");
	$hip = long2ip(mysql_result($res,$i,"hip"));
	$stime = mysql_result($res,$i,"stime");
	$bill = mysql_result($res,$i,"bill");
	$cls = mysql_result($res,$i,"closing");
	$stin=$stin+$tin;
	$stout=$stout+$tout;
	$scost=$scost+$bill;
	$tin=dotize($tin);
	$tout=dotize($tout);
	$bill=dotize($bill);
	
	if (1==$cls) {$closed="bgcolor=\"#7e7e7e\"";} else {$closed="";}
	echo "
<TR $visualclassTR>
    <TD $visualclassTD  $closed><A NAME=\"$sid\"><A HREF=\"?mode=sessdet&id=$sid\"><B>$vname</B></a></TD>
    <TD $visualclassTD  $closed><A HREF=\"?mode=sesskil&id=$sid\">$hip</a></TD>
    <TD $visualclassTDr $closed>$tin</TD>
    <TD $visualclassTDr $closed>$tout</TD>
    <TD $visualclassTD $closed>$stime</TD>
    <TD $visualclassTDr $closed>$bill</TD>
</TR>" ;
    }
    // конец контента
    $stin=dotize($stin);
    $stout=dotize($stout);
    $scost=dotize($scost);
    echo "
<TR $visualclassTotalTR>
    <TD $visualclassTotalTD><B>всего</B></TD>
    <TD $visualclassTotalTD></TD>
    <TD $visualclassTotalTDr>$stin</TD>
    <TD $visualclassTotalTDr>$stout</TD>
    <TD $visualclassTotalTDr>$counter</TD>
    <TD $visualclassTotalTDr>$scost</TD>
</TR>
";
    echo "</table><!--//блок данных по дням-->";
    @mysql_free_result($res);
    return true;
};

function get_session_ip($link, $sid)
{
    $qry="SELECT hip FROM natsess where id=$sid";
    echo "<!-- $qry -->";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo "detail: no session21";
	echo mysql_error();
	return 0;
    }
    if (0<mysql_numrows($res)) {
	$hip = mysql_result($res,0);
    } 
    else 
    {
    @mysql_free_result($res);	
    $qry="SELECT hip FROM natlog where id=$sid";
    echo "<!-- $qry -->";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo "detail: no session22";
	echo mysql_error();
	return 0;
    }
    if (0==mysql_numrows($res)) { echo "get_session_ip: cant find session with id=$sid"; return 0;};
	$hip = mysql_result($res,0);
    };
    @mysql_free_result($res);
    echo "<!-- ($hip ".long2ip($hip).") -->";
    return $hip;
}

// session_list вывод данных по сессиям, уровень абстракции 
function session_detail($link, $sid, $page=0)
{
global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;
    getclasses(0);
    $counter=0;
    $stin=0;
    $stout=0;
    echo "<!--блок данных по сессии -->
    <table $visualclassTable>
    ";
    // конец форматирования
    echo "
<TR>
    <TD $visualclassTitleTD><B>source</B></TD>
    <TD $visualclassTitleTD>destination</TD>
    <TD $visualclassTitleTD>inbound</TD>
    <TD $visualclassTitleTD>outbound</td>
</TR>";
    //$qry="select DISTINCT sip, dip from natdet where ns_id=$sid";
    $hip=get_session_ip($link, $sid);
    if (0==$hip) return 0;
    echo "<!-- $hip -->";
    $startr=$page*100; // page size!
    //$qry="SELECT dip AS ColumnZ FROM natdet where ns_id=$sid UNION SELECT sip AS ColumnZ FROM natdet where ns_id=$sid ORDER BY ColumnZ";
    $qry="SELECT distinct dip AS ColumnZ FROM natdet where ns_id=$sid UNION select distinct sip AS ColumnZ FROM natdet where ns_id=$sid ORDER BY ColumnZ limit $startr, 100;";
    echo "<!-- $qry -->";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo mysql_error();
	return 3;
    }
    //$i=0;
    if ( 0==mysql_numrows($res))
    {
	echo "sessdet: no sess2";
	return false;
    }
    $link2=db_connect();
    // надо вывести таблицу со значениями
    for ($i=0;$i<mysql_num_rows($res);$i++)
    {
	$counter++;
	getclasses($counter);
	$sip = mysql_result($res,$i,"ColumnZ");
	if ($hip==$sip) { // траффик от себя к себе всегда 0 в обе стороны
	    continue;
	};

    $qry="select sum(tin) as _tin, sum(tout) as _tout from natdet where sip=$hip and dip=$sip and ns_id=$sid";
    echo "<!-- $qry -->";
    $res2 = mysql_query($qry, $link2);
    if ( !$res2 )
    {
	echo mysql_error($link2);
	return 3;
    }
    $tin=mysql_result($res2, 0, "_tin");
    $tout=mysql_result($res2, 0, "_tout");
    $qry="select sum(tin) as _tin, sum(tout) as _tout from natdet where sip=$sip and dip=$hip and ns_id=$sid";
    echo "<!-- $qry -->";
    $res2 = mysql_query($qry, $link2);
    if ( !$res2 )
    {
	echo mysql_error($link2);
	return 3;
    }
    // reverse order
    $tin=$tin+mysql_result($res2, 0, "_tout");
    $tout=$tout+mysql_result($res2, 0, "_tin");
	$stin+=$tin; // прибавим траффики  к суммарному значению
    $stout+=$tout;
    
    echo "
    <TR $visualclassTR><!-- $sip $hip -->
    <TD $visualclassTD><b>".long2ip($hip)."</b></TD>
    <TD $visualclassTD><A NAME=\"$sid\"><A HREF=\"admin.php?mode=sessdet2&id=$sid&ip=$sip\"><B>".long2ip($sip)."</B></a></TD>
    <TD $visualclassTDr>".dotize($tin)."</TD>
    <TD $visualclassTDr>".dotize($tout)."</TD>
</TR>" ;
    } // for
@mysql_close($link2);	
    // конец контента
    echo "
<TR $visualclassTotalTR>
    <TD $visualclassTotalTD><B>page total</B></TD>
    <TD $visualclassTotalTD>SID=$sid</TD>
    <TD $visualclassTotalTDr>".dotize($stin)."</TD>
    <TD $visualclassTotalTDr>".dotize($stout)."</TD>
</TR>
<TR $visualclassTotalTR>
    <TD $visualclassTotalTD>";
    if ($page!=0) echo "<a href=\"admin.php?mode=sessdet&id=$sid&page=".($page-1)."\"><<< previous page</a>";
echo     "</TD>
    <TD $visualclassTotalTD><a href=\"admin.php?mode=sessdet&id=$sid&page=".($page+1)."\">next page >>></a></TD>
</TR>

";
    echo "</table><!--//блок данных по дням-->";
    @mysql_free_result($res);
    return true;
};

function session_detail2($link, $sid, $sip)
{
global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;
getclasses(0);
$counter=0;
    $stin=0;
    $stout=0;
    echo "<!--блок данных по сессии -->
    <table $visualclassTable>
    ";
    // конец форматирования
    echo "
<TR>
    <TD $visualclassTitleTD><B>period</B></TD>
    <TD $visualclassTitleTD>source</TD>
    <TD $visualclassTitleTD>destination</TD>
    <TD $visualclassTitleTD>inbound</TD>
    <TD $visualclassTitleTD>outbound</td>
</TR>";
    //$qry="select DISTINCT sip, dip from natdet where ns_id=$sid";
    $hip=get_session_ip($link, $sid);
    if (0==$hip) return 0;
    
    $qry="select UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(min(etime)))) as _min, UNIX_TIMESTAMP(DATE(date_add(FROM_UNIXTIME(max(etime)), interval 1 day))) as _max from  natdet where ((sip=$sip and dip=$hip) or (sip=$hip and dip=$sip)) and ns_id=$sid";
    echo "<!-- $qry -->";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo mysql_error();
	return 3;
    }
    $tmax = @mysql_result($res,0,"_max");
    $tmin = @mysql_result($res,0,"_min");	
    $days = ceil(($tmax - $tmin)/(60*60*24));
    $cmin=$tmin;
    $link2=db_connect();
for ($i = 0; $i < $days; $i++)
{
    $cmax=$cmin+(24*60*60)-1;	
    $qry="select sum(tin) as _tin, sum(tout) as _tout from natdet where (sip=$sip and dip=$hip) and (etime>$cmin and etime<$cmax) and ns_id=$sid;";
    $res2 = mysql_query($qry, $link2);
    if ( !$res2 )
    {
	echo mysql_error($link2);
	return 3;
    }
    $tin=mysql_result($res2, 0, "_tin");
    $tout=mysql_result($res2, 0, "_tout");
    $qry="select sum(tin) as _tin, sum(tout) as _tout from natdet where (sip=$hip and dip=$sip) and (etime>$cmin and etime<$cmax) and ns_id=$sid;";
    echo "<!-- $qry -->";
    $res2 = mysql_query($qry, $link2);
    if ( !$res2 )
    {
	echo mysql_error($link2);
	return 3;
    }
    // reverse order
    $tin=$tin+mysql_result($res2, 0, "_tout");
    $tout=$tout+mysql_result($res2, 0, "_tin");
    
    if ((0==$tin0)&&(0==$tout)) {$cmin=$cmax+1;continue;}; // не отображаем пустые	
    $stin+=$tin; // прибавим траффики  к суммарному значению
    $stout+=$tout;
    $counter++;
    getclasses($counter);
    echo "<TR $visualclassTR><A NAME=\"$cmin\">
    <td $visualclassTD><A HREF=\"admin.php?mode=sessdet3&id=$sid&ip=$sip&p1=$cmin\">".strftime ("%B %d, %T", (int)$cmin)."-".strftime ("%B %d, %T", (int)$cmax)."</a></td>
    <TD $visualclassTD>".long2ip($hip)."</TD>
    <TD $visualclassTD>".long2ip($sip)."</TD>
    <TD $visualclassTDr>".dotize($tout)."</TD>
    <TD $visualclassTDr>".dotize($tin)."</TD>
</TR>" ;
$cmin=$cmax+1;
    } // for
@mysql_close($link2);	
    // конец контента
    echo "<TR $visualclassTotalTR>
    <TD $visualclassTotalTD><B>total</B></TD>
    <TD $visualclassTotalTD>SID=$sid</TD>
    <td $visualclassTotalTD>&nbsp;</td>
    <TD $visualclassTotalTDr>$stout</TD>
    <TD $visualclassTotalTDr>$stin</TD>
</TR>";
    echo "</table><!--//блок данных по дням-->";
    @mysql_free_result($res);
    return true;
};

function session_detail3($link, $sid, $sip, $day)
{
global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;
getclasses(0);
$counter=0;	
    $stin=0;
    $stout=0;
    $cmin=$day;
    echo "<!--блок данных по сессии -->
    <table $visualclassTable>
    ";
    // конец форматирования
    echo "
<TR>
    <TD $visualclassTitleTD><B>time frame</B></TD>
    <TD $visualclassTitleTD>source</TD>
    <TD $visualclassTitleTD>destination</TD>
    <TD $visualclassTitleTD>inbound</TD>
    <TD $visualclassTitleTD>outbound</td>
</TR>";
    $hip=get_session_ip($link, $sid);
    if (0==$hip) return 0;
    $hours = 24; 
    $link2=db_connect();
for ($i = 0; $i < $hours; $i++)
{
    $cmax=$cmin+(60*60)-1;	
    $qry="select sum(tin) as _tin, sum(tout) as _tout from natdet where (sip=$sip and dip=$hip) and (etime>$cmin and etime<$cmax) and ns_id=$sid;";
    $res2 = @mysql_query($qry, $link2);
    $tout=@mysql_result($res2, 0, "_tout");
    $tin=@mysql_result($res2, 0, "_tin");
    $qry="select sum(tin) as _tin, sum(tout) as _tout from natdet where (sip=$hip and dip=$sip) and (etime>$cmin and etime<$cmax) and ns_id=$sid;";
    echo "<!-- $qry -->";
    $res2 = @mysql_query($qry, $link2);
    $tout=$tout+@mysql_result($res2, 0, "_tout");
    $tin=$tin+@mysql_result($res2, 0, "_tin");
    if ((0==$tin0)&&(0==$tout)) {$cmin=$cmax+1;continue;}; // не отображаем пустые часы
    
    $stin+=$tin; // прибавим траффики  к суммарному значению
    $stout+=$tout;
    $counter++;
    getclasses($counter);
    echo "<TR $visualclassTR><!-- $sip $hip --><A NAME=\"$cmin\">
    <td $visualclassTD><A HREF=\"admin.php?mode=sessdet4&id=$sid&ip=$sip&p1=$cmin&p2=$cmax\">".strftime ("%B %d, %T", (int)$cmin)."-".strftime ("%B %d, %T", (int)$cmax)."</a></td>
    <TD $visualclassTD>".long2ip($sip)."</TD>
    <TD $visualclassTD>".long2ip($hip)."</TD>
    <TD $visualclassTDr>$tin</TD>
    <TD $visualclassTDr>$tout</TD>
</TR>" ;
$cmin=$cmax+1;
    } // for
@mysql_close($link2);	
    // конец контента
    echo "<TR $visualclassTotalTR>
    <TD $visualclassTotalTD><B>total</B></TD>
    <TD $visualclassTotalTD>&nbsp;</TD>
    <TD $visualclassTotalTD>SID=$sid</TD>
    <TD $visualclassTotalTDr>$stin</TD>
    <TD $visualclassTotalTDr>$stout</TD>
</TR>";
    echo "</table><!--//блок данных по дням-->";
    @mysql_free_result($res);
    return true;
};


function pays_list($link)
{
global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;	
    getclasses(0);
    
    //echo "<a href=?mode=pa>create payment</a>";
    echo "<!--блок данных по pays -->
    <table $visualclassTable>";
    // конец форматирования
    echo "<TR>
    <TD $visualclassTitleTD><B>date</B></TD>
    <TD $visualclassTitleTD>user</TD>
    <TD $visualclassTitleTD>login</TD>
    <TD $visualclassTitleTD>amount</TD>
    <TD $visualclassTitleTD>status</TD>
    <TD $visualclassTitleTD>comment</td></TR>";
    $qry="select p.id,u.login, u.id, u.vname,p.amount,p.stat,p.paytime,p.cmnt 	from payments  as p, users as u where p.u_id=u.id";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo "list users: no users found!";
	echo mysql_error();
	return 3;
    }	
$counter=0;
    // надо вывести таблицу со значениями
    for ($i=0;$i<mysql_num_rows($res);$i++)
    {
	$pid = (int)mysql_result($res,$i,"p.id");
	$vname = mysql_result($res,$i,"u.vname");
	$vlogin = mysql_result($res,$i,"u.login");
	$uid= mysql_result($res,$i,"u.id");
	$amount = mysql_result($res,$i,"p.amount");
	$stat = (int)mysql_result($res,$i,"p.stat");
	$ptime = mysql_result($res,$i,"p.paytime");
	$cmnt = mysql_result($res,$i,"p.cmnt");
	$counter++;
	getclasses($counter);
	$vavail=$overdraft+$balans;
	echo "<TR $visualclassTR>
    <TD $visualclassTD><A NAME=\"$pid\"><B>$date</B><A HREF=\"?mode=pe&id=$pid\"><img src=images/edit.png alt=edit border=0 ></a></TD>
    <TD $visualclassTD>$vlogin</TD>
    <TD $visualclassTD>$amount</TD>
    <TD $visualclassTD>$stat</TD>
    <TD $visualclassTDr>$stat</TD>
    <TD $visualclassTDr>$cmnt</TD>
</TR>" ;
    }
    // конец контента
    echo "</table><!--//блок данных -->";
    @mysql_free_result($res);	
    
    
//class='dgRownorm' align='left'
//class='dgRowalt' align='left'	
    return true;
};




// user_list - вывод списка пользователей системы
function users_list($link)
{
global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;	
    getclasses(0);
    
    echo "<a href=?mode=useradd>create user</a>";
    echo "<!--блок данных по юзерам -->
    <table $visualclassTable>";
    // конец форматирования
    echo "<TR>
    <TD $visualclassTitleTD><B>username</B></TD>
    <TD $visualclassTitleTD>login</TD>
    <TD $visualclassTitleTD>auth</TD>
    <TD $visualclassTitleTD>tarif</TD>
    <TD $visualclassTitleTD>balans</TD>
    <TD $visualclassTitleTD>in use</td></TR>";
    //$qry="SELECT id, login, vname, balans, bill, active, t_id, a_id, overdraft FROM users";
    $qry="SELECT u.id, login, u.vname, balans, bill, active, t.vname AS t_id, a.name AS a_id, overdraft FROM users AS u, auth AS a, tariffs AS t WHERE a.id=u.a_id AND t.id=u.t_id";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo "list users: no users found!";
	echo mysql_error();
	return 3;
    }	
$counter=0;
    // надо вывести таблицу со значениями
    for ($i=0;$i<mysql_num_rows($res);$i++)
    {
	$aid = (int)mysql_result($res,$i,"id");
	$vname = mysql_result($res,$i,"vname");
	$vlogin = mysql_result($res,$i,"login");
	$balans = mysql_result($res,$i,"balans");
	$vbill = mysql_result($res,$i,"bill");
	$visact = (int)mysql_result($res,$i,"active");
	$overdraft = mysql_result($res,$i,"overdraft");
	$tid = mysql_result($res,$i,"t_id");
	$a_id = mysql_result($res,$i,"a_id");
	$counter++;
	getclasses($counter);
	$vavail=$overdraft+$balans;
	echo "<TR $visualclassTR>
    <TD $visualclassTD><A NAME=\"$aid\"><B>$vname</B><A HREF=\"?mode=eu&id=$aid\"><img src=images/edit.png alt=edit border=0 ></a></TD>
    <TD $visualclassTD>$vlogin <A HREF=\"?mode=shist&id=$aid\"><img src=images/view.png alt='show closed user sessions' border=0></a></TD>
    <TD $visualclassTD>$a_id</TD>
    <TD $visualclassTD>$tid</TD>
    <TD $visualclassTDr>".dotize($vavail)."<A HREF=\"?mode=pa&id=$aid\"><img src=images/add.png alt='add payment' border=0></a></TD>
    <TD $visualclassTDr>".dotize($vbill)."<A HREF=\"?mode=usess&aid=$aid\"><img src=images/view.png alt='show active sessions data' border=0></a></TD>
</TR>" ;
    }
    // конец контента
    echo "</table><!--//блок данных -->";
    @mysql_free_result($res);	
    
    
//class='dgRownorm' align='left'
//class='dgRowalt' align='left'	
    return true;
};

// user_list - вывод списка пользователей системы
function user_shist($link, $uid)
{
global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;	
    getclasses(0);
    
    echo "<!--блок данных по закрытым сессиям -->
    <table $visualclassTable>";
    // конец форматирования
    echo "<TR>
    <TD $visualclassTitleTD><B>start time</B></TD>
    <TD $visualclassTitleTD>end time</TD>
    <TD $visualclassTitleTD>cost</TD>
    <TD $visualclassTitleTD>inbound</TD>
    <TD $visualclassTitleTD>outbound</TD>
    <TD $visualclassTitleTD>from</td></TR>";
    $qry="select id, hip, stime, slen, tin, tout, cost, s_id from natlog where u_id=$uid;";
    echo "<!-- $qry -->";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo "user_shist: $qry executing failed<br>!";
	echo mysql_error();
	return 3;
    }	
    $scost=0;
    $stin=0;
    $stout=0;
$counter=0;
    // надо вывести таблицу со значениями
    for ($i=0;$i<mysql_num_rows($res);$i++)
    {
	$aid = (int)mysql_result($res,$i,"id");
	$vhip = mysql_result($res,$i,"hip");
	$vstime = mysql_result($res,$i,"stime");
	$slen = mysql_result($res,$i,"slen");
	$vtin = mysql_result($res,$i,"tin");
	$vtout = mysql_result($res,$i,"tout");
	$vcost = mysql_result($res,$i,"cost");
	$sid = (int)mysql_result($res,$i,"s_id");
	$counter++;
	getclasses($counter);
	$vendtime1=strtotime($vstime)+$slen;
	$vendtime= date('Y-m-j h:i:s', $vendtime1);  
	$scost=$scost+$vcost;
	$stin=$stin+$vtin;
	$stout=$stout+$vtout;
	
	echo "<TR $visualclassTR>
    <TD $visualclassTD><A NAME=\"$aid\"><B>$vstime</B></TD>
    <TD $visualclassTD>$vendtime ($slen sec = ".date("n\mj\d h:m:s",$slen).")</TD>
    <TD $visualclassTDr>".dotize($vcost)."</TD>
    <TD $visualclassTDr>".dotize($vtin)."</TD>
    <TD $visualclassTDr>".dotize($vtout)."</TD>
    <TD $visualclassTD>".long2ip($vhip)."<A HREF=\"?mode=sessdet&id=$aid\"><img src=images/view.png alt='show session detail' border=0></a></TD>
</TR>" ;
    }
    echo "<TR $visualclassTotalTR>
    <TD $visualclassTotalTD><B>total ($counter)</B></TD>
    <TD $visualclassTotalTD></TD>
    <TD $visualclassTotalTDr>$scost</TD>
    <TD $visualclassTotalTDr>$stin</TD>
    <TD $visualclassTotalTDr>$stout</TD>
    <TD $visualclassTotalTDr></TD>
    
    
</TR>";
    // конец контента
    echo "</table><!--//блок данных -->";
    @mysql_free_result($res);	
    
    
//class='dgRownorm' align='left'
//class='dgRowalt' align='left'	
    return true;
};

// auths_list - вывод списка доступных типов авторизаций
function auths_list($link)
{
    global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;	
    getclasses(0);
    
    echo "<a href=?mode=ea>create authorization</a>";
echo "<table $visualclassTable>
<TR>
    <TD $visualclassTitleTD><B>name</B></TD>
    <TD $visualclassTitleTD>fullname</TD>
    <TD $visualclassTitleTD>type</TD>
    <TD $visualclassTitleTD>param1</TD>
    <TD $visualclassTitleTD>param2</TD>
</TR>";
    $qry="select id, sysname, name, type, prog, param from auth order by sysname";
    echo "<!-- $qry -->";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo mysql_error();
	return 3;
    }
    $i=0;
    if ( 0==mysql_numrows($res))
    {
	echo "error(auths_list):no authorizations found";
	return false;
    }
    $counter=0;
    // надо вывести таблицу со значениями
    for ($i=0;$i<mysql_num_rows($res);$i++)
    {
	$counter++;
	getclasses($counter);
	$aid = mysql_result($res,$i,"id");
	$aname = mysql_result($res,$i,"name");
	$asysname = mysql_result($res,$i,"sysname");
	$atype = mysql_result($res,$i,"type");
	$aparam1 = mysql_result($res,$i,"prog");
	$aparam2 = mysql_result($res,$i,"param");
	echo "
<TR $visualclassTR>
    <TD $visualclassTD><A NAME=\"$aid\"><B>$aname</B><A HREF=\"?mode=ea&id=$aid\"><img src=images/edit.png alt=edit border=0 ></a></TD>
    <TD $visualclassTD>$asysname<A HREF=\"?mode=da&id=$aid\"><img src=images/erase.png alt=delete border=0 ></a></TD>
    <TD $visualclassTD>$atype</TD>
    <TD $visualclassTD>$aparam1</TD>
    <TD  $visualclassTD>$aparam2</TD>
</TR>" ;
    }
    // конец контента
    echo "</table><!--//блок данных -->";
    @mysql_free_result($res);
    
    
    return true;
};

//      Функция user_list() - делает выборку данных пользователя
function show_users2($link)
{
    echo "<!--блок данных по дням -->
    <table width=\"400px\" height=\"60\" cellpadding=\"0\" cellspacing=\"0\" border=\"1\">
    ";
    // конец форматирования
    echo "
<tr>
<a href=?mode=useradd>добавить пользователя</a>
</tr>
<TR>
    <TD><B>имя</B></TD><TD>логин</TD><TD>доступно</TD><TD>заблокировано</TD><TD>тариф</TD><TD>группа</TD>
</TR>
<!-- select 	id, a_id, login, passwd, vname, balans, bill, active, overdraft, t_id, email, perm, enotifyed from users order by vname -->
";
    $res = mysql_query("select 	id, a_id, login, passwd, vname, balans, bill, active, overdraft, t_id, email, perm, enotifyed from users order by vname", $link);
    if ( !$res )
    {
	echo mysql_error();
	return 3;
    }
    $i=0;
    if ( 0==mysql_numrows($res))
    {
	echo "error: $web_client_nouser";
	return false;
    }
    // надо вывести таблицу со значениями
    for ($i=0;$i<mysql_num_rows($res);$i++)
    {
	$uid = mysql_result($res,$i,"id");
	$login = mysql_result($res,$i,"login");
	$vname = mysql_result($res,$i,"vname");
	$avail = mysql_result($res,$i,"balans")+mysql_result($res,$i,"overdraft")-mysql_result($res,$i,"bill");
	$locked = mysql_result($res,$i,"bill");
	$perm = mysql_result($res,$i,"perm");
	$tarif = mysql_result($res,$i,"t_id");

	echo "
<TR>
    <TD><A NAME=\"$uid\"><A HREF=\"admin.php?mode=edit&id=$uid\"><B>$vname</B></a></TD><TD><A HREF=\"admin.php?mode=show&id=$uid\">$login</a></TD><TD>$avail</TD><TD>$locked</TD><TD>$tarif</TD><TD>$perm</TD>
</TR>" ;
    }
    // конец контента
    echo "
<TR>
    <TD><B>всего</B></TD><TD>$stin</TD><TD>$stout</TD><TD>$scst</TD>
</TR>
";
    echo "</table><!--//блок данных по дням-->";
    @mysql_free_result($res);
    return true;
};
// вывод списка сессий за день
function show_day($link, $dt1) //todo
{
    //select n.s_id  as sess_id,  min(stime) as dt1, max(stime) as dt2, sum(n.tin) as tin, sum(n.tout) as tout, sum(n.cost) as ct from natchng as n, natlog as s where (n.dt between DATE(FROM_UNIXTIME(4000)) and date_add(FROM_UNIXTIME(1184004000), interval 1 day))  and u_id=1 group by sess_id
    //select n.s_id  as sess_id, DATE(stime) as dt2, sum(n.tin) as tin, sum(n.tout) as tout, sum(cost) as ct from natchng as n, natsess as s where (n.dt between DATE(FROM_UNIXTIME(4000)) and date_add(FROM_UNIXTIME(1884004000), interval 1 day))  and u_id=1 group by sess_id
    echo "<!--блок данных по активным сессиям -->
    <table width=\"600px\" height=\"60\" cellpadding=\"0\" cellspacing=\"0\" border=\"1\">
    ";
    // конец форматирования
    echo "
<tr>
<a href=?mode=sessadd>добавить сессию</a>
</tr>
<TR>
    <TD><B>имя</B></TD><TD>IP</TD><TD>входящий</TD><TD>исходящий</TD><TD>первый траффик</TD><TD>последний траффика</TD><TD>стоимость</TD>
</TR>";
    $qry="select n.s_id  as sess_id, min(DATE(stime)) as dt1, max(DATE(stime)) as dt2, sum(n.tin) as tin, sum(n.tout) as tout, sum(cost) as ct from natchng as n, natsess as s where (n.dt between DATE(FROM_UNIXTIME($dt1)) and date_add(FROM_UNIXTIME($dt1), interval 1 day))  and u_id=1 group by sess_id";
    echo "<!-- $qry -->";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo mysql_error();
	return 3;
    }
    $i=0;
    if ( 0==mysql_numrows($res))
    {
	echo "error: нет сессий";
	return false;
    }
    // надо вывести таблицу со значениями
    for ($i=0;$i<mysql_num_rows($res);$i++)
    {
	$sid = mysql_result($res,$i,"id");
	$uid = mysql_result($res,$i,"uid");
	$vname = mysql_result($res,$i,"vname");
	$tin = mysql_result($res,$i,"tin");
	$tout = mysql_result($res,$i,"tout");
	$hip = long2ip(mysql_result($res,$i,"hip"));
	$stime = mysql_result($res,$i,"stime");
	$bill = mysql_result($res,$i,"bill");
	echo "
<TR>
    <TD><A NAME=\"$sid\"><A HREF=\"?mode=sessdet&id=$sid\"><B>$vname</B></a></TD><TD>$hip</TD><TD>$tin</TD><TD>$tout</TD><TD>$stime</TD><TD>$bill</TD>
</TR>" ;
    }
    // конец контента
    echo "
<TR>
    <TD><B>всего</B></TD><TD>$stin</TD><TD>$stout</TD><TD>$scst</TD>
</TR>
";
    echo "</table><!--//блок данных по дням-->";
    @mysql_free_result($res);
    return true;
}


// вывод подневной статистики для указанного пользователя
function show_days($link, $uid, $sid)
{
    global $visualclassTD,$visualclassTR,$visualclassTDr,$visualclassTable, $visualclassTitleTD,$visualclassTotalTDr, $visualclassTotalTD, $visualclassTotalTR;	
    getclasses(0);
    
    global $megabyte_cost;
    global $lang;
    if ($lang==0) {include "inc/ru.php";};
    if ($lang==1) {include "inc/en.php";};
    // форматирование
    echo "<!--блок данных по дням -->
    <table $visualclassTable>
    ";
    // конец форматирования
	$qry="select distinct UNIX_TIMESTAMP(date_format(dt,'%Y%m%d000000')) as dtd from natchng as n  where n.s_id=$sid order by dtd";
    echo "
<TR>
    <TD $visualclassTitleTD><B>Дата</B></TD>
    <TD $visualclassTitleTD>входящий</TD>
    <TD $visualclassTitleTD>исходящий</TD>
    <TD $visualclassTitleTD>цена</TD>
</TR>
<!-- $qry -->
";
    $res = mysql_query($qry, $link);
    if ( !$res )
    {
	echo mysql_error();
	return 3;
    }
    $i=0;
    if ( 0==mysql_numrows($res))
    {
	echo "error: no data for this period";
	return false;
    }
    $link2=db_connect();
    $stin=0;
    $stout=0;
    $scst=0;
    $counter=0;
    for ($i=0;$i<mysql_num_rows($res);$i++)
    {
	$dtd  = mysql_result($res,$i,"dtd");
	$qry="select DATE(FROM_UNIXTIME($dtd)) as dt2, sum(n.tin) as tin, sum(n.tout) as tout, sum(cost) as ct from natchng as n, natsess as s where (n.dt between DATE(FROM_UNIXTIME($dtd)) and date_add(FROM_UNIXTIME($dtd), interval 1 day))  and u_id=$uid";
	echo "<!--   		$qry -->";
	$res2 = mysql_query($qry, $link2);
	$counter++;
	getclasses($counter);
	if ( !$res2 ) {
	    echo mysql_error();
	    return 3;}
	    $dt = mysql_result($res2,0,"dt2");
	    $tin = mysql_result($res2,0,"tin");
	    $tout = mysql_result($res2,0,"tout");
	    $cst = mysql_result($res2,0,"ct");
	    $stin=$stin+$tin;
	    $stout=$stout+$tout;
	    $scst=$scst+$cst;
	    echo "
<TR $visualclassTR>
    <TD $visualclassTD><a href=?mode=day&dt1=$dtd><B>$dt</B></a></TD>
    <TD $visualclassTDr>".dotize($tin)."</TD>
    <TD $visualclassTDr>".dotize($tout)."</TD>
    <TD $visualclassTDr>".dotize($cst)."</TD>
</TR>" ;
	    // надо вывести таблицу со значениями
    }
    // конец контента
    echo "
<TR $visualclassTotalTR>
    <TD $visualclassTotalTD><B>всего</B></TD>
    <TD $visualclassTotalTDr>".dotize($stin)."</TD>
    <TD $visualclassTotalTDr>".dotize($stout)."</TD>
    <TD $visualclassTotalTDr>".dotize($scst)."</TD>
</TR>
";
    echo "</table><!--//блок данных по дням-->";
    @mysql_free_result($res);
    return true;
}

//    Функция show_tail() - выводит хвост HTML-страницы ;)
function show_tail()
{
    global $version;
    include("timer_show.php");
}

//    Функция show_menu() - выводит меню для пользователя
// todo, необходимо чтобы показывало в каком меню мы находимся, и скрывало "админка" когда нет прав
function show_menu($mode)
{
    echo "		   <!--Меню-->
	     <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" class=\"menu\">
	       <tr>
	         <td valign=\"middle\" class=\"lev1";
	         if ("show"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=\"?mode=show\">account</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev2";
	         if ("csess"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=\"?mode=csess\">this session</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev2";
		if ("tarif"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=\"?mode=trf\">traffic</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev2";
	         if ("sess"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=\"?mode=sess\">sessions</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev1";
	         if ("admin"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=\"admin.php\">managment mode</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev1";
	         if ("set"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=\"?mode=set\">settings</a></td>
	       </tr>

	       </table>
	   <!--//Меню-->";
    return true;
};

//    Функция show_amenu() - выводит меню для администратора
// todo, необходимо чтобы показывало в каком меню мы находимся
function show_amenu($mode)
{
    echo "		   <!--Меню-->
	     <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" class=\"menu\">
	       <tr>
	         <td valign=\"middle\" class=\"lev1\"><a href=?mode=list>list</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev2";
	         if ("list"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=\"?mode=list\">users</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev2";
	         if ("sess"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=?mode=sess>sessions</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev2";
	         if ("tarif"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=?mode=tarif>tarifs</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev2";
		if ("auth"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};			     
	         echo "<a href=?mode=auth>auths</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev2";
		if ("pays"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=?mode=pays>pays</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev1";
	        if ("svc"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=?mode=svc>services</a></td>
	       </tr>
	       <tr>
	         <td valign=\"middle\" class=\"lev1";
	        if ("prf"==$mode) {echo "_act\"><img src=\"images/strelka.gif\">&nbsp;&nbsp;";} else {echo "\">";};
	         echo "<a href=?mode=prf>settings</a></td>
	       </tr></table><!--//Меню-->";
    return true;
};

//    Функция show_head() - выводит шапку HTML-страницы
function show_head($uservname, $sess_id, $login)
{
    global $version, $page_descr, $uservname;
    global $mod_name, $origin;

    include ("timer_set.php");

    if (isset($origin))
    {
	$title="$origin:$mod_name";
    }
    else
    {
	$title="SAcc$version:$mod_name";
    };

    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
<html>
<head>
<META HTTP-EQUIV=\"Expires\" CONTENT=\"0\">
<META HTTP-EQUIV=\"Pragma\"  CONTENT=\"no-cache\">
<META HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\">
<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=koi8-r\">
<link href=\"css/work.css\" rel=stylesheet type=\"text/css\" />
<TITLE>$title</TITLE>";
    if (isset($origin)) {echo "<TITLE>$origin:$mod_name</TITLE>";}
    else {echo "<TITLE>SAcc$version:$mod_name</TITLE>";};

    global  $objGrid;
    $objGrid = new datagrid;
    $objGrid -> friendlyHTML();
    $objGrid -> setHeader();

    echo "</head><body leftmargin=\"0\" topmargin=\"0\" rightmargin=\"0\" bottommargin=\"0\">";
    if (!isset($_POST['DG_ajaxid']))
    {
	echo "<table width=\"100%\" height=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
<!--Шапка-->
      <tr>
        <td height=\"30\"></td>
	<td valign=\"middle\" align=\"left\" style=\"padding-left:5px;\"><img src=\"images/logo.gif\" alt=\"our passion\" width=\"137\" height=\"44\" border=\"0\"></td>
	<td valign=\"top\" align=\"left\" class=\"content\"><span class=\"h1\">$title</span><div id=\"form\">$page_descr</div></td>
	<td valign=\"middle\" align=\"center\">
	  <!--О пользователе-->
	    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
	      <tr>
	        <td valign=\"middle\" class=\"mini_text\"><a href=\"logout.php\"><img src=\"images/logout.gif\" alt=\"выхода нет\" border=\"0\" align=\"absmiddle\"></a>&nbsp;&nbsp;<a href=\"logout.php\">logout</a></td>
	      </tr>
	      <tr>
	        <td colspan=\"2\" height=\"10\"></td>
	      </tr>
	      <tr>
	        <td colspan=\"2\" class=\"mini_text\">User:</td>
	      </tr>
	      <tr>
	        <td colspan=\"2\" class=\"u_name\"><b>$uservname [$login : $sess_id]</b></td>
	      </tr>
	    </table>
	  <!--//О пользователе-->
	</td>
	<td></td>
      </tr>
    <!--//Шапка-->
    <!--Распорки-->
      <tr>
        <td valign=\"top\" height=\"2\" width=\"1%\" bgcolor=\"#0\"></td>
	<td width=\"15%\" bgcolor=\"#bebfbf\"></td>
	<td width=\"56%\" bgcolor=\"#bebfbf\"></td>
	<td width=\"27%\" bgcolor=\"#bebfbf\"></td>
	<td width=\"1%\" bgcolor=\"#bebfbf\"></td>
      </tr>
    <!--//Распорки-->
      <tr>
         <td></td>
	 <td valign=\"top\" class=\"content\">";
    }
    return true;

};

//
//    Функция show_error() - выводит стандартное сообщение об ошибке access denied
//    Вход : ничего
//    Выход: всегда true
//
function show_error()
{
    global $word_warning,$web_client_noaccess;
    echo "<H1>$word_warning</H1><P>
        <FONT COLOR=#FF0000>$web_client_noaccess</FONT></P>";
    //<FORM ACTION=index.php METHOD=get>
    //<INPUT TYPE=submit CLASS=inputsubmit NAME=try VALUE=\"Попробовать еще раз\">
    //</FORM>";
    return true;
};

?>

