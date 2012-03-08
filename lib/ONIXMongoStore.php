<?php

class ONIXMongoStore
{

	/*
	
		Class that can be extended or added to with ONIX-specific methods
	
	*/

	public function __construct($mongohost='localhost',
															$mongoport='27017',
															$dbname="onixtest",
															$collection="products")
	{
		$this->mongo = new Mongo();
		$this->overwrite = true;
		$this->m = $this->mongo;
		$this->db = $this->m->selectDb($dbname);
		$this->collection = $this->db->selectCollection($collection);
		$this->collection->ensureIndex('RecordReference');
	}

	public function getRecent($c=null)
	{
		$c = $this->_collection($c);
		$cursor = $c->find();
		$ro = array();
		foreach($cursor as $doc) {
			//error_log(print_r($doc,true));
			$ro[] = array(
					'RecordReference'=>$doc['RecordReference'],
					'Message'=>$doc['Title']['TitleText']. " (".$doc['RecordReference'].")",
					'Result'=>$doc);
		}
		return $ro;
	}
	
	public function getRecord($ref, $c=null)
	{
		$c = $this->_collection($c);
		$ro = $c->findOne(array('RecordReference'=>$ref."")); // must convert to string firs
		return $ro;
	}
	
	public function find($query, $c=null)
	{
		$c = $this->_collection($c);
		$cursor = $c->find($query);
		$ro = array();
		foreach($cursor as $doc) {
			//error_log(print_r($doc,true));
			$ro[] = array(
					'RecordReference'=>$doc['RecordReference'],
					'Message'=>$doc['Title']['TitleText']. " (".$doc['RecordReference'].")",
					'Result'=>$doc);
		}
		return $ro;
	}
	
	
	public function store($o, $c=null)
	{
		$c = $this->_collection($c);
		
		if($this->overwrite) {
				
			$ro = $c->findOne(array('RecordReference'=>$o->RecordReference."")); // must convert to string first

			// for some dumb reason, we input objects but get back arrays:
			if(is_array($ro)) {
				$c->update(array('RecordReference'=>$o->RecordReference), $o);
				$ro['isnew'] = true;
				return $ro;
				
			} else {

				$ro = $c->insert($o, array('safe'=>true));			
				$ro['isnew'] = true;
				return $ro;
			}

		} else {
			
			$ro = $c->insert($o, array('safe'=>true));
			$ro['isnew'] = true;
			return $ro;
			
		}
			
	}
	
	public function setOverwriting($bool=true)
	{
		
		
		$this->overwrite = $bool;
		
		
	}
	
	private function _collection($c)
	{
		if(!$c) {
			$c = $this->collection;
		} else {
			$c = $this->db->selectCollection($c);
		}
		if(!$c) {
			throw new Exception("Collection not found!");
		}
		return $c;
	}
	
	
	
	
}

?>