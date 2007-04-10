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
 
//if(!class_exists('xml')){
//	require_once('xt.xml.php');
//}
class html /*extends xml*/{
	public function __construct(&$xt){
		$this->core=$xt;
	}
	
	public function __get($name){
		if($name=='form'){
			return new form($this);
		}
	}
	
	public function title($str, $replace=false){
		if($title=$this->core->getElementByTagName('title')){
			if(!$replace){
				$title->nodeValue.=$str;
			}else{
				$title->nodeValue=$str;
			}
		}
	}
}

class form{
	public function __construct($html){
		$this->core=$html->core;
	}
	
	public function memory($css){
		foreach($this->core->getnode($css.' input[name][type="text"]') as $input){
			$name=$input->getAttribute('name');
			if(isset($_POST[$name])){
				$input->setAttribute('value',$_POST[$name]);
			}
		}
		
		foreach($this->core->getnode($css.' textarea[name]') as $textarea){
			$name=$textarea->getAttribute('name');
			if(isset($_POST[$name])){
				$this->core->add($textarea, $_POST[$name]);
			}
		}
		/* powinno być rozwiązane inaczej - tak jest niewydajnie */
		foreach($_POST as $name => $value){
			foreach($this->core->getnode($css.' select[name="'.$name.'"] option[value="'.$value.'"]') as $select){
				$select->setAttribute('selected','selected');
			}
		}
	}
}
?>