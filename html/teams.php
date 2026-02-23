<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

$myPID = GetPlayerIDFromName($username);
$myTeamID = PlayerTeam($myPID);

// Handle POST actions
if(isset($_POST['action']) && csrf_validate()){
	$action = $_POST['action'];

	if($action == "create" && $myTeamID == 0){
		$tname = trim($_POST['teamname'] ?? "");
		$tcolour = trim($_POST['teamcolour'] ?? "");
		$presets = GetTeamColourPresets();
		if($tname === "" || !in_array($tcolour, $presets)){
			SetFlash("Invalid team name or colour");
			header("Location: teams.php");
		} elseif(IsTeamColourTaken($tcolour)){
			SetFlash("That colour is already taken");
			header("Location: teams.php");
		} else {
			CreateTeam($myPID, $tname, $tcolour);
			AddAlert($myPID, 'team', 'Team '.$tname.' created', 'teams.php');
			SetFlash("Team created");
			header("Location: teams.php");
		}
		exit;
	}

	if($action == "requestjoin" && $myTeamID == 0){
		$tid = (int)($_POST['teamid'] ?? 0);
		if($tid > 0){
			if(RequestJoinTeam($myPID, $tid)){
				AddAlert($myPID, 'team', 'Join request sent to '.TeamNameFromID($tid), 'teams.php');
				SetFlash("Join request sent");
				header("Location: teams.php");
			} else {
				SetFlash("Could not send request");
				header("Location: teams.php");
			}
			exit;
		}
	}

	if($action == "cancelrequest" && $myTeamID == 0){
		CancelJoinRequest($myPID);
		SetFlash("Request cancelled");
		header("Location: teams.php");
		exit;
	}

	if($action == "leave" && $myTeamID > 0){
		$leftTeamName = TeamNameFromID($myTeamID);
		LeaveTeam($myPID);
		AddAlert($myPID, 'team', 'You left '.$leftTeamName, 'teams.php');
		SetFlash("You left the team");
		header("Location: teams.php");
		exit;
	}

	if($action == "raisemotion" && $myTeamID > 0){
		if(RaiseElectionMotion($myTeamID, $myPID)){
			// Check if election started immediately (small team)
			$ti = GetTeamInfo($myTeamID);
			if($ti && $ti->VoteActive){
				SetFlash("Motion carried \xe2\x80\x94 election started");
				header("Location: teams.php");
			} else {
				SetFlash("Motion raised. Needs seconds from other members.");
				header("Location: teams.php");
			}
		} else {
			SetFlash("Cannot raise motion right now");
			header("Location: teams.php");
		}
		exit;
	}

	if($action == "secondmotion" && $myTeamID > 0){
		if(SecondElectionMotion($myTeamID, $myPID)){
			$ti = GetTeamInfo($myTeamID);
			if($ti && $ti->VoteActive){
				SetFlash("Motion carried \xe2\x80\x94 election started");
				header("Location: teams.php");
			} else {
				SetFlash("Motion seconded");
				header("Location: teams.php");
			}
		} else {
			SetFlash("Cannot second motion");
			header("Location: teams.php");
		}
		exit;
	}

	if($action == "resignleader" && $myTeamID > 0 && IsTeamLeader($myPID)){
		if(ResignAsLeader($myPID)){
			SetFlash("You resigned as leader. Election called.");
			header("Location: teams.php");
		} else {
			SetFlash("Could not resign");
			header("Location: teams.php");
		}
		exit;
	}

	if($action == "vote" && $myTeamID > 0){
		$teamInfo = GetTeamInfo($myTeamID);
		if($teamInfo && $teamInfo->VoteActive){
			$cid = (int)($_POST['candidate'] ?? 0);
			if($cid > 0 && PlayerTeam($cid) == $myTeamID){
				CastLeaderVote($myTeamID, $myPID, $cid);
			}
		}
		AddAlert($myPID, 'team', 'Vote recorded in team election', 'teams.php');
		SetFlash("Vote recorded");
		header("Location: teams.php");
		exit;
	}

	if($action == "edit"){
		$tname = trim($_POST['teamname'] ?? "");
		$tcolour = trim($_POST['teamcolour'] ?? "");
		$presets = GetTeamColourPresets();
		if($tname === "" || !in_array($tcolour, $presets)){
			SetFlash("Invalid name or colour");
			header("Location: teams.php");
		} elseif(IsTeamColourTaken($tcolour, $myTeamID)){
			SetFlash("That colour is already taken");
			header("Location: teams.php");
		} else {
			$tname = mysqli_real_escape_string($GLOBALS["conn"], $tname);
			$tcolour = mysqli_real_escape_string($GLOBALS["conn"], $tcolour);
			$sql = "UPDATE teams SET Name='$tname', Colour='$tcolour' WHERE TeamID='$myTeamID'";
			mysqli_query($GLOBALS["conn"], $sql);
			AddAlert($myPID, 'team', 'Team settings updated', 'teams.php');
			SetFlash("Team updated");
			header("Location: teams.php");
		}
		exit;
	}

	if($action == "approve" && $myTeamID > 0 && IsTeamLeader($myPID)){
		$rid = (int)($_POST['requestid'] ?? 0);
		if($rid > 0) ApproveJoinRequest($rid);
		AddAlert($myPID, 'team', 'Join request approved', 'teams.php');
		SetFlash("Request approved");
		header("Location: teams.php");
		exit;
	}

	if($action == "deny" && $myTeamID > 0 && IsTeamLeader($myPID)){
		$rid = (int)($_POST['requestid'] ?? 0);
		if($rid > 0) DenyJoinRequest($rid);
		AddAlert($myPID, 'team', 'Join request denied', 'teams.php');
		SetFlash("Request denied");
		header("Location: teams.php");
		exit;
	}
}

