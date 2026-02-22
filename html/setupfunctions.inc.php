<?php
function StageTwo($username){
	$sqlpos = "SELECT SetupStage FROM players WHERE(UserName = '$username')";
	$respos = mysqli_query($GLOBALS["conn"], $sqlpos);
	$row = mysqli_fetch_object($respos);
	
	if($row->SetupStage == "1") // Not exists
	{
		return true;
	}else
	{
		return false;
	}
}
?>