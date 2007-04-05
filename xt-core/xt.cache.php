<?php
/*
 *	xt templates system
 *	Copyright :(C) 2007 Tomasz 'neo' Kołodziejski <tkolodziejski at gmail dot com>, <neo007 at jabber dot com>
 *	E-mail    :tkolodziejski@gmail.com
 *	Website   :http://neo.mlodzi.pl/xt
 *
 *	This library is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU Lesser General Public
 *	License as published by the Free Software Foundation; either
 *	version 2.1 of the License, or (at your option) any later version.
 *	
 *	This library is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *	Lesser General Public License for more details.
 *
 *	You should have received a copy of the GNU Lesser General Public
 *	License along with this library; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

class cache{
	private
		$references,
		$instructions;
		
	public
		$create=false,
		$objects;
	
	/*
		references  (tablica) zapisywane są
			[nazwa_obiektu] => nazwa_w_xt_cache
			
			np.
			
			[Object id #10] => obiekt3
			[Object id #13] => obiekt3
			
		objects (tablica) zawiera:
			[odwołanie_css_do_obiektu] => nazwa_w_xt_cache
			
			np.
			
			[html > body] => obiekt3
			[html body] => obiekt3
			
		make_cache (tablica)
			[odwołanie_css_do_obiektu] => array( co_z_nim_zrobić )
			
			np.
			
			[html > body] => array ( 'pętla' );
			
	*/
	
	/*
		plik:
			nazwa.xc - plik cache
			nazwa.php - plik zawierający obiekty
	*/
	
	public function __construct(&$xt){
		$this->core=$xt;
	}
	
	/*
		sprawdź czy pliki są
		wczytaj je
	*/
	public function load(){
		if(file_exists($this->core->templates.'/'.$this->core->name.'.xc') && file_exists($this->core->templates.'/'.$this->core->name.'.php')){
			echo '<p>Pliki cache są</p>';
			$this->code=file_get_contents($this->core->templates.'/'.$this->core->name.'.xc');
			$this->objects=unserialize(file_get_contents($this->core->templates.'/'.$this->core->name.'.php'));
			
			$this->count=count($this->objects);
		}else{
			echo '<p>Plik cache nie istnieje.</p>';
		}
	}
	
	public function add($css, $value){
		$this->create=true;
		if($value==STRING){
			$this->instructions[$css]['string']=true;
		}
		
		/*$node=$this->core->dom->getOneNode($css);
		
		if(!isset($this->references[(string)$node])){
			$name='obiekt'.++$this->count;
			$this->references[(string)$node]=$name;
			$this->core->dom->add($node, '<?php echo $this->cache->values[\''.$name.'\'];?>');
		}else{
			$name=$this->references[(string)$node];
		}
		
		$this->objects[$css]=$name;*/
	}
	
	public function create(){ echo "<div style='border:2px solid'>";
		foreach($this->instructions as $css => $value){
			$node=$this->core->dom->getOneNode($css);
			if(!isset($node->name)){
				$node->name='object'.++$this->count;
			}
			if(!isset($this->references[$css])){
				$this->references[$css]=$node->name;
			}
			
			foreach($value as $k => $v){
				switch($v){
					case STRING:
						if(!isset($node->string)){
							$this->core->dom->appendText($node,'<?php echo $this->cache->values[\''.$node->name.'\']; ?>');
							$node->string=true;
						}
				}
			}
		}
		echo '</div>';
	}
	
	public function __destruct(){
		if($this->create){
		
			$this->create();
		
			echo '<p>Tworzenie pliku cache...</p>';
			$header='<?php /*';
			$header.="\n".'Cache szablonu systemu xt. Zbudowano '.date(DATE_RFC822);
			$header.="\n".'*/ ?>';
		
			// zapisz szablon
			//var_dump($this->core->dom);
			if(file_put_contents($this->core->templates.'/'.$this->core->name.'.xc', $header.$this->core->dom->display()) &&
			
			// zapisz obiekty
			file_put_contents($this->core->templates.'/'.$this->core->name.'.php',serialize($this->objects))){
				echo '<p>Utworzono plik cache</p>';
			}
			
			echo '<p>Szablon wynikowy<pre>'.htmlspecialchars($this->core->dom->display()).'</pre></p>';
			
			echo '<p>Tablica referencji css ~> obiektx<pre>';
			print_r($this->references);
			echo '</pre></p>';
			
			echo '<p>Tablica instrukcji<pre>';
			print_r($this->instructions);
			echo '</pre></p>';
			
		}
		/*echo '<pre style="border:2px solid">Tablica $objects';
		print_r($this->objects);
		echo '</pre>';*/
	}
}