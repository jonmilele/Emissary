<?php if(GetShipsInOrbit($PlanetID)>0){?>
<p>There are <?php echo GetShipsInOrbit($PlanetID); ?> unassigned ships in orbit.</p>
<?php } ?>
<?php
$Ships = GetUnassignedShips($PlanetID);
if($Ships["Total"]>0){
?>
<div class="panel" style="width:500px;">
<h3>Move Unassigned Ships to Fleet:</h3>
<form method="post" action="building.php" style="display:inline;">
  <input type="hidden" name="action" value="createfleet">
  <input type="hidden" name="planet" value="<?php echo $PlanetID; ?>">
  <?php echo csrf_token(); ?>
  <p>[<button type="submit">Create a new Fleet with all of these ships</button>]</p>
</form>
<form action="building.php" method="post">
  <input type="hidden" name="action" value="addtofleet">
  <input type="hidden" name="planet" value="<?php echo $PlanetID; ?>">
  <?php echo csrf_token(); ?>
  <p> Transports (<?php echo $Ships["Transports"]; ?> avaliable): 
    <input name="transports" type="text" value="0" size="4">
  </p>
  <p> Colonisers (<?php echo $Ships["Colonisers"]; ?> avaliable): 
    <input name="colonisers" type="text" value="0" size="4">
  </p>
  <p> Frigates (<?php echo $Ships["Frigates"]; ?> avaliable): 
    <input name="frigates" type="text" value="0" size="4">
  </p>
  <p> Cruisers (<?php echo $Ships["Cruisers"]; ?> avaliable): 
    <input name="cruisers" type="text" value="0" size="4">
  </p>
  <p> Warships (<?php echo $Ships["Warships"]; ?> avaliable): 
    <input name="warships" type="text" value="0" size="4">
  </p>
  <p> Motherships (<?php echo $Ships["Motherships"]; ?> avaliable): 
    <input name="motherships" type="text" value="0" size="4">
  </p>
    <p> Fighters (<?php echo $Ships["Fighters"]; ?> avaliable): 
      <input name="fighters" type="text" value="0" size="4">
    </p>
    <p>Fleet: 
      <select name="fleet">
        <option value="new">-New Fleet-</option>
        <?php
	 $Fleets = ListYourFleetsInOrbit($PlanetID);
	 foreach($Fleets as $k=>$Fleet){
	  ?>
        <option value="<?php echo $Fleet->FleetID; ?>"><?php echo h(GetFleetName($Fleet->FleetID)); ?></option>
        <?php } ?>
      </select>
    </p>
    <p>Name (if new): 
      <input name="name" type="text" id="name" size="15">
    </p>
  <p>
    <input type="submit" name="Submit" value="Add Ships">
  </p>
</form></div>
<?php
}else{
?>
<p>No ships or fleets avaliable for modification.</p>
<?php } ?>
