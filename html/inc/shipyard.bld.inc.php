<?php
if(ConstructingShip($PlanetID,$Grid))
{
	$Ship = ShipUnderConstruction($PlanetID,$Grid);
?><div class="side">
<div class="panel" style="width:300;">
<h3>Construction:</h3>
Constructing: <?php echo GetShipTypeString($Ship["Type"]); ?> &quot;<?php echo h($Ship["Name"]); ?>&quot;<br>
Time Remaining: <?php echo $Ship["TTF"]; ?> Minutes
<?php if($edit):
	$_scCosts = mysqli_fetch_object(mysqli_query($GLOBALS["conn"], "SELECT Metal, Mineral, Astrium FROM ship_types WHERE Type='".$Ship["Type"]."'"));
	$_scTotal = CalculateShipBuildTime($PlanetID, $Ship["Type"]);
	if($_scTotal < $Ship["TTF"]) $_scTotal = $Ship["TTF"];
	$_scRate = ($_scTotal > 0) ? $Ship["TTF"] / $_scTotal : 0;
	$_scRefund = round($_scCosts->Metal * $_scRate) . ' Metal, ' . round($_scCosts->Mineral * $_scRate) . ' Mineral, ' . round($_scCosts->Astrium * $_scRate) . ' Astrium';
?>
<p><small>Refund if cancelled: <?php echo $_scRefund; ?></small></p>
<p><form method="post" action="building.php" style="display:inline;">
  <input type="hidden" name="action" value="cancelship">
  <input type="hidden" name="planet" value="<?php echo $PlanetID; ?>">
  <input type="hidden" name="grid" value="<?php echo $Grid; ?>">
  <?php echo csrf_token(); ?>
  <input type="submit" value="Cancel" onclick="return confirm('Cancel ship construction? Refund: <?php echo $_scRefund; ?>');">
</form></p>
<?php endif; ?>
</div>
<div class="panel" style="width:300;"><form name="build" method="post" action="building.php">
  <input type="hidden" name="action" value="addtoqueue">
  <?php echo csrf_token(); ?>
  <h3>Add Ship To Build Queue</h3>
      <p>Type:&nbsp; 
        <select name="type">
          <option value="1">Scout</option>
          <option value="2">Transport</option>
          <option value="3">Coloniser</option>
          <option value="4">Frigate</option>
          <option value="5">Cruiser</option>
          <option value="6">Warship</option>
          <option value="7">Mothership</option>
          <option value="8">Fighter</option>
        </select>
      </p>
      <p> Name: 
        <input name="name" type="text" id="name" size="15" maxlength="15">
        <small><br>
        (max 15 letters)</small></p>      
      <p> 
        <input type="hidden" name="planet" value="<?php echo $PlanetID; ?>">
        <input type="hidden" name="grid" value="<?php echo $Grid; ?>">
      </p>
    <p>
      <input type="submit" name="Submit" value="Build">
    </p>
  </form>
</div>
<?php if(ShipsInQueue($PlanetID,$Grid)){ ?>
<div class="panel" style="width:300;">
<h3>Production Queue</h3>
<ol>
<?php 
	$query = "SELECT * FROM qships WHERE(Yard = '$PlanetID:$Grid') ORDER BY QueuePosition ASC";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
?>
<li><?php echo GetShipTypeString($row->Type); ?> - '<?php echo h($row->Name); ?>'
<?php if($edit): ?>
  <form method="post" action="building.php" style="display:inline;">
    <input type="hidden" name="action" value="removequeue">
    <input type="hidden" name="planet" value="<?php echo $PlanetID; ?>">
    <input type="hidden" name="grid" value="<?php echo $Grid; ?>">
    <input type="hidden" name="qship_id" value="<?php echo $row->ShipID; ?>">
    <?php echo csrf_token(); ?>
    <button type="submit" onclick="return confirm('Remove this ship from queue?');" style="background:none;border:none;color:#ff4444;cursor:pointer;padding:0 4px;font-size:10pt;" title="Remove from queue">&times;</button>
  </form>
<?php endif; ?>
</li>
<?php } ?></ol>
<?php
	$costQuery = "SELECT SUM(st.Metal) AS TotalMetal, SUM(st.Mineral) AS TotalMineral, SUM(st.Astrium) AS TotalAstrium FROM qships q JOIN ship_types st ON q.Type = st.Type WHERE q.Yard = '$PlanetID:$Grid'";
	$costResult = mysqli_query($GLOBALS["conn"], $costQuery);
	$qcosts = mysqli_fetch_object($costResult);
	if($qcosts && ($qcosts->TotalMetal > 0 || $qcosts->TotalMineral > 0 || $qcosts->TotalAstrium > 0)){
?>
<p><strong>Total Queue Cost:</strong> <?php echo number_format($qcosts->TotalMetal); ?> Metal, <?php echo number_format($qcosts->TotalMineral); ?> Mineral, <?php echo number_format($qcosts->TotalAstrium); ?> Astrium</p>
<?php } ?>
<?php
	$_qtTotal = (int)$Ship["TTF"]; // current ship remaining time
	$_qtQ = mysqli_query($GLOBALS["conn"], "SELECT Type FROM qships WHERE Yard='$PlanetID:$Grid' ORDER BY QueuePosition ASC");
	while($_qtRow = mysqli_fetch_object($_qtQ)){
		$_qtTotal += CalculateShipBuildTime($PlanetID, $_qtRow->Type);
	}
	$_qtHours = floor($_qtTotal / 60);
	$_qtMins = $_qtTotal % 60;
?>
<p><strong>Total Build Time:</strong> <?php echo $_qtHours > 0 ? $_qtHours . 'h ' : ''; echo $_qtMins . 'm'; ?> <small>(including current ship)</small></p>
<p><small>If you lack the resources to start the next queued ship, the queue will pause automatically. It will resume on its own once you have enough resources.</small></p>
</div><?php } ?>
</div><!-- /side -->
<?php }else{ ?>
<div class="side">
<div class="panel" style="width:300;"><form name="build" method="post" action="building.php">
  <input type="hidden" name="action" value="consship">
  <?php echo csrf_token(); ?>
  <h3>Build Ship</h3>
      <p>Type:&nbsp; 
        <select name="type">
          <option value="1">Scout</option>
          <option value="2">Transport</option>
          <option value="3">Coloniser</option>
          <option value="4">Frigate</option>
          <option value="5">Cruiser</option>
          <option value="6">Warship</option>
          <option value="7">Mothership</option>
          <option value="8">Fighter</option>
        </select>
      </p>
      <p> Name: 
        <input name="name" type="text" id="name" size="15" maxlength="15">
        <small><br>
        (max 15 letters)</small></p>      
      <p> 
        <input type="hidden" name="planet" value="<?php echo $PlanetID; ?>">
        <input type="hidden" name="grid" value="<?php echo $Grid; ?>">
      </p>
    <p>
      <input type="submit" name="Submit" value="Build">
    </p>
  </form>
</div></div>
<?php } ?>
<div class="planet" style="width:600px;">
<h3>Ship Cost</h3>
<p>
<?php
	$_scRes = mysqli_query($GLOBALS["conn"], "SELECT * FROM ship_types ORDER BY Type ASC");
	while($_sc = mysqli_fetch_object($_scRes)){
		$_scCost = number_format($_sc->Metal) . ' Metal, ' . number_format($_sc->Mineral) . ' Mineral';
		if($_sc->Astrium > 0) $_scCost .= ', ' . number_format($_sc->Astrium) . ' Astrium';
		$_scTurnLabel = $_sc->Turns == 1 ? '1 Turn' : $_sc->Turns . ' Turns';
		echo '<strong>' . htmlspecialchars($_sc->Name) . '</strong>: ' . $_scCost . ' - ' . $_scTurnLabel . '<br/>';
	}
?>
</p>
</div>
