<?php if(GetShipsInOrbit($PlanetID)>0){?>
<p>There are <?php echo GetShipsInOrbit($PlanetID); ?> unassigned ships in orbit.</p>
<?php } ?>
<?php
$Ships = GetUnassignedShips($PlanetID);
if($Ships["Total"]>0){
?>
<div class="panel" style="width:500px;">
<h3>Move Unassigned Ships to Fleet:</h3>
<p>[<a href="building.php?action=createfleet&planet=<?php echo $PlanetID; ?>">Create a new Fleet with all of these ships</a>]</p>
<form action="building.php?action=addtofleet&planet=<?php echo $PlanetID; ?>" method="post">
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
        <option value="<?php echo $Fleet->FleetID; ?>"><?php echo GetFleetName($Fleet->FleetID); ?></option>
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
