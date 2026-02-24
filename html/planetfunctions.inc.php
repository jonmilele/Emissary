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
		return $planetID;
	}
	
	return 0;
}

function Build($PlanetID,$BuildingType,$Grid){
	global $username;

	// Check construction queue limit
	if(!HasFreeConstructionSlot($PlanetID)){
		return -1; // queue full
	}

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
	global $username;
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

function GetConstructionSlots($PlanetID){
	// Base slots from planet type
	$base = 1;
	$sql = "SELECT pt.construction_slots FROM planets p JOIN planet_types pt ON p.Size = pt.Type WHERE p.PlanetID = '$PlanetID'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if($res){
		$row = mysqli_fetch_object($res);
		if($row) $base = (int)$row->construction_slots;
	}
	// Each factory adds one extra construction slot
	$factories = 0;
	$sql = "SELECT COUNT(*) AS count FROM buildings WHERE(PlanetID = '$PlanetID' AND Type = 1)";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if($res){
		$row = mysqli_fetch_object($res);
		if($row) $factories = (int)$row->count;
	}
	return $base + $factories;
}

function HasFreeConstructionSlot($PlanetID){
	return Constructions($PlanetID) < GetConstructionSlots($PlanetID);
}

function GetConstructionSlotsTooltip($PlanetID){
	$sizeLabels = [1=>'Small',2=>'Medium',3=>'Large',4=>'Huge'];
	$base = 1;
	$sizeName = '?';
	$sql = "SELECT p.Size, pt.construction_slots FROM planets p JOIN planet_types pt ON p.Size = pt.Type WHERE p.PlanetID = '$PlanetID'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if($res){
		$row = mysqli_fetch_object($res);
		if($row){ $base = (int)$row->construction_slots; $sizeName = $sizeLabels[(int)$row->Size] ?? 'Type '.$row->Size; }
	}
	$factories = 0;
	$sql = "SELECT COUNT(*) AS cnt FROM buildings WHERE PlanetID = '$PlanetID' AND Type = 1";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if($res){ $row = mysqli_fetch_object($res); if($row) $factories = (int)$row->cnt; }
	$total = $base + $factories;
	$tip = $sizeName . ' base: ' . $base;
	if($factories > 0) $tip .= ' + ' . $factories . ' Factor' . ($factories != 1 ? 'ies' : 'y') . ': +' . $factories;
	$tip .= ' = ' . $total . ' slot' . ($total != 1 ? 's' : '');
	return $tip;
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
// Planet sizes: 1=Small, 2=Medium, 3=Large, 4=Huge
switch($Size){
	case "1": // Small
		$offsetx = 0;
		$offsety = 30;
		break;
	case "2": // Medium
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

	// Resource Rich planet boon: boost base income before other modifiers
	if(HasPlanetBoon($PlanetID, 1)){
		$rrBonus = 1 + (float)GetGameSetting('pboon_resource_rich_bonus', 0.20);
		$income->Percentage($rrBonus, 0);
	}
	
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
		$harvesterBonus = (float)GetGameSetting('harvester_bonus', 0.05);
		// Resource boon: harvesters on resource boon grids get extra bonus
		$_boonHarvCount = 0;
		$boonResBonus = (float)GetGameSetting('boon_resource_bonus', 0.10);
		$_bq = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS cnt FROM buildings b INNER JOIN planet_grid_boons pgb ON pgb.PlanetID = b.PlanetID AND pgb.Grid = b.GridSquare WHERE b.PlanetID='$PlanetID' AND b.Type=3 AND pgb.BoonType=1");
		$_br = mysqli_fetch_object($_bq);
		if($_br) $_boonHarvCount = (int)$_br->cnt;
		$percentage = 1 + ($total_count * $harvesterBonus) + ($_boonHarvCount * $boonResBonus);
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

function GetEffectiveBuildingHP($HP, $PlanetID, $GridSquare = 0, $BuildingType = 0){
	$hp = (int)$HP;
	// Home world buildings get HP multiplier
	if(IsHomeWorldPlanet($PlanetID)){
		$mult = (float)GetGameSetting('home_hp_multiplier', 1.5);
		$hp = (int)round($hp * $mult);
	}
	// Energy grid boon: shields/weapons get HP bonus
	if($GridSquare > 0 && GetGridBoon($PlanetID, $GridSquare) == 3){
		$energyBonus = (float)GetGameSetting('boon_energy_hp_bonus', 0.25);
		$hp = (int)round($hp * (1 + $energyBonus));
	}
	// Geothermal planet boon: shields/weapons get HP bonus
	if(in_array((int)$BuildingType, [6, 7, 8, 9]) && HasPlanetBoon($PlanetID, 2)){
		$geoBonus = (float)GetGameSetting('pboon_geothermal_bonus', 0.50);
		$hp = (int)round($hp * (1 + $geoBonus));
	}
	return $hp;
}

function GetPlanetDefenceStrength($PlanetID){
	$strength = 0;
	$isHome = IsHomeWorldPlanet($PlanetID);
	$hpMult = (float)GetGameSetting('home_hp_multiplier', 1.5);
	$energyHpBonus = (float)GetGameSetting('boon_energy_hp_bonus', 0.25);
	$hasGeo = HasPlanetBoon($PlanetID, 2);
	$geoBonus = $hasGeo ? (float)GetGameSetting('pboon_geothermal_bonus', 0.50) : 0;
	$sql= "SELECT HP, GridSquare FROM buildings WHERE(PlanetID = '$PlanetID' AND (Type = 6 OR Type = '8'))";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		$hp = $isHome ? (int)round($row->HP * $hpMult) : (int)$row->HP;
		if(GetGridBoon($PlanetID, $row->GridSquare) == 3){
			$hp = (int)round($hp * (1 + $energyHpBonus));
		}
		if($hasGeo) $hp = (int)round($hp * (1 + $geoBonus));
		$strength += $hp;
	}
	return $strength;
}

