<?php
$echo = "booboo";
$fp = fopen("4.txt","w") or die("booboo");
fwrite($fp,$echo,strlen($echo));
fclose($fp);
?>