<?php
function AssignStartingPlanet($PlayerID){
	// Find an unclaimed planet for a new player
	// Priority 1: Find a planet in a system with no other players
	$sql = "SELECT p.PlanetID FROM planets p 
	        INNER JOIN Systems s ON p.`System` = s.SystemID
	        WHERE p.PlayerID = 0
	        AND s.SystemID NOT IN (SELECT DISTINCT `System` FROM planets WHERE PlayerID > 0)
	        ORDER BY RAND() LIMIT 1";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	
	// Priority 2: If all systems have players, just find any unclaimed planet
	if(!$row){
		$sql = "SELECT PlanetID FROM planets WHERE PlayerID = 0 ORDER BY RAND() LIMIT 1";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
	}
	
	if($row){
		$planetID = $row->PlanetID;
		$sql = "UPDATE planets SET PlayerID = '$PlayerID' WHERE PlanetID = '$planetID'";
		mysqli_query($GLOBALS["conn"], $sql);
		// Grant starting resources
		$sMetal = (int)GetGameSetting('starting_metal', 500);
		$sMineral = (int)GetGameSetting('starting_mineral', 250);
		$sAstrium = (int)GetGameSetting('starting_astrium', 50);
		$sql = "UPDATE players SET Metal = '$sMetal', Mineral = '$sMineral', Astrium = '$sAstrium', HomePlanetID = '$planetID' WHERE PlayerID = '$PlayerID'";
		mysqli_query($GLOBALS["conn"], $sql);
		// Recalculate system and sector ownership
		$planet = GetPlanet($planetID);
		if($planet){
			CheckSystemMajOwner($planet->System);
			$sys = GetSystem($planet->System);
			if($sys) CalcMajOwner($sys->SectorID);
		}
		return $planetID;
	}
	
	return 0;
}

