<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

if(isset($_POST['action']) && csrf_validate()){
	if(($_POST['action'] ?? "")=="move"){
		$FleetID = ($_POST["fleet"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			header("Location: fleetlist.php?msg=Not+your+fleet");
		}else{
			$Target = "P:".($_POST["value"] ?? "");
			$Strategy = ($_POST["strat"] ?? "");
			if(!EnemyOwned(($_POST["value"] ?? ""))&&($Strategy>1)){ // Free planets can't be attacked/invaded....enter orbit
				$Strategy = 0;
			}
			
			if(($Strategy<2)&&EnemyOwned(($_POST["value"] ?? ""))){ // Trying to Colonise or Enter orbit of enemy owned planet.
				header("Location: fleet.php?id=".$FleetID."&msg=Enemy+Owned");
			}else{
				MoveFleet($FleetID,$Target,$Strategy);
				header("Location: fleet.php?id=".$FleetID."&msg=Moving");
			}
		}
	}
	if(($_POST['action'] ?? "")=="rename"){
		$FleetID = ($_POST["fleet"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			header("Location: fleetlist.php?msg=Not+your+fleet");
		}else{
			$Name = ($_POST["new_name"] ?? "");
			if($Name!=""){
				$sql = "UPDATE fleets SET Name = '$Name' WHERE(FleetID = '$FleetID')";
				$res = mysqli_query($GLOBALS["conn"], $sql);
				header("Location: fleet.php?id=".$FleetID);
			}else{
				header("Location: fleet.php?id=".$FleetID."&msg=Fleet+name+cannot+be+empty");
			}
		}
	}
	if(($_POST['action'] ?? "")=="abort"){
		$FleetID = ($_POST["id"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			header("Location: fleetlist.php?msg=Not+your+fleet");
		}else{
			$Fleet = GetFleet($FleetID);
			$Orig_TTF = CalcTTF($Fleet->MovingFrom,$Fleet->Destination);
			$Abort_TTF = $Orig_TTF-$Fleet->TTF;
			if($Abort_TTF<1){
				$Abort_TTF = 1;
			}
			$sql = "UPDATE fleets SET Strategy = '0', MovingFrom = '".$Fleet->Destination."', Destination = '".$Fleet->MovingFrom."', TTF = '".$Abort_TTF."' WHERE(FleetID = '".$Fleet->FleetID."')";
			$res = mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
			header("Location: fleet.php?id=".$Fleet->FleetID."&msg=Fleet+movement+aborted+".$Abort_TTF);
		}
	}
	if(($_POST['action'] ?? "")=="delete"){
		$FleetID = ($_POST["id"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			header("Location: fleetlist.php?msg=Not+your+fleet");
		}else{
			if(DeleteFleet($FleetID)){
				header("Location: fleetlist.php?msg=Fleet+deleted");
			}else{
				header("Location: fleetlist.php?msg=Fleet+cannot+be+deleted");
			}
		}
	}
}

$FleetID = ($_GET['id'] ?? "");

$Fleet = GetFleet($FleetID);
$CurrentSector = 0;
$CurrentSystem = 0;
$CurrentPlanet = 0;
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Fleet: <?php echo GetFleetName($FleetID); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php");?>
<h2>Fleet: <?php echo GetFleetName($FleetID); ?></h2>
<div class="side"><div class="panel" style="width:250;">
<p> Fleet Size: <?php echo $Fleet->Size; ?> </p>
    <p>Location: <?php echo GetFleetLocationString($FleetID); ?></p>
	<p>
      <?php if($Fleet->Destination!=""&&(substr($Fleet->MovingFrom,0,2)!="X:")){ ?>
      <form method="post" action="fleet.php" style="display:inline;">
        <input type="hidden" name="action" value="abort">
        <input type="hidden" name="id" value="<?php echo $FleetID; ?>">
        <?php echo csrf_token(); ?>
        <button type="submit">Abort</button>
      </form>
      <?php } ?>
    </p>
    <p>HP: <?php echo FleetHP($FleetID); ?><br/>
		AP: <?php echo FleetHP($FleetID); ?></p>
</div><div class="panel" style="width:250;">
<h3>Fleet Modification</h3>
    <p>Rename Fleet:</p>
    <form name="form2" method="post" action="fleet.php">
      <input type="hidden" name="action" value="rename">
      <input name="fleet" type="hidden" value="<?php echo $FleetID; ?>">
      <?php echo csrf_token(); ?>
      <p> Name: 
        <input name="new_name" type="text" id="new_name" size="15">
      </p>
      <p>
        <input type="submit" name="Submit2" value="Rename">
      </p>
    </form>
    <form method="post" action="fleet.php" style="display:inline;">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?php echo $FleetID; ?>">
      <?php echo csrf_token(); ?>
      <button type="submit" onclick="return confirm('Delete this fleet?');">Delete Fleet</button>
    </form>
  </div>

<div class="panel" style="width:250;"><p>Ships:</p>
<ul>
  <li>Transports:<br/>
  <?php 
  if(sizeof($Fleet->Ships->Transports)>0){
 	 foreach($Fleet->Ships->Transports as $k=>$Ship){ 
	  ?>
	  - <?php echo $Ship->Name; ?>
	  <?php
	  }
  }
   ?>
  </li>
  <li>Colonisers:<br/>
  <?php 
  if(sizeof($Fleet->Ships->Colonisers)>0){
 	 foreach($Fleet->Ships->Colonisers as $k=>$Ship){ 
	  ?>
	  - <?php echo $Ship->Name; ?><br/>
	  <?php
	  }
  }
   ?></li>
  <li>Frigates:<br/>
  <?php 
  if(sizeof($Fleet->Ships->Frigates)>0){
 	 foreach($Fleet->Ships->Frigates as $k=>$Ship){ 
	  ?>
	  - <?php echo $Ship->Name; ?><br/>
	  <?php
	  }
  }
   ?></li>
  <li>Cruisers:<br/>
  <?php 
  if(sizeof($Fleet->Ships->Cruisers)>0){
 	 foreach($Fleet->Ships->Cruisers as $k=>$Ship){ 
	  ?>
	  - <?php echo $Ship->Name; ?><br/>
	  <?php
	  }
  }
   ?></li>
  <li>Warships:<br/>
   <?php 
  if(sizeof($Fleet->Ships->Warships)>0){
 	 foreach($Fleet->Ships->Warships as $k=>$Ship){ 
	  ?>
	  - <?php echo $Ship->Name; ?><br/>
	  <?php
	  }
  }
   ?></li>
  <li>Motherships:<br/>
  <?php 
  if(sizeof($Fleet->Ships->Motherships)>0){
 	 foreach($Fleet->Ships->Motherships as $k=>$Ship){ 
	  ?>
	  - <?php echo $Ship->Name; ?><br/>
	  <?php
	  }
  }
   ?></li>
  <li>Fighters:<br/>
  <?php 
  if(sizeof($Fleet->Ships->Fighters)>0){
 	 foreach($Fleet->Ships->Fighters as $k=>$Ship){ 
	  ?>
	  - <?php echo $Ship->Name; ?><br/>
	  <?php
	  }
  }
   ?></li>
</ul>
  </div>
  </div>

<div class="planet"><?php if($Fleet->Location==""){ ?>

<img src="routeimage.img.php?id=<?php echo $FleetID; ?>"/>

<?php }else{ ?>
<h1>Fleet Movement</h1>
<form name="form1" method="post" action="fleet.php">
    <input type="hidden" name="action" value="move">
    <?php echo csrf_token(); ?>
    <p>Target: 
      <input name="fleet" type="hidden" value="<?php echo $FleetID; ?>">
      <input name="value" type="text" id="value" size="10">
      Strategy: 
      <select name="strat">
        <option value="0">Enter Orbit</option>
        <option value="1">Colonise</option>
        <option value="2">Attack</option>
        <option value="3">Invade</option>
      </select>
      <input type="submit" name="Submit" value="Launch">
    </p>
    <h3>Strategies</h3>
    <ol style="width: 400px;">
      <li style="margin-bottom:10px;"><strong>Enter Orbit</strong> - Enter orbit around planet and await instructions.</li>
      <li style="margin-bottom:10px;"><strong>Colonise</strong> - Enter Orbit and colonise planet.</li>
      <li style="margin-bottom:10px;"><strong>Attack</strong> - Destroy orbital and ground defences then await 
        further instructions.</li>
      <li style="margin-bottom:10px;"><strong>Invade</strong> - Destroy all defences and claim planet for 
        your own.</li>
    </ol>
    </p>
  </form>

<?php } ?></div>
</body>
</html>
