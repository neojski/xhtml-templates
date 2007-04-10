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
		foreach($_POST as $name => $value){
			$this->core->set('input[name="'.$name.'"]:not([type="submit"])', array('value'=>$value));
		}
		foreach($_POST as $name => $value){
			if(!empty($value)){
				$value=str_replace(' ', '%20', $value);
				$this->core->set('select[name="'.$name.'"] option[value="'.$value.'"]', array('selected'=>'selected'));
			}
		}
	}
}
?>