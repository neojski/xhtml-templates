<?php
include('../trunk/xt.class.php');

try{
	$xt=new xt('formularz-z-pamiecia.html');
	
	$xt->html->form->memory('form'); // wszystkie formularze
	
	#$xt->html->form->memory('form[method="post"]'); // tylko post
	
	$xt->display(1);
	
	
}catch(xtException $e){
	echo $e;
}
?>