function GetPlanetAttackStrength($PlanetID){
	$strength = 0;
	$energyApBonus = (float)GetGameSetting('boon_energy_ap_bonus', 0.25);
	$hasGeo = HasPlanetBoon($PlanetID, 2);
	$geoBonus = $hasGeo ? (float)GetGameSetting('pboon_geothermal_bonus', 0.50) : 0;
	$sql = "SELECT b.GridSquare, bt.AP FROM buildings b JOIN building_types bt ON b.Type = bt.Type WHERE b.PlanetID = '$PlanetID' AND (b.Type = 7 OR b.Type = 9)";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$ap = (int)$row->AP;
		if(GetGridBoon($PlanetID, $row->GridSquare) == 3){
			$ap = (int)round($ap * (1 + $energyApBonus));
		}
		if($hasGeo) $ap = (int)round($ap * (1 + $geoBonus));
		$strength += $ap;
	}
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

function GetPlanetValueBreakdown($PlanetID){
	$pid = (int)$PlanetID;
	$bd = ['land' => 0, 'buildings' => 0, 'income' => 0, 'military' => 0, 'total' => 0];

	// Resource credit conversion rates from game settings
	$mcv = (int)GetGameSetting('metal_credit_value', 1);
	$ncv = (int)GetGameSetting('mineral_credit_value', 10);
	$acv = (int)GetGameSetting('astrium_credit_value', 100);

	// 1. Land value: grid squares * 10C
	$sql = "SELECT Size FROM planets WHERE PlanetID='$pid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return $bd;
	$grids = GetGridSquares($row->Size);
	$bd['land'] = $grids * 10;

	// 2. Building value: valuation rate of build cost (converted to credits)
	$valuationRate = (float)GetGameSetting('building_valuation_rate', 0.7);
	$sql = "SELECT b.HP AS CurrentHP, bt.Metal, bt.Mineral, bt.Astrium, bt.HP AS MaxHP, bt.AP
	        FROM buildings b JOIN building_types bt ON b.Type = bt.Type
	        WHERE b.PlanetID='$pid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$totalHP = 0;
	$totalAP = 0;
	while($bld = mysqli_fetch_object($res)){
		$buildCost = ((int)$bld->Metal * $mcv) + ((int)$bld->Mineral * $ncv) + ((int)$bld->Astrium * $acv);
		$hpRatio = ((int)$bld->MaxHP > 0) ? (int)$bld->CurrentHP / (int)$bld->MaxHP : 1;
		$bd['buildings'] += (int)round($buildCost * $valuationRate * $hpRatio);
		$totalHP += (int)$bld->CurrentHP;
		$totalAP += (int)$bld->AP;
	}

	// 3. Income value: per-turn income in credits * 10 (represents 10 turns)
	$income = GetPlanetIncome($pid);
	$incomeCredits = ((int)$income->Metal * $mcv) + ((int)$income->Mineral * $ncv) + ((int)$income->Astrium * $acv);
	$bd['income'] = $incomeCredits * 10;

	// 4. Military value: HP/10 + AP/10
	$bd['military'] = (int)round($totalHP / 10) + (int)round($totalAP / 10);

	$bd['total'] = max(1, $bd['land'] + $bd['buildings'] + $bd['income'] + $bd['military']);
	return $bd;
}

