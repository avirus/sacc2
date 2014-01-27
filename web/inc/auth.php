<?php
// authenticators

/*
auth_cli - функция авторизации клиента.
вход: $user, $password
выход: true\false
*/
function auth_cli($user, $passwd, $link)
{
	global $auth_mode;
	global $msg;
	$ares=false;
	$passwd=stripslashes($passwd);
	$user=stripslashes($user);
	$u1=$user;
	$p1=$passwd;
	if ( (!isset($user)) or (!isset($passwd)) )   return false;
	$user=rawurlencode($user);
	$passwd=rawurlencode($passwd);
	$passwd=str_replace("\\.",".",$passwd);
	$user=str_replace("\\.",".",$user);
	$result = mysql_query("select a_id, a_domain from users where login = '$user' limit 1", $link);
if ( !$result )
	{
		echo "auth: zero select from users (probably user mispelled?)";
		return false;
	};
	if ( mysql_num_rows($result) == 0 ) return false;
	$auth_mode = mysql_result( $result, 0, "a_id");
	$domain = mysql_result( $result, 0, "a_domain");
	$result = mysql_query("select type, prog, param, sysname from auth where id = $auth_mode limit 1", $link);
	$param1 = mysql_result( $result, 0, "prog");
	if (!isset($domain)) {$domain=$default_domain;};
	$param2 = mysql_result( $result, 0, "param");
	//$sname = mysql_result( $result, 0, "sysname");
	$amode = mysql_result( $result, 0, "type");
	switch ($amode)
	{
		case "msad":		{
			// AD mode
			return auth_smb($u1, $p1,  $domain, $param1, $param2);
			break;
		}
		case "ncsa": {
			// NCSA mode
			return auth_ncsa($user, $passwd, $param1);
			break;
		}
		case "mysql": {
			// MySQL mode
			return auth_mysql($user, $passwd, $link);
			break;
		}
		default: {
			//WTF?!
			return false;
			echo "incorrect auth mode!";
			die(0);
		}
	}
//$msg=$e." mode ".$auth_mode."/".$ares."/".$user."/".$passwd;
	return false;
}

function auth_ncsa($user, $passwd, $filename)
{
        $fp = fopen($filename, 'r');
        $file_contents = fread($fp, filesize($filename));
        fclose($fp);
        $lines= explode ("\n", trim($file_contents));
        
            foreach($lines as $line) {
                list($username, $password) = explode($this->deliminator, $line);
                if ($username == $user) {
                    $salt = substr($password ,0 ,2);
                    $cryptPasswd = crypt($passwd, $salt);
                    if($password == $cryptPasswd) {
                        return true;
                        break;
                    }
                }
            }
return false;            
}

//      Функция smb_auth($user, $passwd) - авторизует пользователя с заданным логином и паролем
//      Вход :
//             $user - логин (ник) пользователя)
//             $passwd - пароль пользователя
//      Выход: в случае успешного прохождения аутентификации возвращается true, иначе - false
function auth_smb($user, $passwd, $realm, $domain, $dc )
{
include ("inc/adLDAP.php");
$dclist=split(";", $dc);
$options=array(domain_controllers=>$dclist, account_suffix=>$domain);
$adldap = new adLDAP($options);
                if ($adldap -> authenticate($user,$passwd)){
                        return true;
                        exit;
                }
            return false;
}

function auth_mysql($user, $passwd, $link)
{
    //$link=db_connect();
    $result = mysql_query("select * from users where login = '$user' and passwd='$passwd' and perm>1", $link);
    echo mysql_error();
        if ( mysql_num_rows($result) == 0 )
            {
                //mysql_close($link);
            	return false;
            }
        else if ( mysql_num_rows($result) != 0 )
            {
            	//mysql_close($link);
                return true;
            }
    //@mysql_close($link);
    return false;
}

//      Функция auth_adm($user, $passwd) - авторизует пользователя с заданным
//                                              логином и паролем
//      Вход :
//             $user - логин (ник) пользователя)
//             $passwd - пароль пользователя
//      Выход: в случае успешного прохождения аутентификации возвращается true, иначе - false
function auth_adm($user, $passwd)
{
global $dat1;

    if ( (!isset($user)) or (!isset($passwd)) )
        return false;
    else
    {
    $link=db_connect();
    $dat1 = "select * from admins where login = '$user' and passwd = '" . md5($passwd) . "'";
    $result = mysql_query($dat1, $link);
    echo mysql_error();
        if ( mysql_num_rows($result) == 0 )
            {
                mysql_close($link);
            	return false;
            }
        else if ( mysql_num_rows($result) != 0 )
            {
            	mysql_close($link);
                return true;
            }
    }
    @mysql_close($link);
    return false;
}


?>