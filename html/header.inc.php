<p>[<a href="/home.php">Home</a>]&nbsp;&nbsp;&nbsp;[<a href="/alerts/index.html">Alerts</a>]&nbsp;&nbsp;&nbsp;[<a href="/trade.php">Trade</a>]&nbsp;&nbsp;&nbsp;[<a href="/galaxy.php">Galaxy</a>]&nbsp;&nbsp;&nbsp;[<a href="/planetlist.php">Planets</a>]&nbsp;&nbsp;&nbsp;[<a href="/systemlist.php">Systems</a>]&nbsp;&nbsp;&nbsp;[<a href="/teams.php">Teams</a>]&nbsp;&nbsp;&nbsp;[<a href="/fleetlist.php">Fleets</a>]&nbsp;&nbsp;&nbsp;[<a href="/battlelist.php">Battles</a>]<?php if(GetPlayerIDFromName($username) == 1): ?>&nbsp;&nbsp;&nbsp;[<a href="/admin/index.php">Admin</a>]<?php endif; ?><br>
  <small>Next turn in: <?php echo MinutesToNextTurn(); ?> minutes 
  <?php
$resources = GetPlayerResources(GetPlayerIDFromName($username));
?>
  <br>
  Resources: <?php echo $resources["Metal"]; ?> Metal, <?php echo $resources["Mineral"]; ?> 
  Mineral, <?php echo $resources["Astrium"]; ?> Astrium</small></p>
  <?php PrintMessage($_GET["msg"] ?? "");?>