function Build($PlanetID,$BuildingType,$Grid){
	global $username;
	$costmetal = 0;
	$costmineral = 0;
	$costastrium = 0;
	
	$query = "SELECT Metal,Mineral,Astrium FROM building_types WHERE(Type = '$BuildingType')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return 0;
	$costmetal = $row->Metal;
	$costmineral = $row->Mineral;
	$costastrium = $row->Astrium;
	$turns = CalculateBuildTime($PlanetID,$BuildingType);
	
	if(HasSufficientResources(GetPlayerIDFromName($username),1,$costmetal)&&HasSufficientResources(GetPlayerIDFromName($username),2,$costmineral)&&HasSufficientResources(GetPlayerIDFromName($username),3,$costastrium)){
		DeductResources(GetPlayerIDFromName($username),1,$costmetal);
		DeductResources(GetPlayerIDFromName($username),2,$costmineral);
		DeductResources(GetPlayerIDFromName($username),3,$costastrium);
		
		$query = "INSERT INTO cbuildings(Type,PlayerID,PlanetID,Grid,TTF) VALUES('$BuildingType','".GetPlayerIDFromName($username)."','$PlanetID','$Grid','$turns')";
		$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
		return mysqli_insert_id($GLOBALS["conn"]);
	}else{
		return 0;
	}
}
function CalculateBuildTime($PlanetID,$Type){
	$query = "SELECT Turns FROM building_types WHERE(Type = '$Type')";
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

function HasShipyard($PlanetID){
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND PlayerID = '".GetPlayerIDFromName($username)."' AND Type = '4')";
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

function GetGridSquares($Size){
	$sql= "SELECT Grids FROM planet_types WHERE(Type = '$Size')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	return $row ? $row->Grids : 0;
}

function GridSquares($PlanetID){
	$sql= "SELECT Size FROM planets WHERE(PlanetID = '$PlanetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($rescount);
	if(!$row) return "0/0";
	$squares = GetGridSquares($row->Size);
	
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	return $total_count."/".$squares;
}

function Constructions($PlanetID){
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM cbuildings WHERE(PlanetID = '$PlanetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	return $total_count;
}

function HasFleets($PlanetID){
	if(YourFleetsInOrbit($PlanetID)>0){
		return true;
	}
	return false;
}

function EnemyOwned($PlanetID){
	global $username;
	$query = "SELECT PlayerID FROM planets WHERE(PlanetID = '$PlanetID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return false;
	if($row->PlayerID==0){
		return false;
	}
	if(PlayerTeam($row->PlayerID)!=PlayerTeam(GetPlayerIDFromName($username))){
		return true;
	}
	return false;
}

function GetGridContents($PlanetID,$Grid){
	$type = 0;
	$query = "SELECT Type FROM buildings WHERE(PlanetID = '$PlanetID' AND GridSquare='$Grid')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if($row) $type = $row->Type;
	return $type;
}
function GetGridContentString($Type){
	$query = "SELECT Name FROM building_types WHERE(Type = '$Type')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if($row && $row->Name!=""){
		return $row->Name;
	}
	return "Nothing";
}

function GetBldColour($Type){
	$query = "SELECT Colour FROM building_types WHERE(Type = '$Type')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row ? $row->Colour : "128,128,128";
}

function GetOrbitalGridCoords($Size){
$grids = array();
switch($Size){
	case "1":
		$offsetx = 0;
		$offsety = 30;
		break;
	case "2":
		$offsetx = 0;
		$offsety = 80;
		break;
}
//LHS Orbits
$x1 = 20;
$y1 = $offsety+40;
$grids[0] = ($x1).",".($y1).",".($x1+40).",".($y1+40);

$x1 = 10;
$y1 = $offsety+120;
$grids[1] = ($x1).",".($y1).",".($x1+40).",".($y1+40);

$x1 = 5;
$y1 = $offsety+200;
$grids[2] = ($x1).",".($y1).",".($x1+40).",".($y1+40);
$x1 = 10;
$y1 = $offsety+280;
$grids[3] = ($x1).",".($y1).",".($x1+40).",".($y1+40);
$x1 = 20;
$y1 = $offsety+360;
$grids[4] = ($x1).",".($y1).",".($x1+40).",".($y1+40);

// RHS Orbits
$x1 = 540;
$y1 = $offsety+40;
$grids[5] = ($x1).",".($y1).",".($x1+40).",".($y1+40);
$x1 = 550;
$y1 = $offsety+120;
$grids[6] = ($x1).",".($y1).",".($x1+40).",".($y1+40);
$x1 = 555;
$y1 = $offsety+200;
$grids[7] = ($x1).",".($y1).",".($x1+40).",".($y1+40);
$x1 = 550;
$y1 = $offsety+280;
$grids[8] = ($x1).",".($y1).",".($x1+40).",".($y1+40);
$x1 = 540;
$y1 = $offsety+360;
$grids[9] = ($x1).",".($y1).",".($x1+40).",".($y1+40);

return $grids;
}

function GetPlanetIncome($PlanetID){
	$income = new ResourceBundle();
	$query = "SELECT Size FROM planets WHERE(PlanetID = '$PlanetID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return $income;
	$query = "SELECT income FROM planet_types WHERE(Type = '".$row->Size."')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return $income;
	$base = explode(":",$row->income);
	$income->Add($base[0],1);
	$income->Add($base[1],2);
	$income->Add($base[2],3);
	
	// Harvesters add 5% more to the base income.
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND Type = 3)";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	if($total_count>0){
		//echo "Percenting $total_count harvesters<br/>";
		$harvesterBonus = (float)GetGameSetting('harvester_bonus', 0.05);
		$percentage = 1+($total_count*$harvesterBonus);
		$income->Percentage($percentage,0);
	}
	
	return $income;
}

function IsHomeWorldPlanet($PlanetID){
	$sql = "SELECT PlayerID FROM planets WHERE PlanetID='" . (int)$PlanetID . "'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row || $row->PlayerID == 0) return false;
	return IsHomePlanet((int)$row->PlayerID, (int)$PlanetID);
}

function GetEffectiveBuildingHP($HP, $PlanetID){
	// Home world buildings get HP multiplier
	if(IsHomeWorldPlanet($PlanetID)){
		$mult = (float)GetGameSetting('home_hp_multiplier', 1.5);
		return (int)round($HP * $mult);
	}
	return (int)$HP;
}

