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
		'http://neo.mlodzi.pl' => 'moja strona',
		'http://w3.org' => 'pewna organizacja',
		'http://forumweb.pl?strona testowa' => 'ulubione forum'
	);
	
	$xt->view->title = 'test';
	
	function escape($a){
		return str_replace(' ', '-modyfikator-działa-', $a);
	}
	$xt->modifier('a', 'href', escape);
	
	$xt->view->main_title = 'Super strona';
	
	$xt->display(1);
}catch(xtException $e){
	echo $e;
}
?>