<?php if (!defined('PmWiki')) exit();

$FmtPV['$ZAPversion'] = "'Jan 31, 2007'";

## DESCRIPTION:  The ZAPdata recipe extends forms capability on PmWiki, by adding a flexible forms processing engine, extensible with modules. For info, see docs at http://www.pmwiki.org/wiki/Cookbook/ZAPdata, or visit the ZAP demo site at WWW.ZAPSITE.ORG.  Author: Dan Vis  <editor àt fast döt st>, Copyright 2006.  

//REQUIRED PMWIKI PARAMETERS
$HandleActions['zap'] = 'ZAPengine';
$PageAttributes['passwdzap'] = 'Set ZAP forms password: ';
SDV($DefaultPasswords['zap'],'');
SDV($ZAPthreadstart,'1000');
SDV($ZAPmodule['zap'], '');


#######################################
###         Main ZAP ENGINE         ###
#######################################

function ZAPengine($ZAPflag = 0) {

//ZAP INITIALIZATION
	global $pagename, $ScriptUrl, $MessagesFmt, $m, $ZAParray, $ZAPmodule, $ZAPconfig;
	$ZAParray = ZAPsecure();
	$m = "Form submitted. ";
//print_r($ZAParray); // debug line
//die();
	if (!isset($ZAParray['nextpage'])) $ZAParray['nextpage'] = $pagename;
	if (!isset($ZAParray['datapage'])) $ZAParray['datapage'] = $pagename;

	foreach ($ZAParray as $field => $value) {
		$value = $ZAParray[$field];  


//ZAP FORMS PROCESSING COMMANDS

		if (substr($field, 0, 3) == "msg") {
			if (substr($value, 0, 1) == "+") $m .= substr($value, 1) . " ";
			else $m = $value . " ";
			continue;
			}

		if (($field == "warn") && ($value != "")) ZAPwarning($value);

		if ($field == "passdata") {
			$d = explode(",", $ZAParray['passdata']);
			foreach ($d as $v) $passdata .= "?$v=$ZAParray[$v]";
			if ($passdata == "?=") $passdata = "";
			continue;
			}

		if (substr($field, 0, 8) == "savedata") {
			if ($value == "") continue;
			$text = "";
			$data = ZAPsetdata($value);
			ZAPsavepage($ZAParray['datapage'],$text,$data);
			continue;
			}
			
		if (substr($field, -4, 4) == "list") {
			ZAPlist($field, $value);
			}
		
		if (substr($field, 0, 4) == "link") {
			$ZAParray[$field] = "$ScriptUrl?n=$value";
			}

		if (substr($field, -4, 4) == "page") $ZAParray[$field] = ZAPpageshortcuts($value);


//ZAP COMMANDS YOU CANNOT POST

		if (substr($field, 0, 8) == "datapage") {
			if (isset($_POST[$field])) ZAPwarning("The \"$field\" field cannot be processed this way. ");
			$ZAParray['datapage'] = ZAPpageshortcuts($value);
			}

		if (substr($field, 0, 7) == "getdata") {
			if (isset($_POST[$field])) ZAPwarning("The \"$field\" field cannot be processed this way. ");
			$g = explode(",", $value);
			foreach ($g as $f) {
				if (strpos ($f, "|")) $ZAParray[substr($f, strpos($f, "|") + 1)] = ZAPgetdata(substr($f, strpos($f, "|") + 1), substr($f, 0, strpos($f, "|")));
				else $ZAParray[$f] = ZAPgetdata($f, $ZAParray['datapage']);
				}
			continue;
			}

		if ((substr($field, 0, 2) == "if") || (substr($field, 0, 8) == "validate")) {
			if (isset($_POST[$field])) ZAPwarning("The \"$field\" field cannot be processed this way. ");
			$zapcond = substr($value, 0, strpos($value, "||"));
			$zapact = explode("|", substr($value, strpos($value, "||") + 2));
			if (substr($field, 0, 2) == "if") {
				if (CondText($pagename, "! $zapcond", true)) $ZAParray[$zapact[0]] = $zapact[1];
				}
			else {
				$valfield = substr($field, 8);
				if ($ZAParray[$valfield] == "") ZAPwarning("Field \"$valfield\" required. ");
				if (($valfield != "") && (!ereg($zapcond, $ZAParray[$valfield]))) $ZAParray[$zapact[0]] = $zapact[1];
				}
			continue;
			}


// ZAP PLUGIN MODULES
		foreach ($ZAPmodule as $mod => $modfields) {
			if (strpos($modfields, $field)) eval("ZAP$mod('" . $field . "', '" . $value . "');");
			}


// ZAP CLOSING STEPS		
		}
	if ($ZAPflag != 0) return;
	if (($ZAParray['nextpage'] == $pagename) && (! isset($passdata))) {
		$MessagesFmt[] = "<h5 class='wikimessage'>$[$m]</h5>";
		HandleBrowse($pagename);
		}
	else Redirect(FmtPageName($ZAParray['nextpage'] . $passdata, $pagename));
	}


