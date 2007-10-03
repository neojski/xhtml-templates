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
	private $core=array('fragment', 'getnode', 'switcher');
	public function __construct($file=0, $is_string=0){
		$this->dir=dirname(__FILE__);# folder xt-core
		$this->start_time=microtime(true);
		$this->find_plugins();
		$this->debug=false;
		$this->getnode_method=2;
		$this->strict=false;
		if($file){
			$this->load($file, $is_string);
		}
	}
	
	/**
	 * an easy method for finding plugins 
	 * in xt-plugins directory
	 */
	private function find_plugins(){
		$this->plugins=array();
		foreach(glob($this->dir.'/../xt-plugins/*') as $file){
			$this->plugins[]=substr(basename($file, '.php'), 3);
		}
	}
	
	/**
	 * include plugins, only when necessary
	 */
	public function __get($name){
		if(in_array($name, $this->plugins)){
			if(!isset($this->$name)){
				require_once($this->dir.'/../xt-plugins/xt.'.$name.'.php');
				$this->$name=new $name($this);
			}
		}
		if(in_array($name, $this->core)){
			if(!isset($this->$name)){
				require_once($this->dir.'/xt.'.$name.'.php');
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
	public function load($file, $is_string=false){
		if(!$is_string){
			if(file_exists($file)){
				$this->template=file_get_contents($file);
			}else{
				throw new xtException('Plik szablonu <code>'.htmlspecialchars($file).'</code> nie istnieje', E_ERROR);
			}
		}else{
			$this->template=$file;
		}
		$this->name=$file;
		
		$this->xml=new mydom();
		
		$this->check_encoding();
		
		
		$this->entities();
	
		$this->xml->loadxml($this->template);
		
		$this->body=$this->xml->getElementsByTagName('body')->item(0);
		$this->head=$this->xml->getElementsByTagName('head')->item(0);
		$this->root=$this->xml->documentElement;
		
		$this->xml->formatOutput=true;
		$this->xml->standalone=false;
		$this->useXML=$this->xml();
		
		$this->xpath=new DOMXPath($this->xml);
		$this->check_namespaces();
		
		$this->preprocessor();
	}
	
	/**
	 * easy file preprocessor
	 */
	private function preprocessor(){
		if(file_exists($this->name.'.php')){
			require_once($this->name.'.php');
		}
	}
	
	private function entities(){
		$references='';
		foreach(glob($this->dir.'/entities-ref/*.ent') as $ref){
			$references.=file_get_contents($ref);
		}
		
		preg_match_all('#<!ENTITY\s+([a-zA-z0-9]+).*?"(&\#[0-9]+;)"#s', $references, $matches);
		$matches[1]=array_map(create_function('$a', 'return \'&\'.$a.\';\';'), $matches[1]);
		$this->template=str_replace($matches[1], $matches[2], $this->template);
		
		// javascript:alert('&#'+'niedozwolony_znak'.charCodeAt(0)+';');
	}
	
	/**
	 * check encoding:
	 * add xml encoding prologue, which is necessary for loadxml
	 */
	private function check_encoding(){
		if(preg_match('#<\?xml[^>]+encoding="([^"]+)"[^>]*?>#', $this->template, $encoding)){
			$this->encoding=$encoding[1];
		}elseif(preg_match('#<meta[^>]+content="[^=]+=(.*?)"[^>]*>#s', $this->template, $encoding)){
			$this->template='<?xml version="1.0" encoding="'.$encoding[1].'"?>'.$this->template;
			$this->encoding=$encoding[1];
		}else{
			throw new xtException('Brak ustawionego kodowania', E_ERROR);
		}
	}
	
	/**
	 * find namespaces definitions
	 */
	private function check_namespaces(){
		preg_match_all('#xmlns:[a-z]+="[^"]+"#', $this->template, $match);
		$namespaces=$match[0];
		
		# register all namespaces
		foreach($namespaces as $string){
			preg_match('#xmlns:(.*?)=#', $string, $prefix);
			preg_match('#"(.*?)"#', $string, $uri);
			
			$this->xpath->registerNamespace($prefix[1], $uri[1]);
		}
		
		# register default namespace
		if(strlen($this->root->lookupNamespaceURI(null))>0){
			$this->xpath->defaultNamespace=true;
			$this->xpath->registerNamespace('default', $this->root->lookupNamespaceURI(null));
		}else{
			$this->xpath->defaultNamespace=false;
		}
		$this->namespaces=implode(' ', $namespaces);
	}
	
	/**
	 * rozpoznawanie czy przeglądarka obsługuje xhtml
	 * autorem jest dr-no http://www.doktorno.boo.pl/index.php?q=art008
	 */
	private function xml(){
		$xhtml = false;
		if(preg_match('/application\/xhtml\+xml(?![+a-z])(;q=(0\.\d{1,3}|[01]))?/i', $_SERVER['HTTP_ACCEPT'], $matches)){
			$xhtmlQ = isset($matches[2])?($matches[2]+0.2):1;
			if(preg_match('/text\/html(;q=(0\d{1,3}|[01]))s?/i', $_SERVER['HTTP_ACCEPT'], $matches)){
				$htmlQ = isset($matches[2]) ? $matches[2] : 1;
				$xhtml = ($xhtmlQ >= $htmlQ);
			}else{
				$xhtml=true;
			}
		}
		return $xhtml;
	}
	
	private function magic_classes(){
		foreach($this->getElementsByClassName('remove_id') as $node){
			$node->removeAttribute('id');
			
			$classes = preg_split('#\s#', str_replace('remove_id', '', $node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
			
			if(empty($classes[0])){
				$node->removeAttribute('class');
			}else{
				$node->setAttribute('class', implode(' ', $classes));
			}
		}
		
		foreach($this->getElementsByClassName('remove_parent') as $node){
			$this->removeParent($node);
		}
		
		foreach($this->getElementsByClassName('remove_class') as $node){
			$node->removeAttribute('class');
		}
	}
	
	/**
	 * display
	 * @param bool debug (if true displays plain source code)
	 * magic classes
	 * display xhtml or html
	 */	
	public function display($mime=0){
		/************* xpath tests *****************
		$query = '//node()[local-name()="div"]';
		$objects = $this->xpath->query($query);
		
		echo '<div style="border:2px solid">Obiekty:<ul>';
		foreach($objects as $object){
			echo '<li><code>'.$object->nodeName.'</code>:<code>'.$object->nodeValue.'</code></li>';
		}
		echo '</ul></div>';
		
		/************* xpath tests *****************/
		
		//$this->execute_modifiers();
		$this->magic_classes();
		
		//$this->add($this->body, '<p id="stopka">'.(microtime(true)-$this->start_time).'</p>');
		
		$mime_tab=array(
			2 => 'application/xhtml+xml',
			'text/html',
			'text/xml',
			'application/xml',
			'application/rss+xml',
			'application/atom+xml'
		);
		
		
		$this->output=$this->xml->savexml();
		
		// remove namespace...
		foreach($this->removedNamespaces as $ns){
			$this->output=preg_replace('#\s+xmlns:'.$ns.'=".*?"#', '', $this->output);
		}

		if($this->debug){
			echo '<pre><code>'.htmlspecialchars($this->output).'</code></pre>';
		}elseif($mime==0){
			return $this->output;
		}elseif($mime==1){
			if($this->useXML){
				if(!headers_sent()){
					header('Content-Type: application/xhtml+xml; charset='.$this->encoding);
				}
				echo $this->output;
			}else{
				if(!headers_sent()){
					header('Content-Type: text/html; charset='.$this->encoding);
				}
				echo preg_replace('#<\?xml[^?]+\?>#s', '', $this->output, 1);
			}
		}else{
			if(!headers_sent()){
				header('Content-Type: '.$mime_tab[$mime].'; charset='.$this->encoding);
			}
			echo $this->output;
		}
	}
	
	public function __toString(){
		return $this->display(0);
	}
	
	/**
	 * modifier function like smarty's
	 */
	private $modifiers=array();
	public function modifier($node, $attribute, $function){
		$this->modifiers[]=array($node, $attribute, $function);
	}
	
	public function execute_modifiers(){
		foreach($this->modifiers as $array){
			foreach($this->getNode($array[0]) as $node){
				$node->setAttribute($array[1], call_user_func($array[2],$node->getAttribute($array[1])));
			}
		}
	}
	
	/**
	 * delete parent of the node
	 * @param domnode node
	 */
	public function removeParent($name){
		if($this->is_node($name)){
			return $this->removeOneParent($name);
		}else{
			$done=true;
			foreach($this->getNode($name) as $node){
				if(!$this->removeOneParent($node)){
					$done=false;
				}
			}
			return $done;
		}
	}
	public function removeOneParent($name){
		if($node=$this->getOneNode($name)){
			foreach($node->childNodes as $child){
				$node->parentNode->insertBefore($child->cloneNode(true), $node);
			}
			$this->remove($node);
			
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * replace parent node with new_name
	 * @param mixed old
	 * @param string new
	 * @param array attributes
	 */
	public function replaceParent($name, $new_name, $attributes=0){
		if($this->is_node($name)){
			return $this->replaceOneParent($name, $new_name, $attributes=0);
		}else{
			$done=true;
			foreach($this->getNode($name) as $node){
				if(!$this->replaceOneParent($node, $new_name, $attributes=0)){
					$done=false;
				}
			}
			return $done;
		}
	}
	
	public function replaceOneParent($name, $new_name, $attributes=0){
		if($node=$this->getOneNode($name)){
			$old=$this->xml->createdocumentfragment();
			foreach($node->childNodes as $child){
				$old->appendChild($child->cloneNode(true));
			}
			$new=$this->create($new_name, 0, $attributes);
			$new->appendChild($old);
			$this->insertBefore($node,$new);
			$this->remove($node);
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * remove namespace
	 */
	private $removedNamespaces=array();
	public function removeNS($ns, $remove_content=false){
		if(!$remove_content){
			$this->removeParent($ns.'|*');
		}else{
			$this->remove($ns.'|*');
		}
		
		$this->removedNamespaces[]=$ns;
		
		$this->root->removeAttribute('xmlns:'.$ns);
	}
	
	/**
	 * check if object $node is domelement or domdocumentfragment or dom-sth
	 * @param mixed
	 * @return bool is_dom_object
	 */
	protected function is_node($node){
		return substr(print_r($node,true),0,3)==='DOM';
	}
	
	/**
	 * dodanie zawartości tekstowej obiektowi
	 * @param object domelement
	 * @param str append-text
	 */
	public function appendText($node, $str){
		if($this->is_node($node)){
			if($child=$this->text2html($str)){
				if($child->hasChildNodes()){
				// prevent ,,Warning: DOMNode::appendChild(): Document Fragment is empty''
					$node->appendChild($child);
				}
			}
		}
	}
	
	/**
	 * dodanie zawartości tekstowej obiektowi, ale na początku
	 * @param object domelement
	 * @param str append-text
	 */
	public function appendStartText($node, $str){
		if($this->is_node($node)){
			if($node->hasChildNodes()){
				if($child=$this->text2html($str)){
					if($child->hasChildNodes()){// prevent ,,Warning: DOMNode::appendChild(): Document Fragment is empty''
						$this->insertBefore($node->firstChild, $child);
					}
				}
			}else{
				$this->appendText($node, $str);
			}
		}
	}
	
	/**
	 * zamiana tekstu na obiekt dom
	 * @param str text
	 * @return object domelement
	 */
	public function text2html($str){ 
		if($this->is_node($str)){
			return $str;
		}elseif(is_string($str)){
			$fragment=$this->xml->createDocumentFragment();
			$fragment->appendXML('<root '.$this->namespaces.'>'.$str.'</root>');
			
			foreach($fragment->firstChild->childNodes as $child){
				$fragment->appendChild($child);
			}
			$fragment->removeChild($fragment->firstChild);
			
			return $fragment;
		}elseif($str instanceof fragment){
			return $str->s;
		}else{
			return null;
		}
	}
	
	/**
	 * sprawdza, czy node jest dzieckiem głównego dokumentu
	 * @param object domelement
	 * @return bool
	 */
	public function checkNode($node){
		if(!$this->is_node($node)){
			return false;
		}else{
			return $node->ownerDocument==$this->xml;
		}
	}
	
	/**
	 * pobieranie elementów
	 * @param mixed reference_to_object_css_like
	 * @return null / object domnode
	 */
	public function getnode($name, $parent=0, $count=false){
		if($this->is_node($name)){
			return $name;
		}else{
			$str=$this->getnode;
			if(!$parent){
				$parent=$this->root;
			}
			return $str->get($name, $parent, $count);
		}
	}
	
	public function getOneNode($name, $parent=0){
		if($this->is_node($name)){
			return $name;
		}else{
			$nodes=$this->getNode($name, $parent, 0); // getNode($name.':first-of-type', $parent);
			
			return $nodes->item(0);
		}
	}
	
	/**
	 * usuwa id wszystkich dzieci i danego obiektu
	 * @param mixed object
	 */
	public function remove_id($name){
		if($node=$this->getOneNode($name)){
			$query = './descendant-or-self::*[@id]';
			$entries = $this->xpath->query($query, $node);
			foreach($entries as $node){
				$node->removeAttribute('id');
			}
		}else{
			return false;
		}
	}
	
	/**
	 * function add should add values to all nodes which match the css pattern
	 */
	public function add($name, $value){
		if($this->is_node($name)){
			return $this->addOne($name, $value);
		}else{
			$done=true;
			foreach($this->getNode($name) as $node){
				if(!$this->addOne($node, $value)){
					$done=false;
				}
			}
			return $done;
		}
	}
	
	/**
	 * głowna funkcja dodająca wartości/parametry, obsługująca pętle
	 */
	public function addOne($name, $value){
		if($node=$this->getOneNode($name)){
			if(is_array($value) && isset($value[0]) && is_array($value[0])){
				return $this->r($node, $value);
			}elseif(is_scalar($value)){
				return $this->appendText($node, (string)$value);
			}elseif($this->is_node($value)){
				return $node->appendChild($value);
			}elseif($value instanceof fragment){
				return $node->appendChild($value->s);
			}elseif($value[0] instanceof xt_loop){
				$this->loop_xt($node, $value);
			}elseif(is_array($value)){
				return $this->set($node, $value);
			}else{
				throw new xtException('Niepoprawny drugi parametr metody <code>add</code>: <code>'.htmlspecialchars(print_r($value, 1)).'</code>', E_WARNING);
			}
		}else{
			return false;
		}
	}
	
	/**
	 * loop_xt
	 */
	public function loop_xt($node, $value){
		
		
		foreach($value as $loop_xt){
			$clone = $node->cloneNode(true);
			// dodaj wszystko z add_array
			foreach($loop_xt->add_array as $ref => $value){
				$this->add($this->getOneNode($ref, $clone), $value);
			}
			// dodaj wszystko z set_array
			foreach($loop_xt->set_array as $ref => $value){
				$this->set($this->getOneNode($ref, $clone), $value);
			}
			$node->parentNode->insertBefore($clone);
		}
		
		$this->remove($node);
	}

	/**
	 * pomocnicza funkcja głównej - zagnieżdżone pętle
	 */
	private function r($node, $all){
		$done=true;
		foreach($all as $row){
			if(!$clone=$node->cloneNode(true)){
				$done=false;
			}
			foreach($row as $key => $value){
				if($tt=$this->getOneNode($key, $clone)){
					if(!$this->add($tt, $value)){
						$done=false;
					}
				}
			}
			$this->remove_id($clone);
			if(!$node->parentNode->insertBefore($clone, $node)){
				$done=false;
			}
		}
		if(!$node->parentNode->removeChild($node)){
			$done=false;
		}
		return $done;
	}
	
	/**
	 * pętla drugiego rodzaju
	 * nie działa jak należy (?)
	 */
	public function loop($name, $count, $delete_sample=true){
		if($node=$this->getOneNode($name)){
			$str=$node->ownerDocument->savexml($node);
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
	 * nadawanie atrybutów obiektowi
	 * arguemtny w tablicy lub jako kolejene parametry funkcji
	 */
	public function set($name, $attributes){
		if($this->is_node($name)){
			return $this->setOne($name, $attributes);
		}else{
			$done=true;
			foreach($this->getNode($name) as $node){
				if(!$this->setOne($node, $attributes)){
					$done=false;
				}
			}
			return $done;
		}
	}
	
	public function setOne($node, $attributes){
		if(!is_array($attributes)){
			throw new xtException('Metoda <code>set</code> potrzebuje tablicy jako argumentu.', E_WARNING);
		}
		if($node=$this->getOneNode($node)){
			foreach($attributes as $attribute => $value){
				if(is_string($attribute) && is_scalar($value)){
					$value=(string)$value;
					$this->setAttribute($node, $attribute, $value);
				}
			}
		}else{
			return false;
		}
	}
	
	/**
	 * function setting attribute with magic attribute #text
	 */
	public function setAttribute($node, $attribute, $value){
		if($this->is_node($node)){
			if($attribute!=='#text'){
				$node->setAttribute($attribute, $value);
			}else{
				$child = $this->text2html($value);
				while($node->hasChildNodes()){
					$node->removeChild($node->firstChild);
				}
				$node->appendChild($child);
			}
		}
	}
	
	/**
	 * tworzenie elementow dom
	 * @param mixed object
	 * @param string name
	 * @param array attributes
	 */
	public function create($name, $str=0, $arguments=0){
		$node=$this->xml->createElement($name);
		if($str){
			$this->appendText($node, $str);
		}
		if($arguments){
			$this->set($node, $arguments);
		}
		return $node;
	}
	
	/**
	 * usuwanie obiektów
	 * zwraca usuwane dziecko
	 */
	public function remove($name){
		if($this->is_node($name)){
			return $this->removeOne($name);
		}else{
			$done=true;
			foreach($this->getNode($name) as $node){
				if(!$this->removeOne($node)){
					$done=false;
				}
			}
			return $done;
		}
	}
	public function removeOne($name){
		if($node=$this->getOneNode($name)){
			return $node->parentNode->removeChild($node);
		}else{
			return false;
		}
	}
	
	/**
	 * tak jak domowa, nie obsługuje pętli
	 * UWAGA!!! niekompatybilny z domowym!!!
	 */
	public function insertBefore($old, $new){
		if($old=$this->getOneNode($old)){
			if($this->is_node($new) && $this->is_node($old)){
				$old->parentNode->insertBefore($new, $old);
			}elseif($new=$this->text2html($new)){
				$old->parentNode->insertBefore($new, $old);
			}
		}else{
			return false;
		}
	}
	
	/**
	 * jw, tylko dodwawanie po,
	 * dodać sprawdzanie
	 * && $this->is_node($old->nextSibling)
	 */
	public function insertAfter($old, $new){
		if($old=$this->getOneNode($old)){
			//if(!$this->is_node($new)){
			//	$new=$this->text2html($new);
			//}
			
			$fragment=$this->xml->createDocumentFragment();
			$this->add($fragment, $new); # some problems with loop
			
			if($this->is_node(nextSibling)){
				$this->insertBefore($fragment, $old->nextSibling);
			}else{
				$old->parentNode->appendChild($fragment);
			}
		}else{
			return false;
		}
	}
	
	/**
	 * clone - return new fragment
	 */
	public function clone_node($node, $remove_parent=0){
		if($node=$this->getOneNode($node)){
			$fragment=$this->fragment($node->ownerDocument->savexml($node));
			return $fragment;
		}else{
			return null;
		}
	}
	
	/**
	 * something like
	 * {if condition}
	 *   object
	 * {/if}
	 *
	 * if condition isn't true - delete object
	 */
	public function condition(){
		if(func_num_args()>0 && func_num_args()%2==1){
			for($i=0, $count=func_num_args(); $i<$count; $i+=2){
				if(func_get_arg($i-1)){
					
				}
			}
		}
		if($node=$this->getOneNode($object)){
			if(!$condition){
				$this->remove($node);
			}
		}
	}
	
	/**
	 * zwraca listę obiektów w formie domnodelist, using xpath - faster!
	 * @param mixed klasa
	 * @param domnode parent
	 * @param string tag_nane
	 */
	public function getElementsByClassName($class, $parent=0, $name=0){
		if(!$parent){
			$parent=$this->root;
		}
		if(!$name){
			$name='*';
		}
		if(!is_array($class)){
			$class=array($class);
		}
		$query = '//'.$name.'[';
		foreach($class as $c){
			$query.='contains(concat(" ", @class, " "), " '.$c.' ") and ';
		}
		$query=substr($query, 0, -4);
		$query.=']';
		
		return $this->xpath->query($query, $parent);
	}
	
	/**
	 * getElementsByTagName, teoretycznie niepotrzebna
	 */
	public function getElementsByTagName($tag, $parent=0){
		if(!$parent){
			$parent=$this->root;
		}
		return $parent->getElementsByTagName($tag);
	}
	
	/**
	 * zwraca tag element o podanej nazwie
	 */
	public function getElementByTagName($tag, $parent=0, $count=0){
		if(!$parent){
			$parent=$this->root;
		}
		return $this->getElementsByTagName($tag, $parent)->item($count);
	}
	
	/**
	 * zwraca obiekt mając za parametr jego id
	 * @param str object
	 * @param object domnode 
	 * @param str node name
	 * @return object domnode
	 */
	public function getElementById($id, $parent=0, $node_name=0){
		if(!$parent){
			$parent=$this->root;
		}
		if(!$node_name){
			$node_name='*';
		}
		$query = './descendant-or-self::'.$node_name.'[@id="'.$id.'"]';
		$entries = $this->xpath->query($query, $parent);
		return $entries->item(0);
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
	
	public function node($css){
		return new node($this, $css);
	}
}

class xt_loop{
	public $add_array=array();
	public function add($ref, $value){
		$this->add_array[$ref]=$value;
	}
	
	public $set_array=array();
	public function set($ref, $value){
		$this->set_array[$ref]=$value;
	}
}
?>