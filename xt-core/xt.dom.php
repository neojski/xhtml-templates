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

class dom{
	public function __construct(&$xt){
		$this->core=$xt;
		$this->xml=new mydom();
	}
	
	public function is_node($node){
		return substr(print_r($node,true),0,3)==='DOM';
	}
	
	public function load($template){
		$this->template=file_get_contents($template);
		
		$this->check_encoding();
		
		/* usuń xmlns, które tymczasem psuje wszystko */
		$this->template=preg_replace('#xmlns="[^"]+"#', '', $this->template);
		
		$this->xml->loadxml($this->template);
		
		$this->body=$this->xml->getElementsByTagName('body')->item(0);
		$this->head=$this->xml->getElementsByTagName('head')->item(0);
		$this->root=$this->xml->documentElement;
		
		$this->xml->formatOutput=true;
		$this->xml->standalone=false;
		$this->useXML=$this->xml();
	}
	
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
		$mime_tab=array(
			2 => 'application/xhtml+xml',
			'text/html',
			'text/xml',
			'application/xml',
			'application/rss+xml',
			'application/atom+xml'
		);

		if($this->core->debug){
			echo '<pre><code>'.htmlspecialchars($this->xml->savexml()).'</code></pre>';
		}elseif($mime==0){
			return $this->xml->savexml();
		}elseif($mime==1){
			if($this->useXML){
				if(!headers_sent()){
					header('Content-Type: application/xhtml+xml; charset='.$this->encoding);
				}
				echo $this->xml->savexml();
			}else{
				if(!headers_sent()){
					header('Content-Type: text/html; charset='.$this->encoding);
				}
				echo preg_replace(array('#<!DOCTYPE[^>]+>#', '#xml:lang="[^"]+"#', '#xmlns="[^"]+"#'), array('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">', '', ''), $this->xml->saveHTML());
			}
		}else{
			if(!headers_sent()){
				header('Content-Type: '.$mime_tab[$mime].'; charset='.$this->encoding);
			}
			echo $this->xml->savexml();
		}
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
	 * check if object $node is domelement or domdocumentfragment or dom-sth
	 * @param mixed
	 * @return bool is_dom_object
	 */
	
	
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
	public function getnode($name, $parent=0, $count=false){
		if(!isset($this->template)){
			$this->load();
		}
		if($this->is_node($name)){
			return $name;
		}else{
			$str=$this->core->getnode;
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
	public function add($name, $value){
		if(!isset($this->template)){
			$this->load();
		}
		if($node=$this->getOneNode($name)){
			if(is_array($value) && isset($value[0]) && is_array($value[0])){
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
			}else{
				throw new xtException('Niepoprawny drugi parametr metody <code>add</code>: <code>'.htmlspecialchars(print_r($value, 1)).'</code>', E_WARNING);
			}
		}else{
			return false;
		}
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
		}else{
			return false;
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
		}else{
			return false;
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
		if($node=$this->getOneNode($name)){
			return $node->parentNode->removeChild($node);
		}else{
			return false;
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
			
			// jeśli jest ktoś za
			if($this->is_node(nextSibling)){
				$this->insertBefore($fragment, $old->nextSibling);
			}else{
			// jeśl nie ma nikogo za ;-)
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
			$fragment=$this->fragment($this->savexml($node));
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
	public function condition($condition, $object){
		if($node=$this->getOneNode($object)){
			if(!$condition){
				$this->remove($node);
			}
		}
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
		return $xpath->query($query, $parent);
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
}
?>