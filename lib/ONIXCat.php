<?php 
class ONIXCat {

	/*
	
		Main ONIX processor - uses XMLReader (should it just extend it instead?)
		
	*/

	
	public function __construct()
  {
  
     $this->reader = null;
     $this->tag = array(
        
        'a001' => array(
           'o'=>null,
           'c'=>null
           )
        
        );
     $this->anytag = null;

  
  }


  public function processONIX($file)
  {
     // open the XML file
     $this->reader = new XMLReader();
     if(!$this->reader->open($file)) {
				throw new Exception('XMLReader failed to open file '.$file.' for reading');
			}
     while ($this->reader->read()) {
        $this->handleTagEvent();
     }
  }

  
  public function handleTagEvent()
  {
     
     if($this->anytag) {
        $f = $this->anytag;
        $f($this->reader);
     }
     if ($this->reader->nodeType == XMLREADER::ELEMENT) {
        if($f = @$this->tag[$this->reader->name]['o']) {
           $f($this->reader->expand());
        }
     } else if ($this->reader->nodeType == XMLREADER::END_ELEMENT) {
        if($f = @$this->tag[$this->reader->name]['c']) {
           $f($this->reader->expand());
        }
     }
  }

  public function setAnyTagHandler($fun)
  {
     $this->anytag = $fun;
  }
  
  public function setStartTagHandler($tagname, $fun)
  {
     $this->tag[$tagname]['o'] = $fun;
  }

  public function setEndTagHandler($tagname, $fun)
  {
     $this->tag[$tagname]['c'] = $fun;
  }
  
  public function log($msg)
  {
     error_log($msg);
  }
	

   
}
?>