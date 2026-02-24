<?php
function PlayerFleets($PlayerID){
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM fleets WHERE(PlayerID = '$PlayerID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	return $total_count;
}

function GetFleetOwner($FleetID){
	$sql= "SELECT PlayerID FROM fleets WHERE(FleetID = '$FleetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	return $row ? $row->PlayerID : 0;
}
function OwnsFleet($username, $FleetID){
	return GetFleetOwner($FleetID) == GetPlayerIDFromName($username);
}

function GetFleetName($FleetID){
	$sql= "SELECT Name FROM fleets WHERE(FleetID = '$FleetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row || $row->Name==""){
		return "Fleet ".$FleetID;
	}else{
		return $row->Name;
	}
}

function GetDefaultFleetName($PlayerID){
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM fleets WHERE(PlayerID = '".$PlayerID."')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	$total_count++;
	return "Fleet ".$total_count;
}

function ListPlayerFleets($PlayerID){
	$Fleets = array();
	$sql= "SELECT * FROM fleets WHERE(PlayerID = '$PlayerID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		$Fleet = GetFleet($row->FleetID);
		$Fleets[$Fleet->FleetID] = $Fleet;
	}
	return $Fleets;
}
function GetFleetLocationString($FleetID){
	$sql= "SELECT Location,Destination,TTF,Strategy FROM fleets WHERE(FleetID = '$FleetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return "";
	$return = "";
	$TTF = $row->TTF;
	$eta = "";
	$hours = 0;
	$days = 0;
	$minutes = 0;
	if($TTF>=60){
		$hours = floor($TTF/60);
		$minutes = $TTF-($hours*60);
		if($hours>24){
			$days = floor($hours/24);
			$hours = $hours-($days*24);
		}
		if($days>0){
			if($days>1){$s = "s";}else{$s = "";}
			$eta .= $days." day$s ";
		}
		if($hours>0){
			if($hours>1){$s = "s";}else{$s = "";}
			$eta .= $hours." hour$s ";
		}
		if($minutes>0){
			if($minutes>1){$s = "s";}else{$s = "";}
			$eta .= $minutes." minute$s";
		}
	}else{
		if($TTF>1){$s = "s";}else{$s = "";}
		$eta .= $TTF." minute$s";
	}
	if($row->Location!=""){
		switch(substr($row->Location,0,1)){
			// Passed in format:- S:1, P:13, X:9-G:1
		case "S": // System
				$return = "In <a href=\"system.php?id=".substr($row->Location,2,strlen($row->Location))."\">".h(GetSystemNameFromID(substr($row->Location,2,strlen($row->Location))))."</a> System";
				break;
			case "P": // Planet
				$return = "Orbiting <a href=\"planet.php?id=".substr($row->Location,2,strlen($row->Location))."\">".h(GetPlanetNameFromID(substr($row->Location,2,strlen($row->Location))))."</a>";
				break;
			case "X": // Sector
				$return = "In Sector <a href=\"sector.php?id=".substr($row->Location,2,strlen($row->Location))."\">".substr($row->Location,2,strlen($row->Location))."</a>";
				break;
		}
	}else{
		$pre = "";
		$post = "";
		// Strategy: 0=orbit, 1=colonise, 2=attack, 3=invade
		switch($row->Strategy){
			case "0": // Orbit
				$post = "";
				break;
			case "1": // Colonise
				$post = " for colonisation";
				break;
			case "2": // Attack
				$post = " to attack";
				break;
			case "3": // Invade
				$post = " to invade";
				break;
		}
		$return = "Moving to <a href=\"planet.php?id=".substr($row->Destination,2,strlen($row->Destination))."\">".h(GetPlanetNameFromID(substr($row->Destination,2,strlen($row->Destination))))."</a>".$post." - ETA: ".$eta;
	}
	return $return;
}
function FleetsInOrbit($PlanetID){
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM fleets WHERE(Location = 'P:$PlanetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	return $total_count;
}
function YourFleetsInOrbit($PlanetID){
	global $username;
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM fleets WHERE(Location = 'P:$PlanetID' AND PlayerID = '".GetPlayerIDFromName($username)."')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	return $total_count;
}
function ListYourFleetsInOrbit($PlanetID){
	global $username;
	$Fleets = array();
	//echo $filterdate." ";
	$sql= "SELECT * FROM fleets WHERE(Location = 'P:$PlanetID' AND PlayerID = '".GetPlayerIDFromName($username)."')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		$Fleets[] = GetFleet($row->FleetID);	
	}
	return $Fleets;
}
function GetYourOrbitingFleet($PlanetID){
	global $username;
	$sql= "SELECT * FROM fleets WHERE(Location = 'P:$PlanetID' AND PlayerID = '".GetPlayerIDFromName($username)."')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return null;
	return GetFleet($row->FleetID);
}

// Check if a fleet can be disbanded: must be orbiting a planet owned by player or teammate, with a Hangar
function CanDisbandFleet($FleetID){
	$sql = "SELECT Location, PlayerID FROM fleets WHERE FleetID='$FleetID'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return ['can' => false, 'reason' => 'Fleet not found'];
	if($row->Location === '' || substr($row->Location, 0, 2) !== 'P:')
		return ['can' => false, 'reason' => 'Fleet must be in orbit'];
	$PlanetID = (int)substr($row->Location, 2);
	$ownerPID = (int)$row->PlayerID;
	// Check planet ownership: player or teammate
	$pRes = mysqli_query($GLOBALS["conn"], "SELECT PlayerID FROM planets WHERE PlanetID='$PlanetID'");
	$pRow = mysqli_fetch_object($pRes);
	if(!$pRow || (int)$pRow->PlayerID < 1)
		return ['can' => false, 'reason' => 'Planet is uncolonised'];
	$planetOwner = (int)$pRow->PlayerID;
	if($planetOwner != $ownerPID){
		$fleetTeam = (int)PlayerTeam($ownerPID);
		$planetTeam = (int)PlayerTeam($planetOwner);
		if($fleetTeam < 1 || $fleetTeam != $planetTeam)
			return ['can' => false, 'reason' => 'Planet must be owned by you or a teammate'];
	}
	// Check for Hangar (building type 5) on the planet
	$hRes = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS cnt FROM buildings WHERE PlanetID='$PlanetID' AND Type=5");
	$hRow = mysqli_fetch_object($hRes);
	if(!$hRow || (int)$hRow->cnt < 1)
		return ['can' => false, 'reason' => 'Planet needs a Hangar to receive ships'];
	return ['can' => true, 'reason' => '', 'PlanetID' => $PlanetID];
}

