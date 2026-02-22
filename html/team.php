<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
$TeamID = ($_GET['id'] ?? "");
$teamInfo = GetTeamInfo($TeamID);
if(!$teamInfo){
	echo "Not a valid team ID";
	exit;
}
$members = ListPlayersInTeam($TeamID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Team: <?php echo htmlspecialchars($teamInfo->Name); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>
<h2>Team: <?php echo htmlspecialchars($teamInfo->Name); ?></h2>
<div class="side">
<div class="panel" style="width:250px;">
<h3>Statistics</h3>
<p>Colour: <img src="teamcolour.img.php?id=<?php echo $TeamID; ?>" style="vertical-align:middle;"></p>
<p>Leader: <a href="player.php?id=<?php echo $teamInfo->LeaderID; ?>"><?php echo htmlspecialchars(GetPlayerNameFromID($teamInfo->LeaderID)); ?></a></p>
<p>Members: <?php echo count($members); ?></p>
<p>Planets Owned: <?php echo GetNumberOfPlanetsInTeam($TeamID); ?></p>
</div>
<div class="panel" style="width:250px;">
<h3>Members</h3>
<ul>
<?php foreach($members as $pid): ?>
<li><a href="player.php?id=<?php echo $pid; ?>"><?php echo htmlspecialchars(GetPlayerNameFromID($pid)); ?></a>
<?php if($pid == $teamInfo->LeaderID) echo " <small>(Leader)</small>"; ?></li>
<?php endforeach; ?>
</ul>
</div>
</div>
<div class="planet" style="width:450px;">
<h3>Election History</h3>
<?php
$history = GetElectionHistory($TeamID);
if(count($history) == 0){
	echo "<p>No elections held yet.</p>";
} else {
	foreach($history as $e){
		$winnerName = htmlspecialchars(GetPlayerNameFromID($e->WinnerID));
		$date = $e->ResolvedAt;
		$runnerUp = $e->RunnerUpID > 0 ? htmlspecialchars(GetPlayerNameFromID($e->RunnerUpID)) . " (" . $e->RunnerUpVotes . ")" : "&mdash;";
?>
<div style="margin:5px 0; padding:5px; border-bottom:1px solid #333;">
<strong><?php echo $date; ?></strong><br/>
Winner: <?php echo $winnerName; ?> (<?php echo $e->Votes; ?> vote(s))<br/>
Runner-up: <?php echo $runnerUp; ?><br/>
<small>Total voters: <?php echo $e->TotalVoters; ?></small>
</div>
<?php
	}
}
?>
</div>
</body>
</html>
