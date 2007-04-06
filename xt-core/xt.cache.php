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
		}elseif($value==ATTRIBUTES){
			$this->instructions[$css][ATTRIBUTES]=true;
		}
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
				switch($k){
					case STRING:
						if(!isset($node->xt[STRING])){
							$this->core->dom->appendText($node,'<?php echo $this->cache->values[\''.$node->name.'\'][\'string\']; ?>');
							$node->xt[STRING]=true;
						}
						break;
					case ATTRIBUTES:
						if(!isset($node->xt[ATTRIBUTES])){
							if(isset($node->xt[NAME])){ // jeśli ustawiono nowy name - użyj nowego
								$name='$this->cache->values[\''.$node->name.'\'][\'name\']';
							}else{
								$name=$node->nodeName;
							}
							
							/* wczytaj listę starych atrybutów, sprawdzaj, czy nie zastąpiono ich nowymi */
							$old_attr='';
							foreach($this->core->dom->get_attributes($node) as $k=>$v){
								$old_attr.='if(!isset($this->cache->values["'.$node->name.'"]["attributes"]["'.$k.'"])){
									$this->cache->values["'.$node->name.'"]["attributes"]["'.$k.'"]="'.$v.'";
								}';
							}
							
							$this->core->dom->appendStartText($node, '
								<?php 
									echo "<'.$name.'";
									'.$old_attr.'
									foreach($this->cache->values["'.$node->name.'"]["attributes"] as $k => $v){
										echo " ".$k."=\"".$v."\"";
									}
									echo ">"
								?>');
							
							$this->core->dom->appendText($node, '<?php echo "</'.$name.'>" ?>');
							$node->xt[ATTRIBUTES]=true;
							
							$node->xt['delete']=true;
							$this->core->dom->removeParent($node); /* powinno się usuwać potem!!!!!!!!!!!!1*/
						}
						break;
				}
			}
		}
		
		/* usuń ozacznone */
		foreach($this->core->dom->xml->getElementsByTagName('*') as $node){ echo 'xxxxxx'; echo $this->core->dom->display();
			if(isset($node->xt)){
				echo 'ta';
			}
			if(isset($node->xt['delete'])){
				echo 'aaaaaaaaaaaaaaaaaaaaaa';
				$this->core->dom->removeParent($node);
			}
		}
		
		echo '</div>';
	}
	
	public function __destruct(){
		if($this->create){
			echo '<p>Tworzenie pliku cache...</p>';
			$this->create();
			
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
	}
}