function DisbandFleet($FleetID){
	$check = CanDisbandFleet($FleetID);
	if(!$check['can']) return false;
	$PlanetID = $check['PlanetID'];
	$sqlship = "UPDATE ships SET PlanetID = '$PlanetID', FleetID = '0' WHERE(FleetID = '$FleetID')";
	mysqli_query($GLOBALS["conn"], $sqlship);
	$sql = "DELETE FROM fleets WHERE(FleetID = '$FleetID')";
	mysqli_query($GLOBALS["conn"], $sql);
	return true;
}

// Legacy wrapper
function DeleteFleet($FleetID){
	return DisbandFleet($FleetID);
}

function CanColonise($PlanetID,$FleetID = 0){
	$sql= "SELECT PlayerID FROM planets WHERE(PlanetID = '$PlanetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if($row->PlayerID>0){
		return false;
	}
	
	if($FleetID == 0){
		$Fleet = GetYourOrbitingFleet($PlanetID);
	}else{
		$Fleet = GetFleet($FleetID);
	}
	if(sizeof($Fleet->Ships->Colonisers)){
		return true;
	}
	return false;
}

function Colonise($PlanetID){
	global $username;
	$PlayerID = GetPlayerIDFromName($username);
	$sql= "UPDATE planets SET PlayerID = '".$PlayerID."' WHERE(PlanetID = '$PlanetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	
	// Eventually code to remove one coloniser
	
	$Fleet = GetYourOrbitingFleet($PlanetID);
	DestroyShip(GetRandomShipOfType($Fleet->FleetID,3),3);
}

function Invade($PlanetID,$PlayerID){
	// Check if this was the defender's home world before changing ownership
	$defPlanet = GetPlanet($PlanetID);
	if($defPlanet && $defPlanet->PlayerID > 0){
		$defenderID = $defPlanet->PlayerID;
		if(IsHomePlanet($defenderID, $PlanetID)){
			$sql = "UPDATE players SET HomePlanetID=0 WHERE PlayerID='$defenderID'";
			mysqli_query($GLOBALS["conn"], $sql);
		}
	}
	$sql= "UPDATE planets SET PlayerID = '".$PlayerID."' WHERE(PlanetID = '$PlanetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	ClaimUnfinishedShips($PlanetID,$PlayerID);
}

function CanInvade($PlanetID){

}

function FleetsInSystem($SystemID){
	$sid = (int)$SystemID;
	$sql = "SELECT COUNT(*) AS count FROM fleets f
		WHERE f.Location = 'S:$sid'
		OR f.Location IN (SELECT CONCAT('P:', p.PlanetID) FROM planets p WHERE p.`System` = '$sid')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	return $row ? (int)$row->count : 0;
}

class Fleet{
	var $FleetID;
	var $PlayerID;
	var $Location;
	var $Destination;
	var $MovingFrom;
	var $Ships;
	var $Size;
	var $Strategy;
	var $TTF;
	var $Name;
	var $HomePort;
}

class Ship{
	var $ShipID;
	var $FleetID;
	var $PlanetID;
	var $PlayerID;
	var $Type;
	var $Name;
	var $HP;
	var $AP;
	var $Registration;
}

function GetShip($ShipID){
	$sql= "SELECT * FROM ships WHERE(ShipID = '$ShipID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return null;
	$ship = new Ship();
	$ship->ShipID = $row->ShipID;
	$ship->FleetID = $row->FleetID;
	$ship->PlanetID = $row->PlanetID ?? 0;
	$ship->PlayerID = $row->PlayerID;
	$ship->Type = $row->Type;
	$ship->Name = $row->Name;
	$ship->HP = $row->HP;
	$ship->AP = GetShipTypeDefaultAP($row->Type);
	$ship->Registration = $row->Registration ?? '';
	
	return $ship;
}

function GetShipArray($FleetID){
	//echo "Getting Ships<br/>"
	$ships = array();
	$sql= "SELECT ShipID FROM ships WHERE(FleetID = '$FleetID')";
	$res=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		//echo "Getting Ship<br/>"
		$ships[] = GetShip($row->ShipID);
	}
	return $ships;
}

function GetShips($FleetID){
	$Ships = array();
	$total_count = 0;
	$bundle = new ShipBundle();

	
	// Check for Transports
	$sql= "SELECT * FROM ships WHERE(FleetID = '$FleetID')";
	$res=mysqli_query($GLOBALS["conn"], $sql);
	// Ship types: 2=Transport, 3=Coloniser, 4=Frigate, 5=Cruiser, 6=Warship, 7=Mothership, 8=Fighter
	while($row = mysqli_fetch_object($res)){
		if($row->Type >= 2 && $row->Type <= 8){
			$bundle->Add(GetShip($row->ShipID));
		}
	}

		
	return $bundle;
}

class ShipBundle{
	var $Transports = array();
	var $Colonisers = array();
	var $Frigates = array();
	var $Cruisers = array();
	var $Warships = array();
	var $Motherships = array();
	var $Fighters = array();
	
	function Total(){
		$total = 0;
		$total += sizeof($this->Transports);
		$total += sizeof($this->Colonisers);
		$total += sizeof($this->Frigates);
		$total += sizeof($this->Cruisers);
		$total += sizeof($this->Warships);
		$total += sizeof($this->Motherships);
		$total += sizeof($this->Fighters);
		return $total;
	}
	
