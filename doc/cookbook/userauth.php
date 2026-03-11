<?php if (!defined('PmWiki')) exit();

/*

 Username Authentification

 -- Copyright --

 Copyright 2004, 2005 by James McDuffie (ttq3sxl02@sneakemail.com)

 -- License --

 GNU GPL

 -- Description & Installation Instructions --

 See PmWiki UserAuth Cookbook for details
 http://www.pmwiki.org/wiki/Cookbook/UserAuth

 0.65  Dan Weber - Fixed a compatibility issue with PmWiki 2.1 and the login/logout actions
                   Added default password for upload, if not already set
                   Added full name for each user
                   Added support for user groups

 0.70  Dan Weber - Added login persistence option through cookies
                   Store encrypted password in _SESSION
                   

*/

define(USER_AUTH_VERSION, '0.70'); 

// First we need to load the default class that handles username and 
// passwords for the instance
if(!isset($UserInstanceVars)) {
  require_once("userauth/UserSessionVars.php");
  $UserInstanceVars = new UserSessionVars();
}

// Cookie settings
// Cookie default expiration in seconds
SDV($UserAuthCookieExp, 60*60*24*30);
SDV($UserAuthAllowCookie, true);

// Override PmWiki variables
$DefaultAuthFunction = $AuthFunction;
$Author = $UserInstanceVars->GetInstanceUsername();
$AuthFunction = "UserAuth";

// conditional variables that can be used in a wiki page
$Conditions['loggedin']="\$GLOBALS['IsUserLoggedIn']";
$Conditions['member']="IsUserGroupMember(\$condparm)";


// This variable will be set to true once the user has been verified
$IsUserLoggedIn = false;

// Configuration that your probably do not want to change
$HandleActions['logout'] = 'HandleLogout';
$HandleActions['login'] = 'HandleLogin';

SDV($LoginAction, GetLoginAction());
SDV($LoginName, ucfirst(GetLoginAction()));

// default password
SDV($DefaultPasswords['upload'], '');

// Customizable configuration variable
SDV($GuestUsername, "GuestUser");
SDV($LoggedInUsername, "LoggedInUser");

SDV($LoginPage, "Main.LoginPage");

SDV($LackProperAbilitiesFmt, "Insufficient privileges to perform action.");
SDV($WrongUserFmt, "Username does not exist.");
SDV($WrongPasswordFmt, "Wrong password supplied.");
SDV($NoGroupLoginFmt, "Login as user group is not allowed.");

SDV($RedirectToPrevious, true);
SDV($DefaultRedirectionPage, "Main.HomePage");

Markup("loginform", "_end", "/\\(:loginform:\\)/", GetUserLoginForm(true));

# HtPasswd will be used unless the $UserInfoObj is already set.
# This allows someone to write a new user info object that gets
# its information from a database or some other format. This new
# class would only need to implement the same contract that
# HtPasswd.php does
if(!isset($UserInfoObj)) {
  require_once("userauth/HtPasswd.php");

  SDV($HtPasswdFile, getcwd() . "/local/.htpasswd");

  # If htpassword file does not exist then create a default empty one
  if (!file_exists($HtPasswdFile)) {
    $fp = @fopen($HtPasswdFile, "w");
    if ($fp) {
      fwrite($fp, "$GuestUsername::Guest User:read\n");
      fclose($fp);
    }
  }

  // Object which contains username, password and abilities info
  $UserInfoObj = new HtPasswd($HtPasswdFile);

}
if(strlen($UserInfoObj->GetUserFullname($Author)) > 0) {
  $Author = $UserInfoObj->GetUserFullname($Author);	
}


