<?php
	require('feedback_mail.php');
	$page_title = 'pro-zurituutsch.ch - danke!';
	require_once("header.php");
?>
<table>
	<tr><td>&nbsp;</td></tr>
	<tr><td>&nbsp;</td></tr>
	<tr><h3>Vielen Dank f&uuml;r Ihre Bestellung<br>Eine Best&auml;tigung Ihrer Bestellung wurde an folgende Email-Adresse gesandt:<br><?php echo $input['email'];?></h3></tr>
</table>
<?php require_once("footer.php"); ?>
