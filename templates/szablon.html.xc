<?php /*
Cache szablonu systemu xt. Zbudowano Sat, 24 Mar 2007 12:53:23 CET
*/ ?><?xml version="1.0" encoding="utf-8" standalone="no"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
  <title>testujemy<?php echo $this->cache->values['obiekt4'];?></title>
  <meta http-equiv="content-type" content="text/html;encoding=utf-8" />
  <?php echo $this->cache->values['obiekt3'];?></head>
  <body>
  <ul id="lista">
  	<li>podpunkt</li>
  </ul>
  <div id="a">id a<?php echo $this->cache->values['obiekt1'];?></div>
  <div id="b">id b</div>
  <div id="c">id c</div>
  <div id="d">id d</div>

<ul>
<?php
$array=range(0, 2000);

for($i=0; $i<2000; $i++){
?>
<li><?php echo $array[$i]; ?></li>
<?php
}
?>
</ul>
  
  <strong id="test">aaa</strong>
  <?php echo $this->cache->values['obiekt2'];?></body>
</html>
