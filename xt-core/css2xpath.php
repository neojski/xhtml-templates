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
class css2xpath{
	public $xpath;
	public function __construct($css, $defaultNamespace = null){
		$this->defaultNamespace = $defaultNamespace;
		
		$this->add(trim($css));
		
		//echo $this->xpath.'<br>';
		
		return $this->xpath;
	}
	
	public function __toString(){
		return $this->xpath;
	}
	
	private $r = array(
		'name' => '[_a-z0-9]+'
	);
	
	/*
		jeśli atrybut zamiast domyślnego ns nie daje się nic
	*/
	private function name_with_ns(&$str, $attribute = false){
		if(preg_match('#^(?:([a-z]*|\*)(\|))?('.$this->r['name'].'|\*)|\*#i', $str, $match)){
			$ns = !empty($math[1]) ? $match[1] : $ns = null;
			
			$str = substr($str, strlen($match[0]));
			
			if(!empty($match[2])){
				if(!empty($match[1])){
					//mamy namespace
					if($match[1]!=='*'){
						//ustalony namespace
						$name = str_replace('|', ':', $match[0]); # normalnie - namespace:node_name
					}else{
						//dowolny namespace
						if($match[3]!=='*'){
							// mamy nazwę
							$name = '*[local-name()="'.$match[3].'"]';
						}else{
							$name = '*';
							// dowolny obiekt o dowolnym namespace
						}
					}
				}else{
					//elementy z domyślnym namespace
					$name = 'default:'.str_replace('|', ':', $match[3]);
				}
			}else{
				//brak namespace
				if($this->defaultNamespace && $match[3]!=='*'){
					// jest jakiś domyślny
					$name = '*[local-name()="'.$match[3].'"]';
					/*
						jeśli jest domyślny namespace, to wtedy obiekt dowolnego ns ma jakiś domyślny namespace
					*/
				}else{
					if($match[3]!=='*'){
						// mamy nazwę
						$name = '*[local-name()="'.$match[3].'"]';
					}else{
						$name = '*';
					}
				}
			}
			
			if(!$attribute){
				return $name;
			}else{
				if(substr($name, 0, 8) == 'default:'){
					return substr($name, 8);
				}else{
					return $name;
				}
			}
		}else{
			$this->t('brak name');
			return false;
		}
	}
	
	private function t($a){
		//echo '<br>'.$a.'<br>';
	}
	
	/*
		default glue is null -> //
	*/
	private function add($str, $glue = null){
		# name
		if(!$name = $this->name_with_ns($str)){
			$this->name = null;
			
			$name = '*';
		}else{
			$this->name = $name;
		}
		
		//$str = trim($str);
		
		
		
		
		$this->xpath .= $this->getglue($glue).$name;
		
		if(strlen($str)>0){
		
			# attributes
			for($i=0; ; $i++){
				//echo $i;
				
				if(!$this->attribute($str))
					break;
			
			}

			if(!empty($this->type)){
				$this->xpath.='['.implode(' and ', $this->type).']';
			}
			
			unset($this->type, $this->name);
			
			$str = trim($str);
			
			if(strlen($str)>0){
				switch($str{0}){
					case '+':
					case '~':
					case '>':
						$this->add(trim(substr($str, 1)), $str{0});
					break;
					
					case ',':
						$this->xpath .= '|';
						$this->add(trim(substr($str, 1)));
					break;
					
					default:
						$this->add(trim($str), ' ');
				}
			}
		}
		
	}
	
	private function attribute(&$str, $not = false){
		switch($str{0}){
			case '#':
				//po id
				$this->t('chyba po id');
				return $this->g_id($str, $not);
			break;
			
			case '.':
				//klasa
				$this->t('chyba po klasie');
				return $this->g_class($str, $not);
			break;
			
			case '[':
				//atrybut
				$this->t('chyba po atrybucie');
				return $this->g_attribute($str, $not);
			break;
			
			case ':':
				//pseudo-klasa
				$this->t('chyba po pseudo-klasie');
				return $this->g_pseudoclass($str, $not);
			break;
			default:
				return null;
			break;
		}
	}
	
	/*
		odcinaj
		
		#jakiesid
	*/
	private function g_id(&$str, $not = false){
		if(preg_match('#^\#('.$this->r['name'].')#i', $str, $match)){
			$id = $match[1];
			
			$str = substr($str, strlen($match[1])+1); // bo # dochodzi
			
			if(!$not){
				$this->type[] = '@id="'.$id.'"';
			}else{
				$this->type[] = '@id!="'.$id.'"';
			}
			
			return true;
		}else{
			return false;
		}
	}
	
	private function g_class(&$str, $not = false){
		if(preg_match('#^\.('.$this->r['name'].')#i', $str, $match)){
			$class = $match[1];
			
			$str = substr($str, strlen($match[1])+1); // plus .
			if(!$not){
				$this->type[]='contains(concat(" ", @class, " "), " '.$class.' ")';
			}else{
				$this->type[]='not(contains(concat(" ", @class, " "), " '.$class.' "))';
			}
			return true;
		}else{
			return false;
		}
	}
	
