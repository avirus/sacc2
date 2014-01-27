<?php
/**
* Classe que conecta a um 
* banco de dados relacional
* @author Marcelo Soares da Costa
* @email phpmafia at yahoo dot com dot br
* @copyright Marcelo Soares da Costa © 2006. 
* @license FreeBSD http://www.freebsd.org/copyright/freebsd-license.html
* @version 1,1
* @access private
* @package phpmafiasql
* @subpackage phpmafiareldb
* @data 2006-10-18
* @update 2007-02-01
* @ changelog corrigido método getlastid() para postgres
*/ 
class MafiaRelDB 
{ 
function __construct($reldb = null) 
{ 
	$this->__sethost(HOST);
	$this->__setdb(DATABASE);
	$this->__setuser(USER);
	$this->__setpass(PASSWD);
	$this->__setreldb(RELDB);
	
	  if(is_array($reldb))
	  {
	    extract($reldb, EXTR_OVERWRITE);
	    if (!$host)
		 { 
	    $this->__sethost($host);
	    }
	    if (!$database)
	    {
	    $this->__setdb=($database);
	    }
	    if (!$user)
	    {
	    $this->__setuser=($user);
	    }
	    if (!$passwd)
	    {
	    $this->__setpass=($passwd);
	    }
	    if (!$datatype)
	    {
	    $this->__setreldb=($datatype);
	    }
	  }
	 
	 $this->dbconnection = false;
	 $this->connect = false; 
	 $this->query = false;
	 $this->sql = false;
	 $this->row = false; 
} 

# SETA O HOSTNAME
private function __sethost($reqhost) 
{ 
	unset($this->dbhost);
	$this->dbhost = $reqhost; 
} 
# SETA O BANCO DE DADOS DA CONEXAO
private function __setdb($reqdb) 
{ 
	unset($this->database);
	$this->database = $reqdb; 
} 
# SETA O USUARIO DO BANCO DE DADOS
private function __setuser($requser) 
{ 
	unset($this->dbuser);
	$this->dbuser = $requser; 
} 
# FUNCAO QUE SETA A SENHA DE CONEXAO DO BANCO
private function __setpass($reqpassword) 
{ 
   unset($this->dbpassword);
   $this->dbpassword = $reqpassword; 
} 
#
private function __setdbconnection($connection) 
{ 
	unset($this->connect);
	$this->connect = $connection; 
} 
############################################################
# Define o tipo de banco de dados
private function __setreldb($reldb)
{
  unset($this->reldatabase);
  unset($this->datatype);
  unset($this->datatype);
  
  $this->reldatabase=strtolower($reldb);

  switch($this->reldatabase)
  {
  case "mysql": 
    return $this->datatype = "MYSQL";
      break; 
  case "pgsql": 
    return $this->datatype = "PGSQL";
      break;
  default:
    die("Banco de dados => ".$this->reldata." não é suportado");
      break;
  }
}
# Abre uma conexão de acordo com o banco de dados
private function openconn() 
{ 
if($this->dbconnection==false)
 {
   switch($this->datatype)
  {
  case "MYSQL";
   $this->connect = mysql_connect($this->dbhost, $this->dbuser,$this->dbpassword) or die("MYSQL=>Conexao negada");
	 $this->dbconnection = mysql_select_db($this->database) or die("MYSQL=>Não existe o banco ".$this->database);
	  if ($this->dbconnection == true) 
	  { 
		return $this->dbconnection;
	  }else{ 
		return $this->dbconnection=false; 
	  } 
    break;
  case "PGSQL"; 
    $this->connect = "host=".$this->dbhost." dbname=".$this->database." user=".$this->dbuser." password=".$this->dbpassword."";
    $this->dbconnection = pg_connect($this->connect) or die("PGSQL=>Conexao negada"); 
     if ($this->dbconnection == true) 
	  { 
		return $this->dbconnection;
	  }else{ 
		return $this->dbconnection=false; 
	  } 
    break;
  }
 }                
} 
######################################################################
protected function __setquery($querystring) 
{ 
  if ($this->dbconnection == false) 
  { 
   $this->openconn(); 
  } 
	unset($this->query); 
	switch($this->datatype)
  {
   case "MYSQL";
     $this->query = mysql_query($querystring) or die ("MYSQL ERRO NA QUERY =>".$querystring);
    break;
   case "PGSQL"; 
	   $this->query=pg_query($this->dbconnection, $querystring) or die ("PGSQL ERRO NA QUERY=>".$querystring);
    break;
  }
  return $this->query;
} 
#################################
protected function getnumberrows() 
{      
 switch($this->datatype)
   {
    case "MYSQL";
	 $this->numberrows=mysql_num_rows($this->query);
	 break;
	 case "PGSQL";
	 $this->numberrows=pg_num_rows($this->query);
	 break;
   }
  return $this->numberrows; 
} 
################################
protected function getrecord($record)
{  
 switch($this->datatype)
   {
    case "MYSQL";
	 $this->record=mysql_result($this->query,$record);
	 break;
	 case "PGSQL";
	 $this->record=pg_fetch_row($this->query,$record);
	 break;
   }
  return $this->record;  
}
################################
protected function affected() 
{ 
	unset($this->affected);
	switch($this->datatype)
  {
   case "MYSQL";
	$this->affected=mysql_affected_rows();
	break;
	case "PGSQL";
	$this->affected=pg_affected_rows($this->query);
	break;
  }
	return $this->affected;
}

protected function __fetchassoc()
{
	unset($this->fetchassoc);
	switch($this->datatype)
  {
   case "MYSQL";
	$this->fetchassoc=mysql_fetch_assoc($this->query);
	break;
	case "PGSQL";
	$this->fetchassoc=pg_fetch_assoc($this->query);
	break;
  }
	return $this->fetchassoc;
}

protected function __fetchrow($querystring)
{
	unset($this->fetchrow);
	switch($this->datatype)
  {
   case "MYSQL";
	$this->fetchrow=mysql_fetch_row($querystring);
	break;
	case "PGSQL";
	$this->fetchrow=pg_fetch_row($querystring);
	break;
  }
	return $this->fetchrow;
}
############################################################
protected function getlastid() 
{ 
	unset($this->lastid);
	if ($this->affected()>0)
	{
	 switch($this->datatype)
    {
     case "MYSQL";
		$this->lastid=mysql_insert_id();
		break;
	  case "PGSQL";
	  	$this->lastid=pg_fetch_result(pg_query("SELECT lastval()"),0,0);
	  	////list($this->lastid)=pg_fetch_row(pg_query("SELECT lastval()"));
		break;
	 }
	 return $this->lastid; 
	}else{
	 return false;
	}
} 
#
protected function begin() 
{ 
	unset($this->query);
	switch($this->datatype)
    {
     case "MYSQL";
		$this->query=mysql_query("SET AUTOCOMMIT=0");
   	$this->query=mysql_query("BEGIN");
		break;
	  case "PGSQL";
   	$this->query=pg_query($this->dbconnection, "BEGIN");
		break;
	}
	return true;
}
#
protected function commit() 
{ 
	unset($this->query);
	switch($this->datatype)
    {
     case "MYSQL";
		$this->query=mysql_query("COMMIT");
		break;
	 case "PGSQL";
		$this->query=pg_query("COMMIT");
		break;
	 }
	return true;
}
#
protected function closeconn() 
{ 
	if ($this->dbconnection = true) 
	{
	switch($this->datatype)
    {
     case "MYSQL";
		mysql_close($this->dbconnection); 
		break;
	 case "PGSQL";
		pg_close($this->dbconnection);
		break;
	 } 
	 return true;
	} 
	return false;
}
#
protected function freeresult()
{
	switch($this->datatype)
    {
     case "MYSQL";
		mysql_free_result($this->query); 
		break;
	 case "PGSQL";
		pg_free_result($this->query);
		break;
	 } 
	unset($this->query);
	return true;
}
# Fim da Classe
}
?>