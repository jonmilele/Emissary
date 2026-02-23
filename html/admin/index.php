<?php
include("../authenticate.inc.php");
include("../connect.inc.php");
include_once("../userfunctions.inc.php");

// --- Admin check: only PlayerID 1 can access ---
$adminID = GetPlayerIDFromName($username);
if($adminID != 1){
	SetFlash("Access denied — admin only");
	header("Location: /home.php");
	exit;
}

// Allow tools.php to be included from this authenticated admin context
define('ADMIN_TOOLS_ACCESS', true);

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
		case "save_settings":
			include_once(__DIR__ . "/../turnfunctions.inc.php");
			$setting_keys = [
				'home_hp_multiplier','home_income_multiplier',
				'buy_planet_metal','buy_planet_mineral','buy_planet_astrium',
				'harvester_bonus','election_duration',
				'election_auto_interval','election_motion_threshold',
				'starting_metal','starting_mineral','starting_astrium',
				'planet_weapon_hit_chance','base_construction_slots',
				'default_auction_turns'
			];
			$updated = 0;
			foreach($setting_keys as $sk){
				if(isset($_POST[$sk])){
					SetGameSetting($sk, $_POST[$sk]);
					$updated++;
				}
			}
			echo "$updated game settings saved.";
			break;
		case "rename_system_default":
			$sid = intval($_POST['system_id'] ?? 0);
			$newName = trim($_POST['default_name'] ?? '');
			if($sid > 0 && strlen($newName) >= 2 && strlen($newName) <= 100){
				$eName = mysqli_real_escape_string($GLOBALS["conn"], $newName);
				mysqli_query($GLOBALS["conn"], "UPDATE Systems SET DefaultName = '$eName' WHERE SystemID = '$sid'");
				if(mysqli_affected_rows($GLOBALS["conn"]) > 0){
					echo "System $sid default name changed to: $newName";
				} else {
					echo "System $sid not found or name unchanged";
				}
			} else {
				echo "Valid System ID and name (2-100 chars) required";
			}
			break;
		case "save_forbidden_words":
			$content = $_POST['forbidden_words'] ?? '';
			$file = __DIR__ . '/../data/forbidden_words.txt';
			@mkdir(dirname($file), 0755, true);
			file_put_contents($file, $content);
			echo "Forbidden words list saved (" . substr_count(trim($content), "\n") + (trim($content) !== '' ? 1 : 0) . " lines).";
			break;
		case "the_burn":
			include("tools.php");
			include_once("../turnfunctions.inc.php");
			// Wipe all game data (preserve players and reference tables)
		$wipe_tables = ["planets","Systems","ships","fleets","buildings","cbuildings","cships","qships","battles","auctions","auction_cooldowns","gamelog","alerts"];
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
		include_once(__DIR__ . "/../turnfunctions.inc.php");
		SetGameSetting('names_counter', '1');
		echo "System names counter reset\n";
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
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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

