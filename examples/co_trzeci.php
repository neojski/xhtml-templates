<?php

include('../trunk/xt.class.php');

$szablon='co_trzeci.html';
$t=new xt($szablon);

foreach($t->getNode('ol > li:nth-child(3n)') as $node){
	$t->add($node, array('class'=>'nieparzysty', '#text'=>' (numer tego elementu jest podzielny przez 3)'));
}

$t->css->add('.nieparzysty{background:#e6f5de;}');

$t->display(1);

?>