function GetPlanetDefenceStrength($PlanetID){
	$strength = 0;
	$isHome = IsHomeWorldPlanet($PlanetID);
	$hpMult = (float)GetGameSetting('home_hp_multiplier', 1.5);
	$sql= "SELECT HP FROM buildings WHERE(PlanetID = '$PlanetID' AND (Type = 6 OR Type = '8'))";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		$hp = $isHome ? (int)round($row->HP * $hpMult) : (int)$row->HP;
		$strength += $hp;
	}
	return $strength;
}

function GetPlanetAttackStrength($PlanetID){
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND Type = 7)";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	$strength = $total_count * 2000;
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND Type = 9)";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	$strength += $total_count * 6000; // 2000 HP per shield
	
	return $strength;
}

function GetRandomShield($PlanetID){
	$shields = array();
	$sql= "SELECT * FROM buildings WHERE(PlanetID = '$PlanetID' AND (Type = '6' OR Type = 8))";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		$shields[] = $row;
	}
	$rand = rand(0,sizeof($shields)-1);
	$shield = $shields[$rand];
	return $shield->BuildingID;
}

function GetRandomWeapon($PlanetID){
	$shields = array();
	$sql= "SELECT * FROM buildings WHERE(PlanetID = '$PlanetID' AND (Type = '7' OR Type = '9'))";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		$shields[] = $row;
	}
	$rand = rand(0,sizeof($shields)-1);
	$shield = $shields[$rand];
	//echo "WeaponID: ".$shield->BuildingID;
	return $shield->BuildingID;
}

function HasDefencesLeft($PlanetID){
	if(HasShields($PlanetID)||HasWeapons($PlanetID)){
		return true;
	}
	return false;
}

function HasShields($PlanetID){
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND (Type = 6 OR Type = 8))";
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

function CountShields($PlanetID){
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND (Type = 6 OR Type = 8))";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	return $total_count;
}

function HasWeapons($PlanetID){
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND (Type = 7 OR Type = 9))";
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

function CountBuildingsofType($PlanetID,$Type){
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND Type = '$Type')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	return $total_count;
}

function IdleShipyards($PlanetID){
	$yards = 0;
	$tyards = 0;
	$sql= "SELECT GridSquare FROM buildings WHERE(PlanetID = '$PlanetID' AND Type = '4')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		$tyards++;
		$sql= "SELECT COUNT(*) AS count FROM cships WHERE(Yard = '$PlanetID:".$row->GridSquare."')";
		$res=mysqli_query($GLOBALS["conn"], $sql);
		if ($res){
			if ($rowcount = mysqli_fetch_object($res)){
				$total_count = $rowcount->count;
			}
		}
		if($total_count>0)
			$yards++;
	}
	return $tyards - $yards;
}

function CountWeapons($PlanetID){
	$total_count = 0;
	$sql= "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND (Type = 7 OR Type = 9))";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount){
		if ($rowcount = mysqli_fetch_object($rescount)){
			$total_count = $rowcount->count;
		}
	}
	return $total_count;
}

function ClaimUnfinishedShips($PlanetID,$PlayerID){
	$sql = "UPDATE cships SET PlayerID = '$PlayerID' WHERE(PlanetID = '$PlanetID')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
}

class Weapon{
	var $HP;
	var $Type;
}

function GetWeapons($PlanetID){
	//echo "Getting Weapons<br/>";
	$weapons = array();
	$sql= "SELECT * FROM buildings WHERE(PlanetID = '$PlanetID' AND (Type = 7 OR Type = 9))";
	$rescount=mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
	//echo "Getting Weapons 2<br/>";
	while($row = mysqli_fetch_object($rescount)){
	//	echo "Getting Weapon<br/>";
		$weapon = new Weapon();
		$weapon->HP = GetBldDefaultAP($row->Type);
		$weapon->Type = $row->Type;
		$weapons[] = $weapon;
	}
	return $weapons;
}
?>