<!-- Game Settings -->
<?php
	include_once(__DIR__ . "/../turnfunctions.inc.php");
	$gs_defs = [
		'home_hp_multiplier'     => ['label' => 'Home World HP Multiplier',       'default' => '1.5',  'hint' => 'Building HP multiplier on home planets (e.g. 1.5 = +50%)'],
		'home_income_multiplier' => ['label' => 'Home World Income Multiplier',   'default' => '2',    'hint' => 'Resource income multiplier on home planet (e.g. 2 = double)'],
		'harvester_bonus'        => ['label' => 'Harvester Income Bonus',         'default' => '0.05', 'hint' => 'Per-harvester income bonus (0.05 = 5% each, stacks)'],
		'starting_metal'         => ['label' => 'Starting Metal',                 'default' => '500',  'hint' => 'Metal given to new players'],
		'starting_mineral'       => ['label' => 'Starting Mineral',               'default' => '250',  'hint' => 'Mineral given to new players'],
		'starting_astrium'       => ['label' => 'Starting Astrium',               'default' => '50',   'hint' => 'Astrium given to new players'],
		'buy_planet_metal'       => ['label' => 'Buy Planet Cost: Metal',         'default' => '2000', 'hint' => 'Metal cost to purchase a planet'],
		'buy_planet_mineral'     => ['label' => 'Buy Planet Cost: Mineral',       'default' => '1000', 'hint' => 'Mineral cost to purchase a planet'],
		'buy_planet_astrium'     => ['label' => 'Buy Planet Cost: Astrium',       'default' => '200',  'hint' => 'Astrium cost to purchase a planet'],
		'election_duration'      => ['label' => 'Election Duration (turns)',      'default' => '5',    'hint' => 'Number of income turns before election resolves'],
		'election_auto_interval' => ['label' => 'Auto-Election Interval (turns)', 'default' => '100',  'hint' => 'Automatic election every N income turns (0 = disabled)'],
		'election_motion_threshold' => ['label' => 'Election Motion Threshold (%)', 'default' => '25', 'hint' => 'Percentage of team members needed to second a motion'],
		'planet_weapon_hit_chance' => ['label' => 'Planet Weapon Hit Chance (1-in-N)', 'default' => '3', 'hint' => 'Planet weapons fire with 1-in-N chance per round (higher = less accurate)'],
		'base_construction_slots'  => ['label' => 'Base Construction Slots',            'default' => '1',  'hint' => 'Construction queue slots per planet before factories (each factory adds +1)'],
		'default_auction_turns'    => ['label' => 'Default Auction Duration (turns)',    'default' => '5',  'hint' => 'Default number of income turns an auction lasts (each turn = 30 min)'],
	];
?>
<div class="admin-section">
	<h3>Game Settings</h3>
	<form method="post">
		<input type="hidden" name="action" value="save_settings">
		<table>
			<tr><th>Setting</th><th>Value</th><th>Hint</th></tr>
			<?php foreach($gs_defs as $key => $def): ?>
			<tr>
				<td><?php echo $def['label']; ?></td>
				<td><input type="text" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars(GetGameSetting($key, $def['default'])); ?>" size="10"></td>
				<td><small><?php echo $def['hint']; ?></small></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<br>
		<input type="submit" value="Save Settings">
	</form>
</div>

<!-- Player List -->
<div class="admin-section">
	<h3>Players</h3>
	<table>
		<tr><th>ID</th><th>Username</th><th>Email</th><th>Team</th><th>Metal</th><th>Mineral</th><th>Astrium</th><th>Credits</th></tr>
		<?php while($p = mysqli_fetch_object($players_result)): ?>
		<tr>
			<td><?php echo $p->PlayerID; ?></td>
			<td><?php echo h($p->UserName); ?></td>
			<td><?php echo $p->Email; ?></td>
			<td><?php echo $p->TeamID > 0 ? h(TeamNameFromID($p->TeamID)) : "-"; ?></td>
			<td><?php echo $p->Metal; ?></td>
			<td><?php echo $p->Mineral; ?></td>
			<td><?php echo $p->Astrium; ?></td>
			<td><?php echo $p->Credits; ?></td>
		</tr>
		<?php endwhile; ?>
	</table>
</div>

<!-- System Management -->
<div class="admin-section">
	<h3>System Management</h3>
	<form method="post" style="display:inline-block;">
		<input type="hidden" name="action" value="rename_system_default">
		System ID: <input type="number" name="system_id" min="1" size="4" required>
		New Default Name: <input type="text" name="default_name" maxlength="100" size="20" required>
		<input type="submit" value="Set Default Name">
	</form>
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

<!-- Forbidden Words -->
<div class="admin-section">
	<h3>Forbidden Words (Name Filter)</h3>
	<p><small>One word per line. Lines starting with # are comments. Matching is case-insensitive substring.</small></p>
	<form method="post">
		<input type="hidden" name="action" value="save_forbidden_words">
		<textarea name="forbidden_words" rows="12" cols="40" style="background:#222;color:#fff;border:1px solid #555;font-family:monospace;"><?php
			$_fwFile = __DIR__ . '/../data/forbidden_words.txt';
			if(file_exists($_fwFile)) echo htmlspecialchars(file_get_contents($_fwFile));
		?></textarea><br>
		<input type="submit" value="Save Forbidden Words">
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
