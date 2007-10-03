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


	function oko($a){
	
		return $a.'tedst';
	}


try{
	$xt=new xt('szablon.html');
	//$xt->set('div:first-of-type', array('#text'=>'|dodawany-tekst|', 'oko'=>'opa'));
	//$xt->removeNS('xt', 1);
	
	
	class petla extends xt_loop{
		public function __construct($li, $href, $nazwa){
			$this->add('li', $li);
			$this->add('li>a', array('href'=>$href, '#text'=>$nazwa));
		}
	}
	
	$tablica=array();
	$tablica[]=new petla('test', 'http://neo.mlodzi.pl', 'mój ulubiony serwis');
	$tablica[]=new petla('test2', 'oniet.pl', 'nie, nie to');
	$tablica[]=new petla('test3', 'w3.org', 'taki link');
	
	$xt->add('ul#menu>li', $tablica);
	
	$xt->html->title('xxx', 1);
	
	$xt->display(1);
}catch(xtException $e){
	echo $e;
}
?>