<?php /*
Cache szablonu systemu xt. Zbudowano Thu, 05 Apr 2007 20:55:39 CEST
*/ ?><?xml version="1.0" encoding="utf-8" standalone="no"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
  <title>testujemy<?php echo $this->cache->values['object2']['string']; ?></title>
  <meta http-equiv="content-type" content="text/html;encoding=utf-8" />
  </head>
  <body>
  <ul id="lista">
  	<li>podpunkt</li>
  </ul>
  <div id="a">id a<?php echo $this->cache->values['object1']['string']; ?></div>
  <div id="b">id b</div>
  <div id="c">id c</div>
  <div id="d">id d</div>
  
  <strong id="test">aaa</strong>
  </body>
</html>