#######################################
###      ZAP UTILITY FUNCTIONS      ###
#######################################

function ZAPwarning($m) {
	global $pagename, $MessagesFmt;
	$MessagesFmt[] = "<h5 class='wikimessage'>$[$m]</h5>";
	HandleBrowse($pagename);
	die();
	}

function ZAPsavepage($mypage, $mytext, $mydata) {
	global $WorkDir, $m;
	$oldpage = ReadPage($mypage);
	if ($mytext == "") {
		$oldtext = $oldpage['text'];
		if (strpos($oldtext, '(:comment data:)')) $mytext = substr($oldtext, 0, strpos($oldtext, '(:comment data:)'));
		else $mytext = $oldtext . " \n\n";
		}
	$newpage = $oldpage;
	if ($mydata == '') $newpage['text'] = $mytext;
	else $newpage['text'] = $mytext . "(:comment data:)\n\n" . $mydata;
	UpdatePage($mypage, $oldpage, $newpage);
	if ($mydata != "") $m .= "Data has been successfully saved. ";
	return;
	}

function ZAPsetdata($value) {
	if ($value == "") return;
	global $m, $ZAParray;
	$pagedata = "";
	$hidedata = "";
	$hide = false;
	$urlin = array('%0D%0A', '%5C%5C');
	$urlout = array('%5B%5B%3C%3C%5D%5D%0A', '%5c%5c%5c');
	$htmlin = array("'", '"', '   ', ":)");
	$htmlout = array('&#39;', '&quot;', '&nbsp;&nbsp;&nbsp;', '&#x3a;)');
	$d = explode(",", $value);
	foreach ($d as $f) {
		if (substr($f, 0, 4) == "hide") $v = urldecode(str_replace($urlin, $urlout, urlencode($ZAParray[substr($f, 4)])));
		else $v = urldecode(str_replace($urlin, $urlout, urlencode($ZAParray[$f])));
		$v = str_replace($htmlin, $htmlout, $v);
		if ((isset($ZAParray['unprotect'])) && (!isset($_POST[$field]))) {
			$u = explode(",", $ZAParray['unprotect']);
			foreach ($u as $uu) if ($uu == $f) $v = str_replace("&#x3a;)", ":)", $v);
			}
		if (substr($f, 0, 4) == hide) {
			$hidedata .= substr($f, 4) . '="' . $v . '"' . "\n\n";
			$hide = true;
			}
		else $pagedata .= "(:$f: $v:)\n\n"; 
		}
	if ($hide == true) $pagedata = $pagedata . "(:if false:)\n\n$hidedata(:if:)";
	return $pagedata;
	}

function ZAPlist($field, $value) {
	global $ZAParray;
	if ((substr($value, 0, 1) == "+") || (substr($value, 0, 1) == "-")) {
		$list = ZAPgetdata($field, $ZAParray['datapage']);
		$i = explode(",", $value);
		foreach ($i as $ii) {
			$plusminus = substr($ii, 0, 1);
			$item = substr($ii, 1);
			switch($plusminus) {
				case "-" :
					$list = str_replace($item, '', $list);
					break;
				case "+" :
					if (!strpos(" $list", $item)) $list = $list . "," . $item;
					break;
				}
			}
		if (strpos($list, ",,")) $list = str_replace(',,', ',', $list);
		if (substr($list, 0, 1) == ",") $list = substr($list, 1);
		if (substr($list, -1) == ",") $list = substr($list, 0, -1);
		$ZAParray[$field] = $list;
		}
	return;
	}

