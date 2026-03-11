<?php

  /*

   User Session Variables Container Class, default UserInstanceVars class

   -- Copyright --
   Copyright 2004, by James McDuffie (ttq3sxl02@sneakemail.com)
   
   -- License --
   GNU GPL

   -- Description --

   This class encapsulate handling of user variables that should be stored
   between successive calls to the wiki. Information such as the username
   and password for the current user are returned to UserAuth.

   This class implements the UserVariables class interface in by storing
   user information in the $_SESSION array.
   
   -- Example Usage --

   $UserVariables = new UserSessionVars;
   $curr_user = $UserVariables->GetInstanceUsername();
   $curr_pass = $UserVariables->GetInstancePassword();

   $UserVariables->SetInstancePassword($_REQUEST['user_pass']);
   
   -- Change log --
   
   v0.1 - Initial version
   v0.2 - Dan Weber - Added cookie support

  */

define(USERSESSIONVARS, '0.2');

Class UserSessionVars {

  function UserSessionVars() {
    session_start();
  }

  function GetInstanceUsername() {
    if(@$_SESSION['username']) {
      return $_SESSION['username'];
    }
    if(@$_COOKIE[$CookiePrefix.'UserAuthUsername']) {
      $_SESSION['username'] = $_COOKIE[$CookiePrefix.'UserAuthUsername'];
    }
    return $_SESSION['username'];
  }

  function GetInstancePassword() {
    if(@$_SESSION['user_pw']) {
      return $_SESSION['user_pw'];
    }
    if(@$_COOKIE[$CookiePrefix.'UserAuthPassword']) {
      $_SESSION['user_pw'] = $_COOKIE[$CookiePrefix.'UserAuthPassword'];
    }
    return $_SESSION['user_pw'];
  }

  function SetInstanceUsername($username) {
    $_SESSION['username'] = $username;
  }

  function SetInstancePassword($password) {
    $_SESSION['user_pw'] = $password;
  }

  function ClearInstanceVariables() {
    unset( $_SESSION['username'] );
    unset( $_SESSION['user_pw'] );
    $this->ClearUserCookie();
  }

  function SetUserCookie() {
    global $UserAuthCookieExp;
    
    setCookie($CookiePrefix.'UserAuthUsername', $_SESSION['username'], time()+$UserAuthCookieExp, '/');
    setCookie($CookiePrefix.'UserAuthPassword', $_SESSION['user_pw'], time()+$UserAuthCookieExp, '/');
  }

  function ClearUserCookie() {
    setCookie($CookiePrefix.'UserAuthUsername', '', time()-3600, '/');
    setCookie($CookiePrefix.'UserAuthPassword', '', time()-3600, '/');
  }

}

?>
