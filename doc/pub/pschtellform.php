<?php	
header('Location: /Bestellung/Pschtellformoular', 301);
exit;

//old code
session_start();
session_register('user_input');
session_register('missing_email');
$subject = "Bestellung";
$page_title = "Pschtellformular";
require_once("header.php"); 
?>
<form METHOD="POST" ACTION="mail_gesendet.php">
<table>
<tr>
<td rowspan="2">
<table>	
<tr>
<td class="missing-email">
	<?php				
if($missing_email)
{
	echo "Ihre Email-Adresse wird ben&ouml;tigt!";
}
else
{
	echo "&nbsp;";
}
?>
</td>
</tr>

<td class="TDbold">B&uuml;cher bestellen</td>
</tr>
<tr>
<td>Em Liebhaber sys Fach &agrave; Fr. 45.-<br>Ars Amatoria von Ovid auf Z&uuml;richdeutsch</td>
<td><input class="small-textinput" TYPE="text" Name="lieb"	value="<?php echo $user_input["lieb"];?>">&nbsp;Anzahl</td>
</tr>
<tr>
<td>Stylu&euml;bige &agrave; Fr. 7.50.-<br>&Eacute;xercices des Style von R. Queneau<br>auf Z&uuml;richdeutsch</td>
<td><input class="small-textinput" TYPE="text" Name="styl"	value="<?php echo $user_input["styl"];?>">&nbsp;Anzahl</td>
</tr>
<tr>
<td>Vi&euml;r bitterbeuhsi L&eacute;g&agrave;nde &agrave; Fr. 20.-<br>von F. E. Wyss</td>
<td><input class="small-textinput" TYPE="text" Name="bitter"	value="<?php echo $user_input["bitter"];?>">&nbsp;Anzahl</td>
</tr>
<tr>
	<?php
if($missing_email)
{
	echo '<td class="missing-email">Email Adresse</td>';
	echo '<td><input class="med-textinput" TYPE="text" Name="email"></td>';
}
else
{
	echo '<td>Email Adresse</td>';
	echo '<td><input class="med-textinput" TYPE="text" Name="email"></td>';
}
?>
</tr>
<tr>
<td>Anrede</td>
<td><input TYPE="text" Name="title" value="<?php echo $user_input["title"];?>"></td>
</tr>
<tr>
<td>Name</td>
<td><input TYPE="text" Name="name"value="<?php echo $user_input["name"];?>"></td>
</tr>	
<tr>
<td>Vorname</td>
<td><input TYPE="text" Name="firstname"value="<?php echo $user_input["firstname"];?>"></td>
</tr>
<tr>
<td>Firma</td>
<td><input TYPE="text" Name="company"value="<?php echo $user_input["company"];?>"></td>
</tr>
<tr>
<td>Adresse</td>
<td><input TYPE="text" Name="address"value="<?php echo $user_input["address"];?>"></td>
</tr>
<tr>
<td>PLZ / Ort</td>
<td><input TYPE="text" Name="plz_location"value="<?php echo $user_input["plz_location"];?>"></td>
</tr>
<tr>
<td>Telefon</td>
<td><input TYPE="text" Name="phone"value="<?php echo $user_input["phone"];?>"></td>
</tr>
<tr>
<td colspan="2">
	Ich w&uuml;nsche eine Zustellung mit Einzahlungsschein an obige Adresse.<br>
	Die Preise verstehen sich ohne Versandspesen.<br>
	</td>
</tr>
<tr>
<td>&nbsp;</td>
<td colspan="2">
<input type="submit" value="Email senden">
<input type="reset" value="Zur&uuml;cksetzen">
</td>
</tr>
</table>
<td>
<table>
<tr>
<td>
Mitteilungen
</td>
</tr>
<tr>
<td colspan="3"><textarea wrap="hard" rows="20" cols="72" class="TEXTAREA" Name="textfield"></textarea></td>
</tr>
</table>
</td>
</tr>
</table>
</form>
<?php require_once("footer.php");
?>