function ZAPgetdata($f,$p) {
	$l = ReadPage($p);
	$ll = explode("(:comment data:)", $l['text']);
	$field = explode("\n\n", $ll[1]);
	foreach ($field as $value) {
		if (substr($value, 0, 4) == "(:if") continue;
		if (substr($value, 0, 2) == "(:") {
			$value = substr($value, 2, -2);
			$a = ': ';
			}
		else $a = '="';
		$v[0] = substr($value, 0, strpos($value, $a));
		$v[1] = substr($value, strpos($value, $a) + 2);
		if ($a == '="') $v[1] = substr($v[1],0,-1);
		if ($v[0] == $f) return $v[1];
		}
	return;
	}

function ZAPtemplate($x) { 
	global $WorkDir, $pagename, $ZAParray;
	if (isset($ZAParray[$x . "template"])) $xx = ZAPpageshortcuts($ZAParray[$x . "template"]);
	else $xx = $pagename . "-template";
	if(PageExists($xx)) { 
		$page = ReadPage($xx);
		$r = $page['text'];
		}
	else return false;
	$r = str_replace('{*}', '~*~', $r);
	$r = preg_replace('/\\{(\\w+)\\}/e', "\$ZAParray['$1']", $r);
	$r = str_replace('~*~', '{email}', $r);
	return $r;
	}

function ZAPpageshortcuts($v) {
	global $pagename, $FmtPV;
	if (($v == "") || (! strpos($v, "."))) return $pagename;
	$vv[0] = substr($v, 0, strpos($v, "."));  
	$vv[1] = substr($v, strpos($v, ".") + 1);  
	if (strpos($vv[1], "?")) {
		$vv[2] = substr($vv[1], strpos($vv[1], "?"));
		$vv[1] = substr($vv[1], 0, strpos($vv[1], "?"));
		} 
	$v = preg_replace('/\\{(\\w+)\\}/e', "\$_GET[$1]", $v);
	$pn = explode(".", $pagename);
	$rr1 = array('*','^'); 
	$rr2 = array($pn[0],$pn[1]);
	$vv[0] = str_replace($rr1, $rr2, $vv[0]);
	$time = time();
	$order = substr($FmtPV['$orderpage'], 1, -1);
	$rr1 = array('*','^','@','$','+','~','!'); 
	$rr2 = array($pn[1],$pn[0],$GLOBALS[Author],$order,$time,"Profiles","Categories");
	$vv[0] = str_replace($rr1, $rr2, $vv[0]);
	$vv[1] = str_replace($rr1, $rr2, $vv[1]);
	if (strpos(" $vv[1]", "#")) $vv[1] = ZAPgroup("$vv[0]|thread");
	$v = $vv[0] . "." . $vv[1] . $vv[2];	
	return $v;
	}

function ZAPsecure() {
	global $pagename, $_POST, $m;
	if(!CondAuth($pagename, "zap")) ZAPwarning("You are not authorized to submit this form. ");
	foreach ($_POST as $field => $value) {
		if (is_array($value)) $value = implode(",", $value);
		$_POST[$field] = stripmagic($value);
		}
	$fn = $_POST['ZAPkey'];
	$formpage = str_replace(".", "", $pagename);
	session_start();
	if ($_SESSION['ZAP']["$formpage$fn"]['zaplock'] != "set") ZAPwarning("An error occurred. Form could not be processed.");
	unset($ZAParray);
	$ZAParray = $_SESSION['ZAP']["$formpage$fn"];
	foreach($_SESSION['ZAP'] as $fa => $fe) unset($_SESSION['ZAP'][$fa]);  
	foreach($_POST as $f => $v) {
		if (!isset($ZAParray[$f])) $ZAParray[$f] = $v;
		}
	foreach($ZAParray as $field => $value) {
		$ZAParray[$field] = preg_replace('/\\{(\\w+)\\}/e', 'ZAPfieldreplace("$1", $ZAParray)', $value);
		}
	return $ZAParray;
	}
	
function ZAPfieldreplace($x, $ZAParray) {
	if (isset($ZAParray[$x])) return ($ZAParray[$x]);
	return "\{$x}";
	}


#######################################
###           ZAP MARKUPS           ###
#######################################

