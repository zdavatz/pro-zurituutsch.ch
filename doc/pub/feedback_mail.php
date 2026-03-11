<?php
session_start();
session_register('user_input');
session_register('missing_email');
$user_input = array(); 
if(empty($_POST["email"]))
{
	$user_input = $_POST;
	$missing_email = true;
	header("Location: pschtellform.php");
	exit;
}
else
{
	$missing_email = false;
}
include('Mail.php');

$input['email']=$_POST["email"];

$recipients ='fwyss@pro-zurituutsch.ch';
#$recipients ='hannes.wyss@gmail.com';
$conf_recipients = $input['email'];

$headers['MIME-Version'] = '1.0';
$conf_headers['MIME-Version'] = '1.0';
$headers['Content-Type'] = 'text/plain; charset=ISO-8859-1';
$conf_headers['Content-Type'] = 'text/plain; charset=ISO-8859-1';
$headers['From'] = $input['email'];
$conf_headers['From'] = 'fwyss@pro-zurituutsch.ch';
$headers['To'] = $recipients;
$conf_headers['To'] = $input['email'];


$headers['Subject'] = 'pro-zurituutsch.ch - Bestellung';
$conf_headers['Subject'] = 'Bestätigung Ihrer Bestellung bei "pro-zurituutsch.ch"';

$body='';
$conf_body='';

//Confirm Text Begruessung
$conf_body.='Ihre Bestellung bei "pro-zurituutsch.ch:'."\n";
$conf_body.='----------------------------------------'."\n";
//
$lieb =$_POST["lieb"];
if(!empty($lieb))
{
	$body.='Bestellung: Em Liebhaber sys Fach  Anzahl: '.$lieb."\n";
	$conf_body.='Em Liebhaber sys Fach à Fr. 45.-  Anzahl: '.$lieb."\n";
}
$styl =$_POST["styl"];
if(!empty($styl))
{
	$body.='Bestellung: Styluëbige  Anzahl: '.$styl."\n";
	$conf_body.='Styluëbige à Fr. 7.50  Anzahl: '.$styl."\n";
}
$bitter =$_POST["bitter"];
if(!empty($bitter))
{
	$body.='Bestellung: Viër bitterbeuhsi Légànde à Fr. 20.-  Anzahl: '.$bitter."\n";
	$conf_body.='Viër bitterbeuhsi Légànde à Fr. 20.-  Anzahl: '.$bitter."\n";
}
// Confirm Mail Text Lieferung
$conf_body.='------------------------------------------------------------------------------------------------'."\n";
$conf_body.='Die Lieferung erhalten Sie in den nächsten Tagen per Post. Ein Einzahlungsschein wird beigelegt.'."\n";
$conf_body.='Vielen Dank für ihre Bestellung.'."\n";
$conf_body.='mit freundlichen Grüssen.'."\n".'F. E. Wyss';
//
$textfield=$_POST["textfield"];
if(!empty($textfield))
{
	$body.='------------------------------------------------------------------------------------------------'."\n";
	$body.= 'Mitteilung:'."\n";
	$body.= $textfield."\n";
	$body.='------------------------------------------------------------------------------------------------'."\n";
}

$input['email']=$_POST["email"];
if(!empty($input['email']))
{
	$body.="\n";
	$body.='email: '.$input['email']."\n";
}
$name=$_POST["title"];
if(!empty($name))
{
	$body.='title: '.$name."\n";
}

$name=$_POST["name"];
if(!empty($name))
{
	$body.='Name: '.$name."\n";
}
$firstname=$_POST["firstname"];
if(!empty($firstname))
{
	$body.='Vorname: '.$firstname."\n";
}

$company=$_POST["company"];
if(!empty($firstname))
{
	$body.='Firma: '.$company."\n";
}

$address=$_POST["address"];
if(!empty($address))
{
	$body.='Adresse: '.$address."\n";
}

$plz_location=$_POST["plz_location"];
if(!empty($plz_location))
{
	$body.='PLZ / Ort: '.$plz_location."\n";
}

$phone=$_POST["phone"];
if(!empty($phone))
{
	$body.='Telefon: '.$phone."\n";
}
if(!empty($body))
{
	$mail_object =& Mail::factory('sendmail');
	$mail_object->send($recipients,$headers,$body);
	$mail_object->send($conf_recipients,$conf_headers,$conf_body);
}
?>
