<?php

include('../trunk/xt.class.php');

$szablon='zewnetrzne.html';
$t=new xt($szablon);

foreach($t->getNode('a[href^="http://"]') as $node){;
	$t->add($node, array('rel'=>'external', '#text'=>'(link zewnętrzny)', 'class'=>'zewnetrzny'));
}

$t->css->add('a{color:black; display:block; padding:20px} .zewnetrzny{border: 1px solid #31B229}');

$t->display(1);

?>
