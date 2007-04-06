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

date_default_timezone_set('Europe/Warsaw');

include('../xt.class.php');

try{
	$xt=new xt('szablon.html');
	
	
	
	$xt->add('#a', 'test');
		
	$xt->add('title', 'test');
	
	$xt->set('#a', array('style'=>'color:#'.dechex(mt_rand(0,255)).dechex(mt_rand(0,255)).dechex(mt_rand(0,255))));
	
	
	$xt->add('html * *', mt_rand(0,100));
	
	$xt->display(1);
}catch(xtException $e){
	echo $e;
}
?>
