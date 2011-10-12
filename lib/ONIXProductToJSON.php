<?php

class ONIXProductToJSON {
	
	// this class is borken due to borken json_decode
	
	
	public function __construct()
	{
		$this->mydir = realpath(dirname(__FILE__)).'/';
	}
	
	public function load($onix)
	{
		$processor = new XSLTProcessor();
	  $xslDom = new DOMDocument();
		$url = $this->mydir.'xml-2-json.xsl';
		$xsl = file_get_contents($url);
	  @$xslDom->loadxml($xsl);
	  $processor->importStylesheet($xslDom);
	  $json = $processor->transformToXML($onix);
	
		$decoded = json_decode($json);
		$err = json_last_error();
			
		if($err==JSON_ERROR_NONE) {

			return $decoded;

		} elseif($err==JSON_ERROR_DEPTH) {
			
													throw new Exception("too deep..");
			
			
		} elseif($err==JSON_ERROR_STATE_MISMATCH) {
			
													throw new Exception("state mismatch..");
			
			
		}  elseif($err==JSON_ERROR_CTRL_CHAR) {
			
													throw new Exception("ctrl char err..");
			
			
		}  elseif($err==JSON_ERROR_SYNTAX) {
			
													throw new Exception("syntax err.");
			
		}  elseif($err==JSON_ERROR_UTF8) {
			
													throw new Exception("encoding err ..");
			
		}

		return $json;
	}
	
	
}



?>