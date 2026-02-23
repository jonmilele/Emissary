<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

// Use shared ranking function
$_allPlayers = GetPlayerRanking();

// Enrich with display data
$players = [];
foreach($_allPlayers as $p){
	$pid = $p['pid'];
	$players[] = [
		'pid' => $pid,
		'name' => GetPlayerNameFromID($pid),
		'planetValue' => $p['planetValue'],
		'resourceValue' => $p['resourceValue'],
		'totalValue' => $p['totalValue'],
		'teamID' => (int)PlayerTeam($pid),
		'planets' => GetNumberOfPlanets($pid)
	];
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Leaderboard</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>
<h2>Leaderboard</h2>
<p>Players ranked by total net worth (planet values + resource holdings).</p>
<?php
$rank = 0;
$lastVal = -1;
$displayRank = 0;
foreach($players as $i => $p):
	$rank++;
	if($p['totalValue'] !== $lastVal){ $displayRank = $rank; $lastVal = $p['totalValue']; }
	$teamStr = '';
	if($p['teamID'] > 0){
		$teamStr = ' <img src="teamcolour.img.php?id=' . $p['teamID'] . '" style="vertical-align:middle;" width="20" height="10"> '
			. '<small>[' . htmlspecialchars(TeamNameFromID($p['teamID'])) . ']</small>';
	}
?>
<div class="ship" style="padding:4px 8px;">
<p><strong>#<?php echo $displayRank; ?></strong>
<a href="player.php?id=<?php echo $p['pid']; ?>"><?php echo htmlspecialchars($p['name']); ?></a><?php echo $teamStr; ?><br>
<small>
Net Worth: <strong><?php echo number_format($p['totalValue']); ?>C</strong>
&mdash; Planets: <?php echo number_format($p['planetValue']); ?>C (<?php echo $p['planets']; ?> owned)
&mdash; Resources: <?php echo number_format($p['resourceValue']); ?>C
</small></p>
</div>
<?php endforeach; ?>
<?php if(empty($players)): ?>
<p>No players found.</p>
<?php endif; ?>
</body>
</html>
