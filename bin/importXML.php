<?php

require_once('lib/ONIXCat.php');
require_once('lib/RHCat.php');
require_once('lib/BookGluttonONIXTagExpander.php');
require_once('lib/ONIXProductElement.php');
require_once('lib/ONIXProductToJSON.php');
require_once('lib/ONIXMongoStore.php');

$started = time();
$mydir = realpath(dirname(__FILE__)).'/../';

$file = $mydir.'SampleData/Penguin_ONIX_21.xml';
//$file = $mydir.'SampleData/RH-20090301_211018-FULLCAT-COMPREHENSIVE-21.ZOT';

$format = (preg_match('/\.xml$/i', $file)) ? 'XML' : 'ZOT';

if(!file_exists($file)) {
	throw new Exception("Cannot find file:".$file);
}

if($format == 'XML') {

	$onixcat = new ONIXCat();

} else {
	
	$onixcat = new RHCat();
	
}

$te = new BookGluttonONIXTagExpander(); // only necessary if short tags are input

$onixcat->setAnyTagHandler(
	
      function ($reader) {
	
				 // the handler function is a closure, so be sure to call
				 // global for anything you want to reference outside its scope
				
				 global $te;
				
         if($reader->depth==1) {

            if($reader->name=='product') {
               
               if($reader->nodeType == XMLREADER::ELEMENT) {
  

									$xml = $reader->readOuterXML();

									$te->loadXML($xml);
									
							
									$xml_translated = $te->expandTags();

								
									try {
										
										
										$onix = new DomDocument();
									
										$onix->loadXML($xml_translated);

										
										if(!$product = simplexml_load_string($xml_translated, 'ONIXProductElement')) {
											throw new Exception('could not parse xml');
										}

										$oms = new ONIXMongoStore();
										$oms->setOverwriting(true);

/*

Notification Type logic:

You may want to act differently on records with different notification types, and this action
may also vary according to which publisher the record comes from. The notification types are:

(FROM ONIX 2.1 spec)

01	Early notification: use for a complete record issued earlier than approximately six months before publication
02	Advance notification (confirmed): use for a complete record issued to confirm advance information approximately six months before publication; or for a complete record issued after that date and before information has been confirmed from the book-in-hand.
03	Notification confirmed from book-in-hand: use for a complete record issued to confirm advance information using the book-in-hand at or just before actual publication date; or for a complete record issued at any later date.
04	Update: use for any update to a part of the record which is sent without re-issuing the complete record.
05	Delete: use when sending an instruction to delete a record which was previously issued. Note that a delete instruction should NOT be used when a product is cancelled, put out of print, or otherwise withdrawn from sale: this should be handled as a change of availability status, leaving the receiver to decide whether to retain or delete the record. A delete instruction is only used when there is a particular reason to withdraw a record completely, eg because it was issued in error.


*/

										switch(intval($product->NotificationType)) {
											
											case 1: /* early (>6 mos) notice */
											
												$result = $oms->store($product);
												if($result==1) {

													echo "stored early notice product:".$product->RecordReference."\n";											

												} else if($result==2){
													
													echo "replaced early notice product:".$product->RecordReference."\n";											

												}

											break;
											case 2: /* advance (6 mo) notice */
											
											
												$result = $oms->store($product);
												if($result==1) {

													echo "stored advance notice product:".$product->RecordReference."\n";											

												} else if($result==2) {
																										
													echo "replaced advance notice product:".$product->RecordReference."\n";											
													
												}

											
											break;
											case 3: /* book-in-hand (approx. at pub date) */
											
												$result = $oms->store($product);
												if($result==1) {

													echo "stored book-in-hand product:".$product->RecordReference."\n";											

												} else if ($result==2) {

													echo "replaced book-in-hand product:".$product->RecordReference."\n";											

												}
											
											break;
											
											case 4: /* update record */
											
												$result = $oms->store($product);
												if($result==1) {

													echo "stored update notification product:".$product->RecordReference."\n";											

												} else if($result==2) {

													echo "replaced update notification product:".$product->RecordReference."\n";											
													
												}
										
											
											break;
											case 5: /* delete record */
											
												// eg, do nothing
												echo "NOOP: received delete product:".$product->RecordReference."\n";											
												
										
											default:
											break;
										}

										
									} catch (Exception $e) {
									
										echo $e->getMessage();
									
									}


               }

            }              
         }
      }
);

if($format == 'XML') {
	$onixcat->processONIX($file);
} else {
	$onixcat->go($file);
}

$ended = time();
$d = ($ended - $started);

echo "Took ".$d." seconds, or ".($d/60)." minutes\n";


