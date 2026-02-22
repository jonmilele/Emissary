<?php
session_start();
include("connect.inc.php");	
	$user_name = strtolower($_POST['login_username']);
	$password = strtolower($_POST['login_password']);

	//set up the query
	$query = "SELECT UserName,Email,Password FROM players WHERE(UserName='$user_name')";

	//run the query and get the number of affected rows
	$notresult = mysql_query($query) or die(mysql_error());
	$result = mysql_fetch_object($notresult) or die(mysql_error());

	
	$email_add = $result->Email;

//	encrypt submitted password

	$iv = mcrypt_create_iv (mcrypt_get_iv_size (MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
    $key = "nonnyrules";
    $text = $password;
    $crypttext = mcrypt_encrypt (MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv);
	
	if(($crypttext == $result->Password))
	{
	/*
		if($_POST[rememberme] == "true"){
			if($user_name != "Nonny"){
				setcookie("blog", "$user_name|$email_add", time() + 31536000,"/",".nonny.com");
				$message .= "Cookie Set, ";
			}else{
				setcookie("blog", "$user_name|$email_add", time() + 259200,"/",".nonny.com");
				$message .= "Cookie Set 3 days, ";
			}
		}
		*/
			//add the user to our session variables
			$_SESSION['username'] = $user_name;
			
			session_register(user_name);
			session_register(email_add);
			//echo "Logged in";
			header("Location: home.php");
	}
	else
	{
		$message .= "Invalid Username/password combination, ";
		header("Location: index.php?msg=".$message);
	}
?>


