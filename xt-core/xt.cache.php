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
		
	public
		$references,
		$create=false,
		$instructions;
	
	/*
		plik:
			nazwa.xc - plik cache
			nazwa.php - plik zawierający obiekty
			nazwa.i - instrukcje tworzenia cache
	*/
	
	public function __construct(&$xt){
		$this->core=$xt;
	}
	
	/*
		sprawdź czy pliki są
		wczytaj je
	*/
	public function load(){
		if(file_exists($this->core->templates.'/'.$this->core->name.'.xc') && file_exists($this->core->templates.'/'.$this->core->name.'.php') && file_exists($this->core->templates.'/'.$this->core->name.'.i')){
			if($this->core->debug)echo '<p>Pliki cache są</p>';
			$this->code=file_get_contents($this->core->templates.'/'.$this->core->name.'.xc');
			$this->references=unserialize(file_get_contents($this->core->templates.'/'.$this->core->name.'.php'));
			$this->instructions=unserialize(file_get_contents($this->core->templates.'/'.$this->core->name.'.i'));
			
			$this->count=count($this->references);
		}else{
			echo '<p>Plik cache nie istnieje.</p>';
		}
	}
	
	public function add($css, $value){
		$this->create=true;
		if($value==STRING){
			$this->instructions[$css][STRING]=true;
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
		$this->references=array(); // wykasuj referencje, bo tworzymy je od nowa
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
							$this->core->dom->appendText($node,'<?php echo $this->cache->values[\''.$node->name.'\'][\'string\']; ?>');
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
			file_put_contents($this->core->templates.'/'.$this->core->name.'.xc', $header.$this->core->dom->display());
			
			// zapisz referencje obiektów
			file_put_contents($this->core->templates.'/'.$this->core->name.'.php',serialize($this->references));
			
			// zapisz instrukcje tworznia szablonu
			file_put_contents($this->core->templates.'/'.$this->core->name.'.i',serialize($this->instructions));
			
			echo '<p>Utworzono plik cache</p>';
			
			
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