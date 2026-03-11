<?php if (!defined('PmWiki')) exit(); 
      if (!constant('USER_AUTH_VERSION') >= 0.5) exit();

/*

 UserAuth Password Change Interface

 -- Copyright --

 Copyright 2005 by James McDuffie (ttq3sxl02@sneakemail.com)

 -- License --

 GNU GPL

 -- Description --

 This module allows a user to change his userauth password using a form.
 To get to the form you need to append ?action=pwchange on to the url for
 any page. A form will show up allowing the user to submit a new password.

 The user must have a 'pwchange' ability defined for him in order to be
 able to change his password. 
 The 'pwchange' ability can be set for the user defined by the 
 $LoggedInUsername variable in userauth.php. This user is a global user
 that defines abilities for all logged in users.
 
   
 -- Installation Instructions --

 * Include the plugin from your config.php file after userauth.php has been
   setup:

   require_once("cookbook/userauth-pwchange.php");

 * If using HtPasswd.php as your UserInfoObj class then add the ability
   'pwchange' to the users who you wish to be able to change their password.
   Apply this ability to the LoggedInUser so that all logged in users can
   change their password.

 -- History --

 0.1 - Initial version
 0.2 - Added $[internationalization] substitutions
 0.3 - Added default password, if not already set

 -- Configuration --

*/

define(UA_PWCHANGE, '0.3'); 

// default password
SDV($DefaultPasswords['pwchange'], '');


// Configuration that your probably do not want to change
SDV($HandleActions['pwchange'], 'HandlePasswordChange');

// Customizable configuration variables
SDV($PasswordMismatchFmt, 'The submitted passwords do not match.');
SDV($PasswordChangeSuccessFmt, 'Passwords change successful.');


function HandlePasswordChange($pagename) {
  // These variables are all defined by userauth.php
  global $UserInfoObj, $LackProperAbilitiesFmt;

  // userauth-pwchange.php variables
  global $PasswordMismatchFmt, $PasswordChangeSuccessFmt;

  if( UserAuth($pagename, 'pwchange') ) {

    if( $_REQUEST['newpassword1'] != '' && $_REQUEST['newpassword2'] != '' ) {

      // Ensure that the repeated password matches
      if($_REQUEST['newpassword1'] == $_REQUEST['newpassword2']) {

	// The user has passed the abilities check and the 
	// repeated password check so now get the password changed
	$UserInfoObj->SetUserPassword($_SESSION['username'], 
				      $_REQUEST['newpassword1']);

	$UserInfoObj->PublishChanges();

	// Update the user's session password so that
	// The user stays logged in with the new password
	$_SESSION['user_pw'] = $_REQUEST['newpassword1'];

	PrintEmbeddedPage( $pagename, $PasswordChangeSuccessFmt );
	exit;

      } else {
	// Tell the user the passwords do not match
	PrintEmbeddedPage( $pagename, $PasswordMismatchFmt );
	exit;
      }

    } else {
      // Present the password change form
      PrintEmbeddedPage( $pagename, GetPasswordChangeForm() );
      exit;
    }

  } else {
    // Tell the user they can not perform this action
    PrintEmbeddedPage( $pagename, $LackProperAbilitiesFmt );
    exit;
  }

}

function GetPasswordChangeForm() {
  global $pagename;

  $pwchange_form .=
    "<form name='authform' action='{$_SERVER['REQUEST_URI']}' method='post'>
       <table>

       <tr>
           <td>$[Enter new password]:</td>
           <td><input name='newpassword1' class='userauthinput' type='password'/></td>
       </tr>

       <tr>
           <td>$[Repeat new password]:</td>
           <td><input name='newpassword2' class='userauthinput' type='password' /></td>
       </tr>
       <tr>
           <td align=left><input class='userauthbutton' type='submit' value='$[Change]' /></td>
           <td>&nbsp</td> 
       </tr>
       </table>
       </form>";

  return FmtPageName($pwchange_form, $pagename);
}

?>
