<?php if (!defined('PmWiki')) exit(); 
      if (!constant('USER_AUTH_VERSION') >= 0.5) exit();

/*

 UserAuth Admin Tool

 -- Copyright --

 Copyright 2005 by James McDuffie (ttq3sxl02@sneakemail.com)

 -- License --

 GNU GPL

 -- Description --

 This module adds the ability to use a web based administrative tool to
 edit users, passwords and abilities. The administrative tool can only be
 accessed by users who have the admin ability defined in the UserInfoObj.
 To access the administrative tool then add the following to any page when
 logged in as an admin user: ?action=admin
   
 -- Installation Instructions --

 * Include the plugin from your config.php file after userauth.php has been
   setup:

   require_once("cookbook/userauth-admintool.php");


 -- History --

 0.1 - Initial version
 0.2 - Added abilites help text to edit and new user pages
       Added $[internationalization] substitutions
 0.3 - Use SCRIPT_URL instead of PHP_SELF for constructing admin too URL
 0.4 - Added support for user groups
 0.5 - Enhanced the look and functionality of the admin interface

 -- Configuration --

*/

define(UA_ADMINTOOL, '0.5'); 

// Customizable configuration variables
SDV($AdminToolAction, 'admin');
SDV($AdminActionQueryString, 'aa');

// Also could be defined by userauth-pwchange.php
SDV($PasswordMismatchFmt, 'The submitted passwords do not match.');
SDV($UserAlreadyExistsFmt, 'User already exists.');
SDV($GroupAlreadyExistsFmt, 'Group already exists.');

// Configuration that your probably do not want to change
$HandleActions[$AdminToolAction] = 'HandleAdminTool';
$AdminToolMainUrl = $_SERVER['SCRIPT_URL'] . "?action=" . $AdminToolAction;
$AdminQueryUrlPrefix = $AdminToolMainUrl . "&" . $AdminActionQueryString . "=";

