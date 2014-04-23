<?php
/**
 * CMF
 *
 * Contains all methods which are required to transfer data to the CMF. Furthermore it includes methods to create Semantic Web tripes and to display them using the ConnectME ontology.
 *
 * IMPORTANT: PEAR HTTP_Request library is used in this class (http://pear.php.net/package/HTTP_Request8)
 * IMPORTANT: ARC2 ARC2 library is used in this class (https://github.com/semsol/arc2)
 * IMPORTANT: Graphite Graphite library is used in this class (http://graphite.ecs.soton.ac.uk/)
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */
class CMF {
	
  /**
   * Settings of the Hyper Annotation Video Suite
   *
   * @var object Contains all annotation tool settings
   */
	public $settings	= NULL;
	
  /**
   * Language strings of the Hyper Annotation Video Suite
   *
   * @var object Contains all language strings of the annotation tool
   */
	public $language = NULL;

	/**
   * Constructor
   *
   * Creates an instance of the cmf class and returns the settings and language object from it. They are passed to local parameters.
   *
   * Fills @var settings and @var language
   *
   * @return bool true
   */
  public function __construct() {
		try {
			require_once (__ROOT__. '/core/class/core.class.php');
			$core = new Core();
			$this->language = $core->language;
			$this->settings = $core->settings;
			return true;
		}
		catch (Exception $e) {
			print "Error: " .$e;
		}
  }
	
