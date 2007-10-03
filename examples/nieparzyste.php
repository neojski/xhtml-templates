<?php

include('../trunk/xt.class.php');

$szablon='nieparzyste.html';
$t=new xt($szablon);

foreach($t->getNode('ol > li:nth-child(2n+1)') as $node){
	$t->add($node, array('class'=>'nieparzysty'));
}

$t->css->add('.nieparzysty{background:#e6f5de;}');

$t->display(1);

?>
