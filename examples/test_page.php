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
	
	date_default_timezone_set('Europe/Warsaw');
	
	$xt->add('#a', date('U'));
	
	$xt->add('#b', mt_rand(0,100));
	
	$xt->add('#c', 'test');
	
	$xt->display(1);
	
	
}catch(xtException $e){
	echo $e;
}
?>