	// Ship types: 2=Transport, 3=Coloniser, 4=Frigate, 5=Cruiser, 6=Warship, 7=Mothership, 8=Fighter
	function Add($Ship){
		switch($Ship->Type){
			case "2": $this->Transports[] = $Ship; break;
			case "3": $this->Colonisers[] = $Ship; break;
			case "4": $this->Frigates[] = $Ship; break;
			case "5": $this->Cruisers[] = $Ship; break;
			case "6": $this->Warships[] = $Ship; break;
			case "7": $this->Motherships[] = $Ship; break;
			case "8": $this->Fighters[] = $Ship; break;
		}
	}
}

function GetUnassignedShips($PlanetID){
	$total_count = 0;
	$Ships = array();
	
	// Check for Transports
	$sql= "SELECT COUNT(*) AS count FROM ships WHERE(PlanetID = '$PlanetID' AND Type = '2')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count += $rowcount->count;
			$Ships["Transports"] = $rowcount->count;
		}
	}
	
	// Check for Colonisers
	$sql= "SELECT COUNT(*) AS count FROM ships WHERE(PlanetID = '$PlanetID' AND Type = '3')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count += $rowcount->count;
			$Ships["Colonisers"] = $rowcount->count;
		}
	}
	
	// Check for Frigates
	$sql= "SELECT COUNT(*) AS count FROM ships WHERE(PlanetID = '$PlanetID' AND Type = '4')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count += $rowcount->count;
			$Ships["Frigates"] = $rowcount->count;
		}
	}
	
	// Check for Cruisers
	$sql= "SELECT COUNT(*) AS count FROM ships WHERE(PlanetID = '$PlanetID' AND Type = '5')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count += $rowcount->count;
			$Ships["Cruisers"] = $rowcount->count;
		}
	}
	
	// Check for Warships
	$sql= "SELECT COUNT(*) AS count FROM ships WHERE(PlanetID = '$PlanetID' AND Type = '6')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count += $rowcount->count;
			$Ships["Warships"] = $rowcount->count;
		}
	}
	
	// Check for Motherships
	$sql= "SELECT COUNT(*) AS count FROM ships WHERE(PlanetID = '$PlanetID' AND Type = '7')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count += $rowcount->count;
			$Ships["Motherships"] = $rowcount->count;
		}
	}
	
	// Check for Fighters
	$sql= "SELECT COUNT(*) AS count FROM ships WHERE(PlanetID = '$PlanetID' AND Type = '8')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count += $rowcount->count;
			$Ships["Fighters"] = $rowcount->count;
		}
	}
	$Ships["Total"] = $total_count;
	
	return $Ships;
}

function GetFleet($FleetID){
	$Fleet = new Fleet();
	
	$sql= "SELECT * FROM fleets WHERE(FleetID = '$FleetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return null;
	$Fleet->FleetID = $row->FleetID;
	$Fleet->PlayerID = $row->PlayerID;
	$Fleet->Location = $row->Location;
	$Fleet->Destination = $row->Destination;
	$Fleet->MovingFrom = $row->MovingFrom;
	$Fleet->Strategy = $row->Strategy;
	$Fleet->TTF = $row->TTF;
	$Fleet->Name = GetFleetName($FleetID);
	$Fleet->HomePort = (int)($row->HomePort ?? 0);

	$Fleet->Ships = GetShips($FleetID);
	$Fleet->Size = $Fleet->Ships->Total();
	
	return $Fleet;
}
function CreateFleet($PlanetID,$Name,$AllShips){
	global $username;
	$sql= "INSERT INTO fleets(PlayerID,Location,Name) VALUES('".GetPlayerIDFromName($username)."','P:".$PlanetID."','$Name')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$FleetID = mysqli_insert_id($GLOBALS["conn"]);
	if($AllShips){
		$sql= "UPDATE ships SET FleetID = '".$FleetID."', PlanetID = '0' WHERE(PlayerID = '".GetPlayerIDFromName($username)."' AND PlanetID = '$PlanetID')";
		$rescount=mysqli_query($GLOBALS["conn"], $sql);
	}
	return $FleetID;
}

function MoveFleet($FleetID,$Target,$Strategy = 0){
//Pass target in format:- S:1,P:3,X:9-G:1
	$sql = "SELECT * FROM fleets WHERE(FleetID = '$FleetID')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return;
	$TTF = 0;
	$GalLoc = "";
	if($row->Destination!=""){
		$GalLoc = GetGalacticLocation($FleetID);
		$TTF = CalcTTF($GalLoc,$Target);
	}else{
		if(SameSystem($row->Location,$Target)){
			$TTF = 10;
		}else{
			$TTF = CalcTTF($row->Location,$Target);
		}
	}
	if($GalLoc==""){
		$Location = $row->Location;
	}else{
		$Location = $GalLoc;
	}
	$sql= "UPDATE fleets SET MovingFrom = '".$Location."', Location = '', Strategy = '$Strategy', Destination = '".$Target."', TTF = '".$TTF."' WHERE(FleetID = '$FleetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
}

function SameSystem($Planet1,$Planet2){
	$Planet1 = substr($Planet1,2,strlen($Planet1)-2);
	$Planet2 = substr($Planet2,2,strlen($Planet2)-2);
	
	$sql = "SELECT `System` FROM planets WHERE(PlanetID = '$Planet1')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return false;
	$one = $row->System;
	
	$sql = "SELECT `System` FROM planets WHERE(PlanetID = '$Planet2')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return false;
	$two = $row->System;
	
	if($one==$two){
		return true;
	}
	return false;
}

