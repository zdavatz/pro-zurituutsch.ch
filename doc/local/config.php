<?php if (!defined('PmWiki')) exit();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
include_once("$FarmD/cookbook/analyze.php");
$AnalyzeKey = 'NotAtopNotchSecret8753';


##  Also, be sure to take a look at http://www.pmichaud.com/wiki/Cookbook
##  for more details on the types of customizations that can be added
##  to PmWiki.

##  $WikiTitle is the name that appears in the browser's title bar
$WikiTitle = 'Pro-Zurituutsch';
$DefaultName = 'Home';

##  $ScriptUrl is your preferred URL for accessing wiki pages
##  $PubDirUrl is the URL for the pub directory.
$ScriptUrl = 'https://pro-zurituutsch.ch';
$PubDirUrl = 'https://pro-zurituutsch.ch/pub';

## Skin
# Specifies the name of the template file to be used to generate pages. 
#  $PageTemplateFmt = 'pub/skins/ywesee/gila.tmpl';
$Skin = 'pro-zurituutsch';
# The HTML code to be generated for the page logo.
$PageLogoFmt = "<a href='$ScriptUrl/Main/Home'>Pro-Zurituutsch</a>";

XLPage('de','PmWikiDe.XLPage');
		 
$EnableGUIButtons = 1;
#Sectionedit

#$SectionEditWithoutHeaders = true;
#$SectionEditAutoDepth = 2;
#$SectionEditMediaWikiStyle = true;
#include_once('cookbook/sectionedit.php');
#SpellChecker

#include_once('cookbook/spellchecker.php');

##  If your system is able to display the home page but gives
##  "404 Not Found" errors for all others, try setting the following:
$EnablePathInfo = 1;

##  $PageLogoUrlFmt is the URL for a logo image--you can change this to
##  your own logo if you wish.
$PageLogoUrl = '$PubDirUrl/skins/pro-zurituutsch/logo.jpg';

##  Set $SpaceWikiWords if you want WikiWords to automatically have
##  spaces before each sequence of capital letters
# $SpaceWikiWords = 1;			   # turns on WikiWord spacing

## Define usernames and passwords (loaded from external secrets file).
## On the live server, deploy secrets to /etc/pmwiki-secrets.php
$secrets_file = dirname(__FILE__) . '/../../etc/pmwiki-secrets.php';
if (file_exists($secrets_file)) {
  include_once($secrets_file);
}
## Enable authentication based on username.
# include_once('scripts/authuser.php');


##  If you want uploads enabled on your system, set $EnableUpload=1.
##  You'll also need to set a default upload password, or else set
##  passwords on individual groups and pages.  For more information see
##  PmWiki.UploadsAdmin and PmWiki.PasswordsAdmin.
$EnableUpload = 1;
$UploadMaxSize = 100000000;
$DefaultPasswords['upload'] = 'id:*'; 

##Passwords (set in secrets file)
$DefaultPasswords['edit'] = 'id:*';

# include_once("$FarmD/scripts/authuser.php");

#$EnableHtml('object')
# include_once("$FarmD/cookbook/enablehtml.php");
# EnableHtml('a');
#EnableHtml('param');
#EnableHtml('embed');

##  Set $SpaceWikiWords if you want WikiWords to automatically 
##  have spaces before each sequence of capital letters.
# $SpaceWikiWords = 1;                     # turn on WikiWord spacing

##  Set $LinkWikiWords if you want to allow WikiWord links.
# $LinkWikiWords = 1;                      # enable WikiWord links

##  If you want only the first occurrence of a WikiWord to be converted
##  to a link, set $WikiWordCountMax=1.
# $WikiWordCountMax = 1;                   # converts only first WikiWord
# $WikiWordCountMax = 0;                   # another way to disable WikiWords

##  The $WikiWordCount array can be used to control the number of times
##  a WikiWord is converted to a link.  This is useful for disabling
##  or limiting specific WikiWords.
# $WikiWordCount['PhD'] = 0;               # disables 'PhD'
# $WikiWordCount['PmWiki'] = 1;            # convert only first 'PmWiki'

##  By default, PmWiki is configured such that only the first occurrence
##  of 'PmWiki' in a page is treated as a WikiWord.  If you want to 
##  restore 'PmWiki' to be treated like other WikiWords, uncomment the
##  line below.
# unset($WikiWordCount['PmWiki']);

##  If you want to disable WikiWords matching a pattern, you can use 
##  something like the following.  Note that the first argument has to 
##  be different for each call to Markup().  The example below disables
##  WikiWord links like COM1, COM2, COM1234, etc.
# Markup('COM\d+', '<wikilink', '/\\bCOM\\d+/', "Keep('$0')");

##  $DiffKeepDays specifies the minimum number of days to keep a page's
##  revision history.  The default is 3650 (approximately 10 years).
# $DiffKeepDays=30;                        # keep page history at least 30 days

## By default, viewers are able to see the names (but not the
## contents) of read-protected pages in search results and
## page listings.  Set $EnablePageListProtect to keep read-protected
## pages from appearing in search results.
# $EnablePageListProtect = 1;

##  The refcount.php script enables ?action=refcount, which helps to
##  find missing and orphaned pages.  See PmWiki.RefCount.
# if ($action == 'refcount') include_once('scripts/refcount.php');

##  The feeds.php script enables ?action=rss, ?action=atom, ?action=rdf,
##  and ?action=dc, for generation of syndication feeds in various formats.
# if ($action == 'rss') include_once('scripts/feeds.php');   # RSS 2.0
# if ($action == 'atom') include_once('scripts/feeds.php');  # Atom 1.0
# if ($action == 'dc') include_once('scripts/feeds.php');    # Dublin Core
# if ($action == 'rdf') include_once('scripts/feeds.php');   # RSS 1.0

