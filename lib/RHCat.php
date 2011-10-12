<?php

/*

Simple wrapper to extend ONIXCat to
use zipped XML sources with unknown
filenames contained in them..

*/


require_once('ONIXCat.php');

class RHCat extends ONIXCat {
 
   
   public function go($zot)
   {
      // RH uses Zot files (zipped XML)
      // And the name of the contained
      // XML file is unpredictable, so 
      // first we read the index to get
      // a filename we can use in a
      // zip:// wrapper for XmlReader
      
      $this->log("reading zot file");
      $zh = zip_open($zot);
      if(!is_resource($zh)) {
         self::log('error '.$zh);
         exit;
      } else {
         while($e = zip_read($zh)) {
            // grab the XML filename 
            $xmlfile = zip_entry_name($e);
            self::log(zip_entry_name($e));
            self::log('catalog file is '.(zip_entry_filesize($e)/1000/1000).' MB');
            $this->processONIX('zip://' . $zot . '#'.$xmlfile);
         }
     }
     $this->log("done");
   }


   
   
   
}




?>