function UserAuth($pagename, $level, $authprompt=true) {
  global $UserInstanceVars, $UserInfoObj; 
  global $GuestUsername, $HtPasswdFile, $IsUserLoggedIn;

  // Get username from instance class
  $auth_username = $UserInstanceVars->GetInstanceUsername();

  // If no username has been specified then use a default name
  if ($auth_username=="") { $auth_username = $GuestUsername; }

  $userauth_check = 
    CheckUserAuthPassword( $auth_username, $UserInstanceVars->GetInstancePassword(), 
			   $level, $pagename, $authprompt );

  // If user auth has succeeded, we try to see if the user auth password is the
  // same as the pmwiki auth one, so we don't have to ask it twice
  $userpw_wiki_check = false;
  if ($userauth_check) {
    $userpw_wiki_check =
      CheckPmWikiPassword( $UserInstanceVars->GetInstancePassword(), 
			   $level, $pagename );
  }

  // If the user auth password doesn't work for pmwiki auth, then we try the
  // pmwiki auth password
  //
  // $_REQUEST is used since PmWiki's BasicAuth manages storing of Wiki passwords
  $wikipw_wiki_check = false;
  if (!$userpw_wiki_check) {
    $wikipw_wiki_check =
      CheckPmWikiPassword( $_REQUEST['authpw'], $level, $pagename );
  }

  // Admin overrides everything even if a password is already specified
  //
  // "all" abilities only override their particular level type, they are
  // pseudo admin abilities.
  // 
  // Otherwise a userauth and pmwiki authentification check must succeed
  if ( ($userauth_check && $UserInfoObj->UserHasAbility($auth_username, 'admin')) ||
       ($userauth_check && $UserInfoObj->UserHasAbility($auth_username, $level . '_all')) ||
       ($userauth_check && ($userpw_wiki_check || $wikipw_wiki_check)) ) {


    $page = ReadPage($pagename);
    if (!$page) { return false; }

    return $page;
  }

  // Return if we are not supposed to display any prompts, 
  // ie if called from another module
  if (!$authprompt) return false;

  if ( !$userauth_check ) {
    // Remove wrong login information from session
    $UserInstanceVars->ClearInstanceVariables();

    HandleLogin( $pagename );
  } elseif ( !$wiki_check ) {
    PrintEmbeddedPage( $pagename, GetWikiPasswordForm($pagename) );
  }

  // Exit since we either printed a login page, or an embedded wiki password
  // page above
  exit;
}



function CheckUserAuthPassword($auth_username, $auth_password, $level, $pagename, $authprompt) {
  global $GuestUsername, $LoggedInUsername, $UserInfoObj;
  global $LackProperAbilitiesFmt, $NoGroupLoginFmt, $WrongUserFmt, $WrongPasswordFmt;
  global $UserInstanceVars, $IsUserLoggedIn;

  // make sure nobody tries to login as user group
  if($auth_username{0} == '@') {
    $_SESSION['auth_message'] = $NoGroupLoginFmt;
    return false;
  }
  

  // Access username from htpasswd file and see if level and password match
  $stored_password = $UserInfoObj->GetUserPassword($auth_username);

  // Retrieve group name for group specific permissions
  $name  = FmtPageName('$Name',$pagename);
  $group = FmtPageName('$Group',$pagename);

  // Normally the userauth level to check the UserInfoObj is the same
  // as specified by PmWiki to UserAuth
  $userauth_level = $level;

  // Change checked level if group specific abilities exist user info object
  $group_specific_level = $level . "_group-" . $group;
  if($UserInfoObj->AnyUserHasAbility($group_specific_level)) {
    $userauth_level = $group_specific_level;
  }

  // Change checked level if page specific abilities exist user info object
  // page level abilities win over group level abilities
  $page_specific_level = $level . "_page-" . $pagename;
  if($UserInfoObj->AnyUserHasAbility($page_specific_level)) {
    $userauth_level = $page_specific_level;
  }

  // Only allow an empty password for the guest user
  if ( $auth_username == $GuestUsername && 
       $stored_password == "" && $auth_password == "" ) {

    if ($UserInfoObj->UserHasAbility($GuestUsername, $userauth_level)) return true;

  } elseif ($auth_password == $stored_password) {


    // Set variable that can be used for conditional markup
    if($UserInstanceVars->GetInstanceUsername() != ''){
       $IsUserLoggedIn = true;
    }

    if ($UserInfoObj->UserHasAbility($auth_username, 'admin')) return true;
    if ($UserInfoObj->UserHasAbility($GuestUsername, $userauth_level)) return true;
    if ($UserInfoObj->UserHasAbility($LoggedInUsername, $userauth_level)) return true;
    if ($UserInfoObj->UserHasAbility($auth_username, $userauth_level)) return true;
    if ($UserInfoObj->UserHasAbility($auth_username, $level . '_all')) return true;

    // If we are not supposed to display prompts then just return false
    if(!$authprompt) return false;

    // If the above failed then we need to tell the user the action is not
    // possible, but not because of a bad password
    PrintEmbeddedPage( $pagename, $LackProperAbilitiesFmt );
    exit;

  } else {
    if($UserInfoObj->DoesUserExist($auth_username)) {
      $_SESSION['auth_message'] = $WrongPasswordFmt;
    }
    else {
      $_SESSION['auth_message'] = $WrongUserFmt;
    }
  }

  return false;
}



