<?php
/**
* Classe que controla 
* a sessão php
* @author Marcelo Soares da Costa
* @email phpmafia at yahoo dot com dot br
* @copyright Marcelo Soares da Costa © 2007. 
* @license FreeBSD http://www.freebsd.org/copyright/freebsd-license.html
* @version 1,0
* @access public
* @package phpmafiasession
* @subpackage phpmafiasql
* @data 2007-02-06
*/ 
# Tabelas necessarias no banco de dados
/*
-- MYSQL
CREATE TABLE `_session` (
  `id_session` int(11) NOT NULL auto_increment,
  `remote_addr` bigint(12) NOT NULL,
  `request_uri` varchar(128) NOT NULL,
  `keytime` bigint(20) NOT NULL,
  `keepalive` int(11) NOT NULL,
  PRIMARY KEY  (`id_session`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1

CREATE TABLE `login` (
`id_login` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`login` VARCHAR( 20 ) NOT NULL ,
`senhamd5` VARCHAR( 32 ) NOT NULL ,
`senhasha1` VARCHAR( 40 ) NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=latin1;

-- PGSQL
CREATE TABLE _session
(
  id_session serial NOT NULL,
  remote_addr integer NOT NULL,
  request_uri character varying(128) NOT NULL,
  keytime bigint NOT NULL,
  keepalive integer
) 
WITH OIDS;
CREATE TABLE "login"
(
  id_login serial NOT NULL,
  "login" character varying(20) NOT NULL,
  senhamd5 character varying(32) NOT NULL,
  senhasha1 character varying(40) NOT NULL
) 
WITH OIDS;
*/
#################################################################################
include_once ("class_mafiasql.php");
#################################################################################

