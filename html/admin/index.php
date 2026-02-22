<?php
include("../authenticate.inc.php");
include("../connect.inc.php");
include("../userfunctions.inc.php");

// --- Admin check: only PlayerID 1 can access ---
$adminID = GetPlayerIDFromName($username);
if($adminID != 1){
	echo "Access denied.";
	exit;
}

// --- Handle actions ---
$result_msg = "";
$action = $_POST['action'] ?? "";

if($action != ""){
	ob_start();
	switch($action){
		case "populate_sector":
			$id = intval($_POST['sector_id'] ?? 0);
			if($id > 0 && $id <= 100){
				include("tools.php");
				PopulateSector($id);
			} else {
				echo "Invalid sector ID (1-100)";
			}
			break;
		case "clear_sector":
			$id = intval($_POST['sector_id'] ?? 0);
			if($id > 0 && $id <= 100){
				include("tools.php");
				ClearSector($id);
			} else {
				echo "Invalid sector ID (1-100)";
			}
			break;
		case "clear_planets":
			include("tools.php");
			ClearPlanets();
			break;
		case "reset_password":
			$id = intval($_POST['player_id'] ?? 0);
			$newpass = $_POST['new_password'] ?? "";
			if($id > 0 && $newpass != ""){
				include("tools.php");
				ResetPassword($id, $newpass);
				echo "Password reset for PlayerID $id";
			} else {
				echo "Player ID and new password required";
			}
			break;
		case "calc_owners":
			include("tools.php");
			CalcOwners();
			echo "Sector ownership recalculated";
			break;
		case "run_turn":
			include("../miniturn.cron.php");
			echo "Mini-turn processed (construction, fleet movement)";
			break;
		case "populate_all":
			include("tools.php");
			$populated = 0;
			for($s = 1; $s <= 100; $s++){
				if(SystemsInSector($s) == 0){
					PopulateSector($s);
					$populated++;
				}
			}
			echo "$populated empty sectors populated";
			break;
		case "run_income":
			include("../turn.cron.php");
			echo "Income turn processed";
			break;
		case "reset_timer":
			include_once("../turnfunctions.inc.php");
			ResetTurnTimer();
			echo "Turn timer reset to now. Next income turn in 30 minutes.";
			break;
		case "the_burn":
			include("tools.php");
			include_once("../turnfunctions.inc.php");
			// Wipe all game data (preserve players and reference tables)
			$wipe_tables = ["planets","Systems","ships","fleets","buildings","cbuildings","cships","qships","battles","auctions","gamelog"];
			foreach($wipe_tables as $t){
				mysqli_query($GLOBALS["conn"], "DELETE FROM `$t`");
				echo "Cleared $t (" . mysqli_affected_rows($GLOBALS["conn"]) . " rows)\n";
			}
			// Delete teams
			mysqli_query($GLOBALS["conn"], "DELETE FROM teams");
			echo "Cleared teams (" . mysqli_affected_rows($GLOBALS["conn"]) . " rows)\n";
			// Reset player resources but keep accounts
			mysqli_query($GLOBALS["conn"], "UPDATE players SET Metal=0, Mineral=0, Astrium=0, Credits=0, TeamID=0, SetupStage=0");
			echo "Reset all player resources and teams (" . mysqli_affected_rows($GLOBALS["conn"]) . " players)\n";
			// Reset names counter
			$namesFile = __DIR__ . "/../userdata/names.txt";
			if(file_exists($namesFile)){
				$lines = file($namesFile);
				$lines[0] = "1\n";
				file_put_contents($namesFile, implode("", $lines));
				echo "System names counter reset\n";
			}
			// Repopulate all 100 sectors
			$populated = 0;
			for($s = 1; $s <= 100; $s++){
				PopulateSector($s);
				$populated++;
			}
			echo "$populated sectors repopulated\n";
			// Recalculate owners
			CalcOwners();
			echo "Sector ownership recalculated\n";
			// Reset turn timer
			ResetTurnTimer();
			echo "Turn timer reset\n";
			// Clear Emissary cron entries
			$existing = shell_exec("crontab -l 2>/dev/null") ?: "";
			$filtered = preg_replace("/# Emissary turn processing\n/", "", $existing);
			$filtered = preg_replace("/.*(?:miniturn|turn)\.cron\.php.*\n/", "", $filtered);
			$filtered = trim($filtered);
			if($filtered === ""){ exec("crontab -r 2>/dev/null"); } else { $tmp = tempnam(sys_get_temp_dir(),"cron"); file_put_contents($tmp,$filtered."\n"); exec("crontab $tmp"); unlink($tmp); }
			echo "Cron jobs cleared\n";
			echo "\n*** THE BURN IS COMPLETE ***";
			break;
	}
	$result_msg = ob_get_clean();
}