function GetPlanetValue($PlanetID){
	$bd = GetPlanetValueBreakdown($PlanetID);
	return $bd['total'];
}

function PlanetValueTooltip($PlanetID){
	$bd = GetPlanetValueBreakdown($PlanetID);
	return 'Land: ' . number_format($bd['land']) . 'C | '
	     . 'Buildings: ' . number_format($bd['buildings']) . 'C | '
	     . 'Income (10t): ' . number_format($bd['income']) . 'C | '
	     . 'Military: ' . number_format($bd['military']) . 'C';
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
	while($row = mysqli_fetch_object($rescount)){
		$weapon = new Weapon();
		$baseAP = GetBldDefaultAP($row->Type);
		// Energy grid boon: boost AP
		if(GetGridBoon($PlanetID, $row->GridSquare) == 3){
			$apBonus = (float)GetGameSetting('boon_energy_ap_bonus', 0.25);
			$baseAP = (int)round($baseAP * (1 + $apBonus));
		}
		// Geothermal planet boon: boost AP
		if(HasPlanetBoon($PlanetID, 2)){
			$geoApBonus = (float)GetGameSetting('pboon_geothermal_bonus', 0.50);
			$baseAP = (int)round($baseAP * (1 + $geoApBonus));
		}
		$weapon->HP = $baseAP;
		$weapon->Type = $row->Type;
		$weapons[] = $weapon;
	}
	return $weapons;
}

// ==========================================
// Planet Grid Boons
// ==========================================
// Boon types: 1=Resource (Red), 2=Research (Blue), 3=Energy (Yellow)

function GetBoonColour($BoonType){
	switch((int)$BoonType){
		case 1: return '50,255,50';    // Resource - Green
		case 2: return '50,100,255';   // Research - Blue
		case 3: return '255,255,50';   // Energy - Yellow
		default: return '128,128,128';
	}
}

function GetBoonName($BoonType){
	switch((int)$BoonType){
		case 1: return 'Resource';
		case 2: return 'Research';
		case 3: return 'Energy';
		default: return 'Unknown';
	}
}

function GetGridBoon($PlanetID, $Grid){
	$pid = (int)$PlanetID;
	$grid = (int)$Grid;
	$sql = "SELECT BoonType FROM planet_grid_boons WHERE PlanetID='$pid' AND Grid='$grid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	return $row ? (int)$row->BoonType : 0;
}

function GetPlanetGridBoons($PlanetID){
	$pid = (int)$PlanetID;
	$boons = [];
	$sql = "SELECT Grid, BoonType FROM planet_grid_boons WHERE PlanetID='$pid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if($res){
		while($row = mysqli_fetch_object($res)){
			$boons[(int)$row->Grid] = (int)$row->BoonType;
		}
	}
	return $boons;
}

