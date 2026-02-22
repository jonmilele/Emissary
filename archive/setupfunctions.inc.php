<?php
function StageTwo($username){
	$sqlpos = "SELECT SetupStage FROM players WHERE(UserName = '$username')";
	$respos = mysql_query($sqlpos);
	$row = mysql_fetch_object($respos);
	
	if($row->SetupStage == "1") // Not exists
	{
		return true;
	}else
	{
		return false;
	}
}
?>