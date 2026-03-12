<?php if (!defined('PmWiki')) exit();
/*  Copyright 2002-2004 Patrick R. Michaud (pmichaud@pobox.com)
    This file is part of PmWiki; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  See pmwiki.php for full details.

    This script defines "?action=rss".  It will read the current page
    for a WikiTrail, and then output an RSS 2.0 document with the current
    page as the channel and the pages in the WikiTrail as the items.

    To add RSS capabilities to your site, simply add to config.php:

        include_once('scripts/rss.php');

    To avoid the cost of loading this script and initializing RSS variables 
    when you aren't going to generate a feed, use this instead:

        if ($action == 'rss' || $action == 'rdf') 
          include_once('scripts/rss.php');
          
          
Added by Crisses:
	
	Functionality for MP3 enclosures (such as those used for "podcasting" feeds.):
	1) requires uploads enabled before calling rss.php
	2) requires $EnableRssEnclosures = 1; to be set before calling rss.php
	
	
	my settings in config.php -- note that I put the altered rss.php in my farm's local dir:
	
	
	// Begin podcasting setup only for group MyPodcasts       
		if (preg_match('/^MyPodcasts\//',$pagename)){
			$EnableUpload = 1;
			$DefaultPasswords['upload'] = crypt('mysecretandyoucanthaveit');
			$EnableRssEnclosures = 1;
		}
	
	// load alternate rss.php
	if ($action == 'rss' || $action == 'rdf')
			  include_once("$FarmD/local/rss.php");
	
	
	Note that this enables normal RSS for the entire site except the MyPodcasts group, 
	which has the addition of uploads and the enclosures necessary for podcasting.
	
	
	**Additional work would need to be done to enable file types other than mp3.**
          
*/

//version added by Crisses
define(RSS_VERSION, '0.5'); // with the caveat that rss 0.1 was PM's original version

SDV($HandleActions['rss'],'HandleRss');
SDV($HandleActions['rdf'],'HandleRss');

SDV($RssMaxItems,20);				# maximum items to display
SDV($RssSourceSize,400);			# max size to build desc from
SDV($RssDescSize,200);				# max desc size
SDV($RssItems,array());				# RSS item elements
SDV($RssItemsRDFList,array());			# RDF <items> elements


// More variables added by Crisses
SDV($RssFeedTitle, "$WikiTitle | $Group / $Title");  # Allows custom feed titles dependent on wiki, group, or page variables.
SDV($RssFeedDesc, '$RssChannelDesc'); // defaults to prior behavior of reading descriptions from the file
SDV($RssFeedDescFromMetadata, 0); // to allow taking feed descriptions from metadata (:description:) wiki-tag
SDV($RssItemDescFromMetadata, 0); // to allow taking item descriptions from metadata (:description:) wiki-tag
SDV($RssItemTitleOnly, 0); // removes the $Group from the RSS item's title
SDV($EnableRssEnclosures, 0); // allows rss-feed attachments
SDV($RssFeedOptions, ''); // user-configurable RSS feed options
SDV($RssItemOptions, ''); // gives people the ability to add optional tags in their config.php files (like copyright info)
SDV($RssEnclosureTLA, 'mp3'); // default attachments are mp3
SDV($RssEmailAddress, ''); // default value for feed email address
SDV($RssFeedAuthor, $WikiTitle); // default author name for the feed channel
SDV($RssFeedLanguage, 'en'); // feed language code (e.g. 'de', 'de-CH')
SDV($RssFeedImageUrl, ''); // podcast cover image URL (min 1400x1400px for iTunes/Spotify)
SDV($RssFeedCategory, ''); // iTunes category (e.g. 'Arts', 'Society &amp; Culture')
SDV($RssFeedSubCategory, ''); // iTunes subcategory (optional)
SDV($RssFeedExplicit, 'false'); // 'true' or 'false'
SDV($RssFeedType, 'episodic'); // 'episodic' or 'serial'




// modified original RSS xml standards to have xml comments, which will be replaced later.
// The reason for xml comments is so that the feed won't outright break if the comments are not removed.

