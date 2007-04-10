<?php
/*
 *	xt templates system
 *	Copyright :(C) 2007 Tomasz 'neo' Kołodziejski <tkolodziejski at gmail dot com>, <neo007 at jabber dot com>
 *	E-mail    :tkolodziejski@gmail.com
 *	Website   :http://neo.mlodzi.pl/xt
 *	
 *	CSS3 to XPATH1.0 translator
 *	This is a part of xt library.
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
class getNode{
	public function __construct(&$xt){
		$this->core=$xt;
		$this->debug=0;

		$this->xml=$xt->xml;
		$this->root=$xt->root;
		
		$this->method=$xt->getnode_method;
	}
	
	public function get($str, $parent=0, $n=false){
		$this->parent=$parent?$parent:$this->root;
		if($this->method===2){
			$this->xpath='.';
		
			/* uwaga, jeśli atrybut ma ` ` to jest błąd */
			$match=preg_split('#(\s*(?:>|(?<!n)\+|~(?!=))\s*|\s+)#', trim($str), -1, PREG_SPLIT_DELIM_CAPTURE);
			array_unshift($match, null);
			
			if($this->debug){
				echo '<pre>tablica ';
				print_r($match);
				echo '</pre>';
			}
			
			$count=count($match);
			for($i=0; $i<$count; $i+=2){
				$str=$match[$i+1];
				$glue=$match[$i];
				
				$this->add($str, $glue);
			}
			
			$objects=$this->getobjects($n);
			
			if($this->debug){
			echo 'Lista<ul>';
			foreach($objects as $object){
				echo '<li>'.$object->nodeName.'</li>';
			}
			echo '</ul>';}
			
			return $objects;
		}elseif($this->method===1){
			$this->xpath=$str;
			$objects=$this->getobjects();
			return $objects;
		}
	}
	
	private function add($str, $glue=0){
		/*
			zestaw skróconych wyrażeń
		*/
		$r_name='[_a-z0-9]+';
		$r_id='[_a-z0-9-]+';
		$r_hash='\#'.$r_id;
		$r_class='\.'.$r_name;
		$r_attrib='\[\s*(?:'.$r_name.'|\#text)\s*(?:(?:\^=|\$=|\*=|=|~=|\|=)\s*"[^"]+"\s*)?\]';
		
		$r_pseudo=':'.$r_id.'(?:\(.*?\))?';

		$r_negation=':not\(\s*(?:'.$r_name.'|\*|'.$r_hash.'|'.$r_class.'|'.$r_attrib.'|'.$r_pseudo.')\s*\)';
		/*
			koniec listy wyrażeń
		*/
		
		if(preg_match('#^'.$r_name.'|\*#', $str, $match)){
			// tak, mamy nazwę
			$name=$match[0];
			
			$str=substr($str, strlen($name));
			
		}else{
			// nie, nie podano nazwy
			$name='*';
		}
		
		if(!preg_match('#^(?:'.$r_hash.'|'.$r_class.'|'.$r_attrib.'|'.$r_pseudo.'|'.$r_negation.')*$#', $str)){
			die('niepoprawny format <code>'.$str.'</code>');
		}
		preg_match_all('#('.$r_hash.'|'.$r_class.'|'.$r_attrib.'|'.$r_negation.'|'.$r_pseudo.')#', $str, $match);
		
		if($this->debug){
			print_r($match);
		}
		
		if(empty($match[0])){
			// nothing ?
		}else{
			foreach($match[0] as $param){
				$this->addParam($param);
			}
		}
		
		$glue=$this->getglue($glue);
		$this->xpath.=$glue.$name;
		
		if(!empty($this->child)){
			$this->xpath.='/../*['.implode(' and ', $this->child).']/self::'.$name;
		}
		
		if(!empty($this->type)){
			$this->xpath.='['.implode(' and ', $this->type).']';
		}
		
		unset($this->child, $this->type);
		/*
			jeśli $this->child
			
			$glue $name /../*[ $this->child ] /self:: name[ $this->type ]	
			
			jeśli nie
			
			$glue $name [ $this->type ]
			
			else
			
			$glue $name
		*/
	}
	
	private function addParam($param){
		if($this->debug){
			echo '<code>'.$param.'</code><br>';
		}
		
		if(substr($param, 0, 4)==':not'){
			$not=true;
			$param=substr($param, 5, -1);
		}else{
			$not=false;
		}
		
		switch($param{0}){
			case '#':
				//po id
				$this->g_id($param, $not);
			break;
			
			case '.':
				//klasa
				$this->g_class($param, $not);
			break;
			
			case '[':
				//atrybut
				$this->g_attribute($param, $not);
			break;
			
			case ':':
				//pseudo-klasa
				$this->g_pseudoclass($param, $not);
			break;
			
			default:
				//nic z tych rzeczy, zatem po nazwie + not
				if($not){
					$this->type[]='name()!="'.$param.'"';
				}else{
					//nigdy nie powinno się zdarzyć
				}
			break;
		}
	}
	
	private function g_id($id, $not=false){
		$id=substr($id, 1);
		
		if(!$not){
			$this->type[]='@id="'.$id.'"';
		}else{
			$this->type[]='@id!="'.$id.'"';
		}
	}
	
	private function g_class($class, $not=false){
		$class=substr($class, 1);
		
		if(!$not){
			$this->type[]='contains(concat(" ", @class, " "), " '.$class.' ")';
		}else{
			$this->type[]='not(contains(concat(" ", @class, " "), " '.$class.' "))';
		}
	}
	
	private function g_attribute($attribute, $not=false){
		preg_match('#\[(.*?|\#text)(?:([~^$*|]?)="([^"]+)")?\]#', $attribute, $match);
		
		if($match[1]=='#text'){ // magiczny atrybut oznaczający zawartość tekstową
			$attribute='.';
			$separator=$match[2];
			$value=$match[3];
		}else{
			$attribute='@'.$match[1];
			$separator=$match[2];
			$value=$match[3];
		}
		
		if(isset($value)){
			switch($separator){
				case '':
					$match=$attribute.'="'.$value.'"';
					break;
				case '~':
					$match='contains(concat(" ", '.$attribute.', " "), " '.$value.' ")';
					break;
				case '^':
					$match='starts-with('.$attribute.', "'.$value.'")';
					break;
				case '$':
					$match='substring('.$attribute.', string-length('.$attribute.')-'.(strlen($value)-1).')="'.$value.'"';
					break;
				case '*':
					$match='contains('.$attribute.', "'.$value.'")';
					break;
				case '|':
					$match=$attribute.'="'.$value.'" or contains('.$attribute.', " '.$value.'-") or starts-with('.$attribute.', "'.$value.'-")';
					break;
			}
		}else{
			$match=$attribute;
		}
		
		if(!$not){
			$this->type[]=$match;
		}else{
			$this->type[]='not('.$match.')';
		}
	}
	
	private function g_pseudoclass($class, $not){
		preg_match('#^(\:[a-z-]+)(?:\((.*?)\))?$#', $class, $match);
		
		//print_r($match);
		
		$param=$match[2];
		$match=$match[1];
		
		switch($match){
			case ':first-child':
				$child='position()=1';
			break;
			
			case ':last-child':
				$child='position()=last()';
			break;
			
			case ':first-of-type':
				$type='position()=1';
			break;
			
			case ':last-of-type':
				$type='position()=last()';
			break;
			
			case ':only-of-type':
				$type='position()=1 and position()=last()';
			break;
			
			case ':only-child':
				$child='position()=1 and position()=last()';
			break;
			
			case ':root':
				$type='/=.';
			break;
			
			case ':empty':
				$type='count(./child::node())=0';
			break;
			
			case ':lang':
				$type='(@lang="'.$param.'" or contains(@lang, " '.$param.'-") or starts-with(@lang, "'.$param.'-"))';
			break;
			case ':nth-child':
			case ':nth-last-child':
			case ':nth-of-type':
			case ':nth-last-of-type':
				if(strpos($match, 'last')!==false){
					$position='(last()-position()+1)';
				}else{
					$position='position()';
				}
				
				if(strpos($match, 'of-type')!==false){
					$nazwa='type';
				}else{
					$nazwa='child';
				}
				
				if($param=='even'){
					$a=2;
					$b=0;
				}elseif($param=='odd'){
					$a=2;
					$b=1;
				}else{
					if(preg_match('#(-?(\d+)?)n#', $param, $a)){
						if(empty($a[1])){
							$a=1;
						}elseif(empty($a[2])){
							$a=-1;
						}else{
							$a=(int)$a[1];
						}
					}else{
						$a=null;
					}
					
					preg_match('#(-?\d+)(?!n)#', $param, $b);
					if(empty($b[1])){
						$b=0;
					}else{
						$b=(int)$b[1];
					}
				}

				if($a==1){
					$$nazwa=$position.'>'.($b-1);
				}elseif($a==-1){
					$$nazwa=$position.'<'.($b+1);
				}elseif($a){
					$$nazwa='('.$position.'+'.(-$b).')*'.$a.'>=0 and ('.$position.'+'.(-$b).') mod '.$a.'=0';
				}else{
					$$nazwa=$position.'='.$b;
				}
			break;
		}
		
		if(isset($type) && strlen($type)>0){
			if(!$not){
				$this->type[]=$type;
			}else{
				$this->type[]='not('.$type.')';
			}
		}
		if(isset($child) && strlen($child)>0){
			if(!$not){
				$this->child[]=$child;
			}else{
				$this->child[]='not('.$child.')';
			}
		}
	}
	
	private function getobjects($count=false){
		if(empty($this->xpath)){
			return null;
		}else{
			if(!isset($this->xpo)){
				$this->xpo = new DOMXPath($this->xml);
			}

			if(is_int($count)){
				//$this->xpath.='[position()='.$count.']';
			}
			
			$results = $this->xpo->query($this->xpath, $this->parent);
			
			if($this->debug){
				echo '<p>Zapytanie to: <code>'.$this->xpath.'</code></p>';
			}
			
			return $results;
		}
	}
	
	private function getglue($glue){
		if(is_null($glue)){
			return '/descendant-or-self::';
		}else{
			$glue=trim($glue);
			switch($glue){
				case '':
					$glue='/descendant::';
					break;
				case '>':
					$glue='/';
					break;
				case '+':
					$glue='/following::*[1]/self::';
					break;
				case '~':
					$glue='/following::';
					break;
			}
			return $glue;
		}
	}
}

?>