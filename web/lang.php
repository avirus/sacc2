<?php
// $Author: slavik $ $Rev: 35 $
// $Id: lang.php 35 2008-12-23 05:24:14Z slavik $
setcookie('lang',$_GET['lang']);
Header("Location: ".base64_decode($_GET['query']),time()+60*60*24*60);
setcookie('query',"");
?>
