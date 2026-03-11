<?php if (!defined('PmWiki')) exit();

$FmtPV['$ORDERmailversion'] = "'Mar 12, 2007'";

$HandleActions['order'] = 'Order';

function Order($unknown_argument) {
	$input = Carry($_POST);

	if(empty($input["email"]))
	{
		OrderDialog("Bitte geben Sie eine g&uuml;ltige Email-Adresse an.");
	}

	$book_keys = array();
	$book_order = 0;
	foreach($input as $key => $value) {
		if(strncmp($key, 'buch_', 5)==0) {
			$book_keys[] = substr($key, 5);	
			$book_order += $value;
		}
	}

	if($book_order == 0) {
		OrderDialog("Bitte bestellen Sie mindestens ein Buch.");
	}

	sort($book_keys);

	include('Mail.php');
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
	$conf_body='Guten Tag ';

	if($anrede = $input['anrede']) {
		$conf_body.= $anrede;
	} else {
		$conf_body.= $input['vorname'];
	}

	$conf_body.= " ".$input['name']."\n\n";

	//Confirm Text Begruessung
	$conf_body.= "Ihre Bestellung bei \"pro-zurituutsch.ch\":\n\n";
	//$conf_body.='----------------------------------------'."\n";

	global $pagename;
	foreach($book_keys as $key) {
		$count = (int)$input["buch_".$key];
		if($count > 0) {
			$title = PageTextVar($pagename, $key);
			if($idx = strpos($title, '\\')) $title = substr($title, 0, $idx);
			$title = str_pad($title, 50);
			$line = $title." Anzahl: ".$count."\n";
			$body.= "Bestellung: ".$line;
			$conf_body.= $line;
		}
	}
	
	// Confirm Mail Text Lieferung
	//$conf_body.='------------------------------------------------------------------------------------------------'."\n";
	$conf_body.= "\nDie Lieferung erhalten Sie in den nächsten Tagen per Post. Ein Einzahlungsschein wird beigelegt.\n";
	$conf_body.= "Vielen Dank für ihre Bestellung.\n\n";
	$conf_body.= "mit freundlichen Grüssen.\nF. E. Wyss";
	//
	$textfield=$input["mitteilungen"];
	if(!empty($textfield))
	{
		$body.= "\n------------------------------------------------------------------------------------------------\n";
		$body.= "Mitteilung:\n";
		$body.= preg_replace("/\n\n+/", "\n", $textfield)."\n";
		$body.= "------------------------------------------------------------------------------------------------\n\n";
	}

	$label_size = 16;
	$data = array(
		"email"   => "Email",
		"anrede"  => "Anrede",
		"name"    => "Name",
		"vorname" => "Vorname",
		"firma"   => "Firma",
		"adresse" => "Adresse",
		"ort"		  => "PLZ / Ort",
		"telefon" => "Telefon",
	);

	foreach($data as $key => $label) {
		$value = $input[$key];
		if(!empty($value)) {
			$body.= str_pad($label.':', $label_size).$value."\n";
		}
	}

	if(!empty($body))
	{
		$mail_object =& Mail::factory('sendmail');
		$mail_object->send($recipients,$headers,$body);
		$mail_object->send($conf_recipients,$conf_headers,$conf_body);
	}

	OrderDialog("Vielen Dank. Ihre Bestellung wurde Entgegengenommen.");
}

function Carry($values) {
	global $FmtPV, $_POST;
	$valid_keys = array('email', 'anrede', 'name', 'vorname', 'firma',
											'adresse', 'ort', 'telefon', 'mitteilungen');

	$input = array();
	foreach($_POST as $key => $value) {
		if(in_array($key, $valid_keys) || strncmp($key, 'buch_', 5)==0) {
			$FmtPV['$'.$key] = "'".$value."'";
			$input[$key] = $value;
		}
	}
	return $input;
}

function OrderDialog($m) {
	global $pagename, $MessagesFmt;
	$MessagesFmt[] = "<h5 class='wikimessage'>$[$m]</h5>";
	HandleBrowse($pagename);
	die();
}
