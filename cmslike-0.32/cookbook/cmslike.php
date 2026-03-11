<?php if (!defined('PmWiki')) exit();
/*
	The cmslike.php script makes PmWiki behave as a CMS.
	It complements UserAuth by presenting only the actions the current user is
	allowed to perform. It works with a single template, by inserting a dynamic
	actions menu as a variable, and disables the edit links in the text whenever
	editing is not allowed.

	Version 0.32 (development version)

	Copyright 2005 Didier Lebrun <dl@vaour.net>
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published
	by the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	Requirements:
	* pmwiki-2.0.beta26 or above; might work with earlier versions, but not tested
	* userauth.php MUST be loaded
	* userauth-0.62.php or above is needed

	Usage:
	* copy cmslike.php in the cookbook directory
	* add the following lines in your local/config.php file and adjust them to your taste:
		#$CmsLikeMenuItems = array(
		#	'print' => '<a href="$PageUrl?action=print" target="_blank" rel="nofollow">$[Printable View]</a>',
		#	'history' => '<a href="$PageUrl?action=diff" rel="nofollow">$[Page History]</a>',
		#	'edit' => '<a href="$PageUrl?action=edit" rel="nofollow">$[Edit Page]</a>',
		#	'upload' => '<a href="$PageUrl?action=upload" rel="nofollow">$[Upload]</a>',
		#	'attr' => '<a href="$PageUrl?action=attr" rel="nofollow">$[Attributes]</a>',
		#	'admin' => '<a href="$PageUrl?action=admin" rel="nofollow">$[UserAuth Admin]</a>',
		#	'pwchange' => '<a href="$PageUrl?action=pwchange" rel="nofollow">$[Change Password]</a>') );
		#$CmsLikeMenuSep = ' ';
		#$CmsLikeAltMenuItems = $CmsLikeMenuItems;
		#$CmsLikeAltMenuSep = ' - ';
		include_once('cookbook/cmslike.php'); # the module itself (you need this line at least !)
	* in your template:
		* replace your static actions menu with <!--function:CmsLikeMenuFmt--> for the standard menu setup
		  ... or with <!--function:CmsLikeMenuFmt alt--> for the alternate menu setup

	Notes:
	* UserAuth interception is used for calling CmsLikeMakeActionsList when the
	group is known, so as to establish the relevant rights. The conditions
	reproduce UserAuth ones.
	* <!--function:CmsLikeMenuFmt[ alt]--> in the template is used to evaluate
	the variables when they are fully defined.
	* You can use the "alt" optional arg to get the alternate menu setup instead
	of the standard (default) one, so you can have 2 different layouts in the
	same page (ex: a vertical one and an horizontal one like those in PmWiki
	default template)

	History:
	* 0.1	- Initial version

	* 0.2	- Change $_SESSION[...] calls to $UserInstanceVars->... (see userauth-0.6 changes)
			- Added page level auth conditions (see userauth-0.6 changes)
			- Rewrote auth processing to be cleaner and more adaptative to changes
			- Added Admin Tool and Change Password actions (see userauth-admintool and userauth-pwchange)
			- Change the template tags to <!--function:CmsLikeMenuFmt[ alt]--> (adjust your template !)
			- Fixed a potential bug when the configured $CmsLikeMenuItems lacks some actions
			- Change $AuthFunction interception so has to intercept only the first call

	* 0.3	- Delegate auth processing to UserAuth (thanks to JamesMcDuffie)
			- Restore create link disabling depending on edit rights (disappeared by mistake in 0.2 !)
			- Keep $CmsLikeMenuItems and $CmsLikeAltMenuItems configured order for display

	* 0.31	- Fixed create link disabling
	* 0.32	- Fixed create link disabling on spaced words

*/

if (!defined('USER_AUTH_VERSION')) Abort('CMSLike requires UserAuth to be loaded');
define(CMSLIKE_VERSION, '0.32');