Markup('zapform', '<input', '/\(:zapform(.*?):\)/ei', "ZAPform('$1')");
Markup('zapend', 'inline', '/\(:zapend:\\)/', '</form>');
function ZAPform($d) {
	global $pagename, $ScriptUrl;
	$arg = ParseArgs($d);
	if (isset ($arg['upload'])) $u = "enctype=multipart/form-data ";
	if (isset ($arg['action']))	$a = "action=$arg[action] method=post ";
	else $a = "action=$ScriptUrl?n=$pagename method=post ";
	if (isset ($arg['name']))	$fn = "$arg[name]";
	else $fn = '';
	ZAPlock('zaplock', 'set', $fn);
	return "(:input form name=$fn $u$a:)(:input hidden action zap:)(:input hidden ZAPkey \"$fn\":)";
	}

Markup('zaplock', 'inline', '/\(:zap (.*?)\=\"(.*?)\"([ \w]*):\)/ei', "ZAPlock('$1', '$2', '$3')");
function ZAPlock($f, $v, $fn='') {
	global $pagename;
	$fn = trim($fn);
	$formpage = str_replace(".", "", $pagename);
	@session_start();
	$_SESSION['ZAP']["$formpage$fn"][$f] = $v;
	session_write_close();
	return '';
	}

Markup('zapdata', '<{$var}', '/\(:zapdata(.*?):\)/ei', "ZAPdata('$1')");
function ZAPdata($p) {
	global $WorkDir, $FmtPV;
	$p = ZAPpageshortcuts(substr($p, 1));
	if (PageExists($p)) {
		$page = ReadPage($p);
		$contents = $page['text'];
		if (strpos($contents, "(:comment data:)")) {
			$d = substr($contents, strpos($contents, "(:comment data:)") + 16);
			$field = explode("\n\n", $d);
			foreach ($field as $value) {  
				if (substr($value, 0, 4) == "(:if") continue;
				if (substr($value, 0, 2) == "(:") {
					$value = substr($value, 2, -2);
					$a = ': ';
					}
				else $a = '="';
				$v[0] = substr($value, 0, strpos($value, $a));
				$v[1] = substr($value, strpos($value, $a) + 2);
				if ($a == '="') $v[1] = substr($v[1],0,-1);
				if ($v[0] != "") $FmtPV["$$v[0]"] = "'" . $v[1] . "'";
				}
			}
		return;
		}
	return;
	}

Markup('zapget', '<{$var}', '/\(:zapget:\)/e', 'ZAPget()');
function ZAPget() {
	global $FmtPV;
	foreach ($_GET as $g => $gg) if ($gg != "") $FmtPV["$$g"] = "'" . $gg . "'";
	return;
	}

Markup('zapkeep', '<if', '/\\(:keep (.*?):\\)/esi', "Keep(ZAPkeep(PSS('$1')))");
Markup('zapkeep+', '<if', '/\\(:keep\\+ (.*?):\\)/esi', "ZAPkeep(PSS('$1'))");
function ZAPkeep($x) {
	$out = array('%0D%0A');
	$in = array('%5B%5B%26lt%3B%26lt%3B%5D%5D%0A');
	$x = urldecode(str_replace($in, $out, urlencode($x)));
	$htmlout = array("'", '"', '   ', ":)");
	$htmlin = array('&amp;#39;', '&amp;quot;', '&amp;nbsp;&amp;nbsp;&amp;nbsp;', '&amp;#x3a;)');
	$x = str_replace($htmlin, $htmlout, $x);
	return $x;
	}

Markup('select', 'inline', '/\(:select (.*?):\\)/', '<select $1>');
Markup('select+', 'inline', '/\(:select\\+ (.*?):\\)/', '<select $1 onChange="window.location=this.options[this.selectedIndex].value">');
Markup('option', 'inline', '/\\(:option (.*?):\\)/e', "Keep('<option '.PQA(PSS('$1')).' />')");
Markup('selectend', 'inline', '/\(:selectend:\\)/', '</select>');
Markup('textarea', 'inline', '/\\(:textarea (.*?):\\)/e', "Keep('<textarea '.PQA(PSS('$1')).' class=inputbox>')");
Markup('textareaend', 'inline', '/\(:textareaend:\\)/', '</textarea>');

	
#########################################
###         MISC ZAP FREEBIES         ###
#########################################

