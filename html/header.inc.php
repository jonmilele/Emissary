<?php
$_hdrPID = GetPlayerIDFromName($username);
$_unreadAlerts = GetUnreadAlertCount($_hdrPID);
$resources = GetPlayerResources($_hdrPID);
$_backURL = $_SESSION['back_url'] ?? '';
?>
<p>[<a href="/home.php">Home</a>]&nbsp;&nbsp;&nbsp;[<a href="/alerts.php">Alerts<?php if($_unreadAlerts > 0) echo ' (' . $_unreadAlerts . ')'; ?></a>]&nbsp;&nbsp;&nbsp;[<a href="/trade.php">Trade</a>]&nbsp;&nbsp;&nbsp;[<a href="/galaxy.php">Galaxy</a>]&nbsp;&nbsp;&nbsp;[<a href="/planetlist.php">Planets</a>]&nbsp;&nbsp;&nbsp;[<a href="/systemlist.php">Systems</a>]&nbsp;&nbsp;&nbsp;[<a href="/teams.php">Teams</a>]&nbsp;&nbsp;&nbsp;[<a href="/fleetlist.php">Fleets</a>]&nbsp;&nbsp;&nbsp;[<a href="/battlelist.php">Battles</a>]&nbsp;&nbsp;&nbsp;[<a href="/leaderboard.php">Leaderboard</a>]<?php if($_hdrPID == 1): ?>&nbsp;&nbsp;&nbsp;[<a href="/admin/index.php">Admin</a>]<?php endif; ?><br>
  <small><?php echo date('d M Y H:i T'); ?> &mdash; Next turn in: <?php echo MinutesToNextTurn(); ?> minutes
  <br>
  Resources: <?php echo $resources["Metal"]; ?> Metal, <?php echo $resources["Mineral"]; ?> 
  Mineral, <?php echo $resources["Astrium"]; ?> Astrium</small></p>
  <?php PrintMessage(GetFlash());?>
  <?php if($_backURL !== ''): ?><?php $_backName = $_SESSION['back_name'] ?? ''; ?><p><a href="<?php echo htmlspecialchars($_backURL, ENT_QUOTES, 'UTF-8'); ?>">&laquo; Back<?php if($_backName !== '') echo ' to ' . htmlspecialchars($_backName, ENT_QUOTES, 'UTF-8'); ?></a></p><?php endif; ?>
  <?php if(NeedsHomePlanet($_hdrPID)): ?>
  <p style="background:#660000; color:#FF3333; padding:6px; border:1px solid #FF0000; text-align:center;"><strong>&#9888; You have no Home World!</strong> Visit one of your <a href="planetlist.php" style="color:#FFFF00;">planets</a> and set a new home world.</p>
  <?php endif; ?>
