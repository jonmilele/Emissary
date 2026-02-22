<?php
function PrintGridFunctions($PlanetID,$Grid){
	$Type = GetGridContents($PlanetID,$Grid);
	
	switch($Type){
		case "1":
			break;
		case "2":
			break;
		case "3":
			break;
		case "4":
			include("inc/shipyard.bld.inc.php");
			break;
		case "5":
			include("inc/hangar.bld.inc.php");
			break;
		case "6":
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

function Repair($BuildingID){
	$sql= "SELECT Type FROM buildings WHERE(BuildingID = '$BuildingID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return;
	$sql= "UPDATE buildings SET HP = '".GetBldDefaultHP($row->Type)."' WHERE(BuildingID = '$BuildingID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
}

?>