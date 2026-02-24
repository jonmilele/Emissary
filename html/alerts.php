<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

$myPID = GetPlayerIDFromName($username);

// Handle POST actions
if(isset($_POST['action']) && csrf_validate()){
	if($_POST['action'] == 'markread'){
		$cat = $_POST['category'] ?? '';
		MarkAlertsRead($myPID, $cat);
		$redir = 'alerts.php';
		$qs = [];
		if($cat !== '') $qs[] = 'cat=' . urlencode($cat);
		if(!empty($_POST['date'])) $qs[] = 'date=' . urlencode($_POST['date']);
		if(!empty($_POST['unread'])) $qs[] = 'unread=1';
		if($qs) $redir .= '?' . implode('&', $qs);
		header("Location: $redir");
		exit;
	}
	if($_POST['action'] == 'mark_one'){
		$alertID = (int)($_POST['alert_id'] ?? 0);
		MarkAlertRead($alertID, $myPID);
		$redir = 'alerts.php';
		$returnQS = $_POST['return_qs'] ?? '';
		if($returnQS !== '') $redir .= '?' . $returnQS;
		header("Location: $redir");
		exit;
	}
}

$categories = ['', 'system', 'fleet', 'construction', 'combat', 'team', 'economy'];
$catLabels = ['' => 'All', 'system' => 'System', 'fleet' => 'Fleet', 'construction' => 'Construction', 'combat' => 'Combat', 'team' => 'Team', 'economy' => 'Economy'];

$currentCat = $_GET['cat'] ?? '';
if(!in_array($currentCat, $categories)) $currentCat = '';

$currentDate = $_GET['date'] ?? '';
if($currentDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $currentDate)) $currentDate = '';

$unreadOnly = isset($_GET['unread']) && $_GET['unread'] == '1';

$alerts = GetAlerts($myPID, $currentCat, 200, 0, $currentDate, $unreadOnly);
$unreadCount = GetUnreadAlertCount($myPID);
$alertDates = GetAlertDates($myPID);

// Build query string helper
function alertQS($params = []){
	$qs = [];
	foreach($params as $k => $v){
		if($v !== '' && $v !== null && $v !== false) $qs[] = urlencode($k) . '=' . urlencode($v);
	}
	return $qs ? '?' . implode('&', $qs) : '';
}
$currentQS = http_build_query(array_filter([
	'cat' => $currentCat,
	'date' => $currentDate,
	'unread' => $unreadOnly ? '1' : '',
], function($v){ return $v !== '' && $v !== null; }));
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Alerts</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
<style>
	.alert-tabs { margin: 10px 0; }
	.alert-tabs a { display: inline-block; padding: 4px 12px; margin: 0 2px; border: 1px solid #444; color: #ccc; text-decoration: none; font-size: 10pt; }
	.alert-tabs a:hover { border-color: #ff9900; color: #ff9900; }
	.alert-tabs a.active { border-color: #ff9900; color: #ff9900; background: #331a00; }
	.alert-row { padding: 4px 0; border-bottom: 1px solid #333; }
	.alert-filter-bar { margin: 8px 0; padding: 6px 0; border-bottom: 1px solid #333; }
	.alert-filter-bar a { color: #ccc; text-decoration: none; margin: 0 4px; font-size: 10pt; }
	.alert-filter-bar a:hover { color: #ff9900; }
	.alert-filter-bar a.active { color: #ff9900; font-weight: bold; }
	.date-nav { margin: 8px 0; }
	.date-nav a { display: inline-block; padding: 2px 8px; margin: 0 2px; border: 1px solid #333; color: #ccc; text-decoration: none; font-size: 9pt; }
	.date-nav a:hover { border-color: #ff9900; color: #ff9900; }
	.date-nav a.active { border-color: #ff9900; color: #ff9900; background: #331a00; }
</style>
</head>
<body>
<?php include("header.inc.php"); ?>

<h2>Alerts<?php if($unreadCount > 0) echo " <span style=\"color:#ff9900;\">($unreadCount unread)</span>"; ?></h2>

<!-- Category tabs -->
<div class="alert-tabs">
<?php foreach($catLabels as $key => $label):
	$tabQS = alertQS(['cat' => $key, 'date' => $currentDate, 'unread' => $unreadOnly ? '1' : '']);
?>
<a href="alerts.php<?php echo $tabQS; ?>" class="<?php echo $currentCat === $key ? 'active' : ''; ?>"><?php echo $label; ?></a>
<?php endforeach; ?>
</div>

<!-- Unread filter toggle -->
<div class="alert-filter-bar">
	Filter:
	<?php $allQS = alertQS(['cat' => $currentCat, 'date' => $currentDate]); ?>
	<?php $unreadQS = alertQS(['cat' => $currentCat, 'date' => $currentDate, 'unread' => '1']); ?>
	<a href="alerts.php<?php echo $allQS; ?>" class="<?php echo !$unreadOnly ? 'active' : ''; ?>">All</a> |
	<a href="alerts.php<?php echo $unreadQS; ?>" class="<?php echo $unreadOnly ? 'active' : ''; ?>">Unread Only</a>
</div>

<!-- Date navigation -->
<?php if(count($alertDates) > 1): ?>
<div class="date-nav">
	<?php $allDatesQS = alertQS(['cat' => $currentCat, 'unread' => $unreadOnly ? '1' : '']); ?>
	<a href="alerts.php<?php echo $allDatesQS; ?>" class="<?php echo $currentDate === '' ? 'active' : ''; ?>">All Dates</a>
	<?php foreach($alertDates as $d):
		$dateLabel = date('j M', strtotime($d));
		$dQS = alertQS(['cat' => $currentCat, 'date' => $d, 'unread' => $unreadOnly ? '1' : '']);
	?>
	<a href="alerts.php<?php echo $dQS; ?>" class="<?php echo $currentDate === $d ? 'active' : ''; ?>"><?php echo $dateLabel; ?></a>
	<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Mark all read button -->
<?php
$_hasUnreadInView = false;
foreach($alerts as $a){ if(!$a->IsRead){ $_hasUnreadInView = true; break; } }
if($_hasUnreadInView): ?>
<form method="post" action="alerts.php" style="margin: 8px 0;">
	<input type="hidden" name="action" value="markread">
	<input type="hidden" name="category" value="<?php echo h($currentCat); ?>">
	<input type="hidden" name="date" value="<?php echo h($currentDate); ?>">
	<?php if($unreadOnly): ?><input type="hidden" name="unread" value="1"><?php endif; ?>
	<?php echo csrf_token(); ?>
	<input type="submit" value="Mark <?php echo $currentCat !== '' ? h($catLabels[$currentCat]) : 'All'; ?> as Read">
</form>
<?php endif; ?>

<!-- Alert list -->
<div class="panel" style="width:700px;">
<?php if(count($alerts) == 0): ?>
<p>No alerts<?php
	$filterDesc = [];
	if($currentCat !== '') $filterDesc[] = 'in ' . h($catLabels[$currentCat]);
	if($currentDate !== '') $filterDesc[] = 'on ' . date('j M Y', strtotime($currentDate));
	if($unreadOnly) $filterDesc[] = '(unread)';
	if($filterDesc) echo ' ' . implode(' ', $filterDesc);
?>.</p>
<?php else: ?>
<?php foreach($alerts as $alert): ?>
<?php echo RenderAlertRow($alert, $currentQS); ?>
<?php endforeach; ?>
<?php endif; ?>
</div>

</body>
</html>
