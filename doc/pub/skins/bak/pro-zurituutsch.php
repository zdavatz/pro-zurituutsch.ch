<?php if (!defined('PmWiki')) exit();
/**
* This is lean.php, the php script portion of the Lean Skin for PmWiki 2.
*/

## Use GUI buttons on edit pages, including add some extra buttons.
global $EnableGUIButtons, $GUIButtons;
$EnableGUIButtons = 1;
$GUIButtons['h3'] = array(402, '\\n!!! ', '\\n', '$[Subheading]',
                     '$GUIButtonDirUrlFmt/h3.gif"$[Subheading]"');
$GUIButtons['indent'] = array(500, '\\n->', '\\n', '$[Indented text]',
                     '$GUIButtonDirUrlFmt/indent.gif"$[Indented text]"');
$GUIButtons['ul'] = array(530, '\\n* ', '\\n', '$[Unordered list]',
                     '$GUIButtonDirUrlFmt/ul.gif"$[Unordered (bullet) list]"');
$GUIButtons['ol'] = array(520, '\\n# ', '\\n', '$[Ordered list]',
                     '$GUIButtonDirUrlFmt/ol.gif"$[Ordered (numbered) list]"');
$GUIButtons['table'] = array(600,
                     '(:table border=1 width=80%:)\\n(:cell style=\'padding:5px\;\':)\\n1a\\n(:cell style=\'padding:5px\;\':)\\n1b\\n(:cellnr style=\'padding:5px\;\':)\\n2a\\n(:cell style=\'padding:5px\;\':)\\n2b\\n(:tableend:)\\n', '', '',
                     '$GUIButtonDirUrlFmt/table.gif"$[Table]"');
#$GUIButtons['table'] = array(600,
#                     '||border=1 width=80%\\n||!Hdr ||!Hdr ||!Hdr ||\\n||     ||     ||     ||\\n||     ||     ||     ||\\n', '', '',
#                     '$GUIButtonDirUrlFmt/table.gif"$[Table]"');

## Add a (:noleft:) directive.
Markup('noleft','directives','/\\(:noleft:\\)/e',
  "PZZ(\$GLOBALS['PageLeftFmt']='')");

## Add a (:noright:) directive.
Markup('noright','directives','/\\(:noright:\\)/e',
  "PZZ(\$GLOBALS['PageRightFmt']='')");

# Link back to the page from Edit and History pages.
global $SkinPageLinkPreFmt, $SkinPageLinkPostFmt, $SkinHideHead, $SkinHideSide,
  $SkinPageTitlePre, $SkinPageTitlePost;
if (@in_array($_GET['action'], array('edit', 'diff'))) {
    $SkinPageTitlePre    = '';
    $SkinPageTitlePost   = '';
    $SkinPageLinkPreFmt  = "$[Return to] <a href='\$PageUrl'>";
    $SkinPageLinkPostFmt = "</a> &nbsp;(<a
       href='\$PageUrl?action=edit'>$[Edit]</a>)";
    $SkinHideHead        = "style='display:none;'";
    $SkinHideSide        = "style='display:none;'";
} else {
    $SkinPageTitlePre    = '<h1>';
    $SkinPageTitlePost   = '</h1>';
    $SkinPageLinkPreFmt  = "<span style='display:none;'>";
    $SkinPageLinkPostFmt = '</span>';
    $SkinHideHead        = '';
    $SkinHideSide        = '';
}
if (@$_GET['action'] == 'edit' ) {
    $SkinPageLinkPostFmt  = "</a> &nbsp;(<a
       href='\$PageUrl?action=diff'>$[History]</a>)";
}

# Add hotkeys to the page we're editing.
global $PageEditFmt;
$PageEditFmt = "<div id='wikiedit'>
  <a id='top' name='top'></a>
  <h1 class='wikiaction'>$[Editing] \$FullName</h1>
  <form name='editform' method='post' action='\$PageUrl?action=edit'>
  <input type='hidden' name='action' value='edit' />
  <input type='hidden' name='pagename' value='\$FullName' />
  <input type='hidden' name='basetime' value='\$EditBaseTime' />
  \$EditMessageFmt
  <textarea id='text' name='text' rows='25' cols='60'
    onkeydown='if (event.keyCode==27) event.returnValue=false;'
    >\$EditText</textarea><br />
  $[Author]: <input type='text' name='author' value='\$Author' />
  <input type='checkbox' name='diffclass' value='minor' \$DiffClassMinor />
    $[This is a minor edit]<br />
  <input type='submit' name='post' value=' $[Save] ' accesskey='s' />
  <input type='submit' name='preview' value=' $[Preview] ' accesskey='p' />
  <input type='reset' value=' $[Reset] ' /></form></div>";

# Don't link to the Home Page when we're on it.
# Works for both a logo link ($SkinLogoHomeLink) or a text link ($SkinTextHomeLink).
global $DefaultPage, $ScriptUrl, $SkinLogoHomeLink, $SkinTextHomeLink, $WikiTitle;
if ($pagename=='' || $pagename==$DefaultPage) {
  $SkinLogoHomeLink = "<img src='\$SkinDirUrl/benfay.jpg'
        alt='$WikiTitle' border='0' />";
  $SkinTextHomeLink = "$WikiTitle";
} else {
  $SkinLogoHomeLink = "<a href='\$ScriptUrl'><img
        src='\$SkinDirUrl/benfay.jpg'
        alt='$WikiTitle' border='0' /></a>";
  $SkinTextHomeLink = "<a href='\$ScriptUrl'>$WikiTitle</a>";
}

## Don't link to the Group.Group page if it's already the curren page.
global $ScriptUrl, $GroupHdrFmt;
$page_array = explode('.',$pagename);
if ($pagename=='' || $pagename==$DefaultPage || $page_array['0']==$page_array['1']) {
 $GroupHdrFmt = "\$Groupspaced";
} else {
 $GroupHdrFmt = "<a href='\$ScriptUrl/\$Group'
          title='\$Groupspaced \$[Home]'>\$Groupspaced</a>";
}

# Copyright notice
# TODO Language $[settings] don't work
global $SkinCopyright;
/*
$Copyright = "All text is available under the terms of the
          <a href='http://www.gnu.org/copyleft/fdl.html'
           title='GNU FDL Home'>GNU Free Documentation License</a>";
*/

if (isset($Copyright)) {
  $SkinCopyright = "<span id='copyright' title='Copyright notice'>$Copyright</span>";
} else {
  $SkinCopyright = '';
}

?>
