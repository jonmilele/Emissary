<?php
function PrintGridFunctions($PlanetID,$Grid,$edit=false){
	$Type = GetGridContents($PlanetID,$Grid);
	
	// Building types: 1=Factory, 2=Laboratory, 3=Harvester, 4=Shipyard, 5=Hangar, 6=Shield, 7=Pulse Cannon, 8=Gigashield, 9=Missile Silo
	switch($Type){
		case "1": // Factory
			break;
		case "2": // Laboratory
			break;
		case "3": // Harvester
			break;
		case "4": // Shipyard
			include("inc/shipyard.bld.inc.php");
			break;
		case "5": // Hangar
			include("inc/hangar.bld.inc.php");
			break;
		case "6": // Shield
			break;
	}
}

function GetBldDefaultHP($Type){
	$sql= "SELECT HP FROM building_types WHERE(Type = '$Type')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	return $row ? $row->HP : 0;
}
function GetBldDefaultAP($Type){
	$sql= "SELECT AP FROM building_types WHERE(Type = '$Type')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	return $row ? $row->AP : 0;
}
function GetBldHP($PlanetID,$Grid){
	$sql= "SELECT HP FROM buildings WHERE(PlanetID = '$PlanetID' AND GridSquare = '$Grid')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	return $row ? $row->HP : 0;
}

function ConstructingBuilding($PlanetID,$Grid){
	$total_count = 0;
	$yard = $PlanetID.":".$Grid;
	$sql= "SELECT COUNT(*) AS count FROM cbuildings WHERE(PlanetID = '$PlanetID' AND Grid = '$Grid')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	if($total_count>0){
		return true;
	}
	return false;
}

function BuildingUnderConstruction($PlanetID,$Grid){
	$ship = array();
	$sql= "SELECT * FROM cbuildings WHERE(PlanetID = '$PlanetID' AND Grid = '$Grid')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return $ship;
	$ship["ID"] = $row->ID;
	$ship["Type"] = $row->Type;
	$ship["Planet"] = $PlanetID;
	$ship["Grid"] = $Grid;
	$ship["TTF"] = $row->TTF;
	
	return $ship;
}

function GetBldIDFromGrid($PlanetID,$Grid){
	$sql= "SELECT BuildingID FROM buildings WHERE(PlanetID = '$PlanetID' AND GridSquare = '$Grid')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	return $row ? $row->BuildingID : 0;
}

function Repair($BuildingID, $PlayerID){
	$pid = (int)$PlayerID;
	$sql= "SELECT Type, HP FROM buildings WHERE(BuildingID = '$BuildingID')";
	$res=mysqli_query($GLOBALS["conn"], $sql);
	$bld = mysqli_fetch_object($res);
	if(!$bld) return false;

	$maxHP = GetBldDefaultHP($bld->Type);
	if($maxHP <= 0 || $bld->HP >= $maxHP) return false;

	$sql= "SELECT Metal, Mineral, Astrium FROM building_types WHERE(Type = '{$bld->Type}')";
	$res=mysqli_query($GLOBALS["conn"], $sql);
	$costs = mysqli_fetch_object($res);
	if(!$costs) return false;

	$damageRatio = ($maxHP - $bld->HP) / $maxHP;
	$repairMetal = (int)round($costs->Metal * $damageRatio);
	$repairMineral = (int)round($costs->Mineral * $damageRatio);
	$repairAstrium = (int)round($costs->Astrium * $damageRatio);

	if($repairMetal > 0 && !HasSufficientResources($pid, 1, $repairMetal)) return false;
	if($repairMineral > 0 && !HasSufficientResources($pid, 2, $repairMineral)) return false;
	if($repairAstrium > 0 && !HasSufficientResources($pid, 3, $repairAstrium)) return false;

	if($repairMetal > 0) DeductResources($pid, 1, $repairMetal);
	if($repairMineral > 0) DeductResources($pid, 2, $repairMineral);
	if($repairAstrium > 0) DeductResources($pid, 3, $repairAstrium);

	$sql= "UPDATE buildings SET HP = '$maxHP' WHERE(BuildingID = '$BuildingID')";
	mysqli_query($GLOBALS["conn"], $sql);
	return true;
}

function GetRepairCost($PlanetID, $Grid){
	$bldType = GetGridContents($PlanetID, $Grid);
	if($bldType <= 0) return null;
	$hp = GetBldHP($PlanetID, $Grid);
	$maxHP = GetBldDefaultHP($bldType);
	if($maxHP <= 0 || $hp >= $maxHP) return null;
	$sql= "SELECT Metal, Mineral, Astrium FROM building_types WHERE(Type = '$bldType')";
	$res=mysqli_query($GLOBALS["conn"], $sql);
	$costs = mysqli_fetch_object($res);
	if(!$costs) return null;
	$damageRatio = ($maxHP - $hp) / $maxHP;
	return [
		'Metal' => (int)round($costs->Metal * $damageRatio),
		'Mineral' => (int)round($costs->Mineral * $damageRatio),
		'Astrium' => (int)round($costs->Astrium * $damageRatio),
	];
}

?>
