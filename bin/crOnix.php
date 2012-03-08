<?php

/**

Cron hook for automated processing of an ONIX feed or feeds

Pass as the --onix option either an onix file or a full dirpath containing
ONIX files. Every file in the directory will be checked for well-formedness
and if it passes, loaded into the XMLReader.

ONIX Product records will be stored in MongoDb and reserialized as
PHP or JSON object structures.

Pass true or yes to the --clean option to move all processed XML
(whether ONIX or not) to a processed subdirectory called crOnixed.

Eg.

	php ./crOnix.php --onix=/Users/asm/Desktop/NetGalley/ --clean=yes
	
*/



require_once('lib/ONIXCat.php');
require_once('lib/RHCat.php');
require_once('lib/BookGluttonONIXTagExpander.php');
require_once('lib/ONIXProductElement.php');
require_once('lib/ONIXProductToJSON.php');
require_once('lib/ONIXMongoStore.php');

$cli_opts = getopt(null, array(
		
		'onix:', 'clean::'
		
));
$dont_buffer_results = TRUE;
$onixfile_list = array();

$isbatch = FALSE;
if(!$onixfile = $cli_opts['onix']) exit;
echo "checking $onixfile ...\n";

// see if the passed path is a dir, if so, build a list

if(is_dir(realpath($onixfile))) {
	$dh = opendir(realpath($onixfile)); {
		while($e = readdir($dh)) {
			if($e != '.' && $e != '..' && is_file($onixfile.'/'.$e)) {
				
				// only add files to the list if they are valid XML
				
				if(is_xml($onixfile . '/'. $e)) {
					echo "Added XML file ".$e." to the list from directory ".$onixfile."\n";
					$onixfile_list[] = $onixfile . '/'.$e;
				}
			}
		}
	}
	$isbatch = TRUE;
	
} else {
	
	echo realpath($onixfile)." is not a directory\n";
	
}

echo "found ".count($onixfile_list)." XML files.\n";

// if we have neither file nor list of valid files,

if(!is_file($onixfile)&&count($onixfile_list)<1) {
	exit;
}

// create the cleanup dir if it's not there and we have a list

if($isbatch && !is_dir($onixfile . '/crOnixed/') && $cli_opts['clean']) {
	if(!is_writable($onixfile)) {
		$stat = stat($onixfile);
		throw new Exception('The directory '.$onixfile .' is not writable by this scripts uid '.posix_getpwuid($stat['uid']).' and you have requested relocation of processed files');
	}
	mkdir($onixfile . '/crOnixed/');
}

$onixcat = new ONIXCat();
$te = new BookGluttonONIXTagExpander(); // only necessary if short tags are input
$oms = new ONIXMongoStore('localhost',
													'27017',
													'onixtest',
													'products');
$results = array();

$onixcat->setAnyTagHandler(

      function ($reader) {

				 // the handler function is a closure, so be sure to call
				 // global for anything you want to reference outside its scope

				 global $te;
				 global $results;
         if($reader->depth==1) {
            if($reader->name=='product'||$reader->name=='Product') {
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
										switch(intval($product->NotificationType)) {

											case 1: /* early (>6 mos) notice */

												$result = $oms->store($product);
												if(@$result['isnew']) {

													$mess = "stored early notice product:".$product->RecordReference."\n";											

												} else {

													$mess = "replaced early notice product:".$product->RecordReference."\n";											

												}

											break;
											case 2: /* advance (6 mo) notice */


												$result = $oms->store($product);
												
												if(@$result['isnew']) {
												
													$mess = "stored advance notice product:".$product->RecordReference."\n";											

												} else {

												  $mess = "replaced advance notice product:".$product->RecordReference."\n";											

												}


											break;
											case 3: /* book-in-hand (approx. at pub date) */

												$result = $oms->store($product);
												if(@$result['isnew']) {

													$mess = "stored book-in-hand product:".$product->RecordReference."\n";											

												} else {

													$mess = "replaced book-in-hand product:".$product->RecordReference."\n";											

												}

											break;

											case 4: /* update record */

												$result = $oms->store($product);
												if(@$result['isnew']) {
												
													$mess = "stored update notification product:".$product->RecordReference."\n";											

												} else {

													$mess = "replaced update notification product:".$product->RecordReference."\n";											

												}


											break;
											case 5: /* delete record */

												// eg, do nothing
												$mess = "NOOP: received delete product:".$product->RecordReference."\n";											


											default:
												
												$mess = "NOOP: no code: ".$product->RecordReference."\n";	
											break;
										}
										error_log('result id:'.json_encode($result['_id']));
										@$id = $result['_id']->{'$id'};
										$res = array('RecordReference'=>$product->RecordReference,
																				'Message'=>$mess,
																				'Result'=>$id,
																				'Title'=>$product->Title->TitleText,
																				'ISBN'=>$product->ProductIdentifier->IDValue,
																				'Publisher'=>$product->Publisher->PublisherName,
																				'Imprint'=>$product->Imprint->ImprintName
																				);
										if($dont_buffer_results) {
											resMsg($res);
										} else {
											$results[] = $res											
										}



									} catch (Exception $e) {

										throw new Exception('Rethrown:'.$e->getMessage());

									}
               }
            }              
         }
      }
);

if($isbatch) {
	foreach($onixfile_list as $of) {
		try {
			$onixcat->processONIX($of);
			if($cli_opts['clean']) {
				rename($of, $onixfile . '/crOnixed/'.pathinfo($of, PATHINFO_BASENAME));
			}
		} catch (Exception $e) {
			//echo $e->getMessage();
			if($dont_buffer_results) {
				resMsg(array('error'=>$e->getMessage()));
			} else {
				$results = array('error'=>$e->getMessage());
			}
		}
	}
} else {
	try {
		$onixcat->processONIX($onixfile);
	} catch (Exception $e) {
		if($dont_buffer_results) {
			resMsg(array('error'=>$e->getMessage()));
		} else {
			$results = array('error'=>$e->getMessage());
		}
	}
}

if(!$dont_buffer_results) {
	foreach($results as $result) {
		resMsg($result);
	}
}

function resMsg($result)
{
	if(@$result['error']) {
		echo $result['error'];
	} else {
		echo "RecordReference ".$result['RecordReference']." added or updated for title ".$result['Title']." (".$result['ISBN']."), ".$result['Publisher']." [imprint: ".$result['Imprint']."] -- ObjectId is ".$result['Result']."\n";
	}
}

function errMsg($m)
{
	
	
	echo $m;
	
	
}

function is_xml($file)
{ // is the file well-enough formed XML to be parsed by simplexml?
	// this is potentially a memory hog?
 	if(@simplexml_load_file($file)) {
		return true;
	} else {
		return false;
	}
}

?>