function GetSystemCoords($SystemID){
	$sql = "SELECT Coords,SectorID FROM Systems WHERE(SystemID = '".$SystemID."')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return array("x" => 0, "y" => 0);
	$location_sector_coords = GetSectorCoords($row->SectorID);
	$location_system_coords = explode("/",$row->Coords);
	$location_system_coords[0] *= 50; // Get x value into units
	$location_system_coords[1] *= 50; // Get y value into units
	
//	echo "Location Sector x: ".$location_sector_coords[0]."<br/>";
//	echo "Location Sector y: ".$location_sector_coords[1]."<br/>";
	
	$location_sector_offset_coords_x = ($location_sector_coords[0]-1)*500;
	$location_sector_offset_coords_y = ($location_sector_coords[1]-1)*500;
	
//	echo "Location Offset x: ".$location_sector_offset_coords_x."<br/>";
//	echo "Location Offset y: ".$location_sector_offset_coords_y."<br/>";
	$location_absolute = array();
	$location_absolute["x"] = $location_sector_offset_coords_x + $location_system_coords[0];
	$location_absolute["y"] = $location_sector_offset_coords_y + $location_system_coords[1];
	return $location_absolute;
}

function CalcTTF($Location,$Destination){
	$Destination = substr($Destination,2,strlen($Destination)-2);
	if(substr($Location,0,2)=="X:"){
		$coords = substr($Location,2,strlen($Location)-2);
		$carr = explode("/",$coords);
		$location_x_absolute = $carr[0];
		$location_y_absolute = $carr[1];
	}else{
		$Location = substr($Location,2,strlen($Location)-2);
		
		$sql = "SELECT `System` FROM planets WHERE(PlanetID = '$Location')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
		if(!$row) return 0;
		$sql = "SELECT Coords,SectorID FROM Systems WHERE(SystemID = '".$row->System."')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
		if(!$row) return 0;
		$location_sector_coords = GetSectorCoords($row->SectorID);
		$location_system_coords = explode("/",$row->Coords);
		$location_system_coords[0] *= 50; // Get x value into units
		$location_system_coords[1] *= 50; // Get y value into units
		
		$location_sector_offset_coords_x = ($location_sector_coords[0]-1)*500;
		$location_sector_offset_coords_y = ($location_sector_coords[1]-1)*500;

		$location_x_absolute = $location_sector_offset_coords_x + $location_system_coords[0];
		$location_y_absolute = $location_sector_offset_coords_y + $location_system_coords[1];
	}

	$sql = "SELECT `System` FROM planets WHERE(PlanetID = '$Destination')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return 0;
	$dest_coords = GetSystemCoords($row->System);
		
	$destination_x_absolute = $dest_coords["x"];
	$destination_y_absolute = $dest_coords["y"];
	
//	echo "Destination Abs x: ".$destination_x_absolute."<br/>";
//	echo "Destination Abs y: ".$destination_y_absolute."<br/>";
	
	$sum_square = pow(($location_x_absolute-$destination_x_absolute),2)+pow(($location_y_absolute-$destination_y_absolute),2);
	$units = round(sqrt($sum_square),0);
	$units_per_turn = 50;
	$minutes_per_unit = 30/$units_per_turn;
	$TTF = round(($minutes_per_unit*$units),0);
	return $TTF;
}

function GetSectorCoords($SectorID){
	$sql = "SELECT GridCoords FROM sectors WHERE(SectorID = '".$SectorID."')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return array(0, 0);
	$return = explode(".",$row->GridCoords);
//	echo "Return: ".$return[0].":".$return[1]." - ".$row->GridCoords."<br/>";
	return $return;
}

function GetGalacticLocation($FleetID){
	$sql= "SELECT * FROM fleets WHERE(FleetID = '$FleetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return "X:0/0";
	$Orig_TTF = CalcTTF($row->MovingFrom,$row->Destination);
	$cent = (($Orig_TTF-$row->TTF)/$Orig_TTF)*100;
	
	$Location = substr($row->MovingFrom,2,strlen($row->MovingFrom)-2);
	$Destination = substr($row->Destination,2,strlen($row->Destination)-2);
	if(substr($row->MovingFrom,0,2)=="X:"){
		$carr = explode("/",$Location);
		$loc_system_coords = array();
		$loc_system_coords["x"] = $carr[0];
		$loc_system_coords["y"] = $carr[1];
	}else{
		$sql = "SELECT `System` FROM planets WHERE(PlanetID = '$Location')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
		if(!$row) return "X:0/0";
		$loc_system_coords = GetSystemCoords($row->System);
	}
	$sql = "SELECT `System` FROM planets WHERE(PlanetID = '$Destination')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return "X:0/0";
	$dest_system_coords = GetSystemCoords($row->System);
	
	$Ax = $loc_system_coords["x"];
	$Ay = $loc_system_coords["y"];
	$Bx = $dest_system_coords["x"];
	$By = $dest_system_coords["y"];
	$AA1_Length = (($By-$Ay)/100)*$cent;
	$A1F_Length = (($Bx-$Ax)/100)*$cent;
	
	$new_point_x = round($Ax+$A1F_Length,0);
	$new_point_y = round($Ay+$AA1_Length,0);

	return "X:".$new_point_x."/".$new_point_y;
}

