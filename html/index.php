<?php
// Redirect to installer if not yet installed
if(!file_exists(__DIR__ . "/.installed")){ header("Location: install.php"); exit; }
include_once("session.inc.php");
session_start();
include_once("userfunctions.inc.php");
if(!empty($_SESSION['username'])){
	header("Location: home.php");
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Emissary</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<div class="wrapper">
<div class="title"><img src="images/title.jpg" width="900" height="179" /></div>
<?php PrintMessage($_GET["msg"] ?? "");?>
<div class="login"> 
  <form name="form1" method="post" action="login.back.php">
    <p>UserName: <br>
      <input name="login_username" type="text" id="login_username" size="12" maxlength="12">
    </p>
    <p>Password:<br>
      <input name="login_password" type="password" id="login_password" size="15">
    </p>
    <p>
    <input type="submit" name="Submit" value="Login">
  </p>
</form><p><small>At the moment the game is in the beta stage of development and is open to invite only.</small></p>
</div>
<div class="description"> 
  <p>Emissary is an online multiplayer game. Built on ideas taken from other online 
    games as well as classic PC games.</p>
  <p>You start with one planet in one system. From there you must harvest your 
    planet's resources in order to spread your race across the system, then the 
    sector and maybe even the galazy?</p>
  <p>Along the way you will meet other races controlled by other players. Some 
    may be friendly, some may want your resources for their own.</p>
  <p>Team together with other players to form republics or empires that can bring 
    order or chaos to the galaxy. The choice is yours.</p>
  <p>The Universe of Emissary is enormous. You must have your wits about you in 
    order to control all the sectors, systems and planets in your domain. Use 
    your alliances to bring not only profit, but also greater power. You can call 
    on allied fleets to defend your systems, help your own fleets attack targets. 
    Two heads are always better than one, why not several star-systems?</p>
  <p>Emissary not only brings the scale of Planitarion style games, with the playability 
    and teamwork of Kings of Chaos, but also the level of detailed control that 
    before only PC games could give you. You can control what each planet does. 
    What you put in orbit, what you build on the surface. One planet can be a 
    haven for scientific research, another might be industrial and aid construction 
    of you massive star-fleet. The possibilities are endless.</p>
  <p>Gameplay is on a per-turn basis. One turn is one hour. Turn-based events 
    include:</p>
  <p>The collection of resources,<br>
    The advance of a fleet by one sector grid,</p>
  <p>The construction of buildings, stations and ships are on a turn basis. Small 
    ships might be finished in one turn. Motherships can take much longer. You 
    can increase your per-turn construction by building more shipyards on more 
    planets. Advanced shipyards can have up to 5 ships under construction at any 
    one time.</p>
  <p>Exploration is pivotal when starting out in the game. Scout ships and long 
    range sensors can seek out new systems in your sector. As you improve your 
    technology, your scout ships can venture out of your sector and open up more 
    worlds to colonise or conquer.</p>
</div>
<div style="clear: both;">&nbsp;</div>
</div>

</body>
</html>