// --- Gather stats ---
function getCount($table){
	$r = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS c FROM $table");
	$row = mysqli_fetch_object($r);
	return $row->c;
}

$stats = [
	"Players" => getCount("players"),
	"Teams" => getCount("teams"),
	"Planets" => getCount("planets"),
	"Systems" => getCount("Systems"),
	"Sectors" => getCount("sectors"),
	"Ships" => getCount("ships"),
	"Fleets" => getCount("fleets"),
	"Buildings" => getCount("buildings"),
	"Ships Under Construction" => getCount("cships"),
	"Buildings Under Construction" => getCount("cbuildings"),
	"Battles" => getCount("battles"),
	"Auctions" => getCount("auctions"),
];

// --- Player list for reference ---
$players_result = mysqli_query($conn, "SELECT PlayerID, UserName, Email, TeamID, Metal, Mineral, Astrium, Credits FROM players ORDER BY PlayerID");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Admin Panel</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../style.css" rel="stylesheet" type="text/css">
<style>
	.admin-section { background: #1a1a2e; border: 1px solid #444; padding: 10px; margin: 10px 0; }
	.admin-section h3 { margin-top: 0; color: #ff9900; }
	.stats-grid { display: flex; flex-wrap: wrap; gap: 8px; }
	.stat-box { background: #222; padding: 6px 12px; border: 1px solid #555; min-width: 140px; }
	.stat-box strong { color: #ff9900; }
	.result { background: #003300; white-space: pre-wrap; border: 1px solid #00aa00; color: #00ff00; padding: 10px; margin: 10px 0; font-family: monospace; max-height: 200px; overflow-y: auto; }
	.danger { border-color: #aa0000; }
	.danger h3 { color: #ff3333; }
	table { border-collapse: collapse; width: 100%; }
	td, th { border: 1px solid #444; padding: 4px 8px; text-align: left; }
	th { background: #333; }
	input[type=text], input[type=password], input[type=number] { background: #222; color: #fff; border: 1px solid #555; padding: 3px 6px; }
	input[type=submit] { background: #444; color: #fff; border: 1px solid #666; padding: 4px 12px; cursor: pointer; }
	input[type=submit]:hover { background: #555; }
	input[type=submit].danger-btn { background: #660000; border-color: #aa0000; }
	input[type=submit].danger-btn:hover { background: #880000; }
</style>
</head>
<body>
<?php include("../header.inc.php"); ?>
<h2>Admin Panel</h2>

<?php if($result_msg != ""): ?>
<div class="result"><?php echo $result_msg; ?></div>
<?php endif; ?>

<!-- Database Stats -->
<div class="admin-section">
	<h3>Database Stats</h3>
	<div class="stats-grid">
	<?php foreach($stats as $label => $count): ?>
		<div class="stat-box"><strong><?php echo $count; ?></strong> <?php echo $label; ?></div>
	<?php endforeach; ?>
	</div>
</div>

<!-- Player List -->
<div class="admin-section">
	<h3>Players</h3>
	<table>
		<tr><th>ID</th><th>Username</th><th>Email</th><th>Team</th><th>Metal</th><th>Mineral</th><th>Astrium</th><th>Credits</th></tr>
		<?php while($p = mysqli_fetch_object($players_result)): ?>
		<tr>
			<td><?php echo $p->PlayerID; ?></td>
			<td><?php echo $p->UserName; ?></td>
			<td><?php echo $p->Email; ?></td>
			<td><?php echo $p->TeamID > 0 ? TeamNameFromID($p->TeamID) : "-"; ?></td>
			<td><?php echo $p->Metal; ?></td>
			<td><?php echo $p->Mineral; ?></td>
			<td><?php echo $p->Astrium; ?></td>
			<td><?php echo $p->Credits; ?></td>
		</tr>
		<?php endwhile; ?>
	</table>
</div>

<!-- World Generation -->
<div class="admin-section">
	<h3>World Generation</h3>
	<form method="post" style="display:inline-block; margin-right: 20px;">
		<input type="hidden" name="action" value="populate_sector">
		Sector ID: <input type="number" name="sector_id" min="1" max="100" size="4" required>
		<input type="submit" value="Populate Sector">
	</form>
	<form method="post" style="display:inline-block;">
		<input type="hidden" name="action" value="calc_owners">
		<input type="submit" value="Recalculate Sector Owners">
	</form>
	<form method="post" style="display:inline-block; margin-left: 20px;" onsubmit="return confirm('Populate all empty sectors? This may take a moment.');">
		<input type="hidden" name="action" value="populate_all">
		<input type="submit" value="Populate All Empty Sectors">
	</form>
</div>

<!-- Turn Processing -->
<div class="admin-section">
	<h3>Turn Processing</h3>
	<form method="post" style="display:inline-block; margin-right: 20px;">
		<input type="hidden" name="action" value="run_turn">
		<input type="submit" value="Run Mini-Turn (Construction/Movement)">
	</form>
	<form method="post" style="display:inline-block;">
		<input type="hidden" name="action" value="run_income">
		<input type="submit" value="Run Income Turn">
	</form>
	<form method="post" style="display:inline-block; margin-left: 20px;">
		<input type="hidden" name="action" value="reset_timer">
		<input type="submit" value="Reset Turn Timer">
	</form>
</div>

<!-- Player Management -->
<div class="admin-section">
	<h3>Reset Player Password</h3>
	<form method="post">
		<input type="hidden" name="action" value="reset_password">
		Player ID: <input type="number" name="player_id" min="1" size="4" required>
		New Password: <input type="password" name="new_password" required>
		<input type="submit" value="Reset Password">
	</form>
</div>

<!-- Dangerous Actions -->
<div class="admin-section danger">
	<h3>&#9888; Destructive Actions</h3>
	<form method="post" style="display:inline-block; margin-right: 20px;" onsubmit="return confirm('Clear all systems from this sector?');">
		<input type="hidden" name="action" value="clear_sector">
		Sector ID: <input type="number" name="sector_id" min="1" max="100" size="4" required>
		<input type="submit" value="Clear Sector" class="danger-btn">
	</form>
	<form method="post" style="display:inline-block;" onsubmit="return confirm('Delete ALL planets with ID > 6? This cannot be undone!');">
		<input type="hidden" name="action" value="clear_planets">
		<input type="submit" value="Clear All Planets" class="danger-btn">
	</form>
</div>


<!-- The Burn - Galaxy Reset -->
<div class="admin-section danger">
	<h3>&#9760; The Burn - Galaxy Reset</h3>
	<p style="color:#ff6666;">This will wipe the entire galaxy: all planets, systems, ships, fleets, buildings, battles, auctions, and teams. Player accounts are preserved but all resources and progress are reset to zero. The galaxy will be repopulated fresh.</p>
	<form method="post" onsubmit="return confirm('INITIATE THE BURN?\n\nThis will destroy the entire galaxy and reset all player progress.\n\nType BURN to confirm.') && prompt('Type BURN to confirm:') === 'BURN';">
		<input type="hidden" name="action" value="the_burn">
		<input type="submit" value="&#9760; INITIATE THE BURN" class="danger-btn" style="font-size: 16px; padding: 10px 30px;">
	</form>
</div>

</body>
</html>