// Find the nearest planet owned by the player or their teammates
// Returns PlanetID or 0 if none found
function GetNearestFriendlyPlanet($FleetID, $PlayerID){
	// Get fleet position
	$fleet = GetFleet($FleetID);
	if(!$fleet) return 0;
	if($fleet->Location != ''){
		// Stationary — resolve from Location
		$loc = $fleet->Location;
		if(substr($loc,0,2)=='P:'){
			$pid = (int)substr($loc,2);
			$sql = "SELECT `System` FROM planets WHERE PlanetID='$pid'";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$row = mysqli_fetch_object($res);
			if(!$row) return 0;
			$fc = GetSystemCoords($row->System);
		}elseif(substr($loc,0,2)=='S:'){
			$fc = GetSystemCoords((int)substr($loc,2));
		}else{
			return 0;
		}
		$fx = $fc['x']; $fy = $fc['y'];
	}else{
		// In transit — use galactic interpolation
		$galLoc = GetGalacticLocation($FleetID);
		$coords = substr($galLoc,2);
		$carr = explode('/',$coords);
		$fx = (float)$carr[0]; $fy = (float)$carr[1];
	}

	// Get all friendly planets (player + teammates)
	$teamID = PlayerTeam($PlayerID);
	if($teamID > 0){
		$sql = "SELECT p.PlanetID, p.`System` FROM planets p JOIN players pl ON p.PlayerID = pl.PlayerID WHERE pl.TeamID = '$teamID' AND p.PlayerID > 0";
	}else{
		$sql = "SELECT PlanetID, `System` FROM planets WHERE PlayerID = '$PlayerID'";
	}
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if(!$res) return 0;

	$bestPlanet = 0;
	$bestDist = PHP_FLOAT_MAX;
	// Cache system coords to avoid re-querying for planets in same system
	$sysCache = [];
	while($row = mysqli_fetch_object($res)){
		$sysID = (int)$row->System;
		if(!isset($sysCache[$sysID])){
			$sysCache[$sysID] = GetSystemCoords($sysID);
		}
		$sc = $sysCache[$sysID];
		$dist = pow($fx - $sc['x'], 2) + pow($fy - $sc['y'], 2);
		if($dist < $bestDist){
			$bestDist = $dist;
			$bestPlanet = (int)$row->PlanetID;
		}
	}
	return $bestPlanet;
}

function AddShipToFleet($ShipID,$FleetID){
	$sql= "UPDATE ships SET FleetID = '$FleetID' WHERE(ShipID = '$ShipID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	
	$sql= "UPDATE ships SET PlanetID = '0' WHERE(ShipID = '$ShipID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
}

function RemoveShipFromFleet($ShipID,$FleetID){
	// Add code to Check the ship is in orbit of a planet.
	$sql= "UPDATE ships SET FleetID = '0' WHERE(ShipID = '$ShipID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
}
function FleetIsInOrbit($FleetID,$PlanetID){

	$sql= "SELECT Location FROM fleets WHERE(FleetID = '".$FleetID."')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return false;
	if($row->Location=="P:".$PlanetID){
		return true;
	}
	return false;
}

function ShipFleetIsInOrbit($ShipID,$PlanetID){
	$sql= "SELECT FleetID FROM ships WHERE(ShipID = '$ShipID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return false;
	return FleetIsInOrbit($row->FleetID,$PlanetID);
}

function GetNextShipInQueue($PlanetID,$Grid){
	$query = "SELECT * FROM qships WHERE(Yard = '$PlanetID:$Grid') ORDER BY QueuePosition ASC LIMIT 0,1";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row ? $row->ShipID : 0;
}

function ShiftQueue($PlanetID,$Grid){
	$query = "UPDATE qships SET QueuePosition = QueuePosition-1 WHERE(Yard = '$PlanetID:$Grid')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
}

function AddToQueue($PlanetID,$Grid,$Type,$Name){
	$query = "SELECT QueuePosition FROM qships WHERE(Yard = '$PlanetID:$Grid') ORDER BY QueuePosition DESC LIMIT 0,1";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	$newqueue = $row ? $row->QueuePosition+1 : 1;
	$query = "INSERT INTO qships(Type,Name,Yard,QueuePosition) VALUES('$Type','$Name','$PlanetID:$Grid','$newqueue')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
}
function GetQueueSize($PlanetID,$Grid){
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM qships WHERE(Yard = '$PlanetID:$Grid')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	return $total_count;
}

function ShipsInQueue($PlanetID,$Grid){
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM qships WHERE(Yard = '$PlanetID:$Grid')";
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
function CalculateShipBuildTime($PlanetID,$Type){
	$query = "SELECT Turns FROM ship_types WHERE(Type = '$Type')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return 0;
	$turns = $row->Turns*30;
	$ten_cent = $turns/10;
	// Factories remove 5% of build time.
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND Type = 1)";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	//echo "Factories: ".$total_count;
	$five_cent = $ten_cent/2;
	$turns_off = round($five_cent*$total_count,0);
	//echo "Turns Off: ".$turns_off;
	if($turns_off<($turns-$ten_cent)){
		$turns = $turns-$turns_off;
	}else{
		$turns = $ten_cent;
		//echo "Setting 10% build time";
	}
	return $turns;
}
function CreateShip($Type,$PlanetID,$Grid,$Name,$PlayerID = 0){
	global $username;
	if($PlayerID==0){
		$PlayerID = GetPlayerIDFromName($username);
	}
	$costmetal = 0;
	$costmineral = 0;
	$costastrium = 0;
	
	$query = "SELECT * FROM ship_types WHERE(Type = '$Type')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return 0;
	$costmetal = $row->Metal;
	$costmineral = $row->Mineral;
	$costastrium = $row->Astrium;
	$turns = CalculateShipBuildTime($PlanetID,$Type);
	$yard = $PlanetID.":".$Grid;
	
	if(HasSufficientResources($PlayerID,1,$costmetal)&&HasSufficientResources($PlayerID,2,$costmineral)&&HasSufficientResources($PlayerID,3,$costastrium)){
		DeductResources($PlayerID,1,$costmetal);
		DeductResources($PlayerID,2,$costmineral);
		DeductResources($PlayerID,3,$costastrium);
		
		$sql= "INSERT INTO cships(PlayerID,Type,Yard,TTF,Name) VALUES('".$PlayerID."','$Type','$yard','$turns','$Name')";
		$rescount=mysqli_query($GLOBALS["conn"], $sql);
		return mysqli_insert_id($GLOBALS["conn"]);
	}else{
		//echo "Insufficient Resources";
		return 0;
	}
}