// Reload state after POST redirect
$myTeamID = PlayerTeam($myPID);
$isLeader = IsTeamLeader($myPID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Teams</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
<?php include("header.inc.php"); ?>

<h1>Team Management</h1>

<?php if($myTeamID == 0): ?>
<!-- ============ STATE 1: No Team ============ -->

<?php
$pendingReq = GetPlayerJoinRequest($myPID);
if($pendingReq):
	$reqTeamName = TeamNameFromID($pendingReq->TeamID);
?>
<div class="panel" style="width:500px;">
<h3>Pending Request</h3>
<p>You have requested to join <strong><?php echo htmlspecialchars($reqTeamName); ?></strong>
 (sent <?php echo $pendingReq->RequestedAt; ?>)</p>
<form method="POST" action="teams.php" style="display:inline;">
<input type="hidden" name="action" value="cancelrequest">
<?php echo csrf_token(); ?>
<input type="submit" value="Cancel Request">
</form>
</div>
<?php else: ?>

<div class="side">
<div class="panel" style="width:300px;">
<h3>Create a Team</h3>
<form method="POST" action="teams.php">
<input type="hidden" name="action" value="create">
<?php echo csrf_token(); ?>
<p>Team Name:<br/><input type="text" name="teamname" maxlength="100" size="25"></p>
<p>Colour:</p>
<div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px;">
<?php
$presets = GetTeamColourPresets();
$takenColours = [];
$allTeams = ListAllTeams();
foreach($allTeams as $t) $takenColours[] = $t->Colour;
foreach($presets as $c):
	$taken = in_array($c, $takenColours);
?>
<label style="display:block;width:28px;height:28px;background:rgb(<?php echo $c; ?>);border:2px solid #666;cursor:<?php echo $taken ? 'not-allowed' : 'pointer'; ?>;opacity:<?php echo $taken ? '0.25' : '1'; ?>;position:relative;">
<input type="radio" name="teamcolour" value="<?php echo $c; ?>" <?php if($taken) echo 'disabled'; ?> style="position:absolute;opacity:0;width:100%;height:100%;margin:0;cursor:inherit;" onclick="this.parentElement.parentElement.querySelectorAll('label').forEach(l=>l.style.borderColor='#666');this.parentElement.style.borderColor='#FFF';">
</label>
<?php endforeach; ?>
</div>
<p><input type="submit" value="Create Team"></p>
</form>
</div>
</div>

<div class="planet" style="width:400px;">
<h3>Join an Existing Team</h3>
<?php
$allTeams = ListAllTeams();
if(count($allTeams) == 0){
	echo "<p>No teams exist yet.</p>";
} else {
	foreach($allTeams as $t){
		$memberCount = PlayersInTeam($t->TeamID);
?>
<div style="margin:5px 0; padding:5px; border:1px solid #333;">
<strong><a href="team.php?id=<?php echo $t->TeamID; ?>"><?php echo htmlspecialchars($t->Name); ?></a></strong>
 &mdash; <?php echo $memberCount; ?> member(s)
 <img src="teamcolour.img.php?id=<?php echo $t->TeamID; ?>" style="vertical-align:middle;" width="20" height="10">
<form method="POST" action="teams.php" style="display:inline;">
<input type="hidden" name="action" value="requestjoin">
<input type="hidden" name="teamid" value="<?php echo $t->TeamID; ?>">
<?php echo csrf_token(); ?>
<input type="submit" value="Request to Join">
</form>
</div>
<?php
	}
}
?>
</div>

<?php endif; ?>

<?php else: ?>
<!-- ============ STATE 2/3: In a Team ============ -->
<?php
$teamInfo = GetTeamInfo($myTeamID);
$leaderName = GetPlayerNameFromID($teamInfo->LeaderID);
$members = ListPlayersInTeam($myTeamID);
?>

<div class="side">
<div class="panel" style="width:300px;">
<h3>Team: <?php echo htmlspecialchars($teamInfo->Name); ?></h3>
<p>Colour: <img src="teamcolour.img.php?id=<?php echo $myTeamID; ?>" style="vertical-align:middle;"></p>
<p>Leader: <a href="player.php?id=<?php echo $teamInfo->LeaderID; ?>"><?php echo htmlspecialchars($leaderName); ?></a></p>
<p>Members: <?php echo count($members); ?></p>
<p>Planets: <?php echo GetNumberOfPlanetsInTeam($myTeamID); ?></p>
<h3>Members</h3>
<ul>
<?php foreach($members as $pid): ?>
<li><a href="player.php?id=<?php echo $pid; ?>"><?php echo htmlspecialchars(GetPlayerNameFromID($pid)); ?></a>
<?php if($pid == $teamInfo->LeaderID) echo " <small>(Leader)</small>"; ?></li>
<?php endforeach; ?>
</ul>
<form method="POST" action="teams.php" onsubmit="return confirm('Are you sure you want to leave this team?');">
<input type="hidden" name="action" value="leave">
<?php echo csrf_token(); ?>
<input type="submit" value="Leave Team">
</form>
</div>

<!-- ============ Leader Election ============ -->
<div class="panel" style="width:300px;">
<?php if($teamInfo->VoteActive): ?>
<h3>Leader Election</h3>
<p><strong><?php echo $teamInfo->VoteTurnsLeft; ?> turn(s) remaining</strong></p>
<?php
$voteStatus = GetVoteStatus($myTeamID);
$myCurrentVote = $voteStatus["votes"][$myPID] ?? 0;
?>
<form method="POST" action="teams.php">
<input type="hidden" name="action" value="vote">
<?php echo csrf_token(); ?>
<p>Vote for:
<select name="candidate">
<?php foreach($members as $pid): ?>
<option value="<?php echo $pid; ?>"<?php if($pid == $myCurrentVote) echo " selected"; ?>>
<?php echo htmlspecialchars(GetPlayerNameFromID($pid)); ?></option>
<?php endforeach; ?>
</select>
<input type="submit" value="<?php echo $myCurrentVote ? 'Change Vote' : 'Cast Vote'; ?>">
</p>
</form>
<?php if($myCurrentVote): ?>
<p><small>Your current vote: <?php echo htmlspecialchars(GetPlayerNameFromID($myCurrentVote)); ?></small></p>
<?php endif; ?>
<h3>Current Tally</h3>
<?php
if(empty($voteStatus["tally"])){
	echo "<p>No votes cast yet.</p>";
} else {
	foreach($voteStatus["tally"] as $cid => $cnt){
		echo "<p>" . htmlspecialchars(GetPlayerNameFromID($cid)) . ": $cnt vote(s)</p>";
	}
}
?>
<p><small><?php echo count($voteStatus["votes"]); ?>/<?php echo count($members); ?> members voted</small></p>
<?php else: ?>
<?php
$activeMotion = GetActiveMotion($myTeamID);
$motionThreshold = (float)GetGameSetting('election_motion_threshold', 25) / 100;
$neededSeconds = max(1, ceil(count($members) * $motionThreshold));
?>
<h3>Leadership</h3>
<?php if($activeMotion): ?>
<?php
$motionSeconds = GetMotionSeconds($myTeamID);
$secondCount = count($motionSeconds);
$hasSeconded = HasSecondedMotion($myTeamID, $myPID);
?>
<p><strong>Motion for election</strong> raised by <?php echo htmlspecialchars(GetPlayerNameFromID($activeMotion->ProposerID)); ?></p>
<p>Seconds: <strong><?php echo $secondCount; ?>/<?php echo $neededSeconds; ?></strong> needed</p>
<?php if(!$hasSeconded): ?>
<form method="POST" action="teams.php" onsubmit="return confirm('Second this motion for a leadership election?');">
<input type="hidden" name="action" value="secondmotion">
<?php echo csrf_token(); ?>
<input type="submit" value="Second Motion">
</form>
<?php else: ?>
<p><small>You have seconded this motion.</small></p>
<?php endif; ?>
<?php else: ?>
<p><small>Any member can raise a motion for election. It requires <?php echo round($motionThreshold * 100); ?>% of members (<?php echo $neededSeconds; ?>) to second it before an election begins.</small></p>
<?php if(count($members) >= 2): ?>
<form method="POST" action="teams.php" onsubmit="return confirm('Raise a motion for a leadership election?');">
<input type="hidden" name="action" value="raisemotion">
<?php echo csrf_token(); ?>
<input type="submit" value="Raise Motion for Election">
</form>
<?php endif; ?>
<?php endif; ?>
<?php if($isLeader && count($members) >= 2): ?>
<hr style="border-color:#444;">
<form method="POST" action="teams.php" onsubmit="return confirm('Resign as team leader? An election will be called immediately.');">
<input type="hidden" name="action" value="resignleader">
<?php echo csrf_token(); ?>
<input type="submit" value="Resign as Leader">
</form>
<?php endif; ?>
<?php endif; ?>
</div>
</div>

<!-- ============ Leader-Only: Edit & Join Requests ============ -->
<?php if($isLeader): ?>
<div class="planet" style="width:400px;">
<div class="panel">
<h3>Edit Team</h3>
<form method="POST" action="teams.php">
<input type="hidden" name="action" value="edit">
<?php echo csrf_token(); ?>
<p>Team Name:<br/><input type="text" name="teamname" value="<?php echo htmlspecialchars($teamInfo->Name); ?>" maxlength="100" size="25"></p>
<p>Colour:</p>
<div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px;">
<?php
$presets = GetTeamColourPresets();
$takenColours = [];
$otherTeams = ListAllTeams();
foreach($otherTeams as $ot) if($ot->TeamID != $myTeamID) $takenColours[] = $ot->Colour;
foreach($presets as $c):
	$taken = in_array($c, $takenColours);
	$selected = ($c === $teamInfo->Colour);
?>
<label style="display:block;width:28px;height:28px;background:rgb(<?php echo $c; ?>);border:2px solid <?php echo $selected ? '#FFF' : '#666'; ?>;cursor:<?php echo $taken ? 'not-allowed' : 'pointer'; ?>;opacity:<?php echo $taken ? '0.25' : '1'; ?>;position:relative;">
<input type="radio" name="teamcolour" value="<?php echo $c; ?>" <?php if($selected) echo 'checked'; ?> <?php if($taken) echo 'disabled'; ?> style="position:absolute;opacity:0;width:100%;height:100%;margin:0;cursor:inherit;" onclick="this.parentElement.parentElement.querySelectorAll('label').forEach(l=>l.style.borderColor='#666');this.parentElement.style.borderColor='#FFF';">
</label>
<?php endforeach; ?>
</div>
<p><input type="submit" value="Save Changes"></p>
</form>
</div>

<div class="panel">
<h3>Join Requests</h3>
<?php
$requests = GetPendingJoinRequests($myTeamID);
if(count($requests) == 0){
	echo "<p>No pending requests.</p>";
} else {
	foreach($requests as $req){
		$reqPlayerName = GetPlayerNameFromID($req->PlayerID);
?>
<div style="margin:5px 0; padding:5px; border:1px solid #333;">
<a href="player.php?id=<?php echo $req->PlayerID; ?>"><?php echo htmlspecialchars($reqPlayerName); ?></a>
 <small>(<?php echo $req->RequestedAt; ?>)</small>
<form method="POST" action="teams.php" style="display:inline;">
<input type="hidden" name="action" value="approve">
<input type="hidden" name="requestid" value="<?php echo $req->RequestID; ?>">
<?php echo csrf_token(); ?>
<input type="submit" value="Approve">
</form>
<form method="POST" action="teams.php" style="display:inline;">
<input type="hidden" name="action" value="deny">
<input type="hidden" name="requestid" value="<?php echo $req->RequestID; ?>">
<?php echo csrf_token(); ?>
<input type="submit" value="Deny">
</form>
</div>
<?php
	}
}
?>
</div>
</div>
<?php endif; ?>

<?php endif; ?>

</body>
</html>
