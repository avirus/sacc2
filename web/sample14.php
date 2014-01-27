<?php
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>phpMyDatagrid - Sample file</title>

<?php
	include ("phpmydatagrid.class.php");
	$objGrid = new datagrid;
	$objGrid -> friendlyHTML();
	$objGrid -> pathtoimages("./images/");
	$objGrid -> closeTags(true);  
	$objGrid -> form('employee', true);
	$objGrid -> methodForm("post"); 
	$objGrid -> total("balans,overdraft,bill");
	$objGrid -> searchby("id,vname,email,perm");
   $objGrid -> poweredby = false;
$objGrid -> paginationmode('links');
	$objGrid -> linkparam("sess=".$_REQUEST["sess"]."&username=".$_REQUEST["username"]);	 
	$objGrid -> decimalDigits(2);
	$objGrid -> decimalPoint(",");
	/* ADOdb library must be included */
//	include_once('adodb/adodb.inc.php');
	/* to use ADOdb is so simple as say true */
//	$objGrid -> conectadb("127.0.0.1", "root", "", "guru", true, "mysql");
$objGrid -> conectadb("127.0.0.1", "root", "", "sacc2");
	$objGrid -> tabla ("users");
	$objGrid -> buttons(true,true, true,false,true);
	$objGrid -> keyfield("id");
	$objGrid -> salt("Some Code4Stronger(Protection)");
	$objGrid -> TituloGrid("sacc2 Sample page");
	$objGrid -> FooterGrid("<div style='float:left'>&copy; 2007 sacc2</div>");
	$objGrid -> datarows(100);
	$objGrid -> keyfield("id");
$objGrid -> linkparam("sess=".$_REQUEST["sess"]."&username=".$_REQUEST["username"]); 
	$objGrid -> paginationmode('links');
	$objGrid -> orderby("login", "DESC");
//    $objGrid -> noorderarrows();
//	$objGrid -> FormatColumn("id", "user ID", 5, 5, 1, "10", "center", "integer");
	$objGrid -> FormatColumn("vname", "name", 30, 30, 0, "100", "left");
	$objGrid -> FormatColumn("login","login", "30", "30","4","100","left","link:msg=window.open(%s%s),id,vname");
//	$objGrid -> FormatColumn("login", "system name", 30, 30, 0, "150", "left");
	$objGrid -> FormatColumn("email", "email", 30, 30, 0, "150", "left");
	$objGrid -> FormatColumn("a_id", "auth", 0, 0, 0, "50", "center", "select:1_mysql:2_windows:3_radius");
	$objGrid -> FormatColumn("perm", "perm", 10, 10, 0, "100", "center");
	$objGrid -> FormatColumn("balans", "balans", 5, 5, 0, "100", "right");
	$objGrid -> FormatColumn("bill", "locked", 5, 5, 0, "100", "right");
	$objGrid -> FormatColumn("active", "Active", 2, 2, 0,"80", "center", "check:No:Yes");
	$objGrid -> FormatColumn("overdraft", "Overdraft", 5, 5, 0, "100", "right");  
//	$objGrid -> FormatColumn("workeddays","Work days", "10", "10", "5","100","left","chart:percent:val:31");		
//	$objGrid -> where ("active = '1'");
	$objGrid -> setHeader();
?>
</head>

<body>
<?php 
	$objGrid -> ajax("silent");
	$objGrid -> grid();
	$objGrid -> desconectar();
?>
</body>
</html>
