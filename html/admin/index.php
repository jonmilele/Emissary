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
	case "save_email":
			$_ePid = (int)($_POST['player_id'] ?? 0);
			$_eEmail = trim($_POST['email'] ?? '');
			if($_ePid > 0 && $_eEmail !== ''){
				$_eEmailSafe = mysqli_real_escape_string($GLOBALS["conn"], $_eEmail);
				mysqli_query($GLOBALS["conn"], "UPDATE players SET Email='$_eEmailSafe' WHERE PlayerID='$_ePid'");
				echo "Email updated for player #$_ePid";
			} else {
				echo "Invalid player ID or email";
			}
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
				'default_auction_turns','building_salvage_rate',
				'metal_credit_value','mineral_credit_value','astrium_credit_value'
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
		case "save_planet_types":
			$_ptTypes = $_POST['pt_type'] ?? [];
			$_ptUpdated = 0;
			foreach($_ptTypes as $_ptId){
				$_ptId = (int)$_ptId;
				if($_ptId < 1) continue;
				$_ptGrids = (int)($_POST['pt_grids'][$_ptId] ?? 0);
				$_ptRows = (int)($_POST['pt_rowsquares'][$_ptId] ?? 0);
				$_ptMetal = (int)($_POST['pt_metal'][$_ptId] ?? 0);
				$_ptMineral = (int)($_POST['pt_mineral'][$_ptId] ?? 0);
				$_ptAstrium = (int)($_POST['pt_astrium'][$_ptId] ?? 0);
				$_ptIncome = $_ptMetal . ':' . $_ptMineral . ':' . $_ptAstrium;
				$sql = "UPDATE planet_types SET Grids='$_ptGrids', rowsquares='$_ptRows', income='$_ptIncome' WHERE Type='$_ptId'";
				mysqli_query($GLOBALS["conn"], $sql);
				$_ptUpdated++;
			}
			echo "$_ptUpdated planet type(s) updated.";
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
	/* Tab navigation */
	.admin-tabs { display: flex; gap: 0; border-bottom: 2px solid #ff9900; margin: 10px 0 0 0; }
	.admin-tabs a { display: block; padding: 8px 16px; color: #aaa; text-decoration: none; background: #1a1a2e; border: 1px solid #444; border-bottom: none; margin-right: 2px; border-radius: 4px 4px 0 0; }
	.admin-tabs a:hover { color: #fff; background: #2a2a4e; }
	.admin-tabs a.active { color: #ff9900; background: #0d0d1a; border-color: #ff9900; font-weight: bold; }
	.admin-tabs a.tab-danger { color: #ff4444; }
	.admin-tabs a.tab-danger:hover { color: #ff6666; background: #2a1a1a; }
	.admin-tabs a.tab-danger.active { color: #ff3333; border-color: #ff3333; background: #1a0d0d; }
	.tab-panel { display: none; }
	.tab-panel.active { display: block; }
</style>
</head>
<body>
<?php
include("../header.inc.php");
$_tab = $_GET['tab'] ?? ($_POST['_tab'] ?? 'overview');
$_tabs = [
	'overview' => 'Overview',
	'settings' => 'Settings',
	'players'  => 'Players',
	'world'    => 'World',
	'turns'    => 'Turns',
	'moderation' => 'Moderation',
	'danger'   => ['label' => 'Danger Zone', 'class' => 'tab-danger'],
];
?>
<h2>Admin Panel</h2>

<?php if($result_msg != ""): ?>
<div class="result"><?php echo $result_msg; ?></div>
<?php endif; ?>

<div class="admin-tabs">
<?php foreach($_tabs as $_tk => $_tl):
	$_tLabel = is_array($_tl) ? $_tl['label'] : $_tl;
	$_tClass = is_array($_tl) ? ($_tl['class'] ?? '') : '';
	$_tActive = $_tab == $_tk ? 'active' : '';
?>
	<a href="?tab=<?php echo $_tk; ?>" class="<?php echo trim("$_tClass $_tActive"); ?>"><?php echo $_tLabel; ?></a>
<?php endforeach; ?>
</div>

<!-- ==================== OVERVIEW TAB ==================== -->
<div class="tab-panel<?php echo $_tab == 'overview' ? ' active' : ''; ?>">
<div class="admin-section">
	<h3>Database Stats</h3>
	<div class="stats-grid">
	<?php foreach($stats as $label => $count): ?>
		<div class="stat-box"><strong><?php echo $count; ?></strong> <?php echo $label; ?></div>
	<?php endforeach; ?>
	</div>
</div>
</div><!-- /overview -->

<!-- ==================== SETTINGS TAB ==================== -->
<div class="tab-panel<?php echo $_tab == 'settings' ? ' active' : ''; ?>">
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
		'building_salvage_rate'    => ['label' => 'Building Salvage Rate',                'default' => '0.5','hint' => 'Fraction of building cost counted toward planet auction value (0.5 = 50%)'],
		'metal_credit_value'       => ['label' => 'Metal Credit Value',                    'default' => '1',  'hint' => 'Credit value of 1 Metal (used for planet valuation and leaderboard)'],
		'mineral_credit_value'     => ['label' => 'Mineral Credit Value',                  'default' => '10', 'hint' => 'Credit value of 1 Mineral (used for planet valuation and leaderboard)'],
		'astrium_credit_value'     => ['label' => 'Astrium Credit Value',                  'default' => '100','hint' => 'Credit value of 1 Astrium (used for planet valuation and leaderboard)'],
	];
?>
<div class="admin-section">
	<h3>Game Settings</h3>
	<form method="post">
		<input type="hidden" name="_tab" value="settings">
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

<!-- Planet Types -->
<?php
	$_ptSizeLabels = [1 => 'Small', 2 => 'Medium', 3 => 'Large', 4 => 'Huge'];
	$_ptRes = mysqli_query($GLOBALS["conn"], "SELECT * FROM planet_types ORDER BY Type ASC");
	$_ptRows = [];
	while($_ptr = mysqli_fetch_object($_ptRes)) $_ptRows[] = $_ptr;

	// Count existing planets per type
	$_ptCounts = [];
	$_pcRes = mysqli_query($GLOBALS["conn"], "SELECT Size, COUNT(*) AS cnt FROM planets GROUP BY Size");
	while($_pcr = mysqli_fetch_object($_pcRes)) $_ptCounts[(int)$_pcr->Size] = (int)$_pcr->cnt;
?>
<div class="admin-section">
	<h3>Planet Types</h3>
	<p><small>Edit base resource generation and grid slots per planet size. Income is per turn before harvesters/home world bonuses.</small></p>
	<form method="post">
		<input type="hidden" name="_tab" value="settings">
		<input type="hidden" name="action" value="save_planet_types">
		<table>
			<tr><th>Type</th><th>Size</th><th>Grids</th><th>Row Squares</th><th>Metal/turn</th><th>Mineral/turn</th><th>Astrium/turn</th><th>Existing</th></tr>
			<?php foreach($_ptRows as $_pt):
				$_ptInc = explode(':', $_pt->income);
				$_ptLabel = $_ptSizeLabels[(int)$_pt->Type] ?? 'Type '.$_pt->Type;
				$_ptCount = $_ptCounts[(int)$_pt->Type] ?? 0;
			?>
			<tr>
				<td><?php echo $_pt->Type; ?><input type="hidden" name="pt_type[]" value="<?php echo $_pt->Type; ?>"></td>
				<td><?php echo $_ptLabel; ?></td>
				<td><input type="number" name="pt_grids[<?php echo $_pt->Type; ?>]" value="<?php echo $_pt->Grids; ?>" size="5" min="1"></td>
				<td><input type="number" name="pt_rowsquares[<?php echo $_pt->Type; ?>]" value="<?php echo $_pt->rowsquares; ?>" size="5" min="1"></td>
				<td><input type="number" name="pt_metal[<?php echo $_pt->Type; ?>]" value="<?php echo (int)($_ptInc[0] ?? 0); ?>" size="5" min="0"></td>
				<td><input type="number" name="pt_mineral[<?php echo $_pt->Type; ?>]" value="<?php echo (int)($_ptInc[1] ?? 0); ?>" size="5" min="0"></td>
				<td><input type="number" name="pt_astrium[<?php echo $_pt->Type; ?>]" value="<?php echo (int)($_ptInc[2] ?? 0); ?>" size="5" min="0"></td>
				<td style="color:#888;"><?php echo number_format($_ptCount); ?> planet<?php echo $_ptCount != 1 ? 's' : ''; ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<br>
		<input type="submit" value="Save Planet Types">
	</form>
</div>
</div><!-- /settings -->

<!-- ==================== PLAYERS TAB ==================== -->
<div class="tab-panel<?php echo $_tab == 'players' ? ' active' : ''; ?>">
<!-- Player List -->
<div class="admin-section">
	<h3>Players</h3>
	<table>
		<tr><th>ID</th><th>Username</th><th>Email</th><th>Team</th><th>Metal</th><th>Mineral</th><th>Astrium</th><th>Credits</th></tr>
		<?php while($p = mysqli_fetch_object($players_result)): ?>
		<tr>
			<td><?php echo $p->PlayerID; ?></td>
			<td><?php echo h($p->UserName); ?></td>
			<td>
				<form method="post" style="display:inline; white-space:nowrap;">
					<input type="hidden" name="_tab" value="players">
					<input type="hidden" name="action" value="save_email">
					<input type="hidden" name="player_id" value="<?php echo $p->PlayerID; ?>">
					<input type="text" name="email" value="<?php echo htmlspecialchars($p->Email); ?>" size="20">
					<input type="submit" value="&#10003;" title="Save email" style="padding:1px 5px;">
				</form>
			</td>
			<td><?php echo $p->TeamID > 0 ? h(TeamNameFromID($p->TeamID)) : "-"; ?></td>
			<td><?php echo $p->Metal; ?></td>
			<td><?php echo $p->Mineral; ?></td>
			<td><?php echo $p->Astrium; ?></td>
			<td><?php echo $p->Credits; ?></td>
		</tr>
		<?php endwhile; ?>
	</table>
</div>

<!-- Player Management -->
<div class="admin-section">
	<h3>Reset Player Password</h3>
	<form method="post">
		<input type="hidden" name="_tab" value="players">
		<input type="hidden" name="action" value="reset_password">
		Player ID: <input type="number" name="player_id" min="1" size="4" required>
		New Password: <input type="password" name="new_password" required>
		<input type="submit" value="Reset Password">
	</form>
</div>
</div><!-- /players -->

<!-- ==================== WORLD TAB ==================== -->
<div class="tab-panel<?php echo $_tab == 'world' ? ' active' : ''; ?>">
<!-- System Management -->
<div class="admin-section">
	<h3>System Management</h3>
	<form method="post" style="display:inline-block;">
		<input type="hidden" name="_tab" value="world">
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
		<input type="hidden" name="_tab" value="world">
		<input type="hidden" name="action" value="populate_sector">
		Sector ID: <input type="number" name="sector_id" min="1" max="100" size="4" required>
		<input type="submit" value="Populate Sector">
	</form>
	<form method="post" style="display:inline-block;">
		<input type="hidden" name="_tab" value="world">
		<input type="hidden" name="action" value="calc_owners">
		<input type="submit" value="Recalculate Sector Owners">
	</form>
	<form method="post" style="display:inline-block; margin-left: 20px;" onsubmit="return confirm('Populate all empty sectors? This may take a moment.');">
		<input type="hidden" name="_tab" value="world">
		<input type="hidden" name="action" value="populate_all">
		<input type="submit" value="Populate All Empty Sectors">
	</form>
</div>
</div><!-- /world -->

<!-- ==================== TURNS TAB ==================== -->
<div class="tab-panel<?php echo $_tab == 'turns' ? ' active' : ''; ?>">
<!-- Turn Processing -->
<div class="admin-section">
	<h3>Turn Processing</h3>
	<form method="post" style="display:inline-block; margin-right: 20px;">
		<input type="hidden" name="_tab" value="turns">
		<input type="hidden" name="action" value="run_turn">
		<input type="submit" value="Run Mini-Turn (Construction/Movement)">
	</form>
	<form method="post" style="display:inline-block;">
		<input type="hidden" name="_tab" value="turns">
		<input type="hidden" name="action" value="run_income">
		<input type="submit" value="Run Income Turn">
	</form>
	<form method="post" style="display:inline-block; margin-left: 20px;">
		<input type="hidden" name="_tab" value="turns">
		<input type="hidden" name="action" value="reset_timer">
		<input type="submit" value="Reset Turn Timer">
	</form>
</div>
</div><!-- /turns -->

<!-- ==================== MODERATION TAB ==================== -->
<div class="tab-panel<?php echo $_tab == 'moderation' ? ' active' : ''; ?>">
<!-- Forbidden Words -->
<div class="admin-section">
	<h3>Forbidden Words (Name Filter)</h3>
	<p><small>One word per line. Lines starting with # are comments. Matching is case-insensitive substring.</small></p>
	<form method="post">
		<input type="hidden" name="_tab" value="moderation">
		<input type="hidden" name="action" value="save_forbidden_words">
		<textarea name="forbidden_words" rows="12" cols="40" style="background:#222;color:#fff;border:1px solid #555;font-family:monospace;"><?php
			$_fwFile = __DIR__ . '/../data/forbidden_words.txt';
			if(file_exists($_fwFile)) echo htmlspecialchars(file_get_contents($_fwFile));
		?></textarea><br>
		<input type="submit" value="Save Forbidden Words">
	</form>
</div>
</div><!-- /moderation -->

<!-- ==================== DANGER TAB ==================== -->
<div class="tab-panel<?php echo $_tab == 'danger' ? ' active' : ''; ?>">
<!-- Dangerous Actions -->
<div class="admin-section danger">
	<h3>&#9888; Destructive Actions</h3>
	<form method="post" style="display:inline-block; margin-right: 20px;" onsubmit="return confirm('Clear all systems from this sector?');">
		<input type="hidden" name="_tab" value="danger">
		<input type="hidden" name="action" value="clear_sector">
		Sector ID: <input type="number" name="sector_id" min="1" max="100" size="4" required>
		<input type="submit" value="Clear Sector" class="danger-btn">
	</form>
	<form method="post" style="display:inline-block;" onsubmit="return confirm('Delete ALL planets with ID > 6? This cannot be undone!');">
		<input type="hidden" name="_tab" value="danger">
		<input type="hidden" name="action" value="clear_planets">
		<input type="submit" value="Clear All Planets" class="danger-btn">
	</form>
</div>


<!-- The Burn - Galaxy Reset -->
<div class="admin-section danger">
	<h3>&#9760; The Burn - Galaxy Reset</h3>
	<p style="color:#ff6666;">This will wipe the entire galaxy: all planets, systems, ships, fleets, buildings, battles, auctions, and teams. Player accounts are preserved but all resources and progress are reset to zero. The galaxy will be repopulated fresh.</p>
	<form method="post" onsubmit="return confirm('INITIATE THE BURN?\n\nThis will destroy the entire galaxy and reset all player progress.\n\nType BURN to confirm.') && prompt('Type BURN to confirm:') === 'BURN';">
		<input type="hidden" name="_tab" value="danger">
		<input type="hidden" name="action" value="the_burn">
		<input type="submit" value="&#9760; INITIATE THE BURN" class="danger-btn" style="font-size: 16px; padding: 10px 30px;">
	</form>
</div>
</div><!-- /danger -->

</body>
</html>