function CheckPmWikiPassword($page_passwd, $level, $pagename) {
  global $UserInfoObj, $DefaultAuthFunction;

  // Test wiki password using PmWiki's builtin BasicAuth
  if(isset($DefaultAuthFunction)) {
    return $DefaultAuthFunction($pagename, $level, false);
  } else {
    return PmWikiAuth($pagename, $level, false);
  }
}


function PrintEmbeddedPage($pagename, $prompt) {
  global $PageStartFmt, $PageEndFmt, $AuthFunction;

  // Temporarily disable authentification or else its possible
  // that PrintFmt could go into an infinite recursive calls of UserAuth
  // when the user does not have any permissions at all
  // The worst that can happen is a user will see any pages included
  // by the template.
  $OrigAuthFunction = $AuthFunction;
  $AuthFunction = "true";

  $page = ReadPage($pagename);
  PCache($pagename,$page);

  $AuthEmbeddedFmt = array( &$PageStartFmt,
			    $prompt,
			    &$PageEndFmt);

  PrintFmt($pagename, $AuthEmbeddedFmt);

  // Go back to the original auth function just in case there are any
  // calls after this function
  $AuthFunction = $OrigAuthFunction;
}

function PrintStandalonePage($prompt) {
  global $HTMLStartFmt, $HTMLEndFmt;

  $AuthEmbeddedFmt = array( &$HTMLStartFmt,
			    $prompt,
			    &$HTMLEndFmt);

  PrintFmt($pagename, $AuthEmbeddedFmt);
}

function GetUserLoginForm($no_title) {
  global $UserInstanceVars, $Author, $WikiTitle, $pagename, $UserAuthAllowCookie;

  if( $UserInstanceVars->GetInstanceUsername() == "" ) {

    if (!$no_title) {
      $login_form .= "<h1>$[Login to] $WikiTitle</h1>";
    }
    
    if ($_SESSION['auth_message']) {
      $login_form .= $_SESSION['auth_message'] . "<P>\n";
      $_SESSION['auth_message'] = '';
    }

    $login_form .=
      "<form name='authform' action='{$_SERVER['REQUEST_URI']}?action=login' method='post'>
       <table>

       <tr>
           <td>$[Username]:</td>
           <td><input name='username' class='userauthinput' value='$author' /></td>
       </tr>

       <tr>
           <td>$[Password]:</td>
           <td><input name='user_pw' class='userauthinput' type='password' value='' /></td>
       </tr>";
       
    if($UserAuthAllowCookie) {
      $login_form .= "   
       <tr>
           <td>$[Keep me logged in]:</td>
           <td><input name='persistent' class='userauthinput' type='checkbox' value='1' /></td>
       </tr>";
    }
    
    $login_form .= "
       <tr>
           <td align=left><input class='userauthbutton' type='submit' value='$[Login]' /></td>
           <td>&nbsp;</td> 
       </tr>
       </table>
       </form>";

  } else {
    $login_form = 
      "$[Logged in as]: " . $Author . "<br>
       <a href=\"" . $_SERVER['REQUEST_URI'] . "?action=logout\">$[Logout]</a>";
    
  }

  return FmtPageName($login_form, $pagename);
}

function GetWikiPasswordForm($pagename) {
  global $pagename;

  $password_form .=
    "<h1>$[Enter password for] $pagename</h1>
     <form name='authform' action='{$_SERVER['REQUEST_URI']}' method='post'>
     <table>

     <tr>
         <td>$[Password]:</td>
         <td><input name='authpw' class='userauthinput' type='password'  value='' /></td>
     </tr>
     <tr>
         <td align=left><input class='userauthbutton' type='submit' value='$[Submit]' /></td>
         <td>&nbsp;</td> 
     </tr>
     </table>
     </form>";

  return FmtPageName($password_form, $pagename);
}

function GetLoginAction() {
  global $UserInstanceVars;

  if( $UserInstanceVars->GetInstanceUsername() == "" ) {
    return "login";
  } else {
    return "logout";
  }

}

