<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<h2>1. Account Creation</h2>
<p>On this page you will create your player account.</p>
<form name="form1" method="post" action="signup.back.php">
  <p>Username: 
    <input name="signup_name" type="text" id="signup_name" style="border: 2px solid #FF0000;">
    <small>(Must be between 5 and 12 characters long, case insensitive)</small> 
  </p>
  <p>Password: 
    <input name="signup_pass1" type="password" id="signup_pass1" style="border: 2px solid #FF0000;">
    <small>(Case Insensitive)</small></p>
  <p>Repeat Password: 
    <input name="signup_pass2" type="password" id="signup_pass2" style="border: 2px solid #FF0000;">
    <small>(Case Insensitive)</small> </p>
  <p>Email: 
    <input name="signup_email" type="text" id="signup_email" style="border: 2px solid #FF0000;">
  </p>
  <p>Location:
    <input name="signup_location" type="text" id="signup_location">
  </p>
  <p>Country: 
    <input name="signup_country" type="text" id="signup_country" style="border: 2px solid #FF0000;">
  </p>
  <p>
    <input type="submit" name="Submit" value="Submit">
  </p>
</form>
<p>&nbsp;</p>
</body>
</html>
