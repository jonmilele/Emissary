<?php
include_once("session.inc.php");
session_start();
include_once("csrf.inc.php");

if(empty($_SESSION['username'])) {
		// Remember where the user was trying to go so we can redirect back after login
		$_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
		header("Location: /index.php");
		exit;
	}

// Session timeout check
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT){
	session_unset();
	session_destroy();
	session_start();
	SetFlash("Session expired");
	header("Location: /index.php");
	exit;
}
$_SESSION['last_activity'] = time();

// Validate session fingerprint (IP + user agent)
if(isset($_SESSION['ip']) && ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] || ($_SESSION['ua'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? ''))){
	session_unset();
	session_destroy();
	session_start();
	SetFlash("Session invalidated");
	header("Location: /index.php");
	exit;
}

$username = $_SESSION['username'];

// --- Back-link tracking (game pages only, GET requests, skip images/admin) ---
function _GetPageName($url){
	$path = parse_url($url, PHP_URL_PATH) ?? '';
	$page = basename($path);
	parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $qs);
	switch($page){
		case 'home.php': return 'Home';
		case 'planet.php': return 'Planet' . (isset($qs['id']) ? ' ' . $qs['id'] : '');
		case 'system.php': return 'System' . (isset($qs['id']) ? ' ' . $qs['id'] : '');
		case 'sector.php': return 'Sector' . (isset($qs['id']) ? ' ' . $qs['id'] : '');
		case 'galaxy.php': return 'Galaxy';
		case 'fleet.php': return 'Fleet' . (isset($qs['id']) ? ' ' . $qs['id'] : '');
		case 'fleetlist.php': return 'Fleets';
		case 'planetlist.php': return 'Planets';
		case 'systemlist.php': return 'Systems';
		case 'building.php': return 'Building';
		case 'player.php': return 'Player';
		case 'team.php': return 'Team';
		case 'teams.php': return 'Teams';
		case 'trade.php': return 'Trade';
		case 'alerts.php': return 'Alerts';
		case 'battlelist.php': return 'Battles';
		case 'battle.php': return 'Battle';
		case 'leaderboard.php': return 'Leaderboard';
		default: return ucfirst(str_replace('.php', '', $page));
	}
}
if(($_SERVER['REQUEST_METHOD'] ?? '') === 'GET'){
	$_backPage = basename($_SERVER['PHP_SELF'] ?? '');
	$_backIsImg = (substr($_backPage, -8) === '.img.php');
	$_backIsAdmin = (strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false);
	if(!$_backIsImg && !$_backIsAdmin){
		$_backCurrent = $_SERVER['REQUEST_URI'] ?? '';
		$_backStored = $_SESSION['current_url'] ?? '';
		if($_backCurrent !== '' && $_backCurrent !== $_backStored){
			if($_backStored !== ''){
				$_SESSION['back_url'] = $_backStored;
				$_SESSION['back_name'] = _GetPageName($_backStored);
			}
			$_SESSION['current_url'] = $_backCurrent;
		}
	}
}

// Home world / game over checks (skip on pages that handle these states)
$_currentPage = basename($_SERVER['PHP_SELF'] ?? '');
if(!in_array($_currentPage, ['sethome.php', 'gameover.php', 'logout.back.php'])){
	include_once("connect.inc.php");
	include_once("userfunctions.inc.php");
	$_myPID = GetPlayerIDFromName($username);
	if($_myPID > 0){
		$_numPlanets = GetNumberOfPlanets($_myPID);
		if($_numPlanets == 0){
			header("Location: /gameover.php");
			exit;
		}
		if(NeedsHomePlanet($_myPID)){
			header("Location: /sethome.php");
			exit;
		}
	}
}
