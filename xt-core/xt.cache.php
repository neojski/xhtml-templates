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
	public function __construct(&$xt){
		$this->core=$xt;
		$this->createCache=false;
		$this->objects=array();
		
		$this->load();
	}
	
	public function load(){
		if(file_exists('../templates/'.$this->core->name.'.xc')){
			$this->objects=unserialize(file_get_contents('../templates/'.$this->core->name.'.php'));
		}else{
			echo 'nie';
		}
	}
	
	public function add($css, $value){
		$this->createCache=true;
		$this->core->dom->add($css, '<?php /*kod php*/'.$value.'?>');
		$this->objects[]=array($css, 'obiekt');
	}
	
	public function __destruct(){
		if($this->createCache){
			file_put_contents($this->core->filename, $this->core->dom->savexml());
		}
	}
}