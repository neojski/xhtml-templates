<?php
/**
	all files should look like this:
	<ol>
		<li>include of class <code>xt.class.php</code></li>
		<li><code>try</code> block</li>
		<li>other code</li>
		<li><code>catch</code> block</li>
	</ol>
*/

include('../trunk/xt.class.php');

try{
	$xt=new xt('szablon.html');
	
	$xt->view->array = array(
		'http://neo.mlodzi.pl' => 'pierwszy link menu',
		'http://w3.org' => 'drugi link menu',
		'http://forumweb.pl?strona testowa' => 'trzeci link menu',
		'http://forumweb.pl' => 'czwarty link menu',
		'http://google.com' => 'piąty link menu'
	);
	
	$xt->view->title = 'przykładowa strona';
	
	$xt->view->main_title = 'Moja super strona';
	
	/*function escape($a){
		return str_replace(' ', '-', $a);
	}
	$xt->modifier('a', 'href', escape);*/
	
	$xt->display(1);
}catch(xtException $e){
	echo $e;
}
?>