function HandleLogin($pagename) {
  global $UserInstanceVars, $UserInfoObj, $GuestUsername, $LoginPage;
  global $RedirectToPrevious, $DefaultRedirectionPage, $UserAuthAllowCookie;
  
  if (@$_REQUEST['username']) {
    $UserInstanceVars->SetInstanceUsername($_REQUEST['username']);
    if($UserInfoObj->DoesUserExist($_REQUEST['username'])) {
      $UserInstanceVars->SetInstancePassword(crypt($_REQUEST['user_pw'], $UserInfoObj->GetUserPassword($_REQUEST['username'])));
    }
    else {
      // set and invalid password, which will fail the username/password test later on and display the
      // proper message
      $UserInstanceVars->SetInstancePassword("x");
    }
    if(@$_REQUEST['persistent'] && $UserAuthAllowCookie) {
      $UserInstanceVars->SetUserCookie();
    }
    else {
      $UserInstanceVars->ClearUserCookie();
    }
  }


  if( $UserInstanceVars->GetInstanceUsername() != "" ) {
    // Only redirect to the page that called the login method originally
    // if the $RedirectToPrevious configuration variable is set
    if ( $RedirectToPrevious && isset($_SESSION['previous_page'])) {
      $prev_page = $_SESSION['previous_page'];
      unset($_SESSION['previous_page']);

      // Redirect to a URL if we have a referrer else go to
      // the page name we are handling login for
      if (preg_match("/^http/i", $prev_page)) {
	RedirectURL($prev_page);
      } else {
	Redirect($prev_page);
      }

    } else {
      // If a user defined default redirection is set then redirect to that
      // otherwise redirect to whatever pagename was passed to this method
      if(isset($DefaultRedirectionPage)) {
	Redirect($DefaultRedirectionPage);
      } else {
	Redirect($pagename);
      }
    }
  } else {
    // Remember the page that called for login, either by 
    // tracking the referer or by going to the page name
    // whom the login action was called on
    if ($pagename != $LoginPage) {
      if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != "") {
	$_SESSION['previous_page'] = $_SERVER['HTTP_REFERER'];
      } else {
	$_SESSION['previous_page'] = $pagename;
      }
    }
    
    // Make sure both that a login page is defined and that we
    // are not currently at the login page. If no default abilities
    // are defined then the system would keep redirecting to the
    // login page since it is handled by PmWiki. Therefore if the
    // current page is login page when a login action is requested, we
    // just print out the backup standalone page.
    if (PageExists($LoginPage)) {
      // Redirect to the login page to display username password form,
      // but if the Wiki is totally restricted and $LoginPage is still
      // defined then print a simple standalone page, because if we
      // did redirect when the GuestUser had no read ability then we
      // would keep on redirect so long as the browser lets us!
      if($UserInfoObj->UserHasAbility($GuestUsername, "read")) { 
	Redirect($LoginPage);
      } else {
	PrintStandalonePage( GetUserLoginForm(false) );
      }
    } else {
      PrintEmbeddedPage( $pagename, GetUserLoginForm(false) );
    }
  }
}

function HandleLogout($pagename) {
  global $UserInstanceVars, $RedirectToPrevious, $DefaultRedirectionPage;

  // Clear UserAuth instance variables
  $UserInstanceVars->ClearInstanceVariables();

  // PmWiki BasicAuth password storeage array
  unset( $_SESSION['authpw'] );

  // Fully handle logout
  session_destroy();

  // Use referer for redirection if it is available
  if ( $RedirectToPrevious && 
       isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != "" ) {

    RedirectURL($_SERVER['HTTP_REFERER']);

  } else {
    if(isset($DefaultRedirectionPage)) {
      Redirect($DefaultRedirectionPage);
    } else {
      Redirect($pagename);
    }
  }
}

function RedirectURL($url) {
  global $RedirectDelay;
  clearstatcache();

  header("Location: $url");
  header("Content-type: text/html");
  print("<html><head>
    <meta http-equiv='Refresh' Content='$RedirectDelay; URL=$url'>
    <title>Redirect</title></head><body></body></html>");
  exit;
}

function IsUserGroupMember($group) {
  global $UserInstanceVars, $UserInfoObj; 

  $auth_username = $UserInstanceVars->GetInstanceUsername();
  // If no username has been specified then use a default name
  if ($auth_username=="") { $auth_username = $GuestUsername; }

  $g = $group;
  if($g{0} != '@') $g = "@$group";
  
  return $UserInfoObj->UserIsGroupMember($auth_username, $g);
  
}

?>
