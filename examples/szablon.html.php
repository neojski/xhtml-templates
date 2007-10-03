<?php

/*
	$this oznacza obiekt xt
*/

if(!$this){
	die();
}

class petla extends xt_loop{
	public function __construct($href, $nazwa){
		$this->set('li>a', array('href'=>$href, '#text'=>$nazwa));
	}
}

$tablica=array();

foreach($this->view->array as $href => $name){
	$tablica[]=new petla($href, $name);
}

$this->xml->add('ul#menu > li', $tablica);

$this->node('h1') -> add($this->view->main_title);

$this->html->title($this->view->title, 1);

?>