# You can override those in your local/config.php
SDV($CmsLikeMenuItems, array(
	'print' => '<a href="$PageUrl?action=print" target="_blank" rel="nofollow">$[Printable View]</a>',
	'history' => '<a href="$PageUrl?action=diff" rel="nofollow">$[Page History]</a>',
	'edit' => '<a href="$PageUrl?action=edit" rel="nofollow">$[Edit Page]</a>',
	'upload' => '<a href="$PageUrl?action=upload" rel="nofollow">$[Upload]</a>',
	'attr' => '<a href="$PageUrl?action=attr" rel="nofollow">$[Attributes]</a>',
	'admin' => '<a href="$PageUrl?action=admin" rel="nofollow">$[UserAuth Admin]</a>',
	'pwchange' => '<a href="$PageUrl?action=pwchange" rel="nofollow">$[Change Password]</a>') );
SDV($CmsLikeMenuSep, ' ');
# Alternate menu layout, so you can have 2 different menu layouts in your page
SDV($CmsLikeAltMenuItems, $CmsLikeMenuItems);
SDV($CmsLikeAltMenuSep, ' - ');

# Standard level=>actions mapping
$CmsLikeActionsList = array('print');
$CmsLikeLevelToActions = array(
	'admin' => array('history', 'edit', 'upload', 'attr'),
	'edit' => array('history', 'edit'),
	'upload' => array('upload'),
	'attr' => array('attr') );

# AdminTool extension
if (defined('UA_ADMINTOOL'))
	array_push($CmsLikeLevelToActions['admin'], 'admin');

# PwChange extension
if (defined('UA_PWCHANGE')) {
	$CmsLikeLevelToActions['pwchange'] = array('pwchange');
	array_push($CmsLikeLevelToActions['admin'], 'pwchange');
}

# Intercept the first AuthFunction call
$AuthFunction = "CmsLikeAuth";
function CmsLikeAuth($pagename, $level, $authprompt) {
	global $AuthFunction;

	# We build the list on the first AuthFunction call
	CmsLikeMakeActionsList($pagename);
	# ... then we let the calls go directly to UserAuth()
	$AuthFunction = "UserAuth";

	# Redirect to the real UserAuth function
	return UserAuth($pagename, $level, $authprompt);
}

# Build the authorized actions list
function CmsLikeMakeActionsList($pagename) {
	global $CmsLikeLevelToActions, $LinkPageCreateFmt, $LinkPageCreateSpaceFmt, $CmsLikeActionsList;

	foreach($CmsLikeLevelToActions as $level=>$actions) {

		# Auth processing is delegated to UserAuth
		if( UserAuth($pagename, $level, false) ) {
			foreach($actions as $action)
				if (!in_array($action, $CmsLikeActionsList))
					array_push($CmsLikeActionsList, $action);
		}
		# Disables create links in the text when the user doesn't have edit rights
		elseif ($level=='edit') {
			$LinkPageCreateFmt = "<a class='createlinktext'>\$LinkText</a><a class='createlink'>?</a>";
			$LinkPageCreateSpaceFmt = $LinkPageCreateFmt;
		}
		# Stop as soon as we've got all actions set
		if ( count($CmsLikeActionsList) == 1 + count($CmsLikeLevelToActions['admin']) ) break;
	}
}

# Called by the template with <!--function:CmsLikeMenuFmt[ alt]-->
# "alt" is an optional parameter, for sending the alternate menu instead of the standard one
function CmsLikeMenuFmt($pagename, $alt="") {
	global $CmsLikeActionsList;
	$items_fmt = array();

	# Alternate menu
	if ($alt=='alt') {
		global $CmsLikeAltMenuItems, $CmsLikeAltMenuSep;
		foreach($CmsLikeAltMenuItems as $action=>$fmt)
			if ( in_array($action, $CmsLikeActionsList) )
				array_push($items_fmt, $fmt);
		PrintFmt($pagename, implode("\n".$CmsLikeAltMenuSep, $items_fmt));
	}

	# Standard menu
	else {
		global $CmsLikeMenuItems, $CmsLikeMenuSep;
		foreach($CmsLikeMenuItems as $action=>$fmt) {
			if ( in_array($action, $CmsLikeActionsList) )
				array_push($items_fmt, $fmt);
		}
		PrintFmt($pagename, implode("\n".$CmsLikeMenuSep, $items_fmt));
	}
}

?>