function ShipUnderConstruction($PlanetID,$Grid){
	$ship = array();
	$yard = $PlanetID.":".$Grid;
	$sql= "SELECT * FROM cships WHERE(Yard = '$yard')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return $ship;
	$ship["ID"] = $row->ID;
	$ship["Name"] = $row->Name;
	$ship["Type"] = $row->Type;
	$ship["Planet"] = $PlanetID;
	$ship["Grid"] = $Grid;
	$ship["TTF"] = $row->TTF;
	
	return $ship;
}

function ConstructingShip($PlanetID,$Grid){
	$total_count = 0;
	$yard = $PlanetID.":".$Grid;
	$sql= "SELECT COUNT(*) AS count FROM cships WHERE(Yard = '$yard')";
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

function DestroyShip($ShipID,$Reason){
// Reasons: 1=Combat, 2=Self-Destruct, 3=Used
	$sql= "SELECT * FROM ships WHERE(ShipID = '$ShipID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	
	$sql= "DELETE FROM ships WHERE(ShipID = '$ShipID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if($row && !HasShipsLeft($row->FleetID)){
		$sql= "DELETE FROM fleets WHERE(FleetID = '".$row->FleetID."')";
		$rescount=mysqli_query($GLOBALS["conn"], $sql);
	}
}

function GetShipsInOrbit($PlanetID){
	global $username;
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM ships WHERE(PlanetID = '$PlanetID' AND PlayerID = '".GetPlayerIDFromName($username)."')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	return $total_count;
}

function GetRandomShipOfType($FleetID,$Type){
	if(($Type>0)&&($Type<9)){
		$sql= "SELECT ShipID FROM ships WHERE(FleetID = '$FleetID' AND Type = '$Type')";
		$rescount=mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($rescount);
		return $row ? $row->ShipID : 0;
	}
}

function GetRandomShip($FleetID){
	$ships = array();
	$sql= "SELECT ShipID FROM ships WHERE(FleetID = '$FleetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		$ships[] = $row->ShipID;
	}
	$rand = rand(0,sizeof($ships)-1);
	return $ships[$rand];
}

function GetRandomUnassignedShipOfType($PlanetID,$Type){
	global $username;
	if(($Type>0)&&($Type<9)){
		$sql= "SELECT ShipID FROM ships WHERE(PlanetID = '$PlanetID' AND Type = '$Type' AND PlayerID = '".GetPlayerIDFromName($username)."')";
		$rescount=mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($rescount);
		return $row ? $row->ShipID : 0;
	}
}
function GetShipTypeString($Type){
	$query = "SELECT Name FROM ship_types WHERE(Type = '$Type')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row ? $row->Name : "Unknown";
}

function GetShipName($ShipID){
	$query = "SELECT Name, Registration FROM ships WHERE(ShipID = '$ShipID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return "Unknown";
	$reg = ($row->Registration ?? '');
	return $reg !== '' ? $reg . ' ' . $row->Name : $row->Name;
}

function GenerateShipRegistration($Type){
	$query = "SELECT RegPrefix FROM ship_types WHERE(Type = '$Type')";
	$res = mysqli_query($GLOBALS["conn"], $query);
	$row = mysqli_fetch_object($res);
	$prefix = ($row && $row->RegPrefix !== '') ? $row->RegPrefix : 'XX';
	for($i = 0; $i < 100; $i++){
		$num = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
		$code = $prefix . '-' . $num;
		$esc = mysqli_real_escape_string($GLOBALS["conn"], $code);
		$chk = mysqli_query($GLOBALS["conn"], "SELECT 1 FROM ships WHERE Registration='$esc' LIMIT 1");
		if(mysqli_num_rows($chk) == 0) return $code;
	}
	// Fallback: use prefix + ShipID-based suffix
	return $prefix . '-' . rand(10000, 99999);
}

class ShipBundleSingleType{
	var $Type = 0;
	var $Ships = array();
	function ShipBundleSingleType($Type){
		$this->Type = $Type;
	}
	function Add($Ship){
		$this->Ships[] = $Ship;
	}
}
function GetLowestRankVesselTypeInFleet($FleetID){
	// returns array with type, HP and number.
}

function FleetHP($FleetID){
	$hp = 0;
	$query = "SELECT HP FROM ships WHERE(FleetID = '$FleetID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		$hp += $row->HP;
	}
	return $hp;
}

function FleetAP($FleetID){
	$ap = 0;
	$query = "SELECT Type FROM ships WHERE(FleetID = '$FleetID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		$ap += GetShipTypeDefaultAP($row->Type);
	}
	return $ap;
}

class Battle{
	var $Winner;
	var $Attacker;
	var $Defender;
	var $PlanetID;
	var $BattleID;
}

function GetBattle($BattleID){
	$sql = "SELECT * FROM battles WHERE(BattleID = '".$BattleID."')";
	$res = mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($res);
	if(!$row) return null;
	$battle = new Battle();
	$battle->Attacker = $row->Attacker;
	$battle->BattleID = $row->BattleID;
	$battle->Defender = $row->Defender;
	$battle->Winner = $row->Winner;
	$battle->PlanetID = $row->PlanetID;
	return $battle;
}

