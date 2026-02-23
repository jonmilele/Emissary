<?php
// ============================================
// Alert System Functions
// Replaces the old gamelog/Report() system
// ============================================

function AddAlert($PlayerID, $category, $message, $linkURL = ''){
	$pid = (int)$PlayerID;
	if($pid < 1) return;
	$cat = mysqli_real_escape_string($GLOBALS["conn"], $category);
	$msg = mysqli_real_escape_string($GLOBALS["conn"], $message);
	$link = mysqli_real_escape_string($GLOBALS["conn"], $linkURL);
	$sql = "INSERT INTO alerts(PlayerID, Category, Message, LinkURL, CreatedAt) VALUES('$pid', '$cat', '$msg', '$link', NOW())";
	mysqli_query($GLOBALS["conn"], $sql);
}

function AddAlertForCurrentUser($category, $message, $linkURL = ''){
	global $username;
	if(empty($username)) return;
	$pid = GetPlayerIDFromName($username);
	AddAlert($pid, $category, $message, $linkURL);
}

function GetAlerts($PlayerID, $category = '', $limit = 50, $offset = 0, $date = '', $unreadOnly = false){
	$pid = (int)$PlayerID;
	$limit = (int)$limit;
	$offset = (int)$offset;
	$sql = "SELECT * FROM alerts WHERE PlayerID = '$pid'";
	if($category !== ''){
		$cat = mysqli_real_escape_string($GLOBALS["conn"], $category);
		$sql .= " AND Category = '$cat'";
	}
	if($date !== ''){
		$eDate = mysqli_real_escape_string($GLOBALS["conn"], $date);
		$sql .= " AND DATE(CreatedAt) = '$eDate'";
	}
	if($unreadOnly){
		$sql .= " AND IsRead = 0";
	}
	$sql .= " ORDER BY CreatedAt DESC LIMIT $offset, $limit";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$alerts = array();
	if($res){
		while($row = mysqli_fetch_object($res)){
			$alerts[] = $row;
		}
	}
	return $alerts;
}

function GetUnreadAlertCount($PlayerID){
	$pid = (int)$PlayerID;
	$sql = "SELECT COUNT(*) AS cnt FROM alerts WHERE PlayerID = '$pid' AND IsRead = 0";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if(!$res) return 0;
	$row = mysqli_fetch_object($res);
	return $row ? (int)$row->cnt : 0;
}

function MarkAlertsRead($PlayerID, $category = ''){
	$pid = (int)$PlayerID;
	$sql = "UPDATE alerts SET IsRead = 1 WHERE PlayerID = '$pid' AND IsRead = 0";
	if($category !== ''){
		$cat = mysqli_real_escape_string($GLOBALS["conn"], $category);
		$sql .= " AND Category = '$cat'";
	}
	mysqli_query($GLOBALS["conn"], $sql);
}

function MarkAlertRead($AlertID, $PlayerID){
	$aid = (int)$AlertID;
	$pid = (int)$PlayerID;
	if($aid < 1 || $pid < 1) return;
	mysqli_query($GLOBALS["conn"], "UPDATE alerts SET IsRead = 1 WHERE AlertID = '$aid' AND PlayerID = '$pid'");
}

function GetAlertDates($PlayerID){
	$pid = (int)$PlayerID;
	$sql = "SELECT DISTINCT DATE(CreatedAt) AS AlertDate FROM alerts WHERE PlayerID = '$pid' ORDER BY AlertDate DESC";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$dates = [];
	if($res){
		while($row = mysqli_fetch_object($res)){
			$dates[] = $row->AlertDate;
		}
	}
	return $dates;
}

function PurgeOldAlerts($days = 30){
	$days = (int)$days;
	$sql = "DELETE FROM alerts WHERE CreatedAt < DATE_SUB(NOW(), INTERVAL $days DAY)";
	mysqli_query($GLOBALS["conn"], $sql);
}

function GetAlertCategoryLabel($category){
	$labels = [
		'system' => 'System',
		'fleet' => 'Fleet',
		'construction' => 'Construction',
		'combat' => 'Combat',
		'team' => 'Team',
		'economy' => 'Economy',
	];
	return $labels[$category] ?? ucfirst($category);
}

function RenderAlertRow($alert, $queryString = ''){
	$time = date('d M H:i', strtotime($alert->CreatedAt));
	$cat = GetAlertCategoryLabel($alert->Category);
	$isUnread = !$alert->IsRead;
	$bold = $isUnread ? 'font-weight:bold;' : '';
	$msg = h($alert->Message);
	if(!empty($alert->LinkURL)){
		$msg .= ' <a href="' . h($alert->LinkURL) . '">[view]</a>';
	}
	$statusIcon = '';
	if($isUnread){
		$statusIcon = '<form method="post" action="alerts.php" style="display:inline;margin-right:6px;">'
			. '<input type="hidden" name="action" value="mark_one">'
			. '<input type="hidden" name="alert_id" value="' . (int)$alert->AlertID . '">'
			. '<input type="hidden" name="return_qs" value="' . h($queryString) . '">'
			. csrf_token()
			. '<button type="submit" style="background:none;border:none;color:#ff9900;cursor:pointer;padding:0;font-size:14pt;line-height:1;vertical-align:middle;" title="Mark as read">&#9673;</button>'
			. '</form>';
	} else {
		$statusIcon = '<span style="color:#555;font-size:14pt;line-height:1;vertical-align:middle;margin-right:6px;">&mdash;</span>';
	}
	return '<div class="alert-row" style="' . $bold . 'padding:4px 0;border-bottom:1px solid #333;">'
		. $statusIcon
		. '<span style="color:#888;font-size:10pt;">[' . $time . ']</span> '
		. '<span style="color:#ff9900;font-size:10pt;">[' . h($cat) . ']</span> '
		. $msg
		. '</div>';
}
?>
