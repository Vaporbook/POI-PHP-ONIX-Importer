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
	
	public function searchProductIds($q)
	{
		
		 return $this->find(array(
				'ProductIdentifier.IDValue'=>$q
			));
		
		
	}
	
	public function findByProductId($isbn, $type=15)
	{ 
		// usually used with an ISBN-13
		
		return $this->find(array(
				'$and'=>array(
					(object) array(
						"ProductIdentifier.IDValue"=>"$isbn"
					),
					(object) array(
						"ProductIdentifer.ProductIDType"=>"$type"
					)
				)
			));
			
	}
	
	public function searchRecordRefTitleISBN($q)
	{
		// JS where clause for regex matching
		$whc=<<<END
			this.Title.TitleText.match(/$q/i) || this.RecordReference.match(/$q/i)
END
;
		return $this->find(array(
			  '$or'=>array(
					(object) array( // match any part of title or rr
						'$where'=>$whc
					),
					(object) array( // also exact match on ISBN
						"ProductIdentifier.IDValue"=>"$q"
					),
					(object) array( // or exact match on title
						"Title.TitleText"=>"$q"
					)
				 )/*,
				'$where'=>$whc*/
		));
		
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