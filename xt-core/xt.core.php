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
// define constant variables
define('GETNODE_METHOD_XPATH', 1);
define('GETNODE_METHOD_CSS',2);

define('XHTML', 2);
define('HTML', 3);
define('XML', 4);
define('RSS', 5);
define('ATOM', 6);
class xt{
	private $core=array('fragment', 'getnode', 'switcher', 'dom', 'cache');
	public function __construct($file=0){
		$this->start_time=$this->microtime_float();
		$this->find_plugins();
		$this->debug=false;
		$this->getnode_method=2;
		
		$this->templates=dirname(__FILE__).'/../templates';

		if($file){
			$this->load($file);
		}
	}
	
	public function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	private function find_plugins(){
		$this->plugins=array();
		foreach(glob('../xt-plugins/*') as $file){
			$this->plugins[]=substr(basename($file, '.php'), 3);
		}
	}
	
	/**
	 * include plugins
	 */
	public function __get($name){
		if(in_array($name, $this->plugins)){
			if(!isset($this->$name)){
				require_once('../xt-plugins/xt.'.$name.'.php');
				$this->$name=new $name($this);
			}
		}
		
		if(in_array($name, $this->core)){
			if($name=='fragment'){
				if(!class_exists('fragment')){
					require_once('xt.fragment.php');
				}
				return new fragment($this);
			}elseif(!isset($this->$name)){
				require_once('xt.'.$name.'.php');
				$this->$name=new $name($this);
			}
		}
		return $this->$name;
	}
	
	/**
	 * antyliterówka
	 */
	public function __call($name, $arguments){
		if(!method_exists($this, $name)){
			 throw new xtException('Metoda '.$name.' nie istnieje!', E_WARNING);
		}
	}
	
	/**
	 * @param str filename/template
	 */
	public function load($file){
		if(file_exists($file)){
			$this->name=basename($file);
			$this->template=file_get_contents($file);
			
			if(file_exists($this->templates.'/'.$this->template.$this->name.'.xc')){
				$this->cached=file_get_contents($this->templates.'/'.$this->name.'.xc');
				
			}
		}else{
			throw new xtException('Plik szablonu <code>'.htmlspecialchars($file).'</code> nie istnieje', E_ERROR);
		}
	}
	
	/**
	 * głowna funkcja dodająca wartości/parametry, obsługująca pętle
	 */
	public function add($name, $value){
		if(is_string($value) || is_int($value)){
			if(isset($this->cache->objects[$name])){
				$index=$this->cache->objects[$name];
				$this->cache->values[$index]=$value;
			}else{
				$this->cache->add($name, $value);
			}
		}elseif($this->dom->is_node($value)){
			$value=$this->dom->xml->savexml($this->dom->xml->importNode($value, true));
			if(isset($this->cache->objects[$name])){
				$index=$this->cache->objects[$name];
				$this->cache->values[$index]=$value;
			}else{
				$this->cache->add($name, $value);
			}
		}
		/*	if(is_array($value) && isset($value[0]) && is_array($value[0])){
				//$node->removeAttribute('id');
				$this->r($node, $value);
			}elseif(is_array($value)){
				$this->set($node, $value);
			}elseif(is_string($value)){
				if(!in_array($name, $this->caches)){
					$this->createCache=true;
					$this->appendText($node, '<![CDATA[<?php echo $xt->cache[\'obiekt'.$this->cache->count++.'\'];?>]]>');
					
					$this->cache[$name]=$value;
				}else{
					$this->cache[$name]=$value;
				}
				
			}elseif($this->is_node($value)){
				$node->appendChild($value);
			}elseif($value instanceof fragment){
				$node->appendChild($value->s);
			}else{
				throw new xtException('Niepoprawny drugi parametr metody <code>add</code>: <code>'.htmlspecialchars(print_r($value, 1)).'</code>', E_WARNING);
			}
		}else{
			return false;
		}*/
	}
	
	public function display(){
		eval('?>'.$this->cache->code.'<?php');
		echo '<p>Czas wykonywania skryptu to '.($this->microtime_float()-$this->start_time).'s</p>';
	}
	
	/**
	 * smarty compatible
	 */
	public function assign($name, $value){
		$this->add($name, $value);
	}

	/**
	 * pomocnicza funkcja głównej - zagnieżdżone pętle
	 */
	private function r($node, $all){
		foreach($all as $row){
			$clone=$node->cloneNode(true);
			foreach($row as $key => $value){
				if($tt=$this->getOneNode($key, $clone)){
					$this->add($tt, $value);
				}
			}
			$this->remove_id($clone);
			$node->parentNode->insertBefore($clone, $node);
		}
		$node->parentNode->removeChild($node);
	}
	
	/**
	 * pętla drugiego rodzaju
	 * nie działa jak należy (?)
	 */
	public function loop($name, $count, $delete_sample=true){
		if($node=$this->getOneNode($name)){
			$str=$this->xml->savexml($node);
			$count=(int)$count;
			if($count>0){
				for($i=0; $i<$count; $i++){
					$fragment=$this->fragment($str, true);
					
					$node->parentNode->insertBefore($fragment->s, $node);
					
					$fragment->root=$node->previousSibling; //konieczne jest ustawienie rodzica, gdyż w tym miejscu tracimy #document-fragment

					$return[]=$fragment;
				}
				if($delete_sample){
					$this->remove($node); // usuń wzorcowy element
				}
				return $return;
			}else{
				return array();
			}
		}else{
			return array();
		}
	}
	
	
	
	/**
	 * plugins which needs special inteface
	 */
	
	/**
	 * create document-fragment
	 * @param str template_fragment
	 */
	public function fragment($str=false, $is_string=false){
		$fragment=$this->fragment;
		if($str){
			$fragment->load($str, $is_string);
		}
		return $fragment;
	}
	
	public function switcher(){
		$objects=func_get_args();
		$switcher=$this->switcher;
		$switcher->load($objects);
		return $switcher;
	}
}
?>