	/**
   * Generate Annotation Object
   *
   * Reads all data from a specified annotation file on the server and serialized its data using the ConnectME ontology. The output type can be specified by selecting a serializer (n3 triples, turtle, RDF/XML).
   *
   * @param string $annotation_file Path of the currently used annotation file on the server
	 * @param array $video_data Video array which contains the currently selected video ID and its locators
	 * @param string $selected_serializer Allows selecting a serializer for data display (Supported: n3, rdfxml, turtle)
   * @return string Contains the serialized data using the ConnectME ontology
   * @throws PHP Exception
   */
	public function generateAnnotationObject($annotation_file, $video_data, $selected_serializer) {
		try {
			include_once (__ROOT__. '/libraries/arc2/ARC2.php');
			include_once (__ROOT__. '/libraries/graphite/Graphite.php');
			
			$json_obj = $this->parseJson($annotation_file);
			
			$graph = new Graphite();
			// add required namespaces (which aren't already loaded by default)
			$graph->ns("ma", "http://www.w3.org/ns/ma-ont#");
			$graph->ns("cma", "http://connectme.at/ontology#");
			$graph->ns("oac", "http://www.openannotation.org/ns/");
			$graph->ns("cmo", "http://purl.org/twc/ontologies/cmo.owl#");
			$graph->ns("oax", "http://www.w3.org/ns/openannotation/extensions/");
			$uri = "http://connectme.salzburgresearch.at/CMF/resource/video/yoovis/6306";
			
			// create graph
			$graph->resource($json_obj->video[0]->uri);
			$graph->addCompressedTriple($json_obj->video[0]->uri, 'rdf:type', 'ma:MediaResource');
			foreach ($video_data['source'] as $video_source) {
				$graph->addCompressedTriple($json_obj->video[0]->uri, 'ma:locator', $video_source['url'], 'xsd:anyURI');
			}
			
			$element_counter = 1;
			
			$timestamp = time();
			
			// add detailled fragment and annotation information
			foreach ($json_obj->annotations as $annotation) {
				
				if ($annotation->active->value == 1) {
				
					// add framgents
					if (($annotation->annotation->value == 'null') || ($annotation->annotation->value == '')) {
						$element_id = md5($element_counter + microtime());
						$tmp_annotation_uri = $this->settings->annotation_uri.$element_id;
						$annotation->annotation->value = $tmp_annotation_uri;
					}
					$annotation->annotation->value = str_replace("annotation", "fragment", $annotation->annotation->value);
					
					$graph->addCompressedTriple($json_obj->video[0]->uri, 'ma:hasFragment', $annotation->annotation->value);
					
					// fragment details
					$graph->addCompressedTriple($annotation->annotation->value, 'rdf:type', 'ma:MediaFragment');
					
					// generate locator suffix
					$suffix = '#';
					if (isset($annotation->spatial) && isset($annotation->spatial->value)) {
						// spatial region set
						// var_dump($annotation->spatial->value);
						$tmp_spatial = explode(',', $annotation->spatial->value);
						$spatial_set = true;
						foreach ($tmp_spatial as $val) {
							if (!preg_match('/[0-9]+/', $val)) {
								$spatial_set = false;
							}
						}
						if ($spatial_set == true) {
							$suffix .= 'xywh=percent:' .$tmp_spatial[0]. ',' .$tmp_spatial[1]. ',' .$tmp_spatial[4]. ',' .$tmp_spatial[5];
						}
					}
					if (isset($annotation->starttime->value)) {
						// is spatial region set?
						if (strlen($suffix) > 1) {
							// add &
							$suffix	.= '&';
						}
						$suffix	.= 't='.$this->convertToSeconds($annotation->starttime->value);
						if ($annotation->endtime->value != '') {
						$suffix	.= ','.$this->convertToSeconds($annotation->endtime->value);
						}
					}
					
					foreach ($video_data['source'] as $video_source) {
						$graph->addCompressedTriple($annotation->annotation->value, 'ma:locator', $video_source['url'].$suffix, 'xsd:anyURI');
					}
					
					if ($annotation->relation->value != '') {
						$graph->addCompressedTriple($annotation->annotation->value, $annotation->relation->value, $annotation->resource->value);
					}
					
					// annotation details
					$pattern = '/' .preg_quote('bookmark', '/') . '/';
					if (preg_match($pattern, strtolower($annotation->annotationtype->value))) {
						$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'rdf:type', 'oax:Bookmark');
					}
					else {
						$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'rdf:type', 'oac:Annotation');
					}
					$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'oac:hasTarget', $annotation->annotation->value);
					$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'dct:creator', $annotation->creator->value);
					$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'dct:created', date("Y-m-d", $timestamp). 'T' .date("H:i:s", $timestamp). '' .date("P", $timestamp), 'xsd:dateTime');
					if (($annotation->preferredlabel->value != '') && ($annotation->preferredlabel->value != 'null')) {
						$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'cma:preferredLabel', $annotation->preferredlabel->value, 'xsd:string');
					}
					$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'oac:hasBody',  $annotation->resource->value);
					
					$graph->addCompressedTriple($annotation->creator->value, 'rdf:type', 'foaf:Person');
							
					$element_counter++;
				
				}
				
			}

			$return = $graph->serialize($selected_serializer);
		
		}
		catch (Exception $e) {
			print "Error: " .$e;
		}
		
		return $return;
	}
	
	/**
   * Validate URI
   *
   * Tries to get response from a specified URI/URL using the private method getResponse
   *
   * @param string $video_uri Speified URI/URL
   * @return bool True if the URI/URL responded and returned HTTP 200
	 * @return string Error message if URI/URL did not respond
   * @throws PHP Exception
   */
	public function validateURI($video_uri) {
		// check if the URL responding
		$response = $this->getResponse($video_uri);
		if (is_array($response)) {
			// no error => url is working
			if (eregi('200', $response['0'])) {
				// url is responding
				return true;
			}
			else {
				// url not found/error => throw error message
				$error_message = 'ERROR: The selected URL is not responding!';
				$log_message = date('Y-m-d - H:i:s', time()). ': ERROR URL not responding: ' .$video_uri. '!';
				$this->toLog($log_message);
				$this->unloadVideo('video_uri');
				return $error_message;
			}
		}
		else {
			// return error
			return $response;
		}
	}
	
	/**
   * Get Response
   *
   * Tries to get response from a specified URI/URL
   *
   * @param string $input_url Speified URI/URL
   * @return array Received headers of specified URI/URL
   * @throws PHP Exception
   */
	private function getResponse($input_url) {
		// Check if the URL responding
		try {
			// remove spaces
			$input_url = trim($input_url);
			$input_url = ltrim($input_url);
			// Check if the URL responding
			$response = get_headers($input_url, 1);
		}
		catch (Exception $e) {
			$response[] = $e;
		}
		return $response;
	}
	
	/**
   * Parse Json
   *
   * Parses a annotation file which is located on the server and tries to extract its containing JSON string.
   *
   * @param string $input Annotation file name
   * @return object JSON object based on file content
   * @throws PHP Exception
   */
	private function parseJson($input) {
		// parse json string and return eiter json object or error message
		try {
		  
		  if (file_exists($this->settings->annotation_path.$input)) {
			  $file_content = '';
			  $file = fopen($this->settings->annotation_path.$input, 'r');
		  
			  while (!feof($file)) {
			    $file_content .= fgets($file);
			  }
			  
			  fclose($file);
			  
			}
			else {
				throw new Exception('Annotation file not found! <em>' .$this->settings->annotation_path.$input. '</em>');
			}
		  
			$json = json_decode(stripslashes($file_content));
			if (!is_object($json)) {
				throw new Exception('String could not get converted into JSON object!');
			}
		} catch (Exception $e) {
    		$json = 'ERROR: ' .$e->getMessage();
		}
		return $json;
		
	}
	
	/**
   * Convert to seconds
   *
   * Converts an input time string from hh:mm:ss or mm:ss into seconds
   *
   * @param string $input Time string
   * @return integer Seconds based on input time string
   * @throws PHP Exception
   */
	private function convertToSeconds($input) {
		try {
			// switch between minutes and seconds [00:00] and hours, minutes and seconds [00:00:00]
			switch (strlen($input)) {
				case 5:
					// minutes and seconds
					$minutes	= (int)substr($input, 0, 2);
					$seconds	= (int)substr($input, 3, 2);
					$response	= ($minutes * 60 + $seconds);
					break;
				case 8:
					// hours, minutes and seconds
					$horus		= (int)substr($input, 0, 2);
					$minutes	= (int)substr($input, 3, 2);
					$seconds	= (int)substr($input, 6, 2);
					$response	= ($horus * 60 * 60 + $minutes * 60 + $seconds);
					break;
				default:
					throw new Exception('Time input format could not get identified!');
			}
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
		return $response;
	}
	
	/**
   * To Log
   *
   * Writes specified input string to a log file
   *
   * @param string $input String which shall get written to log
	 * @param string $filename (Optional) Filename of log file; Default gets used, if not set
   * @return integer Seconds based on input time string
   * @throws PHP Exception
   */
	public function toLog($input, $filename = NULL) {
		try {
			if ($filename == NULL) {
				$filename = $this->settings->log_file;
			}
			$file = fopen($filename, 'a+');
			fwrite($file, "\n".$input);
			fclose($file);
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
	}
	
	/**
   * Transmit to LMDB
	 *
   * Communicates with the CMF/LMDB by using SPARQL queries. Uses SPARQL UPDATE and the SPARQL webservice of the CMF/LMDB.
	 * Every query gets logged in the log file and can get accessed by an authorized administrator.
   *
   * @param string $lmdb_url Base URL of the CMF/LMDB; Required Username/Password have to get included in this URL (e.g. http://username:password@URL)
	 * @param string $service_url Path of the SPARQL service of the CMF/LMDB (normally: sparql/update)
	 * @param string $sparql_query SPARQL query which gets sent to the CMF/LMDB
   * @return object HTTP response generated by PEAR HTTP_Request library
   * @throws PHP Exception
   */
	private function transmitToLMDB($lmdb_url, $service_url, $sparql_query) {
		try {
			// http request required
			require_once (__ROOT__. '/libraries/HTTP_Request/Request.php');
			
			if (session_id() === "") {
				session_start();
			}
			
			// create new http request
			$request	= new HTTP_Request();
			$request->setURL($lmdb_url.$service_url);
			$request->setMethod(HTTP_REQUEST_METHOD_POST);
			$request->setBody($sparql_query);
			
			// write sparql query to log file
			$now = time();
			$tmp_log_file = $this->settings->base_path.'log/'.$now.'_query.log';
			$this->toLog($sparql_query, $tmp_log_file);
			
			// add query to log list
			$this->toLog(trim(ltrim($_SESSION['cmf']['userid'])). ',' .$now, $this->settings->log_list);
	
			$request->addHeader('Content-Type', 'application/sparql-update');
			$response	= $request->sendRequest();
			
			// print $lmdb_url.$service_url;
			
			if (PEAR::isError($response)) {
				// return PEAR error
				Throw new Exception($response->getMessage());
			}
			else {
				$response = $request;
				// add response to log file
				$this->toLog('Code: ' .$response->getResponseCode(). '; Message: ' .$response->getResponseBody(), $tmp_log_file);
			}
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
		return $response;
	}
	
	/**
   * Update annotations
	 *
   * Parses the currently used annotation file and loops through all existing annotation. Every new annotation gets added to the CMF, existing ones get updated and deleted ones get removed from the CMF instande.
	 * The HTTP resspose of the CMF gets returned by this method.
   *
   * @param string $lmdb_url Base URL of the CMF/LMDB; Required Username/Password have to get included in this URL (e.g. http://username:password@URL)
	 * @param string $service_url Path of the SPARQL service of the CMF/LMDB (normally: sparql/update)
	 * @param string $video_locators Video array which contains the currently selected video ID and its locators
	 * @param string $annotations_file Currently used annotation file name
   * @return object HTTP response generated by PEAR HTTP_Request library
   * @throws PHP Exception
   */
	public function updateAnnotations($lmdb_url, $service_url, $video_locators, $annotations_file) {
		try {
			// switch between create or update
			// if (file_exists($this->settings->annotation_path.$existing_annotations_file)) {
			// update annotations
			// $existing_annotations = $this->parseJson($existing_annotations_file);
			$updated_annotations =  $this->parseJson($annotations_file);
			// var_dump($existing_annotations);
			 // var_dump($updated_annotations);
			// loop updated annotations and update/add annotations
			$sparql_querys = array();
				
			foreach ($updated_annotations->annotations as $annotation_number => $annotation) {				
				// update/add annotation
				// get annotation from existing ones
				if ($annotation->active->value == 1) {
					// annotation needs to get added/updated
					if (isset($annotation->annotation->value) && ($annotation->annotation->value != '')) {
						// update annotation
						$sparql_query = '';
						$sparql_query = $this->createAnnotationTriples($annotation, $annotation_number, $video_locators, $updated_annotations->video[0]->uri, 'update');
						$sparql_querys[] = $sparql_query;
					}
					else {
						// add annotation
						$sparql_query = '';
						$sparql_query .= 'INSERT DATA {'. "\n";
						$sparql_query .= '  GRAPH <' .$updated_annotations->video[0]->uri. '> {'. "\n";
						$sparql_query .= $this->createAnnotationTriples($annotation, $annotation_number, $video_locators, $updated_annotations->video[0]->uri, 'insert');
						$sparql_query .= '  }'. "\n";
						$sparql_query .= '}'. "\n";
						$sparql_querys[] = $sparql_query;
					}
				}
				else if ($annotation->active->value == 0) {
					// annotation needs to get removed
					$sparql_query = '';
					$sparql_query = $this->createAnnotationTriples($annotation, $annotation_number, $video_locators, $updated_annotations->video[0]->uri, 'delete');
					$sparql_querys[] = $sparql_query;
				}
			}
		
			// loop through generated queries and submit them to the cmf
			if (is_array($sparql_querys) && (count($sparql_querys) > 0)) {
				// at least one annotation has been added/modified or needs to get deleted
				$sparql_update_query = '';
				$cnt = 0;
				foreach ($sparql_querys as $query) {
					$sparql_update_query .= $query;
					$cnt++;
					if ($cnt < count($sparql_querys)) {
						$sparql_update_query .= ';'."\n";
					}
				}
				
				// send query to cmf
				$response = $this->transmitToLMDB($lmdb_url, $service_url, $sparql_update_query);
				$response->ResponseCode = $response->getResponseCode();
				
				if ($response->ResponseCode == 200) {
					// update successfully performed
					// write updated annotations to file // both files: backup and regular annotation file
					$file_response = $this->writeToFile($updated_annotations, $annotations_file);
					if ($file_response != true) {
						throw new Exception($file_response);
					}
					unset($file_response);
					$file_response = $this->writeToFile($updated_annotations, $annotations_file);
					if ($file_response != true) {
						throw new Exception($file_response);
					}
					unset($file_response);
					
				}
				
			}
			else {
				// no modification in the cmf needed
	
				// set response manually if no modifications have been made
				$tmp_response->_response->_code = '304';
				$tmp_response->_response->_reason = $this->language->strings->ANNOTATION_SAVE_NO_MODIFICATIONS;
				$response = $tmp_response;
				
			}
			
		}
		catch (Exception $e) {
			$tmp_response->_response->_code = '400';
			$tmp_response->_response->_reason = 'ERROR: ' .$e->getMessage();
			$response = $tmp_response;
		}
		
		return $response;
	}
	
	/**
   * Create Annotations
	 *
   * Parses the currently used annotation file and uses the private method createAnnotationTriples to create all annotation triples which have to get inserted into the CMF. In the case of inserting data for the first time
	 * (no annotations of the selected video exist in the CMF) a n3 string gets created and this string is inserted in the CMF.
   *
   * @param string $lmdb_url Base URL of the CMF/LMDB; Required Username/Password have to get included in this URL (e.g. http://username:password@URL)
	 * @param string $service_url Path of the SPARQL service of the CMF/LMDB (normally: sparql/update)
	 * @param string $video_locators Video array which contains the currently selected video ID and its locators
	 * @param string $annotations_file Currently used annotation file name
   * @return object HTTP response generated by PEAR HTTP_Request library
   * @throws PHP Exception
   */
	public function createAnnotations($lmdb_url, $service_url, $video_locators, $annotations_file) {
		try {
			$new_annotations = $this->parseJson($annotations_file);
			// insert query
			$sparql_query = '';
			$sparql_query .= 'INSERT DATA {'. "\n";
			$sparql_query .= '  GRAPH <' .$new_annotations->video[0]->uri. '> {'. "\n";
			foreach ($new_annotations->annotations as $annotation_number => $new_annotation) {
				$sparql_query .= $this->createAnnotationTriples($new_annotation, $annotation_number, $video_locators, $new_annotations->video[0]->uri, 'insert');	
			}
			$sparql_query .= '  }'. "\n";
			$sparql_query .= '}'. "\n";
			// print $sparql_query;
			$response = $this->transmitToLMDB($lmdb_url, $service_url, $sparql_query);
		}
		catch (Exception $e) {
			$tmp_response->_response->_code = '400';
			$tmp_response->_response->_reason = 'ERROR: ' .$e->getMessage();
			$response = $tmp_response;
		}
		return $response;
	}
	
	/**
   * Create Annotation Triples
	 *
   * Parses the currently used annotation file and uses the method createAnnotationTriples to create all annotation triples which have to get inserted into the CMF. In the case of inserting data for the first time
	 * (no annotations of the selected video exist in the CMF) a n3 string gets created and this string is inserted in the CMF.
   *
   * @param string $annotation Data of the currently selected annotation
	 * @param string $annotation_number Number of the currently selected annotation (annotation array row identifier)
	 * @param string $video_data Video array which contains the currently selected video ID and its locators
	 * @param string $video_id ID of the currently selected video
	 * @param string $type Type of operation which shall get performed in the CMF with the selected annotation (INSERT, UPDATE, DELETE)
   * @return object HTTP response generated by PEAR HTTP_Request library
   * @throws PHP Exception
   */
	private function createAnnotationTriples($annotation, $annotation_number, $video_data, $video_id, $type) {
		try {
			
			include_once (__ROOT__. '/libraries/arc2/ARC2.php');
			include_once (__ROOT__. '/libraries/graphite/Graphite.php');
			
			$graph = new Graphite();
			// add required namespaces (which aren't already loaded by default)
			$graph->ns("ma", "http://www.w3.org/ns/ma-ont#");
			$graph->ns("cma", "http://connectme.at/ontology#");
			$graph->ns("oac", "http://www.openannotation.org/ns/");
			$graph->ns("cmo", "http://purl.org/twc/ontologies/cmo.owl#");
			$graph->ns("oax", "http://www.w3.org/ns/openannotation/extensions/");
			// $uri = "http://connectme.salzburgresearch.at/CMF/resource/video/yoovis/6306";
			
			 $timestamp = time();
			
			// create graph
			$graph->resource($video_id);
			
			if ($type == 'delete') {
				
				// sparql delete query
				$prefix['oac'] = 'http://www.openannotation.org/ns/';
				$prefix['ma'] = 'http://www.w3.org/ns/ma-ont#';
				$query = 'DELETE {'."\n";
				$query .= '	?annotation ?p ?v.'."\n";
				$query .= '	?fragment ?r ?s.'."\n";
				$query .= '	<' .$video_id. '>  <' .$prefix['ma']. 'hasFragment> ?fragment.'."\n";
				$query .= '}'."\n";
				$query .= 'WHERE {'."\n";
				$query .= '	?annotation <' .$prefix['oac']. 'hasTarget> ?fragment.'."\n";
				$query .= '	?annotation ?p ?v.'."\n";
				$query .= '	OPTIONAL {'."\n";
				$query .= '		?fragment ?r ?s'."\n";
				$query .= '	}'."\n";
				$query .= '	FILTER (?fragment = <' .str_replace("annotation", "fragment", $annotation->annotation->value). '>)'."\n";
				$query .= '}';
				
				$response = $query;
				
			}
			else if ($type == 'update') {
				$sparql_query = '';
				$prefix['oac'] = 'http://www.openannotation.org/ns/';
				$prefix['ma'] = 'http://www.w3.org/ns/ma-ont#';
				$sparql_query .= 'WITH <' .$video_id. '>'. "\n";
				$sparql_query .= 'DELETE {'."\n";
				$sparql_query .= '	?annotation ?p ?v.'."\n";
				$sparql_query .= '	?fragment ?r ?s.'."\n";
				$sparql_query .= '	<' .$video_id. '>  <' .$prefix['ma']. 'hasFragment> ?fragment.'."\n";
				$sparql_query .= '}'."\n";
				$sparql_query .= 'INSERT {'. "\n";
				$sparql_query .= $this->createAnnotationTriples($annotation, $annotation_number, $video_data, $video_id, 'insert');
				$sparql_query .= '}'. "\n";
				$sparql_query .= 'WHERE {'."\n";
				$sparql_query .= '	?annotation <' .$prefix['oac']. 'hasTarget> ?fragment.'."\n";
				$sparql_query .= '	?annotation ?p ?v.'."\n";
				$sparql_query .= '	OPTIONAL {'."\n";
				$sparql_query .= '		?fragment ?r ?s'."\n";
				$sparql_query .= '	}'."\n";
				$sparql_query .= '	FILTER (?fragment = <' .str_replace("annotation", "fragment", $annotation->annotation->value). '>)'."\n";
				$sparql_query .= '}';
				
				$response = $sparql_query;
				
			}
			else if ($type == 'insert') {
				
				// sparql insert query

				$timestamp = time();	
				
				// add framgents
				if (($annotation->annotation->value == 'null') || ($annotation->annotation->value == '')) {
					$element_id = md5($annotation_number + microtime());
					$tmp_annotation_uri = $this->settings->annotation_uri.$element_id;
					$annotation->annotation->value = $tmp_annotation_uri;
				}
				$annotation->annotation->value = str_replace("annotation", "fragment", $annotation->annotation->value);
				
				$graph->addCompressedTriple($video_id, 'ma:hasFragment', $annotation->annotation->value);
				
				// fragment details
				$graph->addCompressedTriple($annotation->annotation->value, 'rdf:type', 'ma:MediaFragment');
				
				// generate locator suffix
				$suffix = '#';
				if (isset($annotation->spatial) && isset($annotation->spatial->value)) {
					// spatial region set
					// var_dump($annotation->spatial->value);
					$tmp_spatial = explode(',', $annotation->spatial->value);
					$spatial_set = true;
					foreach ($tmp_spatial as $val) {
						if (!preg_match('/[0-9]+/', $val)) {
							$spatial_set = false;
						}
					}
					if ($spatial_set == true) {
						$suffix .= 'xywh=percent:' .$tmp_spatial[0]. ',' .$tmp_spatial[1]. ',' .$tmp_spatial[4]. ',' .$tmp_spatial[5];
					}
				}
				if (isset($annotation->starttime->value)) {
					// is spatial region set?
					if (strlen($suffix) > 1) {
						// add &
						$suffix	.= '&';
					}
					$suffix	.= 't='.$this->convertToSeconds($annotation->starttime->value);
					if ($annotation->endtime->value != '') {
					$suffix	.= ','.$this->convertToSeconds($annotation->endtime->value);
					}
				}
				
				foreach ($video_data['source'] as $video_source) {
					$graph->addCompressedTriple($annotation->annotation->value, 'ma:locator', $video_source['url'].$suffix, 'xsd:anyURI');
				}
				
				if ($annotation->relation->value != '') {
					$graph->addCompressedTriple($annotation->annotation->value, $annotation->relation->value, $annotation->resource->value);
				}
				
				// annotation details
				$pattern = '/' .preg_quote('bookmark', '/') . '/';
				if (preg_match($pattern, strtolower($annotation->annotationtype->value))) {
					$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'rdf:type', 'oax:Bookmark');
				}
				else {
					$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'rdf:type', 'oac:Annotation');
				}
				$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'oac:hasTarget', $annotation->annotation->value);
				$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'dct:creator', $annotation->creator->value);
				$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'dct:created', date("Y-m-d", $timestamp). 'T' .date("H:i:s", $timestamp). '' .date("P", $timestamp), 'xsd:dateTime');
				if (($annotation->preferredlabel->value != '') && ($annotation->preferredlabel->value != 'null')) {
					$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'cma:preferredLabel', $annotation->preferredlabel->value, 'xsd:string');
				}
				$graph->addCompressedTriple(str_replace("fragment", "annotation", $annotation->annotation->value), 'oac:hasBody',  $annotation->resource->value);
				
				$graph->addCompressedTriple($annotation->creator->value, 'rdf:type', 'foaf:Person');
					
				$response = $graph->serialize('NTriples');
			
			}
			
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
		return $response;
	}
	
	/**
   * Write To File
	 *
   * Parses the currently used annotation file and uses the method createAnnotationTriples to create all annotation triples which have to get inserted into the CMF. In the case of inserting data for the first time
	 * (no annotations of the selected video exist in the CMF) a n3 string gets created and this string is inserted in the CMF.
   *
   * @param object $input Object which shall get written into file (will get JSON encoded before writing to file)
	 * @param string $filename Name of file to write to
   * @return bool True if $input successfully written to file
	 * @return string Error message in case of error
   * @throws PHP Exception
   */
	private function writeToFile($input, $filename) {
		try {
		  
		  // delete file (if it already exists), to make sure that it contains only 1 annotations JSON string
			if (file_exists($this->settings->annotation_path.$filename)) {
				unlink($this->settings->annotation_path.$filename);
			}
				
			$file = fopen($this->settings->annotation_path.$filename, 'a+');
				
			if (fwrite($file, stripslashes(json_encode($input)))) {
				$response = true;
			}
			else {
				$response = 'ERROR: while writing to ' .$filename;
			}
			
			fclose($file);
			
			chmod($this->settings->annotation_path.$filename, 0777);
		  
		} catch (Exception $e) {
    		$response = 'ERROR: ' .$e->getMessage();
		}
		
		return $response;
		
	}

}
?>