if ($action=='rdf') {
  ### RSS 1.0 (RDF) definitions
  SDV($RssTimeFmt,'%Y-%m-%dT%H:%MZ');	# time format
  SDV($RssItemsRDFListFmt,"<rdf:li rdf:resource=\"\$PageUrl\" />\n");
  SDV($RssChannelFmt,array('<?xml version="1.0" encoding="UTF-8"?'.'>
    <rdf:RDF  xmlns="http://purl.org/rss/1.0/"
        xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
        xmlns:dc="http://purl.org/dc/elements/1.1/">
      <channel rdf:about="$PageUrl">
        <!-- feed title here -->
        <link>$PageUrl</link>
        <!-- feed description here -->
        <dc:date>$RssChannelBuildDate</dc:date>
		<!-- feed options here -->
        <items>
          <rdf:Seq>',&$RssItemsRDFList,'
          </rdf:Seq>
        </items>
      </channel>'));
  SDV($RssItemFmt,'
      <item rdf:about="$PageUrl">
        <!-- item title here -->
        <link>$PageUrl</link>
        <!-- item description here -->
        <description>$RssItemDesc</description>
        <pubDate>$RssItemPubDate</pubDate>
        <!-- item options here -->
      </item>');
  // Run subroutine that alters the format variables above    
  ApplyRssOptions();
  SDV($HandleRssFmt,array(&$RssChannelFmt,&$RssItems,'</rdf:RDF>'));
}


### RSS 2.0 definitions
SDV($RssTimeFmt,'%a, %d %b %Y %H:%M:%S GMT');
$RssCategoryXml = '<itunes:category text="' . $RssFeedCategory . '"';
if (!empty($RssFeedSubCategory)) {
  $RssCategoryXml .= '><itunes:category text="' . $RssFeedSubCategory . '"/></itunes:category>';
} else {
  $RssCategoryXml .= '/>';
}
SDV($RssChannelFmt,'<?xml version="1.0" encoding="UTF-8"?'.'>
  <rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
    <channel>
      <!-- feed title here -->
      <link>$PageUrl</link>
      <!-- feed description here -->
      <language>' . $RssFeedLanguage . '</language>
      <managingEditor>' . $RssEmailAddress . ' (' . $RssFeedAuthor . ')</managingEditor>
      <lastBuildDate>$RssChannelBuildDate</lastBuildDate>
      <itunes:author>' . $RssFeedAuthor . '</itunes:author>
      <itunes:owner>
        <itunes:name>' . $RssFeedAuthor . '</itunes:name>
        <itunes:email>' . $RssEmailAddress . '</itunes:email>
      </itunes:owner>
      <itunes:image href="' . $RssFeedImageUrl . '"/>
      ' . $RssCategoryXml . '
      <itunes:explicit>' . $RssFeedExplicit . '</itunes:explicit>
      <itunes:type>' . $RssFeedType . '</itunes:type>
 	  <!-- feed options here -->
      <docs>http://blogs.law.harvard.edu/tech/rss</docs>
      <generator>PmWiki $Version</generator>');
SDV($RssItemFmt,'

        <item>
          <!-- item title here -->
          <link>$PageUrl</link>
          <!-- item description here -->
          <author>' . $RssEmailAddress . ' ($RssItemAuthor)</author>
          <guid isPermaLink="true">$PageUrl</guid>
          <pubDate>$RssItemPubDate</pubDate>
          <!-- item options here -->
        </item>');
// Run subroutine that alters the format variables above    
ApplyRssOptions();

SDV($HandleRssFmt,array(&$RssChannelFmt,&$RssItems,'
    </channel>
  </rss>'));


function ApplyRssOptions(){
	global $RssChannelFmt, $RssItemFmt, $RssItemTitleOnly, $EnableRssEnclosures, $RssItemTitleOnly, $RssFeedDescFromMetadata, $RssItemOptions;

    // this function applies user & default variable data in place of comment tags in the RSS feed information
	  
    //load the correct RSS Item Titles depending on whether title-only is enabled.
	if ($RssItemTitleOnly==1) {
		$RssItemFmt = str_replace ('<!-- item title here -->', '<title>$Title</title>', $RssItemFmt);
	
	} else {
		$RssItemFmt = str_replace ('<!-- item title here -->', '<title>$Group / $Title</title>', $RssItemFmt);
	}
	
	// add in the Item Options, with variation based on whether enclosures are enabled or not.
	// enclosures are only an option in RSS 2.0 -- so this section allows them for only an RSS 2.0 feed
	if ($EnableRssEnclosures && $action='rss'){
		$RssItemOptions = '<enclosure url="$RssItemEnclosureUrl" length="$RssItemEnclosureLength" type="$RssItemEnclosureType"/>
		' . $RssItemOptions;
	} 
	
	$RssItemFmt = str_replace ('<!-- item options here -->', $RssItemOptions, $RssItemFmt);
		
	// creating a variable reference to point to the format portion of interest
	// in RSS 1.0 it's in an array -- in 2.0 it is not.
	if ($action=='rdf') {
		$feed_format_pointer =& $RssChannelFmt[0];
	
	} else {
		$feed_format_pointer =& $RssChannelFmt;
	}
	
	// to allow taking feed descriptions from metadata (:description:) wiki-tag [Pending Feature]
	$feed_format_pointer = str_replace ('<!-- feed title here -->', '<title>$RssFeedTitle</title>', $feed_format_pointer);
	$feed_format_pointer = str_replace ('<!-- feed options here -->', '$RssFeedOptions', $feed_format_pointer);
	
	if ($RssFeedDescFromMetadata == 1){
	//Right now this does nothing -- I am waiting for information from Patrick
		$feed_format_pointer = str_replace ('<!-- feed description here -->', '<description>$RssFeedDesc</description>
      <itunes:summary>$RssFeedDesc</itunes:summary>', $feed_format_pointer);

	} else {
			$feed_format_pointer = str_replace ('<!-- feed description here -->', '<description>$RssFeedDesc</description>
      <itunes:summary>$RssFeedDesc</itunes:summary>', $feed_format_pointer);
	}	
	
	// to allow taking descriptions from metadata (:description:) wiki-tag [Pending Feature]
	If ($RssItemDescFromMetadata == 1) {
		// This does nothing until I hear from Patrick
		$RssItemFmt = str_replace ('<!-- item description here -->', '<description>$RssItemDesc</description>', $RssItemFmt);
	
	} else {
		$RssItemFmt = str_replace ('<!-- item description here -->', '<description>$RssItemDesc</description>', $RssItemFmt);
	}

} // end function ApplyRssOptions




function HandleRss($pagename) {
  global $RssMaxItems,$RssSourceSize,$RssDescSize,
    $RssChannelFmt,$RssChannelDesc,$RssTimeFmt,$RssChannelBuildDate,
    $RssItemsRDFList,$RssItemsRDFListFmt,$RssItems,$RssItemFmt,
    $HandleRssFmt,$FmtV;


    
  // Added by Crisses to detect if enclosures are enabled
  global $EnableRssEnclosures;
  
  // this array grabs the items in the trail  
  $trailpage = ReadTrail($pagename,$pagename);
  
  
  // retrieve page text if authorized to see the page
  $page = RetrieveAuthPage($pagename,'read',false);
  
  // if it didn't grab the page, abort with reason.
  if (!$page) Abort("?cannot read $pagename");

  // grabs page timestamp
  $cbgmt = $page['time'];
  
  // define an array to hold an array of info for each article/item in the feed
  $itemarray = array();
  $seenpages = array();

  // grabs the existing trail items up to the number of max items in the feed
  for($i=0;$i<count($trailpage) && count($itemarray)<$RssMaxItems;$i++) {

    // skip duplicate trail entries to avoid duplicate GUIDs
    if (isset($seenpages[$trailpage[$i]['pagename']])) continue;
    $seenpages[$trailpage[$i]['pagename']] = true;

    if (!PageExists($trailpage[$i]['pagename'])) continue;
    $page = RetrieveAuthPage($trailpage[$i]['pagename'],'read',false); Lock(0);
    if (!$page) continue;
    
    // grabs the page text characters only up to RSS Source Size var.
    
    $text = 
      MarkupToHTML($trailpage[$i]['pagename'],substr($page['text'],0,$RssSourceSize));

	// Note to self / Crisses
	// This area picks out the description for the Items?  Where is the desc for
	// the feed picked up?

    $text = entityencode(preg_replace("/<.*?>/s","",$text)); 
    preg_match("/^(.{0,$RssDescSize}\\s)/s",$text,$match);
    $itemarray[] = array('name' => $trailpage[$i]['pagename'],'time' => $page['time'],
       'desc' => $match[1]." ...", 'author' => $page['author']);
    if ($page['time']>$cbgmt) $cbgmt=$page['time'];
  }
  SDV($RssChannelBuildDate,
    entityencode(gmdate('D, d M Y H:i:s \G\M\T', $cbgmt)));
    
    
    // Here is the channel description
    
  SDV($RssChannelDesc,entityencode(FmtPageName('$Group.$Title',$pagename)));
  
  // Item encoding here
  // iterates through each page in the feed (ie Items)
  foreach($itemarray as $page) {
    $FmtV['$RssItemPubDate'] = gmstrftime($RssTimeFmt,$page['time']);
    $FmtV['$RssItemDesc'] = $page['desc']; 
    global $RssFeedAuthor;
    $FmtV['$RssItemAuthor'] = !empty($page['author']) ? $page['author'] : $RssFeedAuthor;
    
    // Create a temporary item format that can be altered in the iteration of each page
    $TempRssItemFmt = $RssItemFmt;
    
    // Added by Crisses
    // Provide the additional variables for the enclosure format
	if ($EnableRssEnclosures) {
		// grab the necessary variables
		global $UploadDir,$UploadUrlFmt,$UploadExts,$RssEnclosureTLA;

		// find the subdir & name of the enclosure file	
		
		$EnclosureFileName =  str_replace(".", "/", $page['name']) . '.' . $RssEnclosureTLA;
		// The machine's path to the file
		$FmtV['$RssItemEnclosureUrl'] = "$UploadUrlFmt/$EnclosureFileName";
		$filePath = "$UploadDir/$EnclosureFileName";

		// Only look for enclosure information if the file exists
		if (file_exists($filePath)) {

			// Determine the length of the enclosure (required)
			$FmtV['$RssItemEnclosureLength'] = filesize("$UploadDir/$EnclosureFileName");
			// Give the file type
			$FmtV['$RssItemEnclosureType'] = $UploadExts["$RssEnclosureTLA"];
		} else {
			// if there is no attachment, remove the enclosure formatting only for the current item
			$TempRssItemFmt = preg_replace ('!<enclosure url.*/>!', "", $TempRssItemFmt);
		}
	}
    // End (Added by Crisses)
    
    $RssItemsRDFList[] = 
      entityencode(FmtPageName($RssItemsRDFListFmt,$page['name']));
    $RssItems[] = 
      entityencode(FmtPageName($TempRssItemFmt,$page['name']));
  }
  header("Content-type: text/xml");
  PrintFmt($pagename,$HandleRssFmt);
  exit();
}

# entityencode() and $EntitiesTable are used to convert non-ASCII characters 
# and named entities into numeric entities, since the RSS and RDF
# specifications don't have a good way of incorporating them by default.
function entityencode($s) {
  global $EntitiesTable;
  $s = str_replace(array_keys($EntitiesTable),array_values($EntitiesTable),$s);
  // Check if content is valid UTF-8
  if (@preg_match('//u', $s)) {
    // UTF-8: convert multibyte sequences to Unicode codepoint entities
    return preg_replace_callback('/[\xc0-\xf7][\x80-\xbf]+/', function($m) {
      $bytes = $m[0];
      $len = strlen($bytes);
      $b0 = ord($bytes[0]);
      if ($len == 2) {
        $cp = (($b0 & 0x1F) << 6) | (ord($bytes[1]) & 0x3F);
      } elseif ($len == 3) {
        $cp = (($b0 & 0x0F) << 12) | ((ord($bytes[1]) & 0x3F) << 6) | (ord($bytes[2]) & 0x3F);
      } elseif ($len == 4) {
        $cp = (($b0 & 0x07) << 18) | ((ord($bytes[1]) & 0x3F) << 12) | ((ord($bytes[2]) & 0x3F) << 6) | (ord($bytes[3]) & 0x3F);
      } else {
        return $bytes;
      }
      return '&#' . $cp . ';';
    }, $s);
  }
  // Latin-1 fallback: convert each non-ASCII byte individually
  return preg_replace_callback('/[\x80-\xff]/', function($m) {
    return '&#' . ord($m[0]) . ';';
  }, $s);
}

SDVA($EntitiesTable, array(
  # entities defined in "http://www.w3.org/TR/xhtml1/DTD/xhtml-lat1.ent"
  '&nbsp;' => '&#160;', 
  '&iexcl;' => '&#161;', 
  '&cent;' => '&#162;', 
  '&pound;' => '&#163;', 
  '&curren;' => '&#164;', 
  '&yen;' => '&#165;', 
  '&brvbar;' => '&#166;', 
  '&sect;' => '&#167;', 
  '&uml;' => '&#168;', 
  '&copy;' => '&#169;', 
  '&ordf;' => '&#170;', 
  '&laquo;' => '&#171;', 
  '&not;' => '&#172;', 
  '&shy;' => '&#173;', 
  '&reg;' => '&#174;', 
  '&macr;' => '&#175;', 
  '&deg;' => '&#176;', 
  '&plusmn;' => '&#177;', 
  '&sup2;' => '&#178;', 
  '&sup3;' => '&#179;', 
  '&acute;' => '&#180;', 
  '&micro;' => '&#181;', 
  '&para;' => '&#182;', 
  '&middot;' => '&#183;', 
  '&cedil;' => '&#184;', 
  '&sup1;' => '&#185;', 
  '&ordm;' => '&#186;', 
  '&raquo;' => '&#187;', 
  '&frac14;' => '&#188;', 
  '&frac12;' => '&#189;', 
  '&frac34;' => '&#190;', 
  '&iquest;' => '&#191;', 
  '&Agrave;' => '&#192;', 
  '&Aacute;' => '&#193;', 
  '&Acirc;' => '&#194;', 
  '&Atilde;' => '&#195;', 
  '&Auml;' => '&#196;', 
  '&Aring;' => '&#197;', 
  '&AElig;' => '&#198;', 
  '&Ccedil;' => '&#199;', 
  '&Egrave;' => '&#200;', 
  '&Eacute;' => '&#201;', 
  '&Ecirc;' => '&#202;', 
  '&Euml;' => '&#203;', 
  '&Igrave;' => '&#204;', 
  '&Iacute;' => '&#205;', 
  '&Icirc;' => '&#206;', 
  '&Iuml;' => '&#207;', 
  '&ETH;' => '&#208;', 
  '&Ntilde;' => '&#209;', 
  '&Ograve;' => '&#210;', 
  '&Oacute;' => '&#211;', 
  '&Ocirc;' => '&#212;', 
  '&Otilde;' => '&#213;', 
  '&Ouml;' => '&#214;', 
  '&times;' => '&#215;', 
  '&Oslash;' => '&#216;', 
  '&Ugrave;' => '&#217;', 
  '&Uacute;' => '&#218;', 
  '&Ucirc;' => '&#219;', 
  '&Uuml;' => '&#220;', 
  '&Yacute;' => '&#221;', 
  '&THORN;' => '&#222;', 
  '&szlig;' => '&#223;', 
  '&agrave;' => '&#224;', 
  '&aacute;' => '&#225;', 
  '&acirc;' => '&#226;', 
  '&atilde;' => '&#227;', 
  '&auml;' => '&#228;', 
  '&aring;' => '&#229;', 
  '&aelig;' => '&#230;', 
  '&ccedil;' => '&#231;', 
  '&egrave;' => '&#232;', 
  '&eacute;' => '&#233;', 
  '&ecirc;' => '&#234;', 
  '&euml;' => '&#235;', 
  '&igrave;' => '&#236;', 
  '&iacute;' => '&#237;', 
  '&icirc;' => '&#238;', 
  '&iuml;' => '&#239;', 
  '&eth;' => '&#240;', 
  '&ntilde;' => '&#241;', 
  '&ograve;' => '&#242;', 
  '&oacute;' => '&#243;', 
  '&ocirc;' => '&#244;', 
  '&otilde;' => '&#245;', 
  '&ouml;' => '&#246;', 
  '&divide;' => '&#247;', 
  '&oslash;' => '&#248;', 
  '&ugrave;' => '&#249;', 
  '&uacute;' => '&#250;', 
  '&ucirc;' => '&#251;', 
  '&uuml;' => '&#252;', 
  '&yacute;' => '&#253;', 
  '&thorn;' => '&#254;', 
  '&yuml;' => '&#255;', 
  # entities defined in "http://www.w3.org/TR/xhtml1/DTD/xhtml-special.ent"
  '&quot;' => '&#34;', 
  #'&amp;' => '&#38;#38;', 
  #'&lt;' => '&#38;#60;', 
  #'&gt;' => '&#62;', 
  '&apos;' => '&#39;', 
  '&OElig;' => '&#338;', 
  '&oelig;' => '&#339;', 
  '&Scaron;' => '&#352;', 
  '&scaron;' => '&#353;', 
  '&Yuml;' => '&#376;', 
  '&circ;' => '&#710;', 
  '&tilde;' => '&#732;', 
  '&ensp;' => '&#8194;', 
  '&emsp;' => '&#8195;', 
  '&thinsp;' => '&#8201;', 
  '&zwnj;' => '&#8204;', 
  '&zwj;' => '&#8205;', 
  '&lrm;' => '&#8206;', 
  '&rlm;' => '&#8207;', 
  '&ndash;' => '&#8211;', 
  '&mdash;' => '&#8212;', 
  '&lsquo;' => '&#8216;', 
  '&rsquo;' => '&#8217;', 
  '&sbquo;' => '&#8218;', 
  '&ldquo;' => '&#8220;', 
  '&rdquo;' => '&#8221;', 
  '&bdquo;' => '&#8222;', 
  '&dagger;' => '&#8224;', 
  '&Dagger;' => '&#8225;', 
  '&permil;' => '&#8240;', 
  '&lsaquo;' => '&#8249;', 
  '&rsaquo;' => '&#8250;', 
  '&euro;' => '&#8364;', 
  # entities defined in "http://www.w3.org/TR/xhtml1/DTD/xhtml-symbol.ent"
  '&fnof;' => '&#402;', 
  '&Alpha;' => '&#913;', 
  '&Beta;' => '&#914;', 
  '&Gamma;' => '&#915;', 
  '&Delta;' => '&#916;', 
  '&Epsilon;' => '&#917;', 
  '&Zeta;' => '&#918;', 
  '&Eta;' => '&#919;', 
  '&Theta;' => '&#920;', 
  '&Iota;' => '&#921;', 
  '&Kappa;' => '&#922;', 
  '&Lambda;' => '&#923;', 
  '&Mu;' => '&#924;', 
  '&Nu;' => '&#925;', 
  '&Xi;' => '&#926;', 
  '&Omicron;' => '&#927;', 
  '&Pi;' => '&#928;', 
  '&Rho;' => '&#929;', 
  '&Sigma;' => '&#931;', 
  '&Tau;' => '&#932;', 
  '&Upsilon;' => '&#933;', 
  '&Phi;' => '&#934;', 
  '&Chi;' => '&#935;', 
  '&Psi;' => '&#936;', 
  '&Omega;' => '&#937;', 
  '&alpha;' => '&#945;', 
  '&beta;' => '&#946;', 
  '&gamma;' => '&#947;', 
  '&delta;' => '&#948;', 
  '&epsilon;' => '&#949;', 
  '&zeta;' => '&#950;', 
  '&eta;' => '&#951;', 
  '&theta;' => '&#952;', 
  '&iota;' => '&#953;', 
  '&kappa;' => '&#954;', 
  '&lambda;' => '&#955;', 
  '&mu;' => '&#956;', 
  '&nu;' => '&#957;', 
  '&xi;' => '&#958;', 
  '&omicron;' => '&#959;', 
  '&pi;' => '&#960;', 
  '&rho;' => '&#961;', 
  '&sigmaf;' => '&#962;', 
  '&sigma;' => '&#963;', 
  '&tau;' => '&#964;', 
  '&upsilon;' => '&#965;', 
  '&phi;' => '&#966;', 
  '&chi;' => '&#967;', 
  '&psi;' => '&#968;', 
  '&omega;' => '&#969;', 
  '&thetasym;' => '&#977;', 
  '&upsih;' => '&#978;', 
  '&piv;' => '&#982;', 
  '&bull;' => '&#8226;', 
  '&hellip;' => '&#8230;', 
  '&prime;' => '&#8242;', 
  '&Prime;' => '&#8243;', 
  '&oline;' => '&#8254;', 
  '&frasl;' => '&#8260;', 
  '&weierp;' => '&#8472;', 
  '&image;' => '&#8465;', 
  '&real;' => '&#8476;', 
  '&trade;' => '&#8482;', 
  '&alefsym;' => '&#8501;', 
  '&larr;' => '&#8592;', 
  '&uarr;' => '&#8593;', 
  '&rarr;' => '&#8594;', 
  '&darr;' => '&#8595;', 
  '&harr;' => '&#8596;', 
  '&crarr;' => '&#8629;', 
  '&lArr;' => '&#8656;', 
  '&uArr;' => '&#8657;', 
  '&rArr;' => '&#8658;', 
  '&dArr;' => '&#8659;', 
  '&hArr;' => '&#8660;', 
  '&forall;' => '&#8704;', 
  '&part;' => '&#8706;', 
  '&exist;' => '&#8707;', 
  '&empty;' => '&#8709;', 
  '&nabla;' => '&#8711;', 
  '&isin;' => '&#8712;', 
  '&notin;' => '&#8713;', 
  '&ni;' => '&#8715;', 
  '&prod;' => '&#8719;', 
  '&sum;' => '&#8721;', 
  '&minus;' => '&#8722;', 
  '&lowast;' => '&#8727;', 
  '&radic;' => '&#8730;', 
  '&prop;' => '&#8733;', 
  '&infin;' => '&#8734;', 
  '&ang;' => '&#8736;', 
  '&and;' => '&#8743;', 
  '&or;' => '&#8744;', 
  '&cap;' => '&#8745;', 
  '&cup;' => '&#8746;', 
  '&int;' => '&#8747;', 
  '&there4;' => '&#8756;', 
  '&sim;' => '&#8764;', 
  '&cong;' => '&#8773;', 
  '&asymp;' => '&#8776;', 
  '&ne;' => '&#8800;', 
  '&equiv;' => '&#8801;', 
  '&le;' => '&#8804;', 
  '&ge;' => '&#8805;', 
  '&sub;' => '&#8834;', 
  '&sup;' => '&#8835;', 
  '&nsub;' => '&#8836;', 
  '&sube;' => '&#8838;', 
  '&supe;' => '&#8839;', 
  '&oplus;' => '&#8853;', 
  '&otimes;' => '&#8855;', 
  '&perp;' => '&#8869;', 
  '&sdot;' => '&#8901;', 
  '&lceil;' => '&#8968;', 
  '&rceil;' => '&#8969;', 
  '&lfloor;' => '&#8970;', 
  '&rfloor;' => '&#8971;', 
  '&lang;' => '&#9001;', 
  '&rang;' => '&#9002;', 
  '&loz;' => '&#9674;', 
  '&spades;' => '&#9824;', 
  '&clubs;' => '&#9827;', 
  '&hearts;' => '&#9829;', 
  '&diams;' => '&#9830;'));

?>
