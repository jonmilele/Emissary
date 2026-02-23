<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

if(isset($_POST['action']) && $_POST['action'] === 'dismiss_summary' && csrf_validate()){
	unset($_SESSION['show_login_summary']);
	header("Location: home.php");
	exit;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Home</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<p>Home</p>
<p>Welcome <?php echo PlayerProfileLink(GetPlayerIDFromName($username)); ?>. [<a href="logout.back.php">Logout</a>]</p>
<p>You control <?php echo GetNumberOfPlanets(GetPlayerIDFromName($username)); ?> 
  planet(s).</p>
<?php if(!empty($_SESSION['show_login_summary']) && !empty($_SESSION['prev_login_time'])):
$_prevTime = $_SESSION['prev_login_time'];
$_resDiff = $_SESSION['resource_diff'];
$_planetDiff = $_SESSION['planet_diff'] ?? 0;
$_myPID_sum = GetPlayerIDFromName($username);
$_sinceEsc = mysqli_real_escape_string($GLOBALS["conn"], $_prevTime);
?>
<div class="panel" style="width:620px; border-color:#ff9900; margin:10px 0 15px 0;">
  <h3 style="color:#ff9900;">Since Your Last Login
    <form method="POST" action="home.php" style="display:inline; float:right; margin:0;">
      <input type="hidden" name="action" value="dismiss_summary">
      <?php echo csrf_token(); ?>
      <button type="submit" style="background:none; border:none; color:#999; cursor:pointer; font-size:16pt; line-height:1;" title="Dismiss">&times;</button>
    </form>
  </h3>
  <p style="color:#888; margin:2px 0 8px 0;"><small>Last login: <?php echo date('d M Y H:i T', strtotime($_prevTime)); ?></small></p>
  <p style="margin:4px 0;"><strong>Resources:</strong>
  <?php
  foreach(['Metal','Mineral','Astrium'] as $_rType){
      $_rv = $_resDiff[$_rType];
      $_rc = $_rv > 0 ? '#00ff00' : ($_rv < 0 ? '#ff4444' : '#888');
      $_rs = $_rv > 0 ? '+' : '';
      echo " <span style=\"color:$_rc;\">$_rs$_rv</span> $_rType &nbsp;";
  }
  ?>
  </p>
  <?php if($_planetDiff != 0): ?>
  <p style="margin:4px 0;">Planets: <span style="color:<?php echo $_planetDiff > 0 ? '#00ff00' : '#ff4444'; ?>">
    <?php echo ($_planetDiff > 0 ? '+' : '') . $_planetDiff; ?></span></p>
  <?php endif; ?>
  <?php
  $_catRes = mysqli_query($GLOBALS["conn"], "SELECT Category, COUNT(*) AS cnt FROM alerts WHERE PlayerID='$_myPID_sum' AND CreatedAt > '$_sinceEsc' GROUP BY Category ORDER BY cnt DESC");
  $_totalAlerts = 0;
  $_catCounts = [];
  if($_catRes){
      while($_cr = mysqli_fetch_object($_catRes)){
          $_catCounts[$_cr->Category] = (int)$_cr->cnt;
          $_totalAlerts += (int)$_cr->cnt;
      }
  }
  if($_totalAlerts > 0):
  ?>
  <p style="margin:4px 0;"><strong>Activity:</strong> <?php echo $_totalAlerts; ?> notification(s)<br/>
  <?php foreach($_catCounts as $_cat => $_cnt): ?>
    <span style="color:#ff9900;"><?php echo h(GetAlertCategoryLabel($_cat)); ?>:</span> <?php echo $_cnt; ?> &nbsp;
  <?php endforeach; ?>
  </p>
  <?php
  $_kaRes = mysqli_query($GLOBALS["conn"], "SELECT * FROM alerts WHERE PlayerID='$_myPID_sum' AND CreatedAt > '$_sinceEsc' AND Category IN ('combat','fleet') ORDER BY CreatedAt DESC LIMIT 5");
  $_keyAlerts = [];
  if($_kaRes){
      while($_ka = mysqli_fetch_object($_kaRes)) $_keyAlerts[] = $_ka;
  }
  if(count($_keyAlerts) > 0):
  ?>
  <p style="margin:8px 0 4px 0;"><strong>Key Events:</strong></p>
  <?php foreach($_keyAlerts as $_ka): ?>
    <?php echo RenderAlertRow($_ka); ?>
  <?php endforeach; ?>
  <?php endif; ?>
  <?php endif; ?>
  <p style="margin:8px 0 0 0;"><a href="alerts.php">View all alerts &raquo;</a></p>
</div>
<?php endif; ?>
  <div class="side">
<div class="panel" style="width:300px;">
<h3>Finance Report</h3>
<?php $income = GetUserIncome(GetPlayerIDFromName($username)); ?>
<p>Income Next Turn: <br/>
<?php echo $income->Metal; ?> Metal<br/>
<?php echo $income->Mineral; ?> Mineral<br/>
<?php echo $income->Astrium; ?> Astrium</p>
</div>
<div class="panel" style="width:300px;">
<h3>Intelligence Report</h3><p>
<?php
$Fleets = GetIncomingEnemyFleets(GetPlayerIDFromName($username));
if(sizeof($Fleets)>0){
	echo "<strong>Incoming Enemy Fleets!</strong><br/>";
	foreach($Fleets as $k=>$Fleet){
		$post = "";
		// Strategy: 0=orbit, 1=colonise, 2=attack, 3=invade
		switch($Fleet->Strategy){
			case "2": // Attack
				$post = " to attack";
				break;
			case "3": // Invade
				$post = " to invade";
				break;
		}
		echo h(GetFleetName($Fleet->FleetID))." sent by ".h(GetPlayerNameFromID($Fleet->PlayerID))." to ".h(GetPlanetNameFromID(substr($Fleet->Destination,2,strlen($Fleet->Destination)-2))).$post." - ETA: ".$Fleet->TTF." minute(s)<br/>";
	}
}

 ?></p></div></div>
<div class="side">
<div class="panel" style="width: 400px;">
<h3>Recent Alerts</h3>
<?php
$_homeAlerts = GetAlerts(GetPlayerIDFromName($username), '', 10);
if(count($_homeAlerts) == 0){
	echo '<p>No recent alerts.</p>';
} else {
	foreach($_homeAlerts as $_ha){
		echo RenderAlertRow($_ha);
	}
}
?>
<p><a href="alerts.php">View all alerts &raquo;</a></p>
</div></div>

</body>
</html>
