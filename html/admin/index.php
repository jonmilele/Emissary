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
		case "assign_grid_boons":
			include_once(__DIR__ . "/../turnfunctions.inc.php");
			$_bgRes = mysqli_query($GLOBALS["conn"], "SELECT PlanetID FROM planets WHERE PlanetID NOT IN (SELECT DISTINCT PlanetID FROM planet_grid_boons)");
			$_bgCount = 0;
			$_bgTotal = 0;
			while($_bgRow = mysqli_fetch_object($_bgRes)){
				$_bgTotal += AssignPlanetGridBoons($_bgRow->PlanetID);
				$_bgCount++;
			}
			echo "Assigned grid boons to $_bgCount planets ($_bgTotal total boons).";
			break;
		case "assign_planet_boons":
			include_once(__DIR__ . "/../turnfunctions.inc.php");
			$_pbRes = mysqli_query($GLOBALS["conn"], "SELECT PlanetID FROM planets WHERE PlanetID NOT IN (SELECT DISTINCT PlanetID FROM planet_boons)");
			$_pbCount = 0;
			$_pbTotal = 0;
			while($_pbRow = mysqli_fetch_object($_pbRes)){
				$assigned = AssignPlanetBoons($_pbRow->PlanetID);
				$_pbTotal += count($assigned);
				$_pbCount++;
			}
			echo "Assigned planet boons to $_pbCount planets ($_pbTotal total boons).";
			break;
		case "save_settings":
			include_once(__DIR__ . "/../turnfunctions.inc.php");
			$setting_keys = [
				'home_hp_multiplier','home_income_multiplier',
				'buy_planet_metal','buy_planet_mineral','buy_planet_astrium',
				'harvester_bonus','election_duration',
				'election_auto_interval','election_motion_threshold',
				'starting_metal','starting_mineral','starting_astrium',
				'planet_weapon_hit_chance',
				'default_auction_turns','building_salvage_rate','building_valuation_rate','ship_salvage_rate',
				'metal_credit_value','mineral_credit_value','astrium_credit_value',
			'boon_max_ratio','boon_resource_bonus','boon_energy_hp_bonus','boon_energy_ap_bonus',
			'pboon_resource_rich_rarity','pboon_resource_rich_bonus',
			'pboon_geothermal_rarity','pboon_geothermal_bonus',
			'pboon_gravity_well_rarity','pboon_gravity_well_bonus',
			'pboon_rough_terrain_rarity','pboon_rough_terrain_bonus',
			'pboon_boon_planet_rarity','pboon_boon_planet_min','pboon_boon_planet_max'
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
				$_ptSlots = max(1, (int)($_POST['pt_slots'][$_ptId] ?? 1));
				$_ptMetal = (int)($_POST['pt_metal'][$_ptId] ?? 0);
				$_ptMineral = (int)($_POST['pt_mineral'][$_ptId] ?? 0);
				$_ptAstrium = (int)($_POST['pt_astrium'][$_ptId] ?? 0);
				$_ptIncome = $_ptMetal . ':' . $_ptMineral . ':' . $_ptAstrium;
				$sql = "UPDATE planet_types SET Grids='$_ptGrids', rowsquares='$_ptRows', construction_slots='$_ptSlots', income='$_ptIncome' WHERE Type='$_ptId'";
				mysqli_query($GLOBALS["conn"], $sql);
				$_ptUpdated++;
			}
			echo "$_ptUpdated planet type(s) updated.";
			break;
		case "save_player":
			$_spId = (int)($_POST['player_id'] ?? 0);
			if($_spId > 0){
				$_spMetal = (int)($_POST['p_metal'] ?? 0);
				$_spMineral = (int)($_POST['p_mineral'] ?? 0);
				$_spAstrium = (int)($_POST['p_astrium'] ?? 0);
				$_spCredits = (int)($_POST['p_credits'] ?? 0);
				$_spTeam = (int)($_POST['p_team'] ?? 0);
				$_spEmail = mysqli_real_escape_string($GLOBALS["conn"], trim($_POST['email'] ?? ''));
				mysqli_query($GLOBALS["conn"], "UPDATE players SET Email='$_spEmail', Metal='$_spMetal', Mineral='$_spMineral', Astrium='$_spAstrium', Credits='$_spCredits', TeamID='$_spTeam' WHERE PlayerID='$_spId'");
				echo "Player #$_spId updated.";
			} else {
				echo "Invalid player ID";
			}
			break;
		case "save_ship_types":
			$_stTypes = $_POST['st_type'] ?? [];
			$_stUpdated = 0;
			foreach($_stTypes as $_stId){
				$_stId = (int)$_stId;
				if($_stId < 1) continue;
				$_stName = trim($_POST['st_name'][$_stId] ?? '');
				$_stHP = (int)($_POST['st_hp'][$_stId] ?? 0);
				$_stAP = (int)($_POST['st_ap'][$_stId] ?? 0);
				$_stMetal = (int)($_POST['st_metal'][$_stId] ?? 0);
				$_stMineral = (int)($_POST['st_mineral'][$_stId] ?? 0);
				$_stAstrium = (int)($_POST['st_astrium'][$_stId] ?? 0);
				$_stTurns = max(1, (int)($_POST['st_turns'][$_stId] ?? 1));
				$_stPrefix = strtoupper(substr(trim($_POST['st_prefix'][$_stId] ?? 'XX'), 0, 5));
				$_stNameSafe = mysqli_real_escape_string($GLOBALS["conn"], $_stName);
				$_stPrefixSafe = mysqli_real_escape_string($GLOBALS["conn"], $_stPrefix);
				$sql = "UPDATE ship_types SET Name='$_stNameSafe', HP='$_stHP', AP='$_stAP', Metal='$_stMetal', Mineral='$_stMineral', Astrium='$_stAstrium', Turns='$_stTurns', RegPrefix='$_stPrefixSafe' WHERE Type='$_stId'";
				mysqli_query($GLOBALS["conn"], $sql);
				$_stUpdated++;
			}
			echo "$_stUpdated ship type(s) updated.";
			break;
		case "save_building_types":
			$_btTypes = $_POST['bt_type'] ?? [];
			$_btUpdated = 0;
			foreach($_btTypes as $_btId){
				$_btId = (int)$_btId;
				if($_btId < 1) continue;
				$_btHP = (int)($_POST['bt_hp'][$_btId] ?? 0);
				$_btAP = (int)($_POST['bt_ap'][$_btId] ?? 0);
				$_btMetal = (int)($_POST['bt_metal'][$_btId] ?? 0);
				$_btMineral = (int)($_POST['bt_mineral'][$_btId] ?? 0);
				$_btAstrium = (int)($_POST['bt_astrium'][$_btId] ?? 0);
				$_btTurns = max(1, (int)($_POST['bt_turns'][$_btId] ?? 1));
				$_btColour = trim($_POST['bt_colour'][$_btId] ?? '255,255,255');
				$_btColourSafe = mysqli_real_escape_string($GLOBALS["conn"], $_btColour);
				$sql = "UPDATE building_types SET HP='$_btHP', AP='$_btAP', Metal='$_btMetal', Mineral='$_btMineral', Astrium='$_btAstrium', Turns='$_btTurns', Colour='$_btColourSafe' WHERE Type='$_btId'";
				mysqli_query($GLOBALS["conn"], $sql);
				$_btUpdated++;
			}
			echo "$_btUpdated building type(s) updated.";
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
		$wipe_tables = ["planets","Systems","ships","fleets","buildings","cbuildings","cships","qships","battles","auctions","auction_cooldowns","gamelog","alerts","planet_grid_boons","planet_boons"];
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
			// Save boon ratio before repopulating
			$_burnBoonRatio = $_POST['burn_boon_ratio'] ?? '0.15';
			SetGameSetting('boon_max_ratio', $_burnBoonRatio);
			echo "Grid boon ratio set to $_burnBoonRatio\n";
			// Save planet boon rarities
			$_pboonKeys = ['pboon_resource_rich_rarity','pboon_geothermal_rarity','pboon_gravity_well_rarity','pboon_rough_terrain_rarity','pboon_boon_planet_rarity'];
			foreach($_pboonKeys as $_pk){
				if(isset($_POST[$_pk])) SetGameSetting($_pk, $_POST[$_pk]);
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
$_teams_result = mysqli_query($conn, "SELECT TeamID, Name FROM teams ORDER BY Name");
$_teamsList = [];
while($_t = mysqli_fetch_object($_teams_result)) $_teamsList[(int)$_t->TeamID] = $_t->Name;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Admin Panel</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="../style.css" rel="stylesheet" type="text/css">
<style>
	body { max-width: none; }
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
	'buildings' => 'Buildings',
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
		'default_auction_turns'    => ['label' => 'Default Auction Duration (turns)',    'default' => '5',  'hint' => 'Default number of income turns an auction lasts (each turn = 30 min)'],
		'building_salvage_rate'    => ['label' => 'Building Salvage Rate',                'default' => '0.5','hint' => 'Fraction of building cost refunded when demolished (0.5 = 50%, scaled by HP)'],
		'building_valuation_rate'  => ['label' => 'Building Valuation Rate',              'default' => '0.7','hint' => 'Fraction of building cost counted toward planet value (0.7 = 70%, scaled by HP)'],
		'ship_salvage_rate'        => ['label' => 'Ship Salvage Rate',                    'default' => '0.4','hint' => 'Fraction of ship purchase cost refunded when salvaged (0.4 = 40%, scaled by HP)'],
		'metal_credit_value'       => ['label' => 'Metal Credit Value',                    'default' => '1',  'hint' => 'Credit value of 1 Metal (used for planet valuation and leaderboard)'],
		'mineral_credit_value'     => ['label' => 'Mineral Credit Value',                  'default' => '10', 'hint' => 'Credit value of 1 Mineral (used for planet valuation and leaderboard)'],
		'astrium_credit_value'     => ['label' => 'Astrium Credit Value',                  'default' => '100','hint' => 'Credit value of 1 Astrium (used for planet valuation and leaderboard)'],
		'boon_max_ratio'           => ['label' => 'Grid Boon Max Ratio',                    'default' => '0.15','hint' => 'Only applies when creating new planets (install/galaxy reset). Change here then use Assign Grid Boons to reroll.'],
		'boon_resource_bonus'      => ['label' => 'Resource Boon Bonus',                    'default' => '0.10','hint' => 'Extra income bonus for harvesters on resource boon grids (0.10 = +10%)'],
		'boon_energy_hp_bonus'     => ['label' => 'Energy Boon HP Bonus',                   'default' => '0.25','hint' => 'Extra HP for shields/weapons on energy boon grids (0.25 = +25%)'],
		'boon_energy_ap_bonus'     => ['label' => 'Energy Boon AP Bonus',                   'default' => '0.25','hint' => 'Extra AP for weapons on energy boon grids (0.25 = +25%)'],
		'pboon_resource_rich_rarity'  => ['label' => 'Resource Rich Rarity (1-in-N)',    'default' => '10',  'hint' => 'Chance each planet gets Resource Rich boon at creation (10 = 1-in-10)'],
		'pboon_resource_rich_bonus'   => ['label' => 'Resource Rich Bonus',              'default' => '0.20','hint' => 'Base income multiplier for Resource Rich planets (0.20 = +20%)'],
		'pboon_geothermal_rarity'     => ['label' => 'Geothermal Rarity (1-in-N)',       'default' => '10',  'hint' => 'Chance each planet gets Geothermal boon at creation (10 = 1-in-10)'],
		'pboon_geothermal_bonus'      => ['label' => 'Geothermal Bonus',                'default' => '0.50','hint' => 'HP/AP bonus for shields and weapons (0.50 = +50%)'],
		'pboon_gravity_well_rarity'   => ['label' => 'Gravity Well Rarity (1-in-N)',     'default' => '20',  'hint' => 'Chance each planet gets Gravity Well boon (20 = 1-in-20)'],
		'pboon_gravity_well_bonus'    => ['label' => 'Gravity Well Bonus',               'default' => '0.30','hint' => 'HP/AP bonus for orbiting ships (future) (0.30 = +30%)'],
		'pboon_rough_terrain_rarity'  => ['label' => 'Rough Terrain Rarity (1-in-N)',    'default' => '20',  'hint' => 'Chance each planet gets Rough Terrain boon (20 = 1-in-20)'],
		'pboon_rough_terrain_bonus'   => ['label' => 'Rough Terrain Bonus',              'default' => '0.30','hint' => 'HP bonus for defending armies (future) (0.30 = +30%)'],
		'pboon_boon_planet_rarity'    => ['label' => 'Boon Planet Rarity (1-in-N)',      'default' => '30',  'hint' => 'Chance each planet gets Boon Planet (higher grid boon ratio) (30 = 1-in-30)'],
		'pboon_boon_planet_min'       => ['label' => 'Boon Planet Min Ratio',            'default' => '0.30','hint' => 'Minimum grid boon ratio on Boon Planets (0.30 = 30%)'],
		'pboon_boon_planet_max'       => ['label' => 'Boon Planet Max Ratio',            'default' => '0.40','hint' => 'Maximum grid boon ratio on Boon Planets (0.40 = 40%)'],
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
			<tr><th>Type</th><th>Size</th><th>Grids</th><th>Row Squares</th><th>Slots</th><th>Metal/turn</th><th>Mineral/turn</th><th>Astrium/turn</th><th>Existing</th></tr>
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
				<td><input type="number" name="pt_slots[<?php echo $_pt->Type; ?>]" value="<?php echo $_pt->construction_slots; ?>" size="3" min="1"></td>
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

<!-- ==================== BUILDINGS TAB ==================== -->
<div class="tab-panel<?php echo $_tab == 'buildings' ? ' active' : ''; ?>">
<?php
	$_btRes = mysqli_query($GLOBALS["conn"], "SELECT * FROM building_types ORDER BY Type ASC");
	$_btRows = [];
	while($_btr = mysqli_fetch_object($_btRes)) $_btRows[] = $_btr;

	// Count existing buildings per type
	$_btCounts = [];
	$_bcRes = mysqli_query($GLOBALS["conn"], "SELECT Type, COUNT(*) AS cnt FROM buildings GROUP BY Type");
	while($_bcr = mysqli_fetch_object($_bcRes)) $_btCounts[(int)$_bcr->Type] = (int)$_bcr->cnt;
?>
<div class="admin-section">
	<h3>Building Types</h3>
	<p><small>Edit building stats, resource costs, and construction time. Changes apply to new buildings only.</small></p>
	<form method="post">
		<input type="hidden" name="_tab" value="buildings">
		<input type="hidden" name="action" value="save_building_types">
		<table>
			<tr><th>ID</th><th>Name</th><th>HP</th><th>AP</th><th>Metal</th><th>Mineral</th><th>Astrium</th><th>Turns</th><th>Colour (R,G,B)</th><th>Built</th></tr>
			<?php foreach($_btRows as $_bt):
				$_btCount = $_btCounts[(int)$_bt->Type] ?? 0;
			?>
			<tr>
				<td><?php echo $_bt->Type; ?><input type="hidden" name="bt_type[]" value="<?php echo $_bt->Type; ?>"></td>
				<td><?php echo htmlspecialchars($_bt->Name); ?></td>
				<td><input type="number" name="bt_hp[<?php echo $_bt->Type; ?>]" value="<?php echo $_bt->HP; ?>" size="5" min="0"></td>
				<td><input type="number" name="bt_ap[<?php echo $_bt->Type; ?>]" value="<?php echo $_bt->AP; ?>" size="5" min="0"></td>
				<td><input type="number" name="bt_metal[<?php echo $_bt->Type; ?>]" value="<?php echo $_bt->Metal; ?>" size="5" min="0"></td>
				<td><input type="number" name="bt_mineral[<?php echo $_bt->Type; ?>]" value="<?php echo $_bt->Mineral; ?>" size="5" min="0"></td>
				<td><input type="number" name="bt_astrium[<?php echo $_bt->Type; ?>]" value="<?php echo $_bt->Astrium; ?>" size="5" min="0"></td>
				<td><input type="number" name="bt_turns[<?php echo $_bt->Type; ?>]" value="<?php echo $_bt->Turns; ?>" size="3" min="1"></td>
				<td><input type="text" name="bt_colour[<?php echo $_bt->Type; ?>]" value="<?php echo htmlspecialchars($_bt->Colour); ?>" size="11"></td>
				<td style="color:#888;"><?php echo number_format($_btCount); ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<br>
		<input type="submit" value="Save Building Types">
	</form>
</div>
<!-- Ship Types -->
<?php
	$_stRes = mysqli_query($GLOBALS["conn"], "SELECT * FROM ship_types ORDER BY Type ASC");
	$_stRows = [];
	while($_str = mysqli_fetch_object($_stRes)) $_stRows[] = $_str;

	// Count existing ships per type
	$_stCounts = [];
	$_scRes = mysqli_query($GLOBALS["conn"], "SELECT Type, COUNT(*) AS cnt FROM ships GROUP BY Type");
	while($_scr = mysqli_fetch_object($_scRes)) $_stCounts[(int)$_scr->Type] = (int)$_scr->cnt;
?>
<div class="admin-section">
	<h3>Ship Types</h3>
	<p><small>Edit ship stats, resource costs, and build time. Changes apply to newly constructed ships only.</small></p>
	<form method="post">
		<input type="hidden" name="_tab" value="buildings">
		<input type="hidden" name="action" value="save_ship_types">
		<table>
			<tr><th>ID</th><th>Name</th><th>Prefix</th><th>HP</th><th>AP</th><th>Metal</th><th>Mineral</th><th>Astrium</th><th>Turns</th><th>Built</th></tr>
			<?php foreach($_stRows as $_st):
				$_stCount = $_stCounts[(int)$_st->Type] ?? 0;
			?>
			<tr>
				<td><?php echo $_st->Type; ?><input type="hidden" name="st_type[]" value="<?php echo $_st->Type; ?>"></td>
				<td><input type="text" name="st_name[<?php echo $_st->Type; ?>]" value="<?php echo htmlspecialchars($_st->Name); ?>" size="12"></td>
				<td><input type="text" name="st_prefix[<?php echo $_st->Type; ?>]" value="<?php echo htmlspecialchars($_st->RegPrefix ?? 'XX'); ?>" size="3" maxlength="5"></td>
				<td><input type="number" name="st_hp[<?php echo $_st->Type; ?>]" value="<?php echo $_st->HP; ?>" size="5" min="0"></td>
				<td><input type="number" name="st_ap[<?php echo $_st->Type; ?>]" value="<?php echo $_st->AP; ?>" size="5" min="0"></td>
				<td><input type="number" name="st_metal[<?php echo $_st->Type; ?>]" value="<?php echo $_st->Metal; ?>" size="5" min="0"></td>
				<td><input type="number" name="st_mineral[<?php echo $_st->Type; ?>]" value="<?php echo $_st->Mineral; ?>" size="5" min="0"></td>
				<td><input type="number" name="st_astrium[<?php echo $_st->Type; ?>]" value="<?php echo $_st->Astrium; ?>" size="5" min="0"></td>
				<td><input type="number" name="st_turns[<?php echo $_st->Type; ?>]" value="<?php echo $_st->Turns; ?>" size="3" min="1"></td>
				<td style="color:#888;"><?php echo number_format($_stCount); ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<br>
		<input type="submit" value="Save Ship Types">
	</form>
</div>
</div><!-- /buildings -->

<!-- ==================== PLAYERS TAB ==================== -->
<div class="tab-panel<?php echo $_tab == 'players' ? ' active' : ''; ?>">
<!-- Player List -->
<div class="admin-section">
	<h3>Players</h3>
	<table>
		<tr><th>ID</th><th>Username</th><th>Email</th><th>Team</th><th>Metal</th><th>Mineral</th><th>Astrium</th><th>Credits</th><th></th></tr>
		<?php while($p = mysqli_fetch_object($players_result)): ?>
		<tr>
			<form method="post">
			<input type="hidden" name="_tab" value="players">
			<input type="hidden" name="action" value="save_player">
			<input type="hidden" name="player_id" value="<?php echo $p->PlayerID; ?>">
			<td><?php echo $p->PlayerID; ?></td>
			<td><?php echo h($p->UserName); ?></td>
			<td>
				<input type="text" name="email" value="<?php echo htmlspecialchars($p->Email); ?>" size="18">
			</td>
			<td>
				<select name="p_team" style="background:#222;color:#fff;border:1px solid #555;">
					<option value="0"<?php echo $p->TeamID == 0 ? ' selected' : ''; ?>>— None —</option>
					<?php foreach($_teamsList as $_tid => $_tname): ?>
					<option value="<?php echo $_tid; ?>"<?php echo $p->TeamID == $_tid ? ' selected' : ''; ?>><?php echo h($_tname); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td><input type="number" name="p_metal" value="<?php echo $p->Metal; ?>" size="6" min="0"></td>
			<td><input type="number" name="p_mineral" value="<?php echo $p->Mineral; ?>" size="6" min="0"></td>
			<td><input type="number" name="p_astrium" value="<?php echo $p->Astrium; ?>" size="6" min="0"></td>
			<td><input type="number" name="p_credits" value="<?php echo $p->Credits; ?>" size="6" min="0"></td>
			<td><input type="submit" value="Save" style="padding:2px 8px;"></td>
			</form>
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
	<form method="post" style="display:inline-block; margin-left: 20px;" onsubmit="return confirm('Assign grid boons to all planets that don\'t have any?');">
		<input type="hidden" name="_tab" value="world">
		<input type="hidden" name="action" value="assign_grid_boons">
		<input type="submit" value="Assign Grid Boons">
	</form>
	<form method="post" style="display:inline-block; margin-left: 20px;" onsubmit="return confirm('Assign planet boons to all planets that don\'t have any?');">
		<input type="hidden" name="_tab" value="world">
		<input type="hidden" name="action" value="assign_planet_boons">
		<input type="submit" value="Assign Planet Boons">
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
		<p>Grid Boon Max Ratio: <input type="text" name="burn_boon_ratio" value="<?php echo htmlspecialchars(GetGameSetting('boon_max_ratio', '0.15')); ?>" size="6">
		<small style="color:#aaa;">Max fraction of grids with boons (0 = none, 0.15 = up to 15%)</small></p>
		<p style="color:#aaa;"><strong>Planet Boon Rarities (1-in-N):</strong>
		Resource Rich: <input type="number" name="pboon_resource_rich_rarity" value="<?php echo htmlspecialchars(GetGameSetting('pboon_resource_rich_rarity', '10')); ?>" size="4" min="1" style="width:50px;">
		Geothermal: <input type="number" name="pboon_geothermal_rarity" value="<?php echo htmlspecialchars(GetGameSetting('pboon_geothermal_rarity', '10')); ?>" size="4" min="1" style="width:50px;">
		Gravity Well: <input type="number" name="pboon_gravity_well_rarity" value="<?php echo htmlspecialchars(GetGameSetting('pboon_gravity_well_rarity', '20')); ?>" size="4" min="1" style="width:50px;">
		Rough Terrain: <input type="number" name="pboon_rough_terrain_rarity" value="<?php echo htmlspecialchars(GetGameSetting('pboon_rough_terrain_rarity', '20')); ?>" size="4" min="1" style="width:50px;">
		Boon Planet: <input type="number" name="pboon_boon_planet_rarity" value="<?php echo htmlspecialchars(GetGameSetting('pboon_boon_planet_rarity', '30')); ?>" size="4" min="1" style="width:50px;">
		</p>
		<input type="submit" value="&#9760; INITIATE THE BURN" class="danger-btn" style="font-size: 16px; padding: 10px 30px;">
	</form>
</div>
</div><!-- /danger -->

</body>
</html>