SDV($AbilitiesHelpFmt, "
<p>In the help below {action} refers to any level or action PmWiki might pass to UserAuth.</p>
<br/>
<p>Possible abilities:</p>
<ul>
  <li>admin - Can perform basically any action.</li>
  <li>{action}_all - Allows the user to perform action on any page.</li>
  <li>{action}_group-{GroupName} - Allows the user to perform action on all pages in GroupName.</li>
  <li>{action}_page-PageName - Allows user to perform action on a specific PageName.</li>
</ul>
");
SDV($GroupAbilitiesHelpFmt, "
<p>An ability can also be a reference to a user group @{UserGroup}, 
in which case the user inherits the abilities of the referenced user group.</p>
");


function HandleAdminTool($pagename) {
  // PmWiki variables
  global $Action;

  // These variables are all defined by userauth.php
  global $UserInfoObj, $LackProperAbilitiesFmt;

  // userauth-admintool.php variables
  global $AdminActionQueryString;

  $Action = 'UserAuth Administration';

  if( UserAuth($pagename, 'admin') ) {
    $admin_action = $_REQUEST[$AdminActionQueryString];

    if ($admin_action == 'add') {
      HandleAddEditUser($pagename, 'add', false);
    } elseif ($admin_action == 'edit') {
      HandleAddEditUser($pagename, 'edit', false);
    } elseif ($admin_action == 'addgroup') {
      HandleAddEditUser($pagename, 'add', true);
    } elseif ($admin_action == 'editgroup') {
      HandleAddEditUser($pagename, 'edit', true);
    } elseif ($admin_action == 'del') {
      HandleDelUser($pagename);
    } elseif ($admin_action == 'report') {
      HandleUserReport($pagename);
    } else {
      PrintAdminToolPage( $pagename, GetActionChoicePage() );
      exit;      
    }

  } else {
    // Tell the user they can not perform this action
    PrintAdminToolPage( $pagename, $LackProperAbilitiesFmt );
    exit;
  }

}

function HandleAddEditUser($pagename, $method, $groupaction) {
  global $UserInfoObj, $AdminToolMainUrl;
  global $PasswordMismatchFmt, $UserAlreadyExistsFmt;

  $username = $_REQUEST['tool_username'];

  if($_REQUEST['tool_perform']) {
	  
    if(isset($_REQUEST['tool_cancel'])) {
      RedirectURL($AdminToolMainUrl);
    }
	  
    if($groupaction && ($username{0} != '@')) {
      $username = '@' . $_REQUEST['tool_username'];
    }

    $fullname      = $_REQUEST['tool_fullname'];	  
    $abilities_str = $_REQUEST['tool_abilities'];
    if($groupaction) {
      $new_pass1     = 'group_password';
      $new_pass2     = $new_pass1;
    }
    else {
      $new_pass1     = $_REQUEST['tool_newpassword1'];
      $new_pass2     = $_REQUEST['tool_newpassword2'];
    }
    
    if($new_pass1 != $new_pass2) {
      PrintAdminToolPage( $pagename, $PasswordMismatchFmt );
      exit;
    }

    // Now we can process the data
    if($method == 'add') {
      if($UserInfoObj->DoesUserExist($username)) {
	if($groupaction) {
	  PrintAdminToolPage( $pagename, $GroupAlreadyExistsFmt );
        }
        else {
	  PrintAdminToolPage( $pagename, $UserAlreadyExistsFmt );
	}
	exit;
      }

      $UserInfoObj->AddUser($username);
    }

    $abilities_arr = preg_split('/[\s,]+/', $abilities_str);
    
    if($new_pass1 != '') {
      $UserInfoObj->SetUserPassword($username, $new_pass1);
    }

    $UserInfoObj->SetUserFullname($username, $fullname);

    $UserInfoObj->SetUserAbilities($username, $abilities_arr);

    $UserInfoObj->PublishChanges();

    RedirectURL($AdminToolMainUrl);
  } else {
    PrintAdminToolPage( $pagename, GetAddEditUserForm($method, $username, $groupaction) );
    exit;
  } 
  
}

function HandleDelUser($pagename) {
  global $UserInfoObj, $AdminToolMainUrl;

  $username = $_REQUEST['tool_username'];

  if(isset($_REQUEST['tool_confirm'])) {
    if($_REQUEST['tool_confirm'] == "Yes") {
      $UserInfoObj->DelUser($username);
      $UserInfoObj->PublishChanges();
    } 

    RedirectURL($AdminToolMainUrl);

  } else {
    PrintAdminToolPage( $pagename, GetDelUserConfirmPage($username) );
    exit;
  } 

}

function HandleUserReport($pagename) {
  global $UserInfoObj, $AdminToolMainUrl;

  $report_html .= "
    <h3>$[User Administration Report]</h3>

    <table border='1'>
    <tr><th align='center' colspan='3'>$[Users]</th></tr>
    <tr><th>$[User ID]</th><th>$[Full Name]</th><th>$[Abilities]</th></tr>";
    
    foreach($UserInfoObj->GetAllUsers() as $username) {
      $user_fullname = $UserInfoObj->GetUserFullname($username);
      $user_abilities = implode(", ", $UserInfoObj->GetUserAbilities($username));
    
      $report_html .= "
        <tr><td>$username</td><td>$user_fullname</td><td>$user_abilities</td></tr>";
    }
    
    $report_html .= "    
    </table><br>
    <table border='1'>
    <tr><th align='center' colspan='4'>$[User Groups]</th></tr>
    <tr><th>$[Group ID]</th><th>$[Full Name]</th><th>$[Abilities]</th><th>$[Members]</th></tr>";

    foreach($UserInfoObj->GetAllUserGroups() as $groupname) {
      $group_fullname = $UserInfoObj->GetUserFullname($groupname);
      $group_members = implode(", ", $UserInfoObj->GetUsersInGroup($groupname));
      $group_abilities = implode(", ", $UserInfoObj->GetUserAbilities($groupname));
    
      $report_html .= "
        <tr><td>$groupname</td><td>$group_fullname</td><td>$group_abilities</td><td>$group_members</td></tr>";
    }
    
    $report_html .= "    
    </table>
    <br>
    <form name='navigation' action='?action=admin' method='post'>
    <input class='userauthbutton' type='submit' value='$[Admin Main]' />
    </form>";


  PrintAdminToolPage( $pagename, $report_html);
  exit;

}


function GetActionChoicePage() {
  global $UserInfoObj, $AdminQueryUrlPrefix;
  global $GuestUsername, $LoggedInUsername;
  global $pagename;

  $choice_html .= "
    <h3>$[Admin Main]</h3>

    <table border=0><tr><td valign='top'>
    
    <table border=1><tr><th colspan='4' align='center'>$[Users]</th>";

  foreach($UserInfoObj->GetAllUsers() as $curr_username) {
    $curr_fullname = $UserInfoObj->GetUserFullname($curr_username);	  
    $link_postfix = "&tool_username=" . $curr_username;
    $edit_link = $AdminQueryUrlPrefix . "edit" . $link_postfix;
    $del_link  = $AdminQueryUrlPrefix . "del" . $link_postfix;

    $choice_html .= 
      "<tr><td>$curr_username</td>" .
      "<td>$curr_fullname</td>" .
      "<td><a class='wikilink' href='$edit_link'>$[Edit]</a></td>";
    if( $curr_username != $GuestUsername && 
	$curr_username != $LoggedInUsername ) {
      $choice_html .=
	"<td><a class='wikilink' href='$del_link'>$[Delete]</a></td></tr>\n";
    } else {
      $choice_html .=
	"<td>&nbsp;</td></tr>\n";
    }

  }

  $add_link = $AdminQueryUrlPrefix . "add";

  $choice_html .= 
    "<tr><td colspan='4' align='center'><a class='wikilink' href='$add_link'>$[Add User]</a></td></tr></table>
    
    </td><td valign='top'>
    
    <table border=1><tr><th colspan='4' align='center'>$[Groups]</th>";

  foreach($UserInfoObj->GetAllUserGroups() as $curr_username) {
    $curr_fullname = $UserInfoObj->GetUserFullname($curr_username);	  
    $link_postfix = "&tool_username=" . $curr_username;
    $edit_link = $AdminQueryUrlPrefix . "editgroup" . $link_postfix;
    $del_link  = $AdminQueryUrlPrefix . "del" . $link_postfix;

    $choice_html .= 
      "<tr><td>$curr_username</td>" .
      "<td>$curr_fullname</td>" .
      "<td><a class='wikilink' href='$edit_link'>$[Edit]</a></td>" .
      "<td><a class='wikilink' href='$del_link'>$[Delete]</a></td></tr>\n";

  }

  $add_link = $AdminQueryUrlPrefix . "addgroup";

  $choice_html .= 
    "<tr><td colspan='4' align='center'><a class='wikilink' href='$add_link'>$[Add Group]</a></td></tr></table>
    
    </td></tr></table>
    
    <br>
    <form name='navigation' action='?action=admin&aa=report' method='post'>
    <input class='userauthbutton' type='submit' value='$[Show Admin Report]' />
    </form>";

  return FmtPageName($choice_html, $pagename);
}

function GetAddEditUserForm($method, $existing_user = NULL, $groupaction = false) {
  global $UserInfoObj, $AdminQueryUrlPrefix;
  global $AbilitiesHelpFmt, $GroupAbilitiesHelpFmt, $pagename;

  if(isset($existing_user)) {
    $existing_abilities = join( ", ", $UserInfoObj->GetUserAbilities($existing_user) );
    $existing_fullname = $UserInfoObj->GetUserFullname($existing_user);
  } else {
    $existing_abilities = '';
    $existing_fullname = '';
  }

  $method_text = ucfirst($method);

  if($groupaction) {
    $action_url = $AdminQueryUrlPrefix . $method . 'group';
    $add_edit_form .= "<h3>$method_text Group</h3>";
  }
  else {
    $action_url = $AdminQueryUrlPrefix . $method;
    $add_edit_form .= "<h3>$method_text User</h3>";
  }

  $add_edit_form .= "
    <form name='addeditform' action='$action_url' method='post'>
       <input name='tool_perform' value='1' type='hidden'/>
       <table>

       <tr>";
       
  if($groupaction) {
    $add_edit_form .= "<td>$[Groupname]:</td>";
  }
  else {
    $add_edit_form .= "<td>$[Username]:</td>";
  }

  if(isset($existing_user)) {
    $add_edit_form .= "
           <td><input name='tool_username' value='$existing_user' type='hidden'/>
               $existing_user</td>";
  } else {
    $add_edit_form .= "
            <td><input name='tool_username' class='userauthinput'/></td>";
  }
  
  $add_edit_form .= "
       </tr>

       <tr>
           <td>$[Full Name]:</td>
           <td><input name='tool_fullname' value='$existing_fullname' class='userauthinput' /></td>
       </tr>

       <tr>
           <td>$[Abilities]:</td>
           <td><textarea name='tool_abilities' class='userauthinput' rows='3' cols='30'>$existing_abilities</textarea></td>
       </tr>

       <tr><td>&nbsp;</td><td>&nbsp;</td></tr>";
       
  if(!$groupaction) {     
    $add_edit_form .= "
       <tr>
           <td>$[Enter new password]:</td>
           <td><input name='tool_newpassword1' class='userauthinput' type='password'/></td>
       </tr>

       <tr>
           <td>$[Repeat new password]:</td>
           <td><input name='tool_newpassword2' class='userauthinput' type='password' /></td>
       </tr>";
  }

  $add_edit_form .= "
       <tr>
           <td align=left><input class='userauthbutton' type='submit' value='$method_text' /></td>
           <td align=left><input class='userauthbutton' type='submit' name='tool_cancel' value='$[Cancel]' /></td>
           <td>&nbsp</td> 
       </tr>
       </table>
       </form>
       ";
       
  if($groupaction) {
    if(isset($existing_user)) {	  
      $user_list = implode(", ", $UserInfoObj->GetUsersInGroup($existing_user));
      $add_edit_form .= "
        <br>
    	$[Users in this User Group]:<br>
    	$user_list<br>";
    }
    $add_edit_form .= "
      <br>
      $AbilitiesHelpFmt";
  }
  else {
      $group_list = implode(", ", $UserInfoObj->GetAllUserGroups());
      $add_edit_form .= "
        <br>
    	$[Defined User Groups]:<br>
    	$group_list
    	<br><br>
        $AbilitiesHelpFmt
        <br>
        $GroupAbilitiesHelpFmt";
  }

  return FmtPageName($add_edit_form, $pagename);
}


function GetDelUserConfirmPage($username) {
  global $AdminQueryUrlPrefix;
  global $pagename;

  $action_url = $AdminQueryUrlPrefix . "del";

  if($username{0} == '@') {
    $confirm_form .= "<h3>$[Delete Group]</h3>";
  }
  else {
    $confirm_form .= "<h3>$[Delete User]</h3>";
  }

  $confirm_form .= "
    $[Really delete] $username?
    <form name='del_confirm' action='$action_url' method='post'>
      <input name='tool_username' value='$username' type='hidden'/>
      <input class='userauthbutton' name='tool_confirm' type='submit' value='$[Yes]' />
      <input class='userauthbutton' name='tool_confirm' type='submit' value='$[No]' />
    </form>";
  
  return FmtPageName($confirm_form, $pagename);
}

function PrintAdminToolPage($pagename, $html) {
  global $AdminToolMainUrl;
  global $pagename;

  $html = 
    FmtPageName("<h2><a href='$AdminToolMainUrl'>$[UserAuth Administration]</a></h2>", $pagename) . 
    $html;

  PrintEmbeddedPage($pagename, $html);
}


?>
