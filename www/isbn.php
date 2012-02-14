<?php
require_once('../lib/ONIXCat.php');
require_once('../lib/RHCat.php');
require_once('../lib/BookGluttonONIXTagExpander.php');
require_once('../lib/ONIXProductElement.php');
require_once('../lib/ONIXProductToJSON.php');
require_once('../lib/ONIXMongoStore.php');
$onixcat = new ONIXCat();
$te = new BookGluttonONIXTagExpander(); // only necessary if short tags are input

/*

Web API to query or ingest ONIX data in MongoDb store


POST isbn.php

	?onix=[ONIX XML]

GET isbn.php?isbn=[product.RecordReference]


*/

$oms = new ONIXMongoStore();
$results = array();

if($_SERVER['REQUEST_METHOD']=='POST') {
	
	
	// ingest posted XML
	
	$input = $_POST['onix'];
	
	$file = sys_get_temp_dir().'/'.time().'-'.uniqid().'-upload.xml';
	
	if(file_put_contents($file, $input)) {
		error_log('saved '.strlen($input).' bytes');
	}
	
	$onixcat->setAnyTagHandler(

	      function ($reader) {

					 // the handler function is a closure, so be sure to call
					 // global for anything you want to reference outside its scope

					 global $te;
	
	         if($reader->depth==1) {

	            if($reader->name=='product'||$reader->name=='Product') {

				 				//error_log($reader->name);

	               if($reader->nodeType == XMLREADER::ELEMENT) {


										$xml = $reader->readOuterXML();

										$te->loadXML($xml);


										$xml_translated = $te->expandTags();


									//	error_log($xml_translated);

										try {


											$onix = new DomDocument();

											$onix->loadXML($xml_translated);


											if(!$product = simplexml_load_string($xml_translated, 'ONIXProductElement')) {
												throw new Exception('could not parse xml');
											}

											$oms = new ONIXMongoStore();
											$oms->setOverwriting(true);


											error_log('notification type is '.$product->NotificationType);	
												
											switch(intval($product->NotificationType)) {

												case 1: /* early (>6 mos) notice */

													$result = $oms->store($product);
													if(@$result['isnew']) {

														echo "stored early notice product:".$product->RecordReference."\n";											

													} else {

														echo "replaced early notice product:".$product->RecordReference."\n";											

													}

												break;
												case 2: /* advance (6 mo) notice */


													$result = $oms->store($product);
													
													if(@$result['isnew']) {
													
														echo "stored advance notice product:".$product->RecordReference."\n";											

													} else {

														echo "replaced advance notice product:".$product->RecordReference."\n";											

													}


												break;
												case 3: /* book-in-hand (approx. at pub date) */

													$result = $oms->store($product);
													if(@$result['isnew']) {

														echo "stored book-in-hand product:".$product->RecordReference."\n";											

													} else {

														echo "replaced book-in-hand product:".$product->RecordReference."\n";											

													}

												break;

												case 4: /* update record */

													$result = $oms->store($product);
													if(@$result['isnew']) {
													
														echo "stored update notification product:".$product->RecordReference."\n";											

													} else {

														echo "replaced update notification product:".$product->RecordReference."\n";											

													}


												break;
												case 5: /* delete record */

													// eg, do nothing
													echo "NOOP: received delete product:".$product->RecordReference."\n";											


												default:
													
													echo "NOOP: no code: ".$product->RecordReference."\n";	
												break;
											}
											$results[] = $result;


										} catch (Exception $e) {

											echo $e->getMessage();

										}


	               }

	            }              
	         }
	      }
	);
	try {
		
		// this could represent hundreds of records, so the return data will just contain success or failure?
		// how to know what's been processed? list of Mongo ObjectIds would be ideal?
		
		$onixcat->processONIX($file);
		
		echo json_encode($results);
		
	} catch (Exception $e) {
		echo $e->getMessage();
	}
	
} elseif ($_SERVER['REQUEST_METHOD']=='GET') {
	
	$record = $oms->getRecordByISBN($_GET['isbn']);
	echo json_encode($record);
	
} else {
	
	errMsg('Invalid request type, POST or GET only');
	
}


function errMsg($m)
{
	
	
	echo $m;
	
	
}


?>