# classe que controla a session do php
class MafiaSession extends  MafiaSQL
{
# seta o tempo limite para login em microsegundos
private function __timelogin($microtime) 
{ 
	unset($this->timelogin);
	$this->timelogin = $microtime; 
} 
# seta o tempo limite de uma sessão
private function __timelimit($lifetime) 
{ 
	unset($this->timelimit);
	$this->timelimit = $lifetime; 
} 
#função que pega os valores do cliente para a criar a chave de autenticação
private function insertkeydata()
{
$this-> __timelogin(TIMELOGIN);
$this->keytime = str_replace(".",'',microtime(true));
$this->REMOTE_ADDR=str_replace(".",'',$_SERVER["REMOTE_ADDR"]);
if($this->REMOTE_ADDR=="::1")
{
$this->REMOTE_ADDR=127001;
}

if($_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]==str_replace("http://",'',LOGINURL))
{
session_unset();
session_destroy();

$this->REQUEST_URI=$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];

$this->clientedata=$this->REMOTE_ADDR.$this->REQUEST_URI.$this->keytime;

$SQL_CLEAN="DELETE FROM _session WHERE keytime<=".($this->keytime-$this->timelogin);
$SQL_CLEAN.=" AND keepalive=0";

$this->setsql($SQL_CLEAN);

$SQL_KEY="INSERT INTO _session (REMOTE_ADDR,REQUEST_URI,keytime,keepalive) VALUES ('".$this->REMOTE_ADDR."','".$this->REQUEST_URI."',".$this->keytime.",0)";

$this->idkey= $this->setsql($SQL_KEY);

return $this->idkey;
}else{
header("Location: ".KICKURL.""); /* Redirect browser */
}
}
# Função que cria a chave sha256 de autenticação a partir dos dados gravados no bd
private function createserverkey($id)
{
$SQL_KEY="SELECT * FROM _session WHERE id_session=".$id;

extract($this->sql2linearray($SQL_KEY),EXTR_OVERWRITE);

$this->serverkey=hash('sha256',$remote_addr.$request_uri.$keytime);

return true;
}
# Valida a chave de autenticação
private function validkey($id)
{
$this->timecurrent=time();
$SQL="UPDATE _session set keepalive=".$this->timecurrent." WHERE id_session=".$id ;
return $this->setsql($SQL);
}
# Verifica a chave de autenticação
private function checkserverkey($id,$keyserver)
{
//unset($this->keydb);
$SQL_KEY="SELECT * FROM _session WHERE id_session=".$id;//." ".$param;

  if($this->setsql($SQL_KEY)==1) 
  {

 extract($this->getlinearray(),EXTR_OVERWRITE);

  $this->keydb=hash('sha256',$remote_addr.$request_uri.$keytime);
    
    if($keyserver===$this->keydb)
    {
    return true;
    }else{
    return false;
    }
  }else{
  return false;
  }
}
# função que retorna a chave de autenticação
public function newserverkey()
{
$this->createserverkey($this->insertkeydata());
return array("idkey"=>$this->idkey,"serverkey"=>$this->serverkey);
}
# Função que registra como valida a chave inserindo um no campo keepalive
private function registerkey($id,$keyserver)
{
  if($this->checkserverkey($id,$keyserver)==true)
  {
  return $this->validkey($id);
  }else{
  return false;
  }
}
# Verifica se o login é valido
private function checkpasswd($user,$salt,$passwdmd5,$passwdsha1)
{
$SQL_PASS="SELECT * FROM login WHERE login='".$user."'";
//echo $SQL_PASS;
if($this->setsql($SQL_PASS)==1) 
  {
  extract($this->__fetchassoc(),EXTR_OVERWRITE);
  //aplico hmac para verificar se as senhas correpondem
  $hashmd5=hash_hmac('md5',$salt,$senhamd5);
  //echo "MD5=>".$passwdmd5."===".$hashmd5."<br/>";
  $hashsha1=hash_hmac('sha1',$salt,$senhasha1);
  // echo "SHA1=>".$passwdsha1."===".$hashsha1."<br/>";
  if(($passwdmd5===$hashmd5) AND ($passwdsha1===$hashsha1))
  {
  $this->iduser=$id_login;
  return $this->iduser;
  }else{
  return false;
  }
    }else{
  return false;
  }
}
# função que registra a sessão
public function registersession()
{

if(is_array($_POST))
  {

  if($this->registerkey($_POST['id'],$_POST['salt'])==true)
    {

    list($senhamd5,$senhasha1)=explode("|",base64_decode($_POST['password']));
    if($this->checkpasswd(base64_decode($_POST['username']),$_POST['salt'],$senhamd5,$senhasha1)==true)
      {  
      $this->keysession=1;
      $_SESSION['idkey']=$_POST['id'];
      $_SESSION['keylogin']=$_POST['salt'];
      $_SESSION['iduser']=$this->iduser;
      $_SESSION['time']=$this->timecurrent;
      $_SESSION['keysession']=hash_hmac('sha256',$_SERVER["REMOTE_ADDR"].$_POST['username'].$this->timecurrent,SESSIONKEY);
      $_SESSION['remote_addr']=$_SERVER["REMOTE_ADDR"];
      $_SESSION['browser']=$_SERVER["HTTP_USER_AGENT"];
      //echo LOGINOKURL; exit;
      header("Location: ".LOGINOKURL.""); /* Redirect browser */
      }else{
      header("Location: ".LOGINURL.""); /* Redirect browser */
      }
    }
  }
}
# Verifica se a sessão contem dados válidos do cliente
public function checksession()
{

 if(($_SESSION['remote_addr']===$_SERVER["REMOTE_ADDR"]) AND ($_SESSION['browser']===$_SERVER["HTTP_USER_AGENT"]))
 {

   if(time()>($_SESSION['time']+(TIMESESSION*60)+1))
   {
   header("Location: ".LOGINURL.""); /* Redirect browser */
   }

     if(time()>$_SESSION['time']+TIMEKEY)
     {
       if($this->keepalive()==false)
       {
       header("Location: ".LOGINURL.""); /* Redirect browser */
       }
     
     }else{
   //echo "dentro do tempo de validade da sessão<br/>";
     return true;
     }
    
 }else{
 header("Location: ".KICKURL.""); /* Redirect browser */
 }

}

# Verifica se o keepalive é valido e o atualiza
private function keepalive()
{
$keepalive=false;
//$timecurrent=time();
$SQL_PASS="SELECT login FROM login WHERE id_login='".$_SESSION['iduser']."'";
  if($this->setsql($SQL_PASS)==1)
  {
  $login=$this->getrow();
  //extract($this->getrow(),EXTR_OVERWRITE);
    if($_SESSION['keysession']===hash_hmac('sha256',$_SERVER["REMOTE_ADDR"].base64_encode($login).$_SESSION['time'],SESSIONKEY))
     {
     if($this->checkserverkey($_SESSION['idkey'],$_SESSION['keylogin'])==true)
        {
          if($this->validkey($_SESSION['idkey'])==true)
            {
            $_SESSION['time']=$this->timecurrent;
            $_SESSION['keysession']=hash_hmac('sha256',$_SERVER["REMOTE_ADDR"].base64_encode($login).$this->timecurrent,SESSIONKEY);
            $keepalive=true;
            }
        }
     }
  }
return $keepalive;

}
# fim da classe
}
?>