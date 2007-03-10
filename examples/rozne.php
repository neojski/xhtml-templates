<?php

include('../trunk/xt.class.php');

$szablon='rozne.html';
$t=new xt($szablon);

$menu=array(
	array('a' => array('href'=>'link', '#text'=>'opis')),
	array('a' => array('href'=>'link', '#text'=>'opis')),
	array('a' => array('href'=>'link', '#text'=>'opis')),
	array('a' => array('href'=>'link', '#text'=>'opis'))
);

$t->add('ul#menu > li', $menu);

$t->add('h1:nth-of-type(1)', 'Tytuł');


$petla=array(
	array(
		'h2' => 'podtytuł 1',
		'p#data'=>'2007.01.30',
		'#tresc'=>'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Vivamus in dui. Duis quam metus, aliquam sit amet, convallis eu, vulputate et, libero. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Fusce volutpat nunc vulputate ipsum.'
	),
	
	array(
		'h2' => 'podtytuł 2',
		'p#data'=>'2007.01.29',
		'#tresc'=>'n justo magna, feugiat vitae, gravida vitae, egestas vitae, tellus. Aliquam a diam in tellus consectetuer lacinia. Nam urna nibh, sagittis vel, tempus sed, congue in, nulla. Curabitur nisl leo, vestibulum in, dapibus in, bibendum ac, ipsum. Ut ipsum.'
	),
	
	array(
		'h2' => 'podtytuł 3',
		'p#data'=>'2007.01.28',
		'#tresc'=>'Aenean tincidunt leo ut ante. Donec at nunc. Fusce bibendum. Praesent consectetuer cursus tortor. Nulla vulputate fermentum orci.'
	),
	
	array(
		'h2' => 'podtytuł 4',
		'p#data'=>'2007.01.27',
		'#tresc'=>'Praesent congue mauris vel ipsum. Duis venenatis, purus vel faucibus tempor, est neque congue eros, et adipiscing enim purus consequat risus. Ut sit amet nisl. Vivamus pede ligula, tincidunt id, auctor id, feugiat et, eros. Donec aliquet.'
	)
);

$t->add('#petla', $petla);

$t->add('#stopka', 'Przykładowa stopka: strona chroniona prawnie ustawą o ochronie danych osobowych');

$t->display(1);

?>
