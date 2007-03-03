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
	public function __construct($file=0, $is_string=0){
		$this->find_plugins();
		$this->start_time=$this->microtime_float();
		$this->debug=false;
		$this->getnode_method=2;
		$this->strict=false;
		if($file){
			$this->load($file, $is_string);
		}
	}
	
	private function find_plugins(){
		$this->plugins=array();
		foreach(glob(dirname(__FILE__).'/*.php') as $file){
			$file=substr(basename($file, '.php'), 3);
			if($file!='class'){
				$this->plugins[]=$file;
			}
		}
	}
	
	/**
	 * include plugins
	 */
	public function __get($name){
		if(in_array($name, $this->plugins)){
			if(!isset($this->$name)){
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
	 * check parsing time
	 */
	private function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	/**
	 * @param str filename/template
	 */
	public function load($file, $is_string=0){
		if(!$is_string){
			if(file_exists($file)){
				$this->template=file_get_contents($file);
			}else{
				throw new xtException('Plik szablonu nie istnieje!', E_ERROR);
			}
		}else{
			$this->template=$file;
		}
		
		$this->template=str_replace(array('<![CDATA[', ']]>'), '', $this->template);
		
		$this->xml=new DOMDocument();
		
		//$this->xml->resolveExternals=true;
		
		if(preg_match('#<\?xml[^>]+encoding="([^"]+)"[^>]*?>#', $this->template, $encoding)){
			$this->encoding=$encoding[1];
		}elseif(preg_match('#<meta[^>]+content="[^=]+=(.*?)"[^>]*>#s', $this->template, $encoding)){
			$this->template='<?xml version="1.0" encoding="'.$encoding[1].'"?>'.$this->template;
			$this->encoding=$encoding[1];
		}else{
			throw new xtException('Brak ustawionego kodowania', E_ERROR);
		}
		
		$this->xml->loadxml($this->template);
		
		$this->body=$this->xml->getElementsByTagName('body')->item(0);
		$this->head=$this->xml->getElementsByTagName('head')->item(0);
		$this->root=$this->xml->documentElement;
		
		$this->xml->formatOutput=true;
		$this->xml->standalone=false;
		$this->useXML=$this->xml();
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
	
	/**
	 * display
	 * @param bool debug (if true displays plain source code)
	 * magic classes
	 * display xhtml or html
	 */	
	public function display($mime=0){
		/************* xpath tests *****************
		$xpath = new DOMXPath($this->xml);
		$query = '//li/../li[position()=3]/self::li';
		$objects = $xpath->query($query);
		
		echo 'Obiekty<ul>';
		foreach($objects as $object){
			echo '<li><code>'.$object->nodeName.'</code>:<code>'.$object->nodeValue.'</code></li>';
		}
		echo '</ul>';
		
		/************* xpath tests *****************/
		foreach($this->getElementsByClassName('remove_id') as $node){
			$node->removeAttribute('id');
		}
		
		foreach($this->getElementsByClassName('remove_parent') as $node){
			$this->removeParent($node);
		}
		
		foreach($this->getElementsByClassName('remove_class') as $node){
			$node->removeAttribute('class');
		}
		
		$this->add($this->body, '<p id="stopka">Ta strona została wygenerowana właśnie dzięki xt. Czas wykonywania skryptu to '.($this->microtime_float()-$this->start_time).'s</p>');
		
		$mime_tab=array(
			2 => 'application/xhtml+xml',
			'text/html',
			'text/xml',
			'application/xml',
			'application/rss+xml',
			'application/atom+xml'
		);

		if($this->debug){
			echo '<pre><code>'.htmlspecialchars($this->xml->savexml()).'</code></pre>';
		}elseif($mime==0){
			return $this->xml->savexml();
		}elseif($mime==1){
			if($this->useXML){
				header('Content-Type: application/xhtml+xml; charset='.$this->encoding);
				echo $this->xml->savexml();
			}else{
				header('Content-Type: text/html; charset='.$this->encoding);
				echo preg_replace(array('#<!DOCTYPE[^>]+>#', '#xml:lang="[^"]+"#', '#xmlns="[^"]+"#'), array('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">', '', ''), $this->xml->saveHTML());
			}
		}else{
			header('Content-Type: '.$mime_tab[$mime].'; charset='.$this->encoding);
			echo $this->xml->savexml();
		}
	}
	
	public function __tostring(){
		return $this->display(0);
	}
	
	/**
	 * delete parent of the node
	 * @param domnode node
	 */
	public function removeParent($name){
		if($node=$this->getOneNode($name)){
			foreach($node->childNodes as $child){
				$node->parentNode->insertBefore($child->cloneNode(true), $node);
			}
			$this->remove($node);
		}
	}
	
	/**
	 * replace parent node with new_name
	 * @param mixed old
	 * @param string new
	 * @param array attributes
	 ^^^^^^^^^^^^^^^^^^^^^^^^^
	 */
	public function replaceParent($name, $new_name, $attributes=0){
		if($node=$this->getOneNode($name)){
			$old=$this->removeParent($node);
			$new=$this->create($new_name, 0, $attributes);
			$new->appendChild($old);
			$this->insertBefore($new, $node);
			$this->remove($node);
		}
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
	 * na razie miniaturowa funkcja "obsługująca" błędy uniemożliwiające dalsze działanie
	 * TODO:
	 *  podział na błędy uniemożliwiające działanie i ostrzeżenia, np. nie znaleziono obiektu
	 */
	public function error($str){
		die('<strong>'.$str.'</strong>');
	}
	
	/**
	 * dodanie zawartości tekstowej obiektowi
	 * @param object domelement
	 * @param str append-text
	 */
	public function appendText($node, $str){
		if($this->is_node($node)){
			if($child=$this->text2html($str)){
				$node->appendChild($child);
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
			$fragment->appendXML($str);
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
	public function getnode($name, $parent=0){
		if($this->is_node($name)){
			return $name;
		}else{
			$str=$this->getnode;
			if(!$parent){
				$parent=$this->root;
			}
			return $str->get($name, $parent);
		}
	}
	
	private function getOneNode($name, $parent=0){
		if($this->is_node($name)){
			return $name;
		}else{
			$nodes=$this->getNode($name, $parent); // getNode($name.':first-of-type', $parent);
			return $nodes->item(0);
		}
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
		$xpath = new DOMXPath($this->xml);
		$query = './descendant-or-self::'.$node_name.'[@id="'.$id.'"]';
		$entries = $xpath->query($query, $parent);
		return $entries->item(0);
	}
	
	/**
	 * usuwa id wszystkich dzieci i danego obiektu
	 * @param mixed object
	 */
	public function remove_id($name){
		if($node=$this->getOneNode($name)){
			$xpath = new DOMXPath($this->xml);
			$query = './descendant-or-self::*[@id]';
			$entries = $xpath->query($query, $node);
			foreach($entries as $node){
				$node->removeAttribute('id');
			}
		}
	}
	
	/**
	 * głowna funkcja dodająca wartości/parametry, obsługująca pętle
	 */
	public function add($name, $value){
		if($node=$this->getOneNode($name)){
			if(is_array($value) && is_array($value[0])){
				//$node->removeAttribute('id');
				$this->r($node, $value);
			}elseif(is_array($value)){
				$this->set($node, $value);
			}elseif(is_string($value)){
				$this->appendText($node, $value);
			}elseif($this->is_node($value)){
				$node->appendChild($value);
			}elseif($value instanceof fragment){
				$node->appendChild($value->s);
			}
		}
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
	 * nie działa jak należy
	 ^^^^^^^^^^^^^^^^^^^^^^^^^^^
	 */
	public function loop($name, $count, $delete_sample=true){
		if($node=$this->getOneNode($name)){
			$str=$this->xml->savexml($node);
			$count=(int)$count;
			if($count>0){
				for($i=0; $i<$count; $i++){
					$fragment=$this->fragment($str);
					
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
	public function set($node, $attributes){
		if($node=$this->getOneNode($node)){
			foreach($attributes as $attribute => $value){
				if($value!==false){
					if($attribute!='#text'){
						$node->setAttribute($attribute, $value);
					}else{
						$this->appendText($node, $value);
					}
				}
				
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
	 */
	public function remove($name){
		if($node=$this->getOneNode($name)){
			$node->parentNode->removeChild($node);
		}
	}
	
	/**
	 * alias funkcji remove
	 */
	public function delete($name){
		$this->remove($name);
	}
	
	/**
	 * tak jak domowa, nie obsługuje pętli
	 * TODO:
	 *  obsługa pętli - wykorzystywanie funkcji add
	 */
	public function insertBefore($old, $new){
		if($old=$this->getOneNode($old)){
			if($this->is_node($new) && $this->is_node($old)){
				$old->parentNode->insertBefore($new, $old);
			}elseif($new=$this->text2html($new)){
				$old->parentNode->insertBefore($new, $old);
			}
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
			
			// jeśli jest ktoś za
			if($this->is_node(nextSibling)){
				$this->insertBefore($fragment, $old->nextSibling);
			}else{
			// jeśl nie ma nikogo za ;-)
				$old->parentNode->appendChild($fragment);
			}
		}
	}
	
	/**
	 * clone - return new fragment
	 */
	public function clone_node($node, $remove_parent=0){
		if($node=$this->getOneNode($node)){
			$fragment=$this->fragment($this->savexml($node));
			return $fragment;
		}
	}
	
	/**
	 * funkcje dodatkowe, htmlowe
	 */
	public function link($url, $rel, $title=false, $type=false, $media=false){
		$link=$this->create('link', null, array('rel'=>$rel, 'href'=>$url, 'title'=>$title, 'type'=>$type, 'media'=>$media));
		$this->head->appendChild($link);
	}
	
	public function cssFile($url, $title=false, $media=false){
		$this->link($url, 'stylesheet', $title, 'text/css', $media);
	}
	
	public function jsFile($url, $alternate_code=null){
		$this->head->appendChild($this->create('script', $alternate_code, array('type'=>'text/javascript','src'=>$url)));
	}

	/**
	 * @param str css-input
	 * @param bool dodać-nowy-tag-style
	 */
	public function css($str, $new=0){
		if($new){
			$this->head->appendChild($this->create('style', '<![CDATA['. trim($str) .']]>', array('type'=>'text/css')));
		}else{
			if($style=$this->getElementByTagName('style', $this->head)){
				$style->firstChild->data.=trim($str);
			}else{
				$this->css($str, 1);
			}
		}
	}
	
	/**
	 * @param str kod_javascript
	 * @param bool add_new_tag
	 */
	public function js($str, $new=0){
		if($new){
			$this->head->appendChild($this->create('script', '<![CDATA['. trim($str) .']]>', array('type'=>'text/javascript')));
		}else{
			if($script=$this->getElementByTagName('script', $this->head)){
				$script->firstChild->data.=trim($str);
			}else{
				$this->js($str, 1);
			}
		}
	}

	/**
	 * set style to the object
	 * @param mixed node
	 * @param str style
	 */
	public function setStyle($name, $style){
		if($node=$this->getOneNode($name)){
			$node->setAttribute('style', $style);
		}
	}
	
	/**
	 * create document-fragment
	 * @param str template_fragment
	 */
	public function fragment($str=false){
		$fragment=$this->fragment;
		if($str){
			$fragment->load($str);
		}
		return $fragment;
	}
	
	/**
	 * zwraca listę obiektów w formie domnodelist
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
		
		$query = $name.'[';
		
		foreach($class as $c){
			$query.='contains(concat(" ", @class, " "), " '.$c.' ") and ';
		}
			
		$query=substr($query, 0, -4);
			
		$query.=']';
			
		$xpath = new DOMXPath($this->xml);
		return $xpath->query($query);
	}
}

?>