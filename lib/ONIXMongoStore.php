<?php

class ONIXMongoStore
{

	/*
	
		Class that can be extended or added to with ONIX-specific methods
	
	*/

	public function __construct($mongohost='localhost', $mongoport='27017')
	{
		$this->mongo = new Mongo();
		$this->overwrite = true;
	}
	
	
	public function store($o)
	{
		
		$m = $this->mongo;
		$db = $m->onixtest;
		$collection = $db->products;
		
		if($this->overwrite) {
			
			$ro = $collection->findOne(array('RecordReference'=>$o->RecordReference."")); // must convert to string first
 			
			// for some dumb reason, we input objects but get back arrays:
			if(is_array($ro)) {
				$collection->update(array('RecordReference'=>$o->RecordReference), $o);
				return 2;
			}

		} else {
			
			$collection->insert($o);			
			return 1;
			
		}
			
	}
	
	public function setOverwriting($bool=true)
	{
		
		
		$this->overwrite = $bool;
		
		
	}
	
	
	
}

?>