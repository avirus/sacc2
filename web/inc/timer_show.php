<!-- let's show timer -->
<?php
global $tstart,$language,$lang;
//������ ��� �� �� �����, ����� �������� ������� ����� 
    $mtime = microtime(); 
    $mtime = explode(" ",$mtime); 
    $mtime = $mtime[1] + $mtime[0]; 
//���������� ����� ��������� � ������ ���������� 
    $tend = $mtime; 
//��������� ������� 
    $totaltime = ($tend - $tstart); 
//������� �� ����� 
echo "<!--�����, ����� ���������: � $tstart �� $tend = ".($totaltime*1000)." msec -->";
echo "	  <tr>";
echo "	    <td valign=\"top\" height=\"2\" bgcolor=\"#0\"></td>";
echo "		<td bgcolor=\"#bebfbf\"></td>";
echo "		<td bgcolor=\"#bebfbf\"></td>";
echo "		<td bgcolor=\"#bebfbf\"></td>";
echo "		<td bgcolor=\"#bebfbf\"></td>";
echo "	  </tr>
	  <tr>";
echo "	    <td height=\"45\"></td>";
echo "		<td valign=\"middle\" align=\"left\" style=\"padding-left:6px\" class=\"footer\">&copy; Cybersec ltd 2007</td>";
echo "		<td valign=\"middle\" align=\"center\"></td>";
echo "		<td valign=\"middle\" align=\"center\" class=\"footer\"><a href=\"http://sacc.cybersec.ru\" target=\"_blank\">sacc.cybersec.ru</a>&nbsp";

/*
if ($lang==1) {echo "<a href=../lang.php?lang=0&query=".base64_encode($_SERVER['REQUEST_URI']).">�������</a>";};
if ($lang==0) {echo "<a href=../lang.php?lang=1&query=".base64_encode($_SERVER['REQUEST_URI']).">English</a>";};
*/
echo "</td>
		<td></td>
	  </tr>
	<!-- //����� ".date( "Y-M-d --- G:i:s T" )." -->  
	
  </table>
</body>
</html>";
ob_end_flush();
@mysql_close($link);
?>
