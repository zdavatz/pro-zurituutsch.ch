<?php

  /*

   HtPasswd File Reader with Abilities

   -- Copyright --
   Copyright 2004, by James McDuffie (ttq3sxl02@sneakemail.com)
   Portions (C) 2005, by Roman Mamedov (http://rm.pp.ru/?en.contact)
   
   -- License --
   GNU GPL

   -- Description --

   This class implements reading of a .htpasswd file. It simply reads in
   three field from the file separated by the ':' character:
      username:password:abilities
   The password is encrypted in the usual Apache htpasswd file way. The
   abilites are a comma separated list of actions the user can perform.
   
   -- Example Usage --

   $ht_obj = new HtPasswd('/tmp/.htpasswd');
   $fred_password = $ht_obj->GetUserPassword('fred');
   $fred_can_read = $ht_obj->UserHasAbility('fred', 'read');
   
   -- Change log --
   v0.6 -- 12-01-2006 -- DW
   * Added helper for group maintenance

   v0.5 -- 12-31-2005 -- DW
   * Added Full Name field and ability to convert old style format to new
   * Added user group support

   v0.4 -- 3-1-2005 -- JM
   * Added capability to write object to disk
   * Added setting of passwords into object
   
   v0.3 -- 26.02.2005 -- RM
   * fixed a couple of bugs

   v0.2 -- 25.02.2005 -- RM
   * $pwd_file_contents are now loaded with file() instead of
     file_get_contents(), so it also works on PHP older than 4.3
   * $abilities_str is now trim()'med, so it works correctly with
     htpasswd which has windows newlines
   * greatly simplified UserHasAbility(), without hurting
     its functionality

  */

define(HTPASSWD_CLASS, '0.6');

