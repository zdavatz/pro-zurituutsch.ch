<?php if (!defined('PmWiki')) exit();

$FmtPV['$ZAPmailversion'] = "'Jan 14, 2007'";

## DESCRIPTION:  ZAPmail allows you to send email messages to one or more address, or to lists of member names. For more info, visit the ZAP demo site at WWW.ZAPSITE.ORG.  Author: Dan Vis aka Caveman <editor àt fast döt st>, Copyright 2006.  

SDV($ZAPmodule['mail'], " emailto emaillist ");

function ZAPmail($field, $value) {
	global $pagename, $ZAParray, $ZAPlogin, $ZAPconfig, $m;

		if (($field == "emailto") || ($field == "emaillist")) {
			if ((!isset($ZAParray[emailfrom])) || ($ZAParray[emailfrom] == "")) ZAPwarning("No return address entered.  Email not sent. ");
			if ((!isset($ZAParray[emailsubject])) || ($ZAParray[emailsubject] == "")) ZAPwarning("No email subject entered.  Email not sent. ");
			$ZAParray['email'] = "{email}";
			$emailbody = ZAPtemplate("email");
			if ($emailbody == false) $emailbody = $ZAParray['emailbody'];
			if ($emailbody == "") ZAPwarning("No message found.  Email not sent. ");
			if ($field == "emailto") $value = str_replace("self", $ZAParray['emailfrom'], $value);
			$e = explode(",", $value);
			if ($ZAPconfig['mail'] != true) $m .= "ZAPmail is currently in test mode. ";
			foreach($e as $to) {
				if ($field == "emaillist") $to = ZAPgetdata("Email","$ZAPlogin.$to");			
				if (! ereg("^.+@.+\..+$", $to)) $m .= "Invalid email address: $to. ";
				else {
					$mergedbody = str_replace("{email}", $to, $emailbody);
					if ($ZAPconfig['mail'] != true) $m .= "<>Mail sent to $to <> $mergedbody ";
					else {
						mail($to, $ZAParray[emailsubject], $mergedbody, "From: $ZAParray[emailfrom]");
						$m .= "Mail successfully sent to $to. ";
						}
					}
				}
			}

	return;
	}
