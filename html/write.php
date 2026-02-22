<?php
$echo = "booboo";
$fp = fopen(__DIR__ . "/userdata/battles/4.txt","w") or die("booboo");
fwrite($fp,$echo,strlen($echo));
fclose($fp);
?>