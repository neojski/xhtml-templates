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

include('../trunk/xt.class.php');

try{
	$xt=new xt('szablon.html');
	
	$xt->replaceParent('#test', 'em', array('style'=>'color:red'));
	
	$xt->css->set('body > *~*','color:blue');
	
	$xt->display(1);
	
	
}catch(xtException $e){
	echo $e;
}
?>
