<?php
/**
	wszystkie pliki powinny tak wyglądać:
	<ol>
		<li>zainkludowanie klasy <code>xt.class.php</code></li>
		<li>otwarcie bloku <code>try</code></li>
		<li>cały kod</li>
		<li>blok <code>catch</code></li>
	</ol>
*/

include('../xt.class.php');

try{
	$xt=new xt('szablon.html');
	
	$xt->add('#a', 'test');
	
	$xt->display(3);
	
	
}catch(xtException $e){
	echo $e;
}
?>
