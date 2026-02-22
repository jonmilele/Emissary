<?php
/**
 * Emissary - First-Time Installation
 * Creates database, admin user, populates galaxy, configures cron, sets up turn timer.
 * Self-disables after successful installation.
 */

$lockFile = __DIR__ . "/.installed";
if(file_exists($lockFile)){
	die("<!DOCTYPE html><html><body style='background:#111;color:#ff3333;font-family:monospace;padding:40px;'><h2>Installation already completed.</h2><p>Delete <code>.installed</code> to re-run. <a href='/' style='color:#ff9900;'>Go to game</a></p></body></html>");
}

$step = $_POST['step'] ?? '';

// ============================================================
// STEP 2: Run installation with streaming progress output
// ============================================================
if($step === 'run'){
	$db_host       = $_POST['db_host'] ?? 'db';
	$db_root_pass  = $_POST['db_root_pass'] ?? '';
	$admin_user    = trim($_POST['admin_user'] ?? '');
	$admin_pass    = $_POST['admin_pass'] ?? '';
	$admin_email   = trim($_POST['admin_email'] ?? '');
	$cron_mini     = max(1, intval($_POST['cron_mini'] ?? 1));
	$cron_income   = max(1, intval($_POST['cron_income'] ?? 30));
	$cron_enable   = isset($_POST['cron_enable']);

	// Validate before starting
	$preflight = [];
	if($admin_user === '' || $admin_pass === '') $preflight[] = "Admin username and password are required.";
	if(strlen($admin_pass) < 4) $preflight[] = "Admin password must be at least 4 characters.";

	if(!empty($preflight)){
		goto show_form;
	}

	// --- Stream the progress page ---
	header('Content-Type: text/html; charset=utf-8');
	header('X-Accel-Buffering: no');
	ob_implicit_flush(true);
	if(ob_get_level()) ob_end_flush();

	echo '<!DOCTYPE html><html><head><title>Emissary - Installing...</title>';
	echo '<style>
		body { background: #0a0a1a; color: #ccc; font-family: "Courier New", monospace; padding: 20px; max-width: 750px; margin: 0 auto; }
		h1 { color: #ff9900; border-bottom: 1px solid #333; padding-bottom: 10px; }
		.log { background: #111; border: 1px solid #333; padding: 15px; margin: 10px 0; max-height: 500px; overflow-y: auto; font-size: 13px; line-height: 1.6; }
		.ok { color: #00ff00; }
		.err { color: #ff4444; }
		.warn { color: #ffaa00; }
		.step { color: #66aaff; font-weight: bold; margin-top: 8px; display: block; }
		.done { text-align: center; margin-top: 30px; }
		.done a { color: #ff9900; font-size: 18px; text-decoration: none; border: 1px solid #ff9900; padding: 10px 30px; }
		.done a:hover { background: #331a00; }
	</style></head><body>';
	echo '<h1>&#9733; Installing Emissary</h1>';
	echo '<div class="log" id="log">';

	function progress($msg, $class='ok'){
		echo "<span class=\"$class\">$msg</span><br>";
		flush();
	}
	function stepMsg($msg){
		echo "<span class=\"step\">&#9654; $msg</span><br>";
		flush();
	}

	$failed = false;

	// --- 1. Connect to MySQL as root ---
	stepMsg("Connecting to MySQL...");
	$root = @mysqli_connect($db_host, 'root', $db_root_pass);
	if(!$root){
		progress("FAILED: Cannot connect as root: " . mysqli_connect_error(), 'err');
		$failed = true;
	} else {
		progress("Connected to MySQL on '$db_host'");
	}

	if(!$failed){
		// --- 2. Import schema ---
		stepMsg("Importing database schema...");
		$schema = file_get_contents(__DIR__ . "/schema.sql");
		if(!$schema){
			progress("FAILED: Cannot read schema.sql", 'err');
			$failed = true;
		} else {
			mysqli_multi_query($root, $schema);
			do {
				if($r = mysqli_store_result($root)) mysqli_free_result($r);
			} while(mysqli_next_result($root));
			if(mysqli_errno($root)){
				progress("Warning: " . mysqli_error($root) . " (may be OK if tables exist)", 'warn');
			}
			progress("Database 'emissary' created with all tables");
		}
	}

	if(!$failed){
		// --- 3. Create app DB user ---
		stepMsg("Creating database user...");
		$db_name = 'emissary';
		$db_user = 'emissary';
		$db_pass = 'bumpy5';
		mysqli_query($root, "CREATE USER IF NOT EXISTS '$db_user'@'%' IDENTIFIED BY '$db_pass'");
		mysqli_query($root, "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'%'");
		mysqli_query($root, "FLUSH PRIVILEGES");
		progress("User '$db_user' created with full privileges on '$db_name'");

		// --- 4. Connect as app user ---
		$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
		if(!$conn){
			progress("FAILED: Cannot connect as app user: " . mysqli_connect_error(), 'err');
			$failed = true;
		} else {
			$GLOBALS["conn"] = $conn;
			progress("App database connection verified");
		}
	}

	if(!$failed){
		// --- 5. Write secrets.inc.php ---
		stepMsg("Writing configuration...");
		$secrets = "<?php\n\$hostname_conn = \"$db_host\";\n\$database_conn = \"$db_name\";\n\$username_conn = \"$db_user\";\n\$password_conn = \"$db_pass\";\n?>";
		if(file_put_contents(__DIR__ . "/secrets.inc.php", $secrets)){
			progress("secrets.inc.php written");
		} else {
			progress("FAILED: Cannot write secrets.inc.php (check directory permissions)", 'err');
			$failed = true;
		}
	}

	if(!$failed){
		// --- 6. Create admin user ---
		stepMsg("Creating admin account...");
		$cryptpass = password_hash($admin_pass, PASSWORD_DEFAULT);
		$adminEmail = mysqli_real_escape_string($conn, $admin_email);
		$adminName = mysqli_real_escape_string($conn, $admin_user);
		$check = mysqli_query($conn, "SELECT PlayerID FROM players WHERE UserName='$adminName'");
		if(mysqli_num_rows($check) > 0){
			progress("User '$admin_user' already exists (skipping)", 'warn');
		} else {
			$q = "INSERT INTO players(UserName,Password,Email,DateJoined,SetupStage) VALUES('$adminName','$cryptpass','$adminEmail',NOW(),0)";
			if(mysqli_query($conn, $q)){
				$pid = mysqli_insert_id($conn);
				progress("Admin '$admin_user' created (PlayerID $pid)");
			} else {
				progress("FAILED: " . mysqli_error($conn), 'err');
				$failed = true;
			}
		}
	}

	if(!$failed){
		// --- 7. Load game functions ---
		stepMsg("Loading game engine...");
		include_once(__DIR__ . "/userfunctions.inc.php");
		include_once(__DIR__ . "/planetfunctions.inc.php");
		if(!defined('ADMIN_TOOLS_ACCESS')) define('ADMIN_TOOLS_ACCESS', true);
		include_once(__DIR__ . "/admin/tools.php");
		include_once(__DIR__ . "/turnfunctions.inc.php");
		progress("Game functions loaded");

		// --- 8. Reset names counter in DB ---
		SetGameSetting('names_counter', '1');
		progress("System names counter initialized");

		// --- 9. Populate galaxy ---
		stepMsg("Populating galaxy (100 sectors)...");
		$populated = 0;
		$totalSystems = 0;
		$totalPlanets = 0;
		for($s = 1; $s <= 100; $s++){
			ob_start();
			if(SystemsInSector($s) == 0){
				PopulateSector($s);
				$populated++;
			}
			$sectorLog = ob_get_clean();
			$totalSystems += substr_count($sectorLog, "Added System:");
			$totalPlanets += substr_count($sectorLog, "Added Planet:");
			if($populated % 10 == 0){
				progress("  ... $populated/100 sectors done ($totalSystems systems, $totalPlanets planets)");
			}
		}
		progress("Galaxy complete: $populated sectors, $totalSystems systems, $totalPlanets planets");

		// --- 10. Assign starting planet to admin ---
		stepMsg("Assigning admin starting planet...");
		$adminCheck = mysqli_query($conn, "SELECT PlayerID FROM players WHERE UserName='$adminName'");
		if($adminRow = mysqli_fetch_object($adminCheck)){
			$adminPlanet = AssignStartingPlanet($adminRow->PlayerID);
			if($adminPlanet > 0){
				$planetName = GetPlanetNameFromID($adminPlanet);
				progress("Admin assigned to $planetName (PlanetID $adminPlanet)");
			} else {
				progress("Warning: Could not assign starting planet to admin", 'warn');
			}
		}

		// --- 11. Reset turn timer ---
		stepMsg("Initializing turn timer...");
		// Save income turn interval (seconds) so the game knows the cycle length
		$turnInterval = $cron_income * 60;
		SetGameSetting('turn_interval', $turnInterval);
		ResetTurnTimer();
		progress("Turn timer set (income cycle: every $cron_income min, mini-turn: every $cron_mini min)");

		// --- 11. Configure cron ---
		stepMsg("Configuring cron jobs...");
		$phpBin = PHP_BINARY ?: '/usr/local/bin/php';
		$webRoot = __DIR__;
		if($cron_enable){
			// Build crontab lines
			$cronLines  = "# Emissary turn processing\n";
			if($cron_mini >= 60){
				$h = intdiv($cron_mini, 60);
				$cronLines .= "0 */$h * * * $phpBin $webRoot/miniturn.cron.php > /dev/null 2>&1\n";
			} else {
				$cronLines .= "*/$cron_mini * * * * $phpBin $webRoot/miniturn.cron.php > /dev/null 2>&1\n";
			}
			if($cron_income >= 60){
				$h = intdiv($cron_income, 60);
				$cronLines .= "0 */$h * * * $phpBin $webRoot/turn.cron.php > /dev/null 2>&1\n";
			} else {
				$cronLines .= "*/$cron_income * * * * $phpBin $webRoot/turn.cron.php > /dev/null 2>&1\n";
			}

			// Remove any existing Emissary cron entries, then add new ones
			$existing = shell_exec("crontab -l 2>/dev/null") ?: "";
			$filtered = preg_replace('/# Emissary turn processing\n/', '', $existing);
			$filtered = preg_replace('/.*(?:miniturn|turn)\.cron\.php.*\n/', '', $filtered);
			$newCrontab = rtrim($filtered) . "\n" . $cronLines;
			$tmpCron = tempnam(sys_get_temp_dir(), 'cron');
			file_put_contents($tmpCron, $newCrontab);
			$out = [];
			exec("crontab $tmpCron 2>&1", $out, $ret);
			unlink($tmpCron);

			if($ret === 0){
				progress("Cron jobs installed and active");
				progress("  Mini-turn: every $cron_mini minute(s)");
				progress("  Income turn: every $cron_income minute(s)");
			} else {
				progress("Warning: Could not install crontab: " . implode(' ', $out), 'warn');
				progress("You can set up cron manually (see instructions below)", 'warn');
			}
		} else {
			progress("Cron auto-setup skipped (manual mode)");
			progress("  Mini-turn command: $phpBin $webRoot/miniturn.cron.php");
			progress("  Income turn command: $phpBin $webRoot/turn.cron.php");
		}

		// --- 12. Save install config for admin panel reference ---
		$config = "<?php\n\$cron_mini_interval = $cron_mini;\n\$cron_income_interval = $cron_income;\n\$cron_enabled = " . ($cron_enable ? "true" : "false") . ";\n?>";
		file_put_contents(__DIR__ . "/config.inc.php", $config);
		progress("Game configuration saved");

		// --- 13. Write lock file ---
		stepMsg("Finalizing...");
		file_put_contents($lockFile, date('Y-m-d H:i:s') . " - Installed by $admin_user\n");
		progress("Lock file created (.installed)");

		if(isset($conn)) mysqli_close($conn);
		if(isset($root)) mysqli_close($root);
	}

	echo '</div>'; // end .log

	if($failed){
		echo '<div style="color:#ff4444; text-align:center; margin-top:20px;">Installation encountered errors. Fix the issues above and try again.</div>';
		echo '<form method="get" style="text-align:center; margin-top:10px;"><input type="submit" value="&laquo; Back to Setup" style="background:#444;color:#fff;border:1px solid #666;padding:8px 20px;cursor:pointer;font-family:monospace;"></form>';
	} else {
		echo '<div style="background:#003300;border:1px solid #00aa00;color:#00ff00;padding:15px;margin:15px 0;text-align:center;font-size:16px;">&#10003; Installation complete!</div>';

		if(!$cron_enable){
			echo '<h2 style="color:#ff9900;">Manual Cron Setup</h2>';
			echo '<p>Add these to your crontab (<code style="background:#222;padding:2px 6px;color:#ff9900;">crontab -e</code>):</p>';
			echo '<div style="background:#111;border:1px solid #555;padding:12px;margin:10px 0;font-size:13px;color:#0f0;">';
			$phpBin = PHP_BINARY ?: '/usr/local/bin/php';
			echo "*/$cron_mini * * * * $phpBin " . __DIR__ . "/miniturn.cron.php<br>";
			echo "*/$cron_income * * * * $phpBin " . __DIR__ . "/turn.cron.php";
			echo '</div>';
		}

		echo '<div class="done"><a href="/">Enter Emissary &raquo;</a></div>';
	}

	echo '</body></html>';
	exit;
}

// ============================================================
// STEP 1: Show setup form
// ============================================================
show_form:
?>
<!DOCTYPE html>
<html>
<head>
<title>Emissary - Install</title>
<style>
	body { background: #0a0a1a; color: #ccc; font-family: 'Courier New', monospace; padding: 20px; max-width: 700px; margin: 0 auto; }
	h1 { color: #ff9900; border-bottom: 1px solid #333; padding-bottom: 10px; }
	h2 { color: #ff9900; }
	.form-group { margin: 12px 0; }
	label { display: inline-block; width: 200px; color: #aaa; }
	input[type=text], input[type=password], input[type=number] { background: #1a1a2e; color: #fff; border: 1px solid #444; padding: 6px 10px; width: 200px; font-family: monospace; }
	input[type=number] { width: 80px; }
	input[type=submit] { background: #225522; color: #fff; border: 1px solid #33aa33; padding: 8px 24px; cursor: pointer; font-size: 14px; margin-top: 10px; }
	input[type=submit]:hover { background: #338833; }
	input[type=checkbox] { margin-right: 8px; }
	.error { background: #330000; border: 1px solid #aa0000; color: #ff4444; padding: 10px; margin: 8px 0; }
	.info { background: #1a1a2e; border: 1px solid #444; padding: 10px; margin: 8px 0; }
	.hint { color: #888; font-size: 12px; margin-left: 205px; }
</style>
</head>
<body>

<h1>&#9733; Emissary Installation</h1>

<?php if(!empty($preflight)): ?>
	<?php foreach($preflight as $e): ?>
		<div class="error"><?php echo htmlspecialchars($e); ?></div>
	<?php endforeach; ?>
<?php endif; ?>

<p>This will set up Emissary for the first time: create the database, seed reference data, create an admin account, populate the galaxy, and configure turn processing.</p>

<form method="post">
	<input type="hidden" name="step" value="run">

	<h2>Database</h2>
	<div class="form-group">
		<label>MySQL Host:</label>
		<input type="text" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'db'); ?>">
	</div>
	<div class="form-group">
		<label>MySQL Root Password:</label>
		<input type="password" name="db_root_pass" value="">
	</div>

	<h2>Admin Account</h2>
	<div class="info">This will be PlayerID 1, which has admin panel access.</div>
	<div class="form-group">
		<label>Admin Username:</label>
		<input type="text" name="admin_user" value="<?php echo htmlspecialchars($_POST['admin_user'] ?? ''); ?>" required>
	</div>
	<div class="form-group">
		<label>Admin Password:</label>
		<input type="password" name="admin_pass" value="" required>
	</div>
	<div class="form-group">
		<label>Admin Email:</label>
		<input type="text" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>">
	</div>

	<h2>Turn Processing</h2>
	<div class="info">Cron jobs handle the game's turn-based economy and construction. You can adjust intervals or set them up manually later.</div>
	<div class="form-group">
		<label>Mini-turn interval:</label>
		<input type="number" name="cron_mini" min="1" max="60" value="<?php echo htmlspecialchars($_POST['cron_mini'] ?? '1'); ?>"> minutes
		<div class="hint">Processes construction queues and fleet movement.</div>
	</div>
	<div class="form-group">
		<label>Income turn interval:</label>
		<input type="number" name="cron_income" min="1" max="1440" value="<?php echo htmlspecialchars($_POST['cron_income'] ?? '30'); ?>"> minutes
		<div class="hint">Awards resource income from planets to all players.</div>
	</div>
	<div class="form-group">
		<label>&nbsp;</label>
		<label style="width:auto;"><input type="checkbox" name="cron_enable" checked> Activate cron jobs automatically</label>
		<div class="hint">Uncheck to set up cron manually after installation.</div>
	</div>

	<input type="submit" value="&#9733; Install Emissary">
</form>

</body>
</html>