Markup('group', '<if', '/\{\#g (.*?)\}/e', "ZAPgroup('$1')");
function ZAPgroup($g) {
	$gg = explode("|", $g);
	if (!isset($gg[1])) return;
	$gg[0] = substr(ZAPpageshortcuts($gg[0] . ".G"), 0, -2);
	if ($gg[1] == "count") {
		$c = count(ListPages("/^$gg[0]\./"));
		if (PageExists("$gg[0].GroupHeader")) $c = $c - 1;
		if (PageExists("$gg[0].GroupFooter")) $c = $c - 1;
		if (PageExists("$gg[0].GroupSidebar")) $c = $c - 1;
		if (PageExists("$gg[0].GroupAttributes")) $c = $c - 1;
		if (PageExists("$gg[0].RecentChanges")) $c = $c - 1;
		return $c;
		}
	if ($gg[1] == "thread") {
		global $ZAPthreadstart;
		$e = $ZAPthreadstart - 1;
		$ggg = explode(",", $gg[0]);
		foreach($ggg as $gggg) {
			foreach(ListPages("/^$gggg\\.\\d/") as $n) {
			$n = substr($n,strlen($gggg)+1);
			if (! ereg("^[0-9]+$", $n)) continue;
			$e = max($e,$n);
			}
		}
		$e = $e + 1;
		return $e;
		}
	return;
	}

Markup('random', '<{$var}', '/\{\#r ([0-9]+)\|([0-9]+)\}/e', "ZAPrand('$1','$2')");
function ZAPrand($x, $y) {
	global $FmtPV;
	$r = rand($x, $y);
	$FmtPV['$captcha'] = "'" . $r . "'";
	return $r;
	}

Markup('time', '<if', '/\{\#t (.*?)\}/e', "ZAPtime('$1')");
function ZAPtime($x) {
	$t = explode("|", $x);
	if ($t[0] == "+") $t[0] = time();
	if (!ereg("^[0-9]{10}$", $t[0])) $t[0] = strtotime(str_replace('-', '/', $t[0]));
	if (!isset($t[1])) return $t[0];
	if (substr($t[1], 0, 1) == "%") return strftime($t[1], $t[0]);
	if (! ereg("^(\+|\-){1}", $t[1])) return $t[0];
	$seconds['s'] = 1;
	$seconds['m'] = 60;
	$seconds['h'] = 3600;
	$seconds['d'] = 86400;
	$seconds['w'] = 604800;
	$s = bcmul($seconds[substr($t[1], -1, 1)], substr($t[1], 1, -1));
	if (substr($t[1], 0, 1) == "+") $s = $t[0] + $s;
	if (substr($t[1], 0, 1) == "-") $s = $t[0] - $s;
	if (substr($t[2], 0, 1) == "%") return strftime($t[2], $s);
	return $s;	
	}

Markup('date', '<if', '/\{\#d (.*?)\}/e', "ZAPdate('$1')");
function ZAPdate($x) {
	$d = explode("|", $x);
	if (substr($d[0], 0, 1) == "0") $d[0] = substr($d[0],1);
	$ZAPmonth = array("","January","February","March","April","May","June","July","August","September","October","November","December");
	$ZAPweek = array("","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
	if ($d[1] == "m") $x = $ZAPmonth[$d[0]];
	if ($d[1] == "w") $x = $ZAPweek[$d[0]];
	return $x;
	}

$Conditions['exists'] = 'PageExists($condparm)';

$Conditions['gexists'] = 'GroupExists($condparm)';
function GroupExists($x) {
	$c = count(ListPages("/^$x\./"));
	if ($c != 0) return true;
	return false;
	}

$Conditions['pagefmt'] = 'CheckFmt($condparm)';
function CheckFmt($x) {
	global $pagename;
	if ((strpos($x, ".")) && ($x == FmtPageName('$FullName', $x))) return true;
	if ((!strpos($x, ".")) && ($x == PageVar(MakePageName($pagename, $x), '$Name'))) return true;
	return false;
	}
	
$Conditions['inlist'] = 'InList($condparm)';
function InList($x) {
	global $pagename;
	$xx = explode("|", $x);
	if(!isset($xx[2])) $xx[2] = $pagename;
  	$l = explode(",", ZAPgetdata($xx[1], $xx[2]));
	foreach ($l as $ll) {
		if (strcmp($ll, $xx[0]) == 0) return true;
		}
	return false;
	}
	
$Conditions['checktime'] = 'CheckTime($condparm)';
function CheckTime($x) {
	$t = explode("|", $x);
	$x = bccomp($t[0], $t[1]);
	if ($x == -1) $x = 0;
	return $x;
	}
