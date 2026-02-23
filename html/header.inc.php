<?php
$_hdrPID = GetPlayerIDFromName($username);
$_unreadAlerts = GetUnreadAlertCount($_hdrPID);
$resources = GetPlayerResources($_hdrPID);
?>
<p>[<a href="/home.php">Home</a>]&nbsp;&nbsp;&nbsp;[<a href="/alerts.php">Alerts<?php if($_unreadAlerts > 0) echo ' (' . $_unreadAlerts . ')'; ?></a>]&nbsp;&nbsp;&nbsp;[<a href="/trade.php">Trade</a>]&nbsp;&nbsp;&nbsp;[<a href="/galaxy.php">Galaxy</a>]&nbsp;&nbsp;&nbsp;[<a href="/planetlist.php">Planets</a>]&nbsp;&nbsp;&nbsp;[<a href="/systemlist.php">Systems</a>]&nbsp;&nbsp;&nbsp;[<a href="/teams.php">Teams</a>]&nbsp;&nbsp;&nbsp;[<a href="/fleetlist.php">Fleets</a>]&nbsp;&nbsp;&nbsp;[<a href="/battlelist.php">Battles</a>]<?php if($_hdrPID == 1): ?>&nbsp;&nbsp;&nbsp;[<a href="/admin/index.php">Admin</a>]<?php endif; ?><br>
  <small>Next turn in: <?php echo MinutesToNextTurn(); ?> minutes
  <br>
  Resources: <?php echo $resources["Metal"]; ?> Metal, <?php echo $resources["Mineral"]; ?> 
  Mineral, <?php echo $resources["Astrium"]; ?> Astrium</small></p>
  <?php PrintMessage(GetFlash());?>