function AttackPlanet($FleetID,$PlanetID,$Invade = false){
	global $echo;
	$echo = "";
	$attacker = GetFleetOwner($FleetID);
	$Planet = GetPlanet($PlanetID);
	$defender = $Planet->PlayerID;
	// This function executes a sequence of functions to attack a planet untill either
	// the attacking fleet is destroyed or the planet is rendered defenceless.
	$echo .= "Beginning Battle<br/>";
	
/*	if(EnemyFleetsInOrbit($attacker,$PlanetID){ //Planet has orbiting fleets
		$enemy_fleets = GetEnemyFleetsInOrbit($attacker,$PlanetID);
		foreach($enemy_fleets as $k=>$fleet){
			Fleetbattle($FleetID,$fleet->FleetID);
			if(!HasShipsLeft($FleetID))
				break;
		}
	}
	*/
	while(HasShipsLeft($FleetID)&&HasDefencesLeft($PlanetID)){
		if(PlanetFires($PlanetID,$FleetID)){
			FleetFires($FleetID,$PlanetID);
		}
	}
	$echo .= "Battle over<br/>";
	$winner = "";
	if(HasShipsLeft($FleetID)){
		$echo .= "Attacker Wins";
		$winner = $attacker;
		if($Invade){
			Invade($PlanetID,$attacker);
			$echo .= "<br/>Planet Invaded";
			AddAlert($attacker, 'combat', 'You have invaded '.GetPlanetNameFromID($PlanetID), 'planet.php?id='.$PlanetID);
			AddAlert($defender, 'combat', GetPlanetNameFromID($PlanetID).' was invaded', 'planet.php?id='.$PlanetID);
		}
	}else{
		$echo .= "Defender Wins";
		$winner = $defender;
	}
	
	$logEscaped = mysqli_real_escape_string($GLOBALS["conn"], $echo);
	$sql = "INSERT INTO battles(PlanetID,Defender,Attacker,Winner,Date,Log) VALUES('$PlanetID','".$defender."','".$attacker."','$winner',NOW(),'".$logEscaped."')";
	$res=mysqli_query($GLOBALS["conn"], $sql);
	$id = mysqli_insert_id($GLOBALS["conn"]);
	
	$_planetName = GetPlanetNameFromID($PlanetID);
	if($winner == $attacker){
		AddAlert($attacker, 'combat', 'You have won the battle of '.$_planetName, 'battle.php?id='.$id);
		AddAlert($defender, 'combat', 'You have lost the battle of '.$_planetName, 'battle.php?id='.$id);
	} else {
		AddAlert($attacker, 'combat', 'You have lost the battle of '.$_planetName, 'battle.php?id='.$id);
		AddAlert($defender, 'combat', 'You have won the battle of '.$_planetName, 'battle.php?id='.$id);
	}
}

function PlanetFires($PlanetID,$FleetID){	
	global $echo;

	$hitChance = (int)GetGameSetting('planet_weapon_hit_chance', 3);
	$rhit = rand(1, $hitChance); // 1 in N chance of hitting the fleet
	if($rhit==1){
		$echo .= "Planet Fires!<br/>";
		$weapons = GetWeapons($PlanetID);
		foreach($weapons as $k=>$weapon){
		///	echo "Grabbing Weapon";
			if(HasShipsLeft($FleetID)){
				$ship = GetRandomShip($FleetID);
				$query = "SELECT HP,Name FROM ships WHERE(ShipID = '$ship')";
				$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
				$row = mysqli_fetch_object($notresult);
				if($weapon->HP>=$row->HP){
					DestroyShip($ship,1);
					$echo .= "The ship '".h($row->Name)."' was destroyed<br/>";
				}else{
					$new_hp = $row->HP - $weapon->HP;
					$nquery = "UPDATE ships SET HP = '$new_hp' WHERE(ShipID = '".$ship."')";
					$nresult = mysqli_query($GLOBALS["conn"], $nquery) or die(mysqli_error($GLOBALS["conn"]));
					$echo .= "The ship '".h($row->Name)."' was damaged - HP: ".$new_hp."<br/>";
				}
			}else{
				break;
			}
		}
	}else{
		$echo .= "Planet missed target.<br/>";
	}
	return HasShipsLeft($FleetID);
}

function FleetFires($FleetID,$PlanetID){
	global $echo;
	$echo .= "Fleet Firing<br/>";
	if(HasShields($PlanetID)){
		//echo "Has Shields, Firing....<br/>";
		$ships = GetShipArray($FleetID);
		foreach($ships as $k=>$ship){
			//echo "Ship Firing<br/>";
			if(HasShields($PlanetID)){
				$shield = GetRandomShield($PlanetID);
				$sql= "SELECT * FROM buildings WHERE(BuildingID = '$shield')";
				$rescount=mysqli_query($GLOBALS["conn"], $sql);
				$row = mysqli_fetch_object($rescount);
				$typestring = GetGridContentString($row->Type);
				$effectiveHP = GetEffectiveBuildingHP($row->HP, $PlanetID, $row->GridSquare, $row->Type);
				if($ship->AP>=$effectiveHP){
					$sql= "DELETE FROM buildings WHERE(BuildingID = '$shield')";
					$rescount=mysqli_query($GLOBALS["conn"], $sql);
					$echo .= $typestring." in grid ".$row->GridSquare." was Destroyed<br/>";
				}else{
					$new_hp = $effectiveHP - $ship->AP;
					$diff  = $effectiveHP - $new_hp;
					$nquery = "UPDATE buildings SET HP = '$new_hp' WHERE(BuildingID = '".$shield."')";
					$nresult = mysqli_query($GLOBALS["conn"], $nquery) or die(mysqli_error($GLOBALS["conn"]));
					$echo .= $typestring." in grid ".$row->GridSquare." was Damaged - HP: ".$new_hp."<br/>";
					Collateral($PlanetID,$diff,$shield);
				}
			}else{
				break;
			}			
		}
	}else{
		//echo "Has weapons, Firing....<br/>";
		$ships = GetShipArray($FleetID);
		foreach($ships as $k=>$ship){
			//echo "Ship Firing<br/>";
			if(HasWeapons($PlanetID)){
				$shield = GetRandomWeapon($PlanetID);
				$sql= "SELECT * FROM buildings WHERE(BuildingID = '$shield')";
				$rescount=mysqli_query($GLOBALS["conn"], $sql);
				$row = mysqli_fetch_object($rescount);
				$typestring = GetGridContentString($row->Type);
				$effectiveHP = GetEffectiveBuildingHP($row->HP, $PlanetID, $row->GridSquare, $row->Type);
				if($ship->AP>=$effectiveHP){
					$sql= "DELETE FROM buildings WHERE(BuildingID = '$shield')";
					$rescount=mysqli_query($GLOBALS["conn"], $sql);
					$echo .= $typestring." in grid ".$row->GridSquare." was Destroyed<br/>";
				}else{
					$new_hp = $effectiveHP - $ship->AP;
					$nquery = "UPDATE buildings SET HP = '$new_hp' WHERE(BuildingID = '".$shield."')";
					$nresult = mysqli_query($GLOBALS["conn"], $nquery) or die(mysqli_error($GLOBALS["conn"]));
					$echo .= $typestring." in grid ".$row->GridSquare." was Damaged<br/>";
				}
			}else{
				break;
			}			
		}
	}
}