$EnableDiag = 1;

##  PmWiki allows a great deal of flexibility for creating custom markup.
##  To add support for '*bold*' and '~italic~' markup (the single quotes
##  are part of the markup), uncomment the following lines. 
##  (See PmWiki.CustomMarkup and the Cookbook for details and examples.)
# Markup("'~", "inline", "/'~(.*?)~'/", "<i>$1</i>");        # '~italic~'
# Markup("'*", "inline", "/'\\*(.*?)\\*'/", "<b>$1</b>");    # '*bold*'

##  If you want to have to approve links to external sites before they
##  are turned into links, uncomment the line below.  See PmWiki.UrlApprovals.
##  Also, setting $UnapprovedLinkCountMax limits the number of unapproved
##  links that are allowed in a page (useful to control wikispam).
# include_once('scripts/urlapprove.php');
# $UnapprovedLinkCountMax = 10;

##  The following lines make additional editing buttons appear in the
##  edit page for subheadings, lists, tables, etc.
 $GUIButtons['h2'] = array(400, '\\n!! ', '\\n', '$[Heading]',
                     '$GUIButtonDirUrlFmt/h2.gif"$[Heading]"');
 $GUIButtons['h3'] = array(402, '\\n!!! ', '\\n', '$[Subheading]',
                     '$GUIButtonDirUrlFmt/h3.gif"$[Subheading]"');
 $GUIButtons['indent'] = array(500, '\\n->', '\\n', '$[Indented text]',
                     '$GUIButtonDirUrlFmt/indent.gif"$[Indented text]"');
 $GUIButtons['outdent'] = array(510, '\\n-<', '\\n', '$[Hanging indent]',
                     '$GUIButtonDirUrlFmt/outdent.gif"$[Hanging indent]"');
 $GUIButtons['ol'] = array(520, '\\n# ', '\\n', '$[Ordered list]',
                     '$GUIButtonDirUrlFmt/ol.gif"$[Ordered (numbered) list]"');
 $GUIButtons['ul'] = array(530, '\\n* ', '\\n', '$[Unordered list]',
                     '$GUIButtonDirUrlFmt/ul.gif"$[Unordered (bullet) list]"');
 $GUIButtons['hr'] = array(540, '\\n----\\n', '', '',
                     '$GUIButtonDirUrlFmt/hr.gif"$[Horizontal rule]"');
 $GUIButtons['table'] = array(600,
                       '||border=1 width=80%\\n||!Hdr ||!Hdr ||!Hdr ||\\n||     ||     ||     ||\\n||     ||     ||     ||\\n', '', '', 
                     '$GUIButtonDirUrlFmt/table.gif"$[Table]"');

$EnableRssEnclosures = 1;
$RssEmailAddress = "bwyss.zurituutsch@gmail.com";
$RssFeedAuthor = "Pro-Zurituutsch";
$RssMaxItems = 250;
$RssFeedTitle = 'PodCast';

if ($action == 'rss' || $action == 'rdf')
		  include_once("cookbook/rssenclosures.php");

$TableRowIndexMax = 1;

#include_once("cookbook/zap.php");
#include_once("cookbook/zapmail.php");
# include_once("cookbook/attachdel.php");
$ZAPconfig = array("mail" => true);
/*
$CmsLikeMenuItems = array(
 'view' => '<a href="$PageUrl" rel="nofollow">$[view]</a>',
 'edit' => '<a href="$PageUrl?action=edit"
      rel="nofollow">$[edit]</a>',
 'side' => '<a href="$PageUrl?n=Main.SideBar?action=edit"
      rel="nofollow">$[sidebar]</a>',
 'upload' => '<a href="$PageUrl?action=upload"
      rel="nofollow">$[attach]</a>',
 'print' => '<a href="$PageUrl?action=print" target="_blank"
      rel="nofollow">$[print]</a>',
 'history' => '<a href="$PageUrl?action=diff"
      rel="nofollow">$[history]</a>',
);
*/
#$CmsLikeMenuSep = ' ';
#$CmsLikeAltMenuItems = $CmsLikeMenuItems;
#$CmsLikeAltMenuSep = ' - ';
#include_once('cookbook/userauth.php');
#include_once('cookbook/cmslike.php');

include_once("local/ordermail.php");
# Old Markup for PHP versions before 5.5
# Markup('textarea', '>{$var}',
# 	'/\\(:textarea\\s*(.*?):\\)(.*?)\\(:textareaend:\\)/esi',
# 	"FmtTextArea(\$pagename,PSS('$1'),PSS('$2'))");
# Old Markup for PHP versions >= 5.5. See http://www.pmwiki.org/wiki/PmWiki/CustomMarkup#php55
Markup('textarea', '>{$var}',
 	'/\\(:textarea\\s*(.*?):\\)(.*?)\\(:textareaend:\\)/si',
 	"FmtTextArea(\$pagename,PSS('$m[1]'),PSS('$m[2]'))");
function FmtTextArea($pagename, $args, $value)
{
	global $InputValues;
	$opt = ParseArgs($args);
	$attributes = array('name', 'id', 'class', 'rows', 'cols',
	'maxlength', 'accesskey', 'disabled', 'readonly', 'style');
	$myattr = "";
	foreach($attributes as $k)
	{   if(isset($opt[$k])) $myattr.=" $k=\"".htmlspecialchars($opt[$k])."\"";}
	if(!strlen($value))$value = htmlspecialchars(@$InputValues[@$opt['name']]);
	return Keep(FmtPageName("<textarea $myattr>$value</textarea>", $pagename));
}
