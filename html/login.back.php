<?php
include_once("session.inc.php");
session_start();
include("connect.inc.php");
	$user_name = strtolower(($_POST['login_username'] ?? ""));
	$password = ($_POST['login_password'] ?? "");

	// Prepared statement to prevent SQL injection
	$stmt = mysqli_prepare($conn, "SELECT UserName, Email, Password FROM players WHERE UserName = ?");
	mysqli_stmt_bind_param($stmt, "s", $user_name);
	mysqli_stmt_execute($stmt);
	$notresult = mysqli_stmt_get_result($stmt);
	$result = mysqli_fetch_object($notresult);
	mysqli_stmt_close($stmt);

	if($result && password_verify($password, $result->Password))
	{
			// Regenerate session ID to prevent session fixation
			session_regenerate_id(true);

			//add the user to our session variables
			$_SESSION['username'] = $user_name;
			$_SESSION['last_activity'] = time();
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

			// Login summary: snapshot resources for comparison
			$_snapStmt = mysqli_prepare($conn, "SELECT PlayerID, Metal, Mineral, Astrium, LastLogin, LoginMetal, LoginMineral, LoginAstrium, LoginPlanets FROM players WHERE UserName = ?");
			mysqli_stmt_bind_param($_snapStmt, "s", $user_name);
			mysqli_stmt_execute($_snapStmt);
			$_snapRes = mysqli_stmt_get_result($_snapStmt);
			$_pdata = mysqli_fetch_object($_snapRes);
			mysqli_stmt_close($_snapStmt);
			if($_pdata){
				$_pid = (int)$_pdata->PlayerID;
				$_pcRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM planets WHERE PlayerID = '$_pid'");
				$_pcRow = mysqli_fetch_object($_pcRes);
				$_curPlanets = $_pcRow ? (int)$_pcRow->cnt : 0;

				if($_pdata->LastLogin){
					$_SESSION['prev_login_time'] = $_pdata->LastLogin;
					$_SESSION['resource_diff'] = [
						'Metal' => (int)$_pdata->Metal - (int)$_pdata->LoginMetal,
						'Mineral' => (int)$_pdata->Mineral - (int)$_pdata->LoginMineral,
						'Astrium' => (int)$_pdata->Astrium - (int)$_pdata->LoginAstrium,
					];
					$_SESSION['planet_diff'] = $_curPlanets - (int)$_pdata->LoginPlanets;
					$_SESSION['show_login_summary'] = true;
				}

				mysqli_query($conn, "UPDATE players SET LastLogin=NOW(), LoginMetal=Metal, LoginMineral=Mineral, LoginAstrium=Astrium, LoginPlanets='$_curPlanets' WHERE PlayerID='$_pid'");
			}

			// Redirect back to the page they were trying to reach, or home
			$redirect = $_SESSION['redirect_after_login'] ?? 'home.php';
			unset($_SESSION['redirect_after_login']);
			// Sanitize: only allow relative paths (prevent open redirect)
			if(empty($redirect) || $redirect[0] !== '/' || strpos($redirect, '//') !== false){
				$redirect = 'home.php';
			}
			header("Location: " . $redirect);
	}
	else
	{
		$message = "Invalid Username/password combination";
		header("Location: /index.php?msg=".urlencode($message));
	}