function AssignPlanetGridBoons($PlanetID){
	$pid = (int)$PlanetID;
	// Get grid count from planet size
	$sql = "SELECT Size FROM planets WHERE PlanetID='$pid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return 0;
	$grids = GetGridSquares($row->Size);
	if($grids < 1) return 0;

	// Boon Planet override: use higher ratio
	if(HasPlanetBoon($PlanetID, 5)){
		$bpMin = (float)GetGameSetting('pboon_boon_planet_min', 0.30);
		$bpMax = (float)GetGameSetting('pboon_boon_planet_max', 0.40);
		$maxRatio = $bpMin + (mt_rand() / mt_getrandmax()) * ($bpMax - $bpMin);
	} else {
		$maxRatio = (float)GetGameSetting('boon_max_ratio', 0.15);
	}
	$maxBoons = (int)floor($grids * $maxRatio);
	if($maxBoons < 1) return 0;
	$numBoons = rand(0, $maxBoons);
	if($numBoons < 1) return 0;

	// Pick random grid numbers (1-based)
	$allGrids = range(1, $grids);
	shuffle($allGrids);
	$chosen = array_slice($allGrids, 0, $numBoons);

	$inserted = 0;
	foreach($chosen as $grid){
		$boonType = rand(1, 3);
		$sql = "INSERT IGNORE INTO planet_grid_boons (PlanetID, Grid, BoonType) VALUES ('$pid', '$grid', '$boonType')";
		mysqli_query($GLOBALS["conn"], $sql);
		$inserted++;
	}
	return $inserted;
}

// ==========================================
// Planet-wide Boons
// ==========================================
// Types: 1=Resource Rich, 2=Geothermal Energy, 3=Gravity Well, 4=Rough Terrain, 5=Boon Planet

function GetPlanetBoonName($BoonType){
	switch((int)$BoonType){
		case 1: return 'Resource Rich';
		case 2: return 'Geothermal Energy';
		case 3: return 'Gravity Well';
		case 4: return 'Rough Terrain';
		case 5: return 'Boon Planet';
		default: return 'Unknown';
	}
}

function GetPlanetBoonColour($BoonType){
	switch((int)$BoonType){
		case 1: return '#FF9900'; // Resource Rich - Orange
		case 2: return '#FFFF00'; // Geothermal - Yellow
		case 3: return '#AA44FF'; // Gravity Well - Purple
		case 4: return '#996633'; // Rough Terrain - Brown
		case 5: return '#00FFFF'; // Boon Planet - Cyan
		default: return '#888888';
	}
}

function GetPlanetBoonDesc($BoonType){
	switch((int)$BoonType){
		case 1: return '+' . round((float)GetGameSetting('pboon_resource_rich_bonus', 0.20) * 100) . '% base resource income';
		case 2: return '+' . round((float)GetGameSetting('pboon_geothermal_bonus', 0.50) * 100) . '% shield/weapon HP and AP';
		case 3: return '+' . round((float)GetGameSetting('pboon_gravity_well_bonus', 0.30) * 100) . '% orbiting ship HP and AP (future)';
		case 4: return '+' . round((float)GetGameSetting('pboon_rough_terrain_bonus', 0.30) * 100) . '% defending army HP (future)';
		case 5: return 'Higher grid boon placement chance';
		default: return '';
	}
}

function HasPlanetBoon($PlanetID, $BoonType){
	$pid = (int)$PlanetID;
	$bt = (int)$BoonType;
	$sql = "SELECT 1 FROM planet_boons WHERE PlanetID='$pid' AND BoonType='$bt' LIMIT 1";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	return $res && mysqli_num_rows($res) > 0;
}

function GetPlanetBoons($PlanetID){
	$pid = (int)$PlanetID;
	$boons = [];
	$sql = "SELECT BoonType FROM planet_boons WHERE PlanetID='$pid' ORDER BY BoonType";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if($res){
		while($row = mysqli_fetch_object($res)){
			$boons[] = (int)$row->BoonType;
		}
	}
	return $boons;
}

function AssignPlanetBoons($PlanetID){
	$pid = (int)$PlanetID;
	$assigned = [];
	// Each boon type: roll independently based on rarity (1-in-N chance)
	$boonDefs = [
		1 => 'pboon_resource_rich_rarity',
		2 => 'pboon_geothermal_rarity',
		3 => 'pboon_gravity_well_rarity',
		4 => 'pboon_rough_terrain_rarity',
		5 => 'pboon_boon_planet_rarity',
	];
	foreach($boonDefs as $type => $settingKey){
		$rarity = max(1, (int)GetGameSetting($settingKey, 10));
		if(rand(1, $rarity) == 1){
			mysqli_query($GLOBALS["conn"], "INSERT IGNORE INTO planet_boons (PlanetID, BoonType) VALUES ('$pid', '$type')");
			$assigned[] = $type;
		}
	}
	return $assigned;
}
?>
