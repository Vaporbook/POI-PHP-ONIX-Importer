<?php

require_once('ONIXCat.php');


class ORMCat extends ONIXCat {
	
	/*
		
		Example of a publisher-specific ONIXCat extension
		Can be used as a container for publisher-specific
		ONIX handling and methods...
	
	*/
	
	public function go($file)
	{
	   
		$this->processONIX($file);
		
	}
	
}

?>