	private function g_attribute(&$str, $not = false){
		$str = substr($str, 1); // utnij [
		
		// sprawdź atrybut
		if(!$attribute = $this->name_with_ns($str, true)){
			if(substr($str, 0, 5) !== '#text'){
				die('błąd');
			}else{
				$attribute = '.';
				$str = substr($str, 5); // utnij #text
			}
		}else{
			$attribute = '@'.$attribute; // jeśli ok - dopisz @ na początku
		}
		
		$str = trim($str);
		
		if($str{0}==']'){
			$match = $attribute;
			
			$str = substr($str, 1);
		}else{
			$separator = $str{0};
			
			// FIXME: negative lookbehind
			if(!preg_match('#^[~^$|*]?="(.*?)"]#i', $str, $match)){
				die('coś nie pasi');
			}
			
			$str = substr($str, strlen($match[0]));
			
			$value = $match[1];
			
			switch($separator){
				case '=':
					$match = $attribute.'="'.$value.'"';
					break;
				case '~':
					/* Section 6.3.1: Represents the att attribute whose value is a
					space-separated list of words, one of which is exactly "val". If this
					selector is used, the words in the value must not contain spaces
					(since they are separated by spaces). */
					if(strpos($value, ' ')!==false){
						$match = '1=0'; // always false
					}else{
						$match = 'contains(concat(" ", '.$attribute.', " "), " '.$value.' ")';
					}
					break;
				case '^':
					$match = 'starts-with('.$attribute.', "'.$value.'")';
					break;
				case '$':
					$match = 'substring('.$attribute.', string-length('.$attribute.')-'.(strlen($value)-1).')="'.$value.'"';
					break;
				case '*':
					$match = 'contains('.$attribute.', "'.$value.'")';
					break;
				case '|':
					$match = $attribute.'="'.$value.'" or contains('.$attribute.', " '.$value.'-") or starts-with('.$attribute.', "'.$value.'-")';
					break;
			}
		}
		
		if(!$not){
			$this->type[] = $match;
		}else{
			$this->type[] = 'not('.$match.')';
		}
		
		return true;
	}
	
	private function g_pseudoclass(&$str, $not){
		if(!preg_match('#^(\:[a-z-]+)(?:\((.*?)\))?#i', $str, $match)){
			die('coś nie pasi');
		}
		
		$str = substr($str, strlen($match[0]));
		
		if(isset($match[2])){
			$param = $match[2];
		}else{
			// bez parametru
		}
		$match = $match[1];
		
		switch($match){
			case ':first-child':
				$type = './parent::* and count(preceding-sibling::*)=0';
			break;
			
			case ':last-child':
				$type = './parent::* and count(folowing-sibling::*)=0';
			break;
			
			case ':only-child':
				$type = './parent::* and count(../*)=1';
			break;
			
			case ':first-of-type':
				if(!isset($this->name)){
					die(':...-of-type needs node-name');
				}
				$type = './parent::* and count(preceding::'.$this->name.')=0';
			break;
			
			case ':last-of-type':
				if(!isset($this->name)){
					die(':...-of-type needs node-name');
				}
				$type = './parent::* and count(folowing::'.$this->name.')=0';
			break;
			
			case ':only-of-type':
				$type = './parent::* and count(../'.$this->name.')=0';
			break;
			
			case ':root':
				$type = 'not(./parent::*)';
			break;
			
			case ':empty':
				$type = 'count(./child::node())=0';
			break;
			
			case ':lang':
				$type = '(@lang="'.$param.'" or contains(@lang, " '.$param.'-") or starts-with(@lang, "'.$param.'-"))';
			break;
			case ':nth-child':
			case ':nth-last-child':
			case ':nth-of-type':
			case ':nth-last-of-type':
				if(strpos($match, 'last')!==false){
					$dir = 'folowing';
				}else{
					$dir = 'preceding';
				}
				
				if(strpos($match, 'of-type')!==false){
					if(!isset($this->name)){
						die(':...-of-type needs node-name');
					}
					$sibling = '::'.$this->name;
				}else{
					$sibling = '-sibling::*';
				}
				
				if($param == 'even'){
					$a=2;
					$b=2;
				}elseif($param == 'odd'){
					$a=2;
					$b=1;
				}else{
					if(preg_match('#(-?(\d+)?)n#i', $param, $a)){
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
					
					preg_match('#(-?\d+)(?!n)#i', $param, $b);
					if(empty($b[1])){
						$b=0;
					}else{
						$b=(int)$b[1];
					}
				}

				if($a==1){
					$type = 'count('.$dir.$sibling.')>'.($b);
				}elseif($a==-1){
					$type = 'count('.$dir.$sibling.')<'.($b);
				}elseif($a){
					$type = 'count('.$dir.$sibling.') mod '.$a.'='.($b-1);
				}else{
					$type = 'count('.$dir.$sibling.')='.$b;
				}
				
				$type = './parent::* and '.$type;
			break;
			
			case ':not':
				// FIXME - problems with (), i.e. :not(:lang(fr))
				$this->attribute($param, true);
			break;
			
			default:
				die('coś nie pasi');
			break;
		}
		
		if(isset($type) && strlen($type)>0){
			if(!$not){
				$this->type[]=$type;
			}else{
				$this->type[]='not('.$type.')';
			}
		}
		
		return true;
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
					$glue='/child::';
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