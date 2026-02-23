<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

$myPID = GetPlayerIDFromName($username);

// Handle mark-read action
if(isset($_POST['action']) && csrf_validate()){
	if($_POST['action'] == 'markread'){
		$cat = $_POST['category'] ?? '';
		MarkAlertsRead($myPID, $cat);
		$redir = 'alerts.php';
		if($cat !== '') $redir .= '?cat=' . urlencode($cat);
		header("Location: $redir");
		exit;
	}
}

$categories = ['', 'system', 'fleet', 'construction', 'combat', 'team', 'economy'];
$catLabels = ['' => 'All', 'system' => 'System', 'fleet' => 'Fleet', 'construction' => 'Construction', 'combat' => 'Combat', 'team' => 'Team', 'economy' => 'Economy'];

$currentCat = $_GET['cat'] ?? '';
if(!in_array($currentCat, $categories)) $currentCat = '';

$alerts = GetAlerts($myPID, $currentCat, 100);
$unreadCount = GetUnreadAlertCount($myPID);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Alerts</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
<style>
	.alert-tabs { margin: 10px 0; }
	.alert-tabs a { display: inline-block; padding: 4px 12px; margin: 0 2px; border: 1px solid #444; color: #ccc; text-decoration: none; font-size: 10pt; }
	.alert-tabs a:hover { border-color: #ff9900; color: #ff9900; }
	.alert-tabs a.active { border-color: #ff9900; color: #ff9900; background: #331a00; }
	.alert-row { padding: 4px 0; border-bottom: 1px solid #333; }
	.alert-unread { font-weight: bold; }
</style>
</head>
<body>
<?php include("header.inc.php"); ?>

<h2>Alerts<?php if($unreadCount > 0) echo " <span style=\"color:#ff9900;\">($unreadCount unread)</span>"; ?></h2>

<div class="alert-tabs">
<?php foreach($catLabels as $key => $label): ?>
<a href="alerts.php<?php echo $key !== '' ? '?cat=' . urlencode($key) : ''; ?>" class="<?php echo $currentCat === $key ? 'active' : ''; ?>"><?php echo $label; ?></a>
<?php endforeach; ?>
</div>

<?php if($unreadCount > 0): ?>
<form method="post" action="alerts.php" style="margin: 8px 0;">
	<input type="hidden" name="action" value="markread">
	<input type="hidden" name="category" value="<?php echo h($currentCat); ?>">
	<?php echo csrf_token(); ?>
	<input type="submit" value="Mark <?php echo $currentCat !== '' ? h($catLabels[$currentCat]) : 'All'; ?> as Read">
</form>
<?php endif; ?>

<div class="panel" style="width:700px;">
<?php if(count($alerts) == 0): ?>
<p>No alerts<?php echo $currentCat !== '' ? ' in this category' : ''; ?>.</p>
<?php else: ?>
<?php foreach($alerts as $alert): ?>
<?php echo RenderAlertRow($alert); ?>
<?php endforeach; ?>
<?php endif; ?>
</div>

</body>
</html>
