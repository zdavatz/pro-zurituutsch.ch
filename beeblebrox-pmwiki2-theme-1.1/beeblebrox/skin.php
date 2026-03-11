<?php
// Bring into scope in case needed below
global $pagename;

// The following code takes the Wiki Title and turns it into HTML that alternates between 
// black and white for each part of the WikiWord, if you hate this then define $PageLogoFmt
// in your config.php directly
global $PageLogoFmt, $PageLogoSpacer, $LogoTagline, $WikiTitle, $AsSpacedFunction;

SDV($PageLogoSpacer, '');

$spaced_title = preg_split('/[\\s]+/', $AsSpacedFunction($WikiTitle));
for ($title_index = 0; $title_index < count($spaced_title); $title_index++) {
  if($title_index % 2 == 0) {
    $html_title .= $spaced_title[$title_index] . $PageLogoSpacer;
  } else {
    $html_title .= "<span>" . $spaced_title[$title_index] . "</span>" . $PageLogoSpacer;
  }
}
SDV($LogoTagline, "Subtitle");
SDV($PageLogoFmt,"<a href='$ScriptUrl'>$html_title</a><br />
  <span id='tag'>$LogoTagline</span>");

// Copyright notice. For example set this to something like "&copy; 2005 PmWiki User"
global $CopyrightNotice;
SDV($CopyrightNotice, "&nbsp;");

// External links
$UrlLinkFmt = "<a class='urllink' href='\$LinkUrl' 
  rel='external'>\$LinkText</a><img src='images/external.png'/>";

// Link decoration
global $IMapLinkFmt, $LinkPageCreateFmt, $LinkUploadCreateFmt;
$IMapLinkFmt['Attach:'] =
  "<a class='attachlink' href='\$LinkUrl' rel='nofollow'>\$LinkText</a>";
$LinkUploadCreateFmt =
  "<a class='createlinkupload' href='\$LinkUpload'>\$LinkText</a>";
$LinkPageCreateFmt =
  "<a class='createlinktext' href='\$PageUrl?action=edit'>\$LinkText</a>";

// Define a login link if UserAuth is being used. If UserAuth is not used
// then the variable will just become empty text
global $LoginLink;
if( defined('USER_AUTH_VERSION') ) {
  $LoginLink = FmtPageName('<a href="$PageUrl?action=$LoginAction" accesskey="l">$LoginAction</a> &middot;', $pagename);
  global $action, $PageInlineStyle;
} 
else {
  $LoginLink = '';
}

// Set up buttons using CMSLike if it has been loaded
global $CmsLikeMenuItems, $CmsLikeMenuSep;
$CmsLikeMenuItems =
  array( 
	 'history'  => '<a href="$PageUrl?action=diff" accesskey="$[ak_history]">$[history]</a> &middot;',
	 'edit'     => '<a href="$PageUrl?action=edit" accesskey="$[ak_edit]">$[edit]</a> &middot;',
	 'print'    => '<a href="$PageUrl?action=print" rel="external">$[print]</a> ',
	 'upload'   => '&middot; <a href="$PageUrl?action=upload">$[upload]</a> &middot;',
	 'attr'     => '<a href="$PageUrl?action=attr">$[attributes]</a> &middot;',
	 'admin'    => '<a href="$PageUrl?action=admin">$[admin]</a> &middot;',
	 'pwchange' => '<a href="$PageUrl?action=pwchange">$[change pass]</a>'
	 );
$CmsLikeMenuSep = "\n";

// If CMSLike is not being used then define a function to display some buttons 
// that are likely to be accessible to users
if( !defined('CMSLIKE_VERSION') ) {

  function CmsLikeMenuFmt($pagename, $alt="") {
    global $CmsLikeMenuItems;

    PrintFmt($pagename, implode( "\n", 
				 array( 
				        $CmsLikeMenuItems['edit'],
					$CmsLikeMenuItems['history'],
					$CmsLikeMenuItems['print']
					)
				 )
	     );
  }
}
?>