Class HtPasswd {

  # user_passwords - An array hash where the key is the username and value 
  # the password
  #
  # user_abilites - An array hash where the key is the username and the value
  # is an array of ability strings
  # 
  # user_fullnames - An array hash where the key is the username and the value
  # is the user full name
  var $user_passwords = array();
  var $user_abilities = array();
  var $user_fullnames = array();

  // Below are the HtPasswd specific methods. These do not need to be 
  // implemented persay if you create a new UserInfoObj class. But
  // you still probably need a constructor for your new class, but you
  // don't necessarily need one that requires a filename

  function HtPasswd($htpasswd_file) {

    $this->htpasswd_file = $htpasswd_file;

    $this->ReadPasswordFile();

  }

  // Never call the below methods directly, instead use the generic
  // method names defined later in this class

  function ReadPasswordFile() {

    if(!file_exists($this->htpasswd_file)) {
      trigger_error("userauth htpasswd file does not exist!");
    }

    @$pwd_file_contents = file($this->htpasswd_file);

    foreach($pwd_file_contents as $file_line) {
      # Skip lines not beginning with letter or numbers or the group symbol
      if(preg_match('/^[\w\d@]+/', $file_line)) {

	// check if it is an old style format (no full name) or new
	if(substr_count($file_line, ":") > 2) {
		// more than two colons means we have the full name data
		list($username, $password, $fullname, $abilities_str) = preg_split("/\s*:\s*/", $file_line);
	}
	else {
		// old style
		list($username, $password, $abilities_str) = preg_split("/\s*:\s*/", $file_line);
		$fullname = "";
	}
	@$abilities = preg_split("/\s*,\s*/", trim($abilities_str));

	$this->user_passwords[$username] = $password;
	$this->user_fullnames[$username] = $fullname;
	$this->user_abilities[$username] = $abilities;
      }
    }
    ksort($this->user_passwords);
    ksort($this->user_fullnames);
    ksort($this->user_abilities);

  }

  function WritePasswordFile() {

    // Make a backup of the password file just in case somethign goes wrong
    copy($this->htpasswd_file, $this->htpasswd_file . ".bak");

    if (!is_writable($this->htpasswd_file)) {
      trigger_error("userauth htpasswd file is not writable");
    }
  
    $pw_file_handle = fopen($this->htpasswd_file, 'w');

    // For each user write an entry to disk
    foreach($this->user_fullnames as $curr_username => $name) {
      $curr_password = $this->user_passwords[$curr_username];
      $curr_fullname = $this->user_fullnames[$curr_username];
      $curr_abilities = $this->user_abilities[$curr_username];
      
      $user_out_line = 
	$curr_username . ":" .
	$curr_password . ":" .
	$curr_fullname . ":" .
	implode(",", $curr_abilities) . 
	"\r\n";

      if (fwrite($pw_file_handle, $user_out_line) === FALSE) {
	trigger_error("userauth htpasswd file can not be written to");
      }
    }

    fclose($pw_file_handle);

  }

  // If you wish to implement a new UserInfoObj type object then
  // Implementing the same methods as below will allow you to easily
  // use your new class instead of this one.

  function PublishChanges() {
    // This function has a generic name so that anyone implementing a new
    // UserInfoObj class is not bound to the notion of passwords and abilities
    // being stored in a file. Instead they could be stored in a db
    $this->WritePasswordFile();
    ksort($this->user_passwords);
    ksort($this->user_fullnames);
    ksort($this->user_abilities);

  }

  function AnyUserHasAbility($ability) {
    // this will now also catch user group definitions because they are like regular
    // users but the name starts with an at (@) sign
    foreach($this->user_abilities as $ab) {
      if(@in_array($ability, $ab)) {
	return true;
      }
    }
    return false;
  }
	
  function UserHasAbility($username, $ability) {
    // if the user ability contains a group reference, then we need to resolve those first
    $resolved_abilities = array();
    if(!isset($this->user_passwords[$username])) {
	    return false;
    }
    foreach($this->user_abilities[$username] as $ab) {
	if($ab{0} == '@') {
		// group reference, get all group abilities and add it as this users abilities
		// in the temporary array
		foreach($this->user_abilities[$ab] as $group_abilities) {
			$resolved_abilities[] = $group_abilities;
		}
	}
	else {
		$resolved_abilities[] = $ab;
	}
    }
    return @in_array($ability, $resolved_abilities);
  }

  function UserIsGroupMember($username, $groupname) {
    return @in_array($groupname, $this->user_abilities[$username]);
  }

  function DoesUserExist($username) {
    return isset($this->user_passwords[$username]);
  }

  function AddUserAbility($username, $ability) {
    if(!in_array($ability,  $this->user_abilities[$username])) {
      push( $this->user_abilities[$username], $ability);
    }
  }

  function DelUserAbility($username, $ability) {
    $ability_loc = 
      array_search($ability, $this->user_abilities[$username]);
    unset( $this->user_abilities[$username][$ability_loc] );
  }

  function GetUserAbilities($username) {
    return $this->user_abilities[$username];
  }

  function SetUserAbilities($username, $abilities_array) {
    $this->user_abilities[$username] = $abilities_array;
  }

  function GetUserPassword($username) {
    return $this->user_passwords[$username];
  }

  function SetUserPassword($username, $newpassword) {
    $this->user_passwords[$username] = crypt($newpassword);
  }

  function GetUserFullname($username) {
    return $this->user_fullnames[$username];
  }

  function SetUserFullname($username, $fullname) {
    $this->user_fullnames[$username] = $fullname;
  }

  function GetAllUsers() {
    $clean_users = array();	  
    
    foreach($this->user_fullnames as $user => $name) {
	    if($user{0} != '@') {
		    $clean_users[] = $user;
	    }
    }
    return $clean_users;
  }

  function GetAllUserGroups() {
    $clean_groups = array();	  
    
    foreach($this->user_fullnames as $user => $name) {
	    if($user{0} == '@') {
		    $clean_groups[] = $user;
	    }
    }
    return $clean_groups;
  }

  function GetUsersInGroup($groupname) {
    $users = array();
    
    foreach($this->GetAllUsers() as $username) {
      if($this->UserIsGroupMember($username, $groupname)) {
        $users[] = $username;
      }
    }
    return $users;
  }

  function AddUser($username) {
    $this->user_passwords[$username] = '';
    $this->user_fullnames[$username] = '';
    $this->user_abilities[$username] = array();
  }

  function DelUser($username) {
    unset( $this->user_passwords[$username] );
    unset( $this->user_fullnames[$username] );
    unset( $this->user_abilities[$username] );
  }

}

?>
