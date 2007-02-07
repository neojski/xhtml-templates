<?php
/*
 *	xt templates system
 *	Copyright :(C) 2007 Tomasz Kołodziejski
 *	E-mail    :tkolodziejski@gmail.com
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
	public function __construct($xt){
		$this->debug=1;
		
		$this->xpath='.';
		
		$this->xml=$xt->xml;
		$this->root=$xt->root;
		
		$this->method=$xt->getnode_method;
	}
	
	public function get($str, $parent=0){
		if($this->method===2){
			$match=preg_split('#(\s*(?:>|(?<!n)\+|~(?!=))\s*|\s+)#', trim($str), -1, PREG_SPLIT_DELIM_CAPTURE);
			array_unshift($match, null);
			
			if($this->debug){
				echo '<pre>tablica ';
				print_r($match);
				echo '</pre>';
			}
			
			$this->parent=$parent?$parent:$this->root;
			
			$count=count($match);
			
			for($i=0; $i<$count; $i+=2){
				$str=$match[$i+1];
				$glue=$match[$i];
				
				$this->add($str, $glue);
			}
			
			$objects=$this->getobjects();
			
			if($this->debug){
			echo '<ul>';
			foreach($objects as $object){
				echo '<li>'.$object->nodeName.'</li>';
			}
			echo '</ul>';}
			
			return $objects;
		}elseif($this->method===1){
			if(!$parent){
				$parent=$this->root;
			}
			$xpath = new DOMXPath($this->xml);
			$results = $xpath->query($this->xpath, $parent);
			return $results;
		}
	}
	
	private function add($str, $glue=0){
		$unicode='[0-9a-f]{1,6}(?:\r\n|[ \n\r\t\f])?';
		$nonascii='[^\0-\177]';
		$escape=$unicode.'|[^\n\r\f0-9a-f]';
		
		$nmchar='[_a-z0-9-]|'.$nonascii.'|'.$escape;
		$nmstart='[_a-z]|'.$nonascii.'|'.$escape;
		$ident='-?'.$nmstart.$nmchar.'*';
		$string=$string1.'|'.$string2;
		$string1='"(?:[^\n\r\f\\"]|\\'.$nl.'|'.$nonascii.'|'.$escape.')*"';
		$string1='\'(?:[^\n\r\f\\\']|\\'.$nl.'|'.$nonascii.'|'.$escape.')*\'';
		$function=$ident.'(?:';
		$universal='\*';
		$num='[0-9]+|[0-9]*\.[0-9]+';
		$expression='(?:(?:\+|-|'.$num.$ident.'|'.$num.'|'.$string.'|'.$ident.')\s*)+';
		$funcitonal_pseudo=$function.'\s*'.$expression.')';
		
		$type_selector=$ident;
		
		$name=$nmchar.'+';
		$hash='\#'.$name;
		$class='\.'.$ident;
		$attrib='\[\s*'.$ident.'\s*(?:(?:\^=|\$=|\*=|=|~=|\|=)\s*(?:'.$ident.'|'.$string.')\s*)?\]';
		$pseudo=':(?:'.$ident.'|'.$funcitonal_pseudo.')';
		
		$negation_arg=$type_selector.'|'.$universal.'|'.$hash.'|'.$class.'|'.$attrib.'|'.$pseudo;
		$negation=':not\(\s*'.$negation_arg.'\s*\)';
		
		echo '#('.$type_selector.'|'.$universal.')('.$hash.'|'.$class.'|'.$attrib.'|'.$pseudo.'|'.$negation.')*|('.$hash.'|'.$class.'|'.$attrib.'|'.$pseudo.'|'.$negation.')+#';
		
		preg_match_all('#(('.$type_selector.'|'.$universal.')('.$hash.'|'.$class.'|'.$attrib.'|'.$pseudo.'|'.$negation.')*|('.$hash.'|'.$class.'|'.$attrib.'|'.$pseudo.'|'.$negation.')+)#', $str, $match);
		
		print_r($match);
		
		
		
		preg_match_all('#(('.$type_selector.'|'.$universal.')('.$hash.'|'.$class.'|'.$attrib.'|'.$pseudo.'|'.$negation.')*|('.$hash.'|'.$class.'|'.$attrib.'|'.$pseudo.'|'.$negation.')+)#', $str, $match);
		
		print_r($match);
		

		if(preg_match('#(.*?)((:first-child|:first-of-type|:last-child|:last-of-type|:nth-child\('.$r_nth.'\)|:nth-of-type\('.$r_nth.'\)|:root|:empty))#', $str, $match)){
			$str=$match[1];
			$pseudo_class=$match[2];
		}
		
		$glue=$this->getglue($glue);
		
		if(preg_match('#^('.$r_name.'|\*)?\#('.$r_id.')$#', $str, $match)){
			if($this->debug){echo '<p>po id <code>'.$str.'</code></p>';}
			
			$name=empty($match[1])?'*':$match[1];
			$id=$match[2];
			
			$xpath=$name.'[@id="'.$id.'"]';
		}elseif(preg_match('#^('.$r_name.'|\*)?((?:\.'.$r_id.')+)$#', $str, $match)){
			if($this->debug){echo '<p>po klasie <code>'.$str.'</code></p>';}
			
			$name=empty($match[1])?'*':$match[1];
			$class=explode('.',substr($match[2], 1));
			
			$count=empty($match[3])?null:$match[3];
			
			$xpath=$name.'[';
	
			foreach($class as $c){
				$xpath.='contains(concat(" ", @class, " "), " '.$c.' ") and ';
			}
			
			$xpath=substr($xpath, 0, -4);
			
			$xpath.=']';
		}elseif(preg_match('#^('.$r_name.'|\*)$#', $str, $match)){
			if($this->debug){echo '<p>po nazwie <code>'.$str.'</code></p>';}
			
			$name=$match[1];
			$count=$match[2];
			
			$xpath=$name;
		/*
			może być więcej nawiasów kwadratowych
		*/
		}elseif(preg_match('#('.$r_name.'|\*)?\[('.$r_attribute.')(?:([~^$*|]?)="('.$r_value.')"\])?#', $str, $match)){
			if($this->debug){echo '<p>po atrybucie rozszerzonym <code>'.$str.'</code></p>';}
			
			
			$name=empty($match[1])?'*':$match[1];
			$attribute=$match[2];
			$separator=$match[3];
			$value=$match[4];
			$count=empty($match[5])?null:$match[5];
			
			if($value){
				switch($separator){
					case '':
						$match='[@'.$attribute.'="'.$value.'"]';
						break;
					case '~':
						$match='[contains(concat(" ", @'.$attribute.', " "), " '.$value.' ")]';
						break;
					case '^':
						$match='[starts-with(@'.$attribute.', '.$value.')]';
						break;
					case '$':
						$match='[substring(@'.$attribute.', string-length(@'.$attribute.')-'.strlen($value).')="'.$value.'"]';
						break;
					case '*':
						$match='[contains(@'.$attribute.', "'.$value.'")]';
						break;
					case '|':
						$match='[contains(@'.$attribute.', " '.$value.'-") or starts-with(@'.$attribute.', "'.$value.'-")]';
						break;
				}
			}else{
				$match='[@'.$attribute.']';
			}
			
			$xpath=$name.$match;
		}else{
			//exit('błąd parsowania ciągu <code>'.$str.'</code>');
		}
		/*
			:first-child	[../*[position()=1]=.]
			:last-child	[../*[position()=last()]=.]
			:nth-child
			:only-child	[../*[position()=1 and position()=last()]=.]
			
			:first-of-type	[position()=1]
			:last-of-type	[position()=last()]
			:nth-of-type
			:only-of-type	[position()=1 and position()=last()]
			
			:root		[/=.]
			:empty		[count(./child::node())=0]
			
			:lang(x)	to samo co [lang|="x"]
		*/
		
		
		if($pseudo_class){
			if($pseudo_class==':first-child'){
				$xpath='*/../*[1]/self::'.$xpath;
			}
			
			if($pseudo_class==':last-child'){
				$xpath='*/../*[last()]/self::'.$xpath;
			}
			
			if($pseudo_class==':first-of-type'){
				$xpath.='[1]';
			}
			
			if($pseudo_class==':last-of-type'){
				$xpath.='[last()]';
			}
			
			if(substr($pseudo_class, 0, 10)==':nth-child'){
			
				if(preg_match('#(-?(\d+)?)n#', substr($pseudo_class, 11, -1), $a)){
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
				
				preg_match('#(-?\d+)(?!n)#', $pseudo_class, $b);
				if(empty($b[1])){
					$b=0;
				}else{
					$b=(int)$b[1];
				}
				
				if($a==1){
					$xpath='*/../*[position()>'.($b-1).']/self::'.$xpath;
				}elseif($a==-1){
					$xpath='*/../*[position()<'.($b-1).']/self::'.$xpath;
				}elseif($a){
					$xpath='*/../*[(position()+'.(-$b).')*'.$a.'>=0 and (position()+'.(-$b).') mod '.$a.'=0]/self::'.$xpath;
				}else{
					$xpath='*/../*[position()='.$b.']/self::'.$xpath;
				}
			}
			
			if(substr($pseudo_class, 0, 12)==':nth-of-type'){
			
				if(preg_match('#(-?(\d+)?)n#', substr($pseudo_class, 11, -1), $a)){
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
				
				preg_match('#(-?\d+)(?!n)#', $pseudo_class, $b);
				if(empty($b[1])){
					$b=0;
				}else{
					$b=(int)$b[1];
				}
				
				if($a==1){
					$xpath.='[position()>'.($b-1).']';
				}elseif($a==-1){
					$xpath.='[position()<'.($b-1).']';
				}elseif($a){
					$xpath.='[(position()-'.$b.')*'.$a.'>=0 and (position()-'.$b.') mod '.$a.'=0]';
				}else{
					$xpath.='[position()='.$b.']';
				}
			}
			
			if(substr($pseudo_class, 0, 6)==':empty'){
				$xpath.='[count(./child::node())=0]';
			}
			
			$this->xpath.=$glue.$xpath;
		}else{
			$this->xpath.=$glue.$xpath;
		}
	}
	
	private function getobjects(){
		if(empty($this->xpath)){
			return null;
		}else{
			$xpath = new DOMXPath($this->xml);
			$results = $xpath->query($this->xpath, $this->parent);
			
			if($this->debug){
				echo $this->xpath;
			}
			
			return $results;
		}
	}
	
	private function getglue($glue){
		$glue=trim($glue);
		switch($glue){
			case null:
				$glue='/descendant-or-self::';
				break;
			case '':
				$glue='//';
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
echo '<br><br><br><br><br><br><br><br><br><br><br><br><pre>';

$str='test#a.b';
preg_match('#(\#[a-z]+|\.[a-z]+)+#', $str, $match);

print_r($match);
echo '</pre><br><br><br><br><br><br><br><br><br><br><br><br>';

?>