<?php

include('../trunk/xt.class.php');

$szablon='petla.html';
$t=new xt($szablon);

$t->add('li', array(
	array('a'=>'test0'),
	array('a'=>array('#text'=>'test1', 'href'=>'jakis-plik1.html')),
	array('a'=>array('#text'=>'test2', 'href'=>'jakis-plik2.html')),
	array('a'=>array('#text'=>'test3', 'href'=>'jakis-plik3.html')),
	array('a'=>array('#text'=>'test4', 'href'=>'jakis-plik4.html')),
	array('a'=>array('#text'=>'test5', 'href'=>'jakis-plik5.html')),
	array('a'=>array('#text'=>'test6', 'href'=>'jakis-plik6.html')),
	array('a'=>array('#text'=>'test7', 'href'=>'jakis-plik7.html')),
	array('a'=>array('#text'=>'test8', 'href'=>'jakis-plik8.html')),
	array('a'=>array('#text'=>'test9', 'href'=>'jakis-plik9.html')),
	array('a'=>array('#text'=>'test10', 'href'=>'jakis-plik10.html')),
	array('a'=>array('#text'=>'test11', 'href'=>'jakis-plik11.html'))
));

$t->display(1);

?>
