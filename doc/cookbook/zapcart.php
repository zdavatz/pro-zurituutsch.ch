<?php if (!defined('PmWiki')) exit();

$FmtPV['$ZAPcartversion'] = "'Jan 14, 2007'";

## DESCRIPTION:  ZAPcart simply adds a couple markups you can use to create a simple shopping cart system for your site.  ZAPcart requires a separate javascript order.js file in your pub directory to do price calculations, and to submit the form to PayPal.  Feel free to edit the javascript function as desired.  For more info, visit the ZAP demo site at WWW.ZAPSITE.ORG.  Author: Dan Vis aka Caveman <editor àt fast döt st>, Copyright 2006.  

SDV($ZAPordertable, "<table cellpadding=5>");
$FmtPV['$orderpage'] = "'" . "$GLOBALS[AuthId]-" . strftime("%Y%j", time()) . "'";


Markup('zapcart', '>zapdata', '/\(:zapcart (.*?):\)/ei', "ZAPcart('$1')"); // takes item name
Markup('zaporder', '>{$var}', '/\(:zaporder(.*?):\)/ei', "Keep(ZAPorder('$1'))"); // takes page name of items list
Markup('zappay', '>{$var}', '/\(:zappay(.*?):\)/ei', "Keep(ZAPpaypal('$1'))"); // takes name & path of graphic


function ZAPcart($x) { 
	global $FmtPV;
	$o = substr($FmtPV['$orderpage'], 1, -1);
	$c = '(:zapform:)(:input hidden datapage "' . $o . '":)[--ADD TO CART--] (:input text "' . $x . '" "{$' . $x . '}" size=2:) [--QTY--] (:input hidden savedata "+' . $x . '":)(:input submit button value=Add:)(:zapend:)';
	return $c;
	}
	
function ZAPorder($x) { 
	global $ZAPordertable, $FmtPV, $MessagesFmt, $m;
	$error[no_items] = "<blockquote><b><i>There are currently no items in your shopping cart!</i></b></blockquote>";
	$error[invalid_form] = "<blockquote><b><i>There was an error processing your order.</i></b></blockquote>";
	if ($x != "") $o = "Order." . substr($x, 1);
	else $o = "Order.$GLOBALS[AuthId]-" . strftime("%Y%j", time());
	if (! PageExists($o)) return $error[no_items];
	$page = ReadPage($o);
	$contents = $page[text];
	$order = "";
	if (strpos($contents, "(:comment data:)")) {
		$d = substr($contents, strpos($contents, "(:comment data:)") + 16);
		$field = explode("\n\n", $d);
		foreach ($field as $value) {  
			if ($value == "") continue;
			if (substr($value, 0, 4) == "(:if") continue;
			$value = substr($value, 2, -2);
			$v[0] = substr($value, 0, strpos($value, ': '));
			$v[1] = substr($value, strpos($value, ': ') + 2);
			if($v[0] == "") continue;
			$item = ZAPgetdata($v[0], "Order.Items");
			$itemname = substr($item, 0, strpos($item, "|"));
			$itemprice = substr($item, strpos($item, "|") + 1);
			$itemcost = bcmul($v[1], $itemprice, 2);
			$itemlist .= ",$v[0]"; 
			$costlist .= ",$itemprice"; 
			$order .= "<tr><td nowrap>$itemname&nbsp;&nbsp;&nbsp;</td><td><input type=text size=4 name=$v[0] value=$v[1] onblur=calculate()>&nbsp;&nbsp;&nbsp;at</td><td align=right>$$itemprice&nbsp;&nbsp;</td><td><input type=text size=7 name=" . $v[0] . "cost value=$itemcost onblur=calculate() onFocus=blur()></td></tr>";
			}
		if ($order == "") return $error[no_items];
		$itemlist = substr($itemlist, 1);
		$costlist = substr($costlist, 1);
		$order = "<script src='pub/order.js'></script>\n$ZAPordertable" . $order . "<tr><td colspan=3 align=right>Subtotal&nbsp;&nbsp;</td><td><input type=text size=7 name=subtotal onblur=calculate()></td></tr><tr><td rowspan=2 valign=top>U.S. Order?<br><input type=radio name=usorder value=Yes onClick=calculate() checked>Yes <input type=radio name=usorder value=No onClick=calculate()>No</td><td colspan=2 align=right>Shipping &amp; Handling&nbsp;&nbsp;</td><td colspan=2><input type=text size=7 name=myshipping onblur=calculate()></td></tr><tr><td align=right colspan=2><input name=mybutton type=button value='Amount Due:' onclick=calculate()>&nbsp;&nbsp;</td><td><input type=text size=7 name=total><input type=hidden name=itemslist value='" . $itemlist . "'><input type=hidden name=costlist value='" . $costlist . "'><img src=null height=0 width=0 onError=calculate()></td></tr></table>";
		return $order;
		}
	else return "<blockquote><b><i>There was an error processing your order.</i></b></blockquote>";
	return $error[invalid_form];
	}
	
	
function ZAPpaypal($x) {
	if ($x == "") $x = "pub/paypal.gif";
	else $x = substr($x, 1);
	return "<input type=image src='" . $x . "' ALT='Make payments with PayPal - fast, free and secure!' onclick=paypal()>";
	}