function Collateral($PlanetID,$Damage,$Exclude){
	global $echo;
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	$t_damage = round($Damage/$total_count,0);
	
	$sql= "SELECT * FROM buildings WHERE(PlanetID = '$PlanetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if($row && ($row->BuildingID != $Exclude) && ($t_damage>0)){
		$typestring = GetGridContentString($row->Type);
		if($t_damage>=$row->HP){
			$sql= "DELETE FROM buildings WHERE(BuildingID = '".$row->BuildingID."')";
			$rescount=mysqli_query($GLOBALS["conn"], $sql);
			$echo .= $typestring." in grid ".$row->GridSquare." was Destroyed by collateral<br/>";
		}else{
			$new_hp = $row->HP - $t_damage;
			$nquery = "UPDATE buildings SET HP = '$new_hp' WHERE(BuildingID = '".$row->BuildingID."')";
			$nresult = mysqli_query($GLOBALS["conn"], $nquery) or die(mysqli_error($GLOBALS["conn"]));
			$echo .= $typestring." in grid ".$row->GridSquare." was Damaged by collateral<br/>";
		}
	}
}

function FleetBattle($Fleet1, $Fleet2){
	global $echo;
	$echo .= "Battle beginning between fleets '".h(GetFleetName($Fleet1))."' and '".h(GetFleetName($Fleet2))."'<br/>";
	$next = $Fleet1;
	$other = $Fleet2;
	while(HasShipsLeft($Fleet1)&&HasShipsLeft($Fleet2)){	
		$ships = GetShipArray($next);
		foreach($ships as $k=>$ship){	
			$tid = GetRandomShip($other);
			if($tid==""){
				break;
			}
			$target = GetShip($tid);
			$typestring = GetShipTypeString($target->Type);
			if($ship->AP>=$target->HP){
				$sql= "DELETE FROM ships WHERE(ShipID = '".$target->ShipID."')";
				$rescount=mysqli_query($GLOBALS["conn"], $sql);
				$echo .= "The ".h($typestring)." ".h($target->Name)." was Destroyed<br/>";
			}else{
				$new_hp = $target->HP - $ship->AP;
				$nquery = "UPDATE ships SET HP = '$new_hp' WHERE(ShipID = '".$target->ShipID."')";
				$nresult = mysqli_query($GLOBALS["conn"], $nquery) or die(mysqli_error($GLOBALS["conn"]));
				$echo .= "The ".h($typestring)." ".h($target->Name)." was Damaged<br/>";
			}
		}
		$temp = $next;
		$next = $other;
		$other = $temp;
	}
}

function HasShipsLeft($FleetID){

	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM ships WHERE(FleetID = '$FleetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
		//echo "a<br/>";
			$total_count = $rowcount->count;
		}
	}
	//echo $total_count." ships remaining [$FleetID]<br/>";
	if($total_count>0){
		return true;
	}
	return false;
}

function DropFleetHPByPercentage($FleetID,$Cent){
	global $echo;
	$query = "SELECT ShipID,HP,Type FROM ships WHERE(FleetID = '$FleetID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		$s_cent = (GetShipTypeDefaultHP($row->Type)/100)*$Cent;
		if($s_cent>=$row->HP){
			$name = GetShipName($row->ShipID);
			DestroyShip($row->ShipID,1);
			$echo .= "The ship '".$name."' was destroyed<br/>";
		}else{
			$new_hp = $row->HP - $s_cent;
			$nquery = "UPDATE ships SET HP = '$new_hp' WHERE(ShipID = '".$row->ShipID."')";
			$nresult = mysqli_query($GLOBALS["conn"], $nquery) or die(mysqli_error($GLOBALS["conn"]));
			$echo .= "The ship '".h(GetShipName($row->ShipID))."' was damaged<br/>";
		}
	}
	if(!HasShipsLeft($FleetID)){
		$sql= "DELETE FROM fleets WHERE(FleetID = '$FleetID')";
		$rescount=mysqli_query($GLOBALS["conn"], $sql);
		$echo .= "Attacking fleet destroyed!<br/>";
	}
}

function GetShipTypeDefaultHP($Type){
	$query = "SELECT HP FROM ship_types WHERE(Type = '$Type')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row ? $row->HP : 0;
}

function GetShipTypeDefaultAP($Type){
	$query = "SELECT AP FROM ship_types WHERE(Type = '$Type')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row ? $row->AP : 0;
}

function EnemyFleetsInOrbit($PlayerID,$PlanetID){
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM fleets WHERE(PlayerID <> '$PlayerID' AND PlanetID = '$PlanetID')";
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

function GetEnemyFleetsInOrbit($PlayerID,$PlanetID){
	$fleets = array();
	$query = "SELECT FleetID,Destination,PlayerID FROM fleets WHERE (PlayerID <> '$PlayerID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		if($row->Location=="P:".$PlanetID){
			$fleets[] = GetFleet($row->FleetID);
		}
	}
	return $fleets;
}

function GetIncomingEnemyFleets($PlayerID){
	global $username;
	$fleets = array();
	$query = "SELECT FleetID,Destination,PlayerID FROM fleets WHERE (PlayerID <> '$PlayerID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		if($row->Destination!=""){
			$target = substr($row->Destination,2,strlen($row->Destination)-2);
			if(OwnsPlanet($username,$target)){
				$fleets[] = GetFleet($row->FleetID);
			}
		}
	}
	return $fleets;
}

?>