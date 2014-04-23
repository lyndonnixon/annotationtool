<?php
/**
 * Hyperannotation Video Suite
 *
 * This class implements the hypervideo annotation functionality for
 * the connectme project.
 *
 * @author      	Matthias Bauer <matthias.bauer@sti2.org>
 * @version     	Development: 0.3
 * @require		    PEAR HTTP_Request class is required to use
 *					this class. It can get dowloaded at
 *					http://pear.php.net/
 *
 * @created			07.01.2012
 * @lastchanged		2012-02-23
 * @changelog		0.1:
 *					----
 *					- Implementation of the basic features
 *
 *					0.2:
 *					----
 *					- Annotations temporarily stored in files instead of cookies. Needed to be done because of cookie and header size limit.
 *
 *					0.3:
 *					----
 *					- Annotation download in n3 syntax included.
 *
 *					0.4:
 *					----
 *					- Annotation download adapted to the new annotation model
 *
 *					0.5:
 *					----
 *					- Sparql Query generation for insert/delete from/in linked media data base
 *
 *					0.6:
 *					----
 *					- Sparql Update for updating existing annotations
 *
 */
require_once (__ROOT__. '/libraries/HTTP_Request/Request.php');

class HAS {
	
	// private variables
	private $base_path				= '';
	private $annotation_path	= '';
	private $download_path		= '';
	private $ann_prefix				= array();
	private $fragment_uri			= '';
	private $annotation_uri		= '';
	private $log_file					= '';
	private $check_values			= array();

	// Constructor
  public function __construct() {
		include_once(__ROOT__. '/configs/settings.php');
		$this->base_path 				= $base_path;
		$this->annotation_path	= $annotation_path;
		$this->download_path		= $download_path;
		$this->ann_prefix				= $ann_prefix;
		$this->log_file					= $log_file;
		$this->fragment_uri			= $fragment_uri;
		$this->annotation_uri		= $annotation_uri;
		$this->check_values			= $check_values;
    return true;
  }
	
	// Validate submitted video uri
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
	
	// parse json string to object
	private function parseJson($input) {
		// parse json string and return eiter json object or error message
		try {
		  
		    if (file_exists($this->annotation_path.$input)) {
			  $file_content = '';
			  $file = fopen($this->annotation_path.$input, 'r');
		  
			  while (!feof($file)) {
			    $file_content .= fgets($file);
			  }
			  
			  fclose($file);
			  
			}
			else {
				throw new Exception('Annotation file not found! <em>' .$this->annotation_path.$input. '</em>');
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
	
	// time conversion into seconds
	private function timeConversion($currenttime) {
    	$parse = array();
    	if (preg_match ('#^(?<hours>[\d]{2}):(?<mins>[\d]{2}):(?<secs>[\d]{2})$#', $currenttime, $parse)) {
			return (int) $parse['hours'] * 3600 + (int) $parse['mins'] * 60 + (int) $parse['secs'];
    	}
		else if (preg_match ('#^(?<mins>[\d]{2}):(?<secs>[\d]{2})$#', $currenttime, $parse)) {
			return (int) $parse['mins'] * 60 + (int) $parse['secs'];
		}
		else {
			// Throw error, exception, etc
        	throw new Exception("Hour format not valid");
		}
	}

	
	// sort annotation list by starttime and return it
	public function showAnnotationList($input) {
		// sort annotation json entries
		$sorted_list = $this->parseJson($input);
		return $sorted_list;
		
	}
	
	// comparison function for annotation list sort
	public function json_sort($a, $b)
	{
		$seconds_a = $this->timeConversion($a->starttime);
		$seconds_b = $this->timeConversion($b->starttime);
    	if ($seconds_a < $seconds_b) {
        	return -1;
    	} else if ($seconds_a > $seconds_b) {
        	return 1;
    	} else {
        	return 0;
    	}
	}

	
	// Delete video cookie
	public function unloadVideo($cookie_name)
	{
		setcookie($cookie_name, '', (time() - 3600));
	}
	
	// Get video type
	public function getVideoType($video_uri)
	{
		$response = $this->getResponse($video_uri);
		if (is_array($response)) {
			// no error => url is working
			if (($response['Content-Type'] == 'video/webm') || ($response['Content-Type'] == 'video/ogg') || ($response['Content-Type'] == 'video/mp4')) {
				// allowed video type found
				// generate video source tag
				switch ($response['Content-Type']) {
					case 'video/mp4':
					  $type 	= array('mime' => 'video/mp4', 'codecs' => array('avc1.42E01E', 'mp4a.40.2'));
					  break;
					case 'video/webm':
					  $type 	= array('mime' => 'video/webm', 'codecs' => array('vp8', 'vorbis'));
					  break;
					case 'video/ogg':
					  $type 	= array('mime' => 'video/ogg', 'codecs' => array('theora', 'vorbis'));
					  break;
					default:
					  $type		 = 'ERROR: You have selected an unsupported video type ("' .$response['Content-Type']. '")! HTML5 video playback supports only mp4, ogg or webm videos! Make sure that you use one of those!';
				}
				return $type;
			}
			else {
				// content type not found => throw error message
				$error_message = 'ERROR: You have selected an unsupported content type ("' .$response['Content-Type']. '")! HTML5 video playback supports only mp4, ogg or webm videos! Make sure that you use one of those!';
				$log_message = date('Y-m-d - H:i:s', time()). ': ERROR while loading ' .$video_uri. ' - ' .$response['Content-Type']. '!';
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
	
	// Annotation donwload
	public function downloadAnnotations($video_uri, $annotation_file) {
		try {
			if (($video_uri == '') || ($annotation_file == '')) {
				throw new Exception("Annotation parameters invalid");
			}
			else {
				$response = $this->createN3File($video_uri, $annotation_file);
			}
		}
		catch (Exception $e) {
    		$response = 'ERROR: ' .$e->getMessage();
		}
		return $response;
	}
	
	// create n3 file from current annotations
	private function createN3File($video_uri, $annotation_file, $sparql_type = '') {
		try {
				$ann_json =  $this->parseJson($annotation_file);
			if (is_object($ann_json)) {
				// json successfully created
				// save video values for annotation conversion into percent (instead of pixel)
				$video_with = $ann_json->video[0]->width;
				$video_height = $ann_json->video[0]->height;
				// switch between sparql query and file generation
				$create_sparql_query = false;
				if (($sparql_type != '') && (($sparql_type == 'insert') || ($sparql_type == 'delete'))) {
					// valid sparql type has been selected
					$create_sparql_query = true;
				}
				$n3_content = '';
				// generate n3 header prefix
				foreach ($this->ann_prefix as $prefix_key => $prefix_uri) {
					if ($create_sparql_query != false) {
						$n3_content .= "PREFIX ";
					}
					else {
						$n3_content .= "@prefix ";
					}
					$n3_content .= $prefix_key. ": " .$prefix_uri;
					if ($create_sparql_query != false) {
						$n3_content .= "\n";
					}
					else {
						$n3_content .= "."."\n";
					}
				}
				
				if ($create_sparql_query != false) {
					// if query its a sparql query add query type
					$n3_content .= "\n" . strtoupper($sparql_type). " DATA" . "\n" . "{" . "\n";
					$n3_content .= "\n" ." GRAPH <" .trim(ltrim($video_uri)). ">" . "\n" . "{" . "\n";
				}
				
				$n3_content .= "\n"."<" .trim(ltrim($video_uri)). "> a ma:MediaResource;"."\n";
				
				$media_fragments	= array();
				$fragment_details	= array();
				$annotation_details	= array();
				$element_counter	= 1;
				foreach ($ann_json->annotations as $annotation) {
					$element_id = md5($element_counter + time());
					$player_with = $ann_json->video[0]->player_width;
					$player_height = $ann_json->video[0]->player_height;
					$values	= array('x' => round((($annotation->x1)/($player_with/100)), 0), 'y' => round((($annotation->y1)/($player_height/100)), 0), 'height' => round((($annotation->height)/($player_height/100)), 0), 'width' => round((($annotation->width)/($player_with/100)), 0));
					$sidebar = array('width' => round((($player_with - $video_with)/2), 0), 'height' => round((($player_height - $video_height)/2), 0));
					if ($player_with == $video_with) {
						// sidebars top/bottom
						if (($annotation->y1 >= $sidebar['height']) && ($annotation->y1 <= ($sidebar['height'] + $video_height))) {
							// y1 in video content
							// y1 needs to get reduced by the height of the top sidebar and converted to percent
							$values['y'] = round((($annotation->y1 - $sidebar['height'])/($player_height/100)), 0);
							// x1, width and height stay untouched
						}
						else if ($annotation->y1 < $sidebar['height']) {
							// y1 in top sidebar
							// y1 gets set to 0
							$values['y'] = 0;
							// height needs to get reduced by (sidebar top - y1) and converted to percent
							$values['height'] = round((($annotation->height - ($sidebar['height'] - $annotation->y1))/($player_height/100)), 0);
							// x1 and width stay untouched
						}
						else if ($annotation->y1 > ($sidebar['height'] + $video_height)) {
							// y1 in bottom sidebar
							// not allowed => set y1 and height to string => annotation area is not used anymore!
							$values['y'] = '';
							$values['height'] = '';
						}
						if (($annotation->y2 >= $sidebar['height']) && ($annotation->y2 <= ($sidebar['height'] + $video_height))) {
							// y2 in video content
							// no action required
						}
						else if ($annotation->y2 < $sidebar['height']) {
							// y2 in top sidebar =>  not allowed => set y1 and with to string => annotation area is not used anymore!
							// y1 gets set to 0
							$values['y'] = '';
							$values['height'] = '';
						}
						else if ($annotation->y2 > ($sidebar['height'] + $video_height)) {
							// y2 in bottom sidebar
							// reduce annotation height by sidebar height
							$values['height'] = round((($annotation->height - ($sidebar['height'] - $annotation->y2))/($player_height/100)), 0);
						}
					}
					elseif ($player_height == $video_height) {
						// sidebars left/right
						if (($annotation->x1 >= $sidebar['width']) && ($annotation->x1 <= ($sidebar['width'] + $video_with))) {
							// x1 in video content
							// x1 needs to get reduced by the width of the left sidebar and converted to percent
							$values['x'] = round((($annotation->x1 - $sidebar['width'])/($player_with/100)), 0);
							// y1, width and height stay untouched
						}
						else if ($annotation->x1 < $sidebar['width']) {
							// x1 in left sidebar
							// x1 gets set to 0
							$values['x'] = 0;
							// width needs to get reduced by x1 and converted to percent
							$values['width'] = round((($annotation->width - ($sidebar['width'] - $annotation->x1))/($player_with/100)), 0);
							// y1 and height stay untouched
						}
						else if ($annotation->x1 > ($sidebar['width'] + $video_with)) {
							// x1 in right sidebar
							// not allowed => set x1 and with to string => annotation area is not used anymore!
							$values['x'] = '';
							$values['width'] = '';
						}
						if (($annotation->x2 >= $sidebar['width']) && ($annotation->x2 <= ($sidebar['width'] + $video_with))) {
							// x2 in video content
							// no action required
						}
						else if ($annotation->x2 < $sidebar['width']) {
							// x2 in left sidebar =>  not allowed => set x1 and with to string => annotation area is not used anymore!
							// x1 gets set to 0
							$values['x'] = '';
							$values['width'] = '';
						}
						else if ($annotation->x2 > ($sidebar['width'] + $video_with)) {
							// x2 in right sidebar
							// reduce annotation width by sidebar width
							$values['width'] = round((($annotation->width - ($sidebar['width'] - $annotation->x2))/($player_with/100)), 0);
						}
					}
					$suffix	= '#';
					// spatial region
					if ((is_numeric($values['x'])) && (is_numeric($values['y'])) && (is_numeric($values['width']) && ($values['width'] > 0)) && (is_numeric($values['height']) && ($values['height'] > 0))) {
						$suffix	.= 'xywh=percent:' .$values['x']. ',' .$values['y']. ',' .$values['width']. ',' .$values['height'];
					}
					// timestamp(s)
					if ($annotation->starttime != '') {
						// spatial region set?
						if (strlen($suffix) > 1) {
							// add &
							$suffix	.= '&';
						}
						$suffix	.= 't='.$this->convertToSeconds($annotation->starttime);
						if ($annotation->endtime != '') {
							$suffix	.= ','.$this->convertToSeconds($annotation->endtime);
						}
					}
					$n3_content .= 'ma:hasFragment  <' .trim(ltrim($this->fragment_uri)).trim(ltrim($element_id)). '>';
					if ($element_counter < count($ann_json->annotations)) {
						$n3_content .= ";"."\n";
					}
					else {
							$n3_content .= "."."\n";
					}
					$fragment_string	= "\n"."<" .trim(ltrim($this->fragment_uri)).trim(ltrim($element_id)). "> a ma:MediaFragment;";
					$fragment_string	.= "\n"."ma:locator <" .trim(ltrim($video_uri)).trim(ltrim($suffix)). ">;";
					$fragment_string	.= "\n"."cma:" .trim(ltrim($annotation->type)). " <" .trim(ltrim($annotation->uri)). ">.";
					$annotation_string	= "\n"."<" .trim(ltrim($this->annotation_uri)).trim(ltrim($element_id)). "> a oac:Annotation;";
					$annotation_string	.= "\n"."oac:target <" .trim(ltrim($this->fragment_uri)).trim(ltrim($element_id)). ">;";
					$annotation_string	.= "\n"."oac:body <" .trim(ltrim($annotation->uri)). ">;";
					
					$annotation_string	.= "\n"."dct:creator <" .trim(ltrim($_SESSION['cmf']['userid'])). ">;";
					$annotation_string	.= "\n"."<" .trim(ltrim($_SESSION['cmf']['userid'])). "> a foaf:Person."."\n";

					$fragment_details[] = $fragment_string.$annotation_string;
					$element_counter++;
				}
				foreach ($fragment_details as $detail) {
					$n3_content .= $detail;
				}
				if ($create_sparql_query != false) {
					// if query its a sparql query add query type
					$n3_content .= "\n" . "}";
					$n3_content .= "\n" . "}";
					$response = $n3_content;
				}
				else {
					// generate export file name
					$export_filename = str_replace('.', '_', basename($video_uri)). '_'. time(). '.n3';
					$response = $this->writeToFile($export_filename, $n3_content);
				}
			}
			else {
				throw new Exception($ann_json);
			}
		}
		catch (Exception $e) {
    		$response = 'ERROR: ' .$e->getMessage();
		}
		
		return $response;
	}
	
	// creates a sparql update query for updating existing annotations in the cmf
	// INSERT { GRAPH <g1> { x y z } } DELETE { GRAPH <g1> { a b c } } USING <g1> WHERE { ... }
	public function sparqlInsertDelete($lmdb_url, $service_url, $video_uri, $annotation_file, $sparql_type = 'insert') {
		try {
			$ann_json =  $this->parseJson($annotation_file);
			if (is_object($ann_json)) {
				// json successfully created
				// save video values for annotation conversion into percent (instead of pixel)
				$video_with = $ann_json->video[0]->width;
				$video_height = $ann_json->video[0]->height;
				// switch between sparql query and file generation
				$n3_content = '';
				// generate n3 header prefix
				foreach ($this->ann_prefix as $prefix_key => $prefix_uri) {
					$n3_content .= "PREFIX ";
					$n3_content .= $prefix_key. ": " .$prefix_uri;
					$n3_content .= "\n";
				}
				
				if ($sparql_type == 'clear') {
					$n3_content .= "CLEAR GRAPH <" .trim(ltrim($video_uri)). ">";
				}
				else {
					
					$n3_content .= "\n" . strtoupper($sparql_type) . " DATA" . "\n" . "{" . "\n";
					$n3_content .= "\n" ." GRAPH <" .trim(ltrim($video_uri)). ">" . "\n" . "{" . "\n";
					
					$n3_content .= "\n"."<" .trim(ltrim($video_uri)). "> a ma:MediaResource;"."\n";
					
					$media_fragments	= array();
					$fragment_details	= array();
					$annotation_details	= array();
					$element_counter	= 1;
					
					foreach ($ann_json->annotations as $annotation) {
						$element_id = md5($element_counter + time());
						$player_with = $ann_json->video[0]->player_width;
						$player_height = $ann_json->video[0]->player_height;
						$values	= array('x' => round((($annotation->x1)/($player_with/100)), 0), 'y' => round((($annotation->y1)/($player_height/100)), 0), 'height' => round((($annotation->height)/($player_height/100)), 0), 'width' => round((($annotation->width)/($player_with/100)), 0));
						$sidebar = array('width' => round((($player_with - $video_with)/2), 0), 'height' => round((($player_height - $video_height)/2), 0));
						if ($player_with == $video_with) {
							// sidebars top/bottom
							if (($annotation->y1 >= $sidebar['height']) && ($annotation->y1 <= ($sidebar['height'] + $video_height))) {
								// y1 in video content
								// y1 needs to get reduced by the height of the top sidebar and converted to percent
								$values['y'] = round((($annotation->y1 - $sidebar['height'])/($player_height/100)), 0);
								// x1, width and height stay untouched
							}
							else if ($annotation->y1 < $sidebar['height']) {
								// y1 in top sidebar
								// y1 gets set to 0
								$values['y'] = 0;
								// height needs to get reduced by (sidebar top - y1) and converted to percent
								$values['height'] = round((($annotation->height - ($sidebar['height'] - $annotation->y1))/($player_height/100)), 0);
								// x1 and width stay untouched
							}
							else if ($annotation->y1 > ($sidebar['height'] + $video_height)) {
								// y1 in bottom sidebar
								// not allowed => set y1 and height to string => annotation area is not used anymore!
								$values['y'] = '';
								$values['height'] = '';
							}
							if (($annotation->y2 >= $sidebar['height']) && ($annotation->y2 <= ($sidebar['height'] + $video_height))) {
								// y2 in video content
								// no action required
							}
							else if ($annotation->y2 < $sidebar['height']) {
								// y2 in top sidebar =>  not allowed => set y1 and with to string => annotation area is not used anymore!
								// y1 gets set to 0
								$values['y'] = '';
								$values['height'] = '';
							}
							else if ($annotation->y2 > ($sidebar['height'] + $video_height)) {
								// y2 in bottom sidebar
								// reduce annotation height by sidebar height
								$values['height'] = round((($annotation->height - ($sidebar['height'] - $annotation->y2))/($player_height/100)), 0);
							}
						}
						elseif ($player_height == $video_height) {
							// sidebars left/right
							if (($annotation->x1 >= $sidebar['width']) && ($annotation->x1 <= ($sidebar['width'] + $video_with))) {
								// x1 in video content
								// x1 needs to get reduced by the width of the left sidebar and converted to percent
								$values['x'] = round((($annotation->x1 - $sidebar['width'])/($player_with/100)), 0);
								// y1, width and height stay untouched
							}
							else if ($annotation->x1 < $sidebar['width']) {
								// x1 in left sidebar
								// x1 gets set to 0
								$values['x'] = 0;
								// width needs to get reduced by x1 and converted to percent
								$values['width'] = round((($annotation->width - ($sidebar['width'] - $annotation->x1))/($player_with/100)), 0);
								// y1 and height stay untouched
							}
							else if ($annotation->x1 > ($sidebar['width'] + $video_with)) {
								// x1 in right sidebar
								// not allowed => set x1 and with to string => annotation area is not used anymore!
								$values['x'] = '';
								$values['width'] = '';
							}
							if (($annotation->x2 >= $sidebar['width']) && ($annotation->x2 <= ($sidebar['width'] + $video_with))) {
								// x2 in video content
								// no action required
							}
							else if ($annotation->x2 < $sidebar['width']) {
								// x2 in left sidebar =>  not allowed => set x1 and with to string => annotation area is not used anymore!
								// x1 gets set to 0
								$values['x'] = '';
								$values['width'] = '';
							}
							else if ($annotation->x2 > ($sidebar['width'] + $video_with)) {
								// x2 in right sidebar
								// reduce annotation width by sidebar width
								$values['width'] = round((($annotation->width - ($sidebar['width'] - $annotation->x2))/($player_with/100)), 0);
							}
						}
						$suffix	= '#';
						// spatial region
						if ((is_numeric($values['x'])) && (is_numeric($values['y'])) && (is_numeric($values['width']) && ($values['width'] > 0)) && (is_numeric($values['height']) && ($values['height'] > 0))) {
							$suffix	.= 'xywh=percent:' .$values['x']. ',' .$values['y']. ',' .$values['width']. ',' .$values['height'];
						}
						// timestamp(s)
						if ($annotation->starttime != '') {
							// spatial region set?
							if (strlen($suffix) > 1) {
								// add &
								$suffix	.= '&';
							}
							$suffix	.= 't='.$this->convertToSeconds($annotation->starttime);
							if ($annotation->endtime != '') {
								$suffix	.= ','.$this->convertToSeconds($annotation->endtime);
							}
						}
						$n3_content .= 'ma:hasFragment  <' .trim(ltrim($this->fragment_uri)).trim(ltrim($element_id)). '>';
						if ($element_counter < count($ann_json->annotations)) {
							$n3_content .= ";"."\n";
						}
						else {
								$n3_content .= "."."\n";
						}
						$fragment_string	= "\n"."<" .trim(ltrim($this->fragment_uri)).trim(ltrim($element_id)). "> a ma:MediaFragment;";
						$fragment_string	.= "\n"."ma:locator <" .trim(ltrim($video_uri)).trim(ltrim($suffix)). ">;";
						$fragment_string	.= "\n"."cma:" .trim(ltrim($annotation->type)). " <" .trim(ltrim($annotation->uri)). ">.";
						$annotation_string	= "\n"."<" .trim(ltrim($this->annotation_uri)).trim(ltrim($element_id)). "> a oac:Annotation;";
						$annotation_string	.= "\n"."oac:target <" .trim(ltrim($this->fragment_uri)).trim(ltrim($element_id)). ">;";
						$annotation_string	.= "\n"."oac:body <" .trim(ltrim($annotation->uri)). ">;";
						
						$annotation_string	.= "\n"."dct:creator <" .trim(ltrim($_SESSION['cmf']['userid'])). ">;";
						$annotation_string	.= "\n"."<" .trim(ltrim($_SESSION['cmf']['userid'])). "> a foaf:Person."."\n";
	
						$fragment_details[] = $fragment_string.$annotation_string;
						$element_counter++;
					}
					foreach ($fragment_details as $detail) {
						$n3_content .= $detail;
					}
		
					// if query its a sparql query add query type
					$n3_content .= "\n" . "}";
					$n3_content .= "\n" . "}";
					// $response = $n3_content;
				
				}
				
				$response = $this->transmitToLMDB($lmdb_url, $service_url, $n3_content);	
			}
			else {
				throw new Exception($ann_json);
			}
		}
		catch (Exception $e) {
    		$response = 'ERROR: ' .$e->getMessage();
		}
		
		return $response;
	}
	
	// creates a sparql update query for updating existing annotations in the cmf
	// INSERT { GRAPH <g1> { x y z } } DELETE { GRAPH <g1> { a b c } } USING <g1> WHERE { ... }
	public function sparqlUpdateAnnotations($lmdb_url, $service_url, $video_uri, $updated_annotations_file, $existing_annotations_file) {
		try {
			$updated_annotations 	=  $this->parseJson($updated_annotations_file);
			$original_annotations =  $this->parseJson($existing_annotations_file);
			if (is_object($updated_annotations) && is_object($original_annotations)) {
			
				// json successfully created
				// save video values for annotation conversion into percent (instead of pixel)
				$video_with = $updated_annotations->video[0]->width;
				$video_height = $updated_annotations->video[0]->height;
				
				$errors = array();

				$prefix = '';
				// generate n3 header prefix
				foreach ($this->ann_prefix as $prefix_key => $prefix_uri) {
					$prefix .= "PREFIX ";
					$prefix .= $prefix_key. ": " .$prefix_uri;
					$prefix .= "\n";
				}
				
				// already processed old fragments/annotations
				$processed = array();
				
				// loop through updated annotations and update/add them
				foreach ($updated_annotations->annotations as $updated_annotation) {
					// if cmf uri is specified search for annotation in original annotations
					if ($updated_annotation->cmfuri != '') {
						// cmf uri found => search for changes
						foreach ($original_annotations->annotations as $original_annotation) {
							if (urldecode($original_annotation->cmfuri) == urldecode($updated_annotation->cmfuri)) {
								// element identified => any changes made?
								// add element to processed ones (needed for annotation delete compare)
								$processed[] = urldecode($updated_annotation->cmfuri);
								// updates needed?
								$needs_update = false;
								foreach($updated_annotation as $annotationkey => $annotationvalue) {
									if (in_array($annotationkey, $this->check_values)) {
										if (urldecode($updated_annotation->$annotationkey) != urldecode($original_annotation->$annotationkey)) {
											$needs_update = true;
										}
									}
								}
								if ($needs_update == true) {
									// update needed
									$annotationuri	= urldecode($updated_annotation->cmfuri);
									$fragmenturi 		= str_replace('annotation', 'fragment', urldecode($updated_annotation->cmfuri));
									
									// calculate percentage values for spatial region
									$player['width'] = $original_annotations->video[0]->player_width;
									$player['height'] = $original_annotations->video[0]->player_height;
									
									$video['width'] = $original_annotations->video[0]->width;
									$video['height'] = $original_annotations->video[0]->height;
									
									/*
									$values	= array('x' => round((($updated_annotation->x1)/($player_with/100)), 0), 'y' => round((($updated_annotation->y1)/($player_height/100)), 0), 'height' => round((($updated_annotation->height)/($player_height/100)), 0), 'width' => round((($updated_annotation->width)/($player_with/100)), 0));
									
									// sidebar
									$sidebar = array('width' => round((($player_with - $video_with)/2), 0), 'height' => round((($player_height - $video_height)/2), 0));
									
									if ($player_with == $video_with) {
										// sidebars top/bottom
										if (($updated_annotation->y1 >= $sidebar['height']) && ($updated_annotation->y1 <= ($sidebar['height'] + $video_height))) {
											// y1 in video content
											// y1 needs to get reduced by the height of the top sidebar and converted to percent
											$values['y'] = round((($updated_annotation->y1 - $sidebar['height'])/($player_height/100)), 0);
											// x1, width and height stay untouched
										}
										else if ($updated_annotation->y1 < $sidebar['height']) {
											// y1 in top sidebar
											// y1 gets set to 0
											$values['y'] = 0;
											// height needs to get reduced by (sidebar top - y1) and converted to percent
											$values['height'] = round((($updated_annotation->height - ($sidebar['height'] - $updated_annotation->y1))/($player_height/100)), 0);
											// x1 and width stay untouched
										}
										else if ($updated_annotation->y1 > ($sidebar['height'] + $video_height)) {
											// y1 in bottom sidebar
											// not allowed => set y1 and height to string => annotation area is not used anymore!
											$values['y'] = '';
											$values['height'] = '';
										}
										if (($updated_annotation->y2 >= $sidebar['height']) && ($updated_annotation->y2 <= ($sidebar['height'] + $video_height))) {
											// y2 in video content
											// no action required
										}
										else if ($updated_annotation->y2 < $sidebar['height']) {
											// y2 in top sidebar =>  not allowed => set y1 and with to string => annotation area is not used anymore!
											// y1 gets set to 0
											$values['y'] = '';
											$values['height'] = '';
										}
										else if ($updated_annotation->y2 > ($sidebar['height'] + $video_height)) {
											// y2 in bottom sidebar
											// reduce annotation height by sidebar height
											$values['height'] = round((($updated_annotation->height - ($sidebar['height'] - $updated_annotation->y2))/($player_height/100)), 0);
										}
									}
									elseif ($player_height == $video_height) {
										// sidebars left/right
										if (($updated_annotation->x1 >= $sidebar['width']) && ($updated_annotation->x1 <= ($sidebar['width'] + $video_with))) {
											// x1 in video content
											// x1 needs to get reduced by the width of the left sidebar and converted to percent
											$values['x'] = round((($updated_annotation->x1 - $sidebar['width'])/($player_with/100)), 0);
											// y1, width and height stay untouched
										}
										else if ($updated_annotation->x1 < $sidebar['width']) {
											// x1 in left sidebar
											// x1 gets set to 0
											$values['x'] = 0;
											// width needs to get reduced by x1 and converted to percent
											$values['width'] = round((($updated_annotation->width - ($sidebar['width'] - $updated_annotation->x1))/($player_with/100)), 0);
											// y1 and height stay untouched
										}
										else if ($updated_annotation->x1 > ($sidebar['width'] + $video_with)) {
											// x1 in right sidebar
											// not allowed => set x1 and with to string => annotation area is not used anymore!
											$values['x'] = '';
											$values['width'] = '';
										}
										if (($updated_annotation->x2 >= $sidebar['width']) && ($updated_annotation->x2 <= ($sidebar['width'] + $video_with))) {
											// x2 in video content
											// no action required
										}
										else if ($updated_annotation->x2 < $sidebar['width']) {
											// x2 in left sidebar =>  not allowed => set x1 and with to string => annotation area is not used anymore!
											// x1 gets set to 0
											$values['x'] = '';
											$values['width'] = '';
										}
										else if ($updated_annotation->x2 > ($sidebar['width'] + $video_with)) {
											// x2 in right sidebar
											// reduce annotation width by sidebar width
											$values['width'] = round((($updated_annotation->width - ($sidebar['width'] - $updated_annotation->x2))/($player_with/100)), 0);
										}
									}
									$suffix	= '#';
									// spatial region
									if ((is_numeric($values['x'])) && (is_numeric($values['y'])) && (is_numeric($values['width']) && ($values['width'] > 0)) && (is_numeric($values['height']) && ($values['height'] > 0))) {
										$suffix	.= 'xywh=percent:' .$values['x']. ',' .$values['y']. ',' .$values['width']. ',' .$values['height'];
									}
									// timestamp(s)
									if ($updated_annotation->starttime != '') {
										// spatial region set?
										if (strlen($suffix) > 1) {
											// add &
											$suffix	.= '&';
										}
										$suffix	.= 't='.$this->convertToSeconds($updated_annotation->starttime);
										if ($updated_annotation->endtime != '') {
											$suffix	.= ','.$this->convertToSeconds($updated_annotation->endtime);
										}
									}
									*/
									
									$suffix = $this->calculateVideoParameters($player, $video, $updated_annotation);
									// print "SUFFIX: " .$suffix;
									
									// create removal query for fragments
									/*
									$fragment_update_query = $prefix. "\n";
									// with graph
									$fragment_update_query .= 'WITH <' .$video_uri. '>'. "\n";
									// delete fragments
									$fragment_update_query .= 'DELETE {'. "\n";
									$fragment_update_query .= '  <' .$video_uri. '> ma:hasFragment <' .$fragmenturi. '>.'. "\n";
									$fragment_update_query .= '  <' .$fragmenturi. '> rdf:type ma:MediaFragment.'. "\n";
									$fragment_update_query .= '  <' .$fragmenturi. '> ma:locator <' .urldecode($original_annotation->fragmenturi). '>.'. "\n";
									$fragment_update_query .= '  <' .$fragmenturi. '> cma:' .$original_annotation->type. ' <' .$original_annotation->uri. '>.'. "\n";
									$fragment_update_query .= '}'. "\n";
									// add updated fragments
									$fragment_update_query .= 'INSERT {'. "\n";
									$fragment_update_query .= '  <' .$video_uri. '> ma:hasFragment <' .$fragmenturi. '>.'. "\n";
									$fragment_update_query .= '  <' .$fragmenturi. '> rdf:type ma:MediaFragment.'. "\n";
									$fragment_update_query .= '  <' .$fragmenturi. '> ma:locator <' .$video_uri.$suffix.'>.'. "\n";
									$fragment_update_query .= '  <' .$fragmenturi. '> cma:' .$updated_annotation->type. ' <' .$updated_annotation->uri. '>.'. "\n";
									$fragment_update_query .= '}'. "\n";
									// where
									$fragment_update_query .= 'WHERE {'. "\n";
									$fragment_update_query .= '  <' .$video_uri. '> ma:hasFragment <' .$fragmenturi. '>.'. "\n";
									$fragment_update_query .= '}'. "\n";
									*/
									$fragment_update_query = $this->createFragmentUpdateQuery($prefix, $video_uri, $fragmenturi, 'update', array('original' => array('fragment_uri' => $original_annotation->fragmenturi, 'resource_uri' => $original_annotation->uri, 'annotation_type' => $original_annotation->type), 'updated' => array('suffix' => $suffix, 'resource_uri' => $updated_annotation->uri, 'annotation_type' => $updated_annotation->type)));
									// send to cmf
									// print $fragment_update_query;
									
									// send query to cmf
									$response = $this->transmitToLMDB($lmdb_url, $service_url, $fragment_update_query);
									
									if (!is_string($response)) {
										if ($response->getResponseCode() != 200) {
											$errors[] = $fragmenturi. ': Response[' .$response->getResponseBody(). ']';
										}
									}
									else {
										$errors[] = $fragmenturi. ': Response['. $response. ']';
									}
									unset($response);
									
									/*
									// create removal query for annotations
									$annotation_update_query = $prefix. "\n";
									// with graph
									$annotation_update_query .= 'WITH <' .$video_uri. '>'. "\n";
									// delete fragments
									$annotation_update_query .= 'DELETE {'. "\n";
									$annotation_update_query .= '  <' .$annotationuri. '> oac:target <' .$fragmenturi. '>.'. "\n";
									$annotation_update_query .= '  <' .$annotationuri. '> rdf:type oac:Annotation.'. "\n";
									$annotation_update_query .= '  <' .$annotationuri. '> dct:creator <' .urldecode($original_annotation->creator). '>.'. "\n";
									$annotation_update_query .= '  <' .$annotationuri. '> oac:body <' .$original_annotation->uri. '>.'. "\n";
									$annotation_update_query .= '}'. "\n";
									// add updated annotations
									$annotation_update_query .= 'INSERT {'. "\n";
									$annotation_update_query .= '  <' .$annotationuri. '> oac:target <' .$fragmenturi. '>.'. "\n";
									$annotation_update_query .= '  <' .$annotationuri. '> rdf:type oac:Annotation.'. "\n";
									$annotation_update_query .= '  <' .$annotationuri. '> dct:creator <' .$_SESSION['cmf']['userid'].'>.'. "\n";
									$annotation_update_query .= '  <' .$annotationuri. '> oac:body <' .$updated_annotation->uri. '>.'. "\n";
									$annotation_update_query .= '  <' .$_SESSION['cmf']['userid'].'> rdf:type foaf:Person.'. "\n";
									$annotation_update_query .= '}'. "\n";
									// where
									$annotation_update_query .= 'WHERE {'. "\n";
									$annotation_update_query .= '  <' .$annotationuri. '> rdf:type oac:Annotation.'. "\n";
									$annotation_update_query .= '}'. "\n";
									*/
									
									$annotation_update_query = $this->createAnnotationUpdateQuery($prefix, $video_uri, $fragmenturi, $annotationuri, 'update', array('original' => array('fragment_uri' => $original_annotation->fragmenturi, 'resource_uri' => $original_annotation->uri, 'annotation_creator' => $original_annotation->creator), 'updated' => array('resource_uri' => $updated_annotation->uri, 'annotation_creator' => $_SESSION['cmf']['userid'])));
									
									// print $annotation_update_query;
									
									// send query to cmf
									$response = $this->transmitToLMDB($lmdb_url, $service_url, $annotation_update_query);
									
									if (!is_string($response)) {
										if ($response->getResponseCode() != 200) {
											$errors[] = $annotationuri. ': Response[' .$response->getResponseBody. ']';
										}
									}
									else {
										$errors[] = $annotationuri. ': Response['. $response. ']';
									}
									unset($response);
									
								}
							}
						}
					}
					else {
						// no cmf uri present => add new annotation to existing graph
						// update needed
						$random_number	= rand(0, 2048);
						$element_id			= $element_id = md5($random_number + time());;
						$fragmenturi 		= trim(ltrim($this->fragment_uri)).trim(ltrim($element_id));
						$annotationuri	= str_replace('fragment', 'annotation', $fragmenturi);
									
						// calculate percentage values for spatial region
						$player['width'] = $original_annotations->video[0]->player_width;
						$player['height'] = $original_annotations->video[0]->player_height;
							
						$video['width'] = $original_annotations->video[0]->width;
						$video['height'] = $original_annotations->video[0]->height;
						
						$suffix = $this->calculateVideoParameters($player, $video, $updated_annotation);
						// print $suffix;
						
						$fragment_new_query = $this->createFragmentUpdateQuery($prefix, $video_uri, $fragmenturi, 'insert', array('original' => array('fragment_uri' => '', 'resource_uri' => '', 'annotation_type' => ''), 'updated' => array('suffix' => $suffix, 'resource_uri' => $updated_annotation->uri, 'annotation_type' => $updated_annotation->type)));
						// send to cmf
						// print $fragment_new_query;
									
						// send query to cmf
						$response = $this->transmitToLMDB($lmdb_url, $service_url, $fragment_new_query);
								
						if (!is_string($response)) {
							if ($response->getResponseCode() != 200) {
								$errors[] = $fragmenturi. ': Response[' .$response->getResponseBody(). ']';
							}
						}
						else {
							$errors[] = $fragmenturi. ': Response['. $response. ']';
						}
						unset($response);
						
						$annotation_new_query = $this->createAnnotationUpdateQuery($prefix, $video_uri, $fragmenturi, $annotationuri, 'insert', array('original' => array('fragment_uri' => '', 'resource_uri' => '', 'annotation_creator' => ''), 'updated' => array('resource_uri' => $updated_annotation->uri, 'annotation_creator' => $_SESSION['cmf']['userid'])));
									
						// print $annotation_new_query;
									
						// send query to cmf
						$response = $this->transmitToLMDB($lmdb_url, $service_url, $annotation_new_query);
									
						if (!is_string($response)) {
							if ($response->getResponseCode() != 200) {
								$errors[] = $annotationuri. ': Response[' .$response->getResponseBody. ']';
							}
						}
						else {
							$errors[] = $annotationuri. ': Response['. $response. ']';
						}
						unset($response);
					}
				}
				
				// loop through existing annotations and delete selected one
				foreach ($original_annotations->annotations as $original_annotation) {
					$delete_item = true;
					foreach ($processed as $processed_annotation) {
						if ($original_annotation->cmfuri == $processed_annotation) {
							$delete_item = false;
						}
					}
					if ($delete_item == true) {
						// delete selected annotation
						
						$annotationuri	= urldecode($original_annotation->cmfuri);
						$fragmenturi 		= str_replace('annotation', 'fragment', urldecode($original_annotation->cmfuri));
						
						$fragment_delete_query = $this->createFragmentUpdateQuery($prefix, $video_uri, $fragmenturi, 'delete', array('original' => array('fragment_uri' => $original_annotation->fragmenturi, 'resource_uri' => $original_annotation->uri, 'annotation_type' => $original_annotation->type), 'updated' => array('suffix' => '', 'resource_uri' => '', 'annotation_type' => '')));
						// send to cmf
						// print $fragment_delete_query;
										
						// send query to cmf
						$response = $this->transmitToLMDB($lmdb_url, $service_url, $fragment_delete_query);
								
						if (!is_string($response)) {
							if ($response->getResponseCode() != 200) {
								$errors[] = $fragmenturi. ': Response[' .$response->getResponseBody(). ']';
							}
						}
						else {
							$errors[] = $fragmenturi. ': Response['. $response. ']';
						}
						unset($response);
							
						$annotation_delete_query = $this->createAnnotationUpdateQuery($prefix, $video_uri, $fragmenturi, $annotationuri, 'delete', array('original' => array('fragment_uri' => $original_annotation->fragmenturi, 'resource_uri' => $original_annotation->uri, 'annotation_creator' => $original_annotation->creator), 'updated' => array('resource_uri' => '', 'annotation_creator' => '')));
										
						// print $annotation_delete_query;
									
						// send query to cmf
						$response = $this->transmitToLMDB($lmdb_url, $service_url, $annotation_delete_query);
										
						if (!is_string($response)) {
							if ($response->getResponseCode() != 200) {
								$errors[] = $annotationuri. ': Response[' .$response->getResponseBody. ']';
							}
						}
						else {
							$errors[] = $annotationuri. ': Response['. $response. ']';
						}
						unset($response);
					}
				}
				
				
				// return $prefix;
				if (count($errors) > 0) {
					return $errors;
				}
				else {
					$response = 200;
					return $response;
				}
			}
			else {
				if (!is_object($updated_annotations)) {
					throw new Exception($updated_annotations);
				}
				else if (!is_object($original_annotations)) {
					throw new Exception($original_annotations);
				}
			}
		}
		catch (Exception $e) {
    		$response = 'ERROR: ' .$e->getMessage();
		}
		
		return $response;
	}
	
	// convert hours, minutes and seconds to seconds only
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
	
	// write data to file
	private function writeToFile($filename, $input) {
		try {

			// delete file (if it already exists), to make sure that it contains only 1 annotations JSON string
			if (file_exists($this->download_path.$filename)) {
				unlink($this->download_path.$filename);
			}
		  
			$file = fopen($this->download_path.$filename, 'a+');
		  
			if (fwrite($file, $input)) {
				$response = $filename;
			}
			else {
				throw new Exception('Error while writing to ' .$this->download_path.$filename);
			}
		  
			fclose($file);
			chmod($this->download_path.$filename, 0777);
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
		return $response;
	}
	
	// write data to logfile
	private function toLog($input) {
		try {
			$file = fopen($this->log_file, 'a+');
			fwrite($file, "\n".$input);
			fclose($file);
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
	}
	
	// transmits data to a specified lmdb and gives its responses back
	// currently this function uses existing webservices of the lmdb to communicate
	private function transmitToLMDB($lmdb_url, $service_url, $sparql_query) {
		try {
			// http request required
			require_once (__ROOT__. '/libraries/HTTP_Request/Request.php');
			
			// create new http request
			$request	= new HTTP_Request();
			$request->setURL($lmdb_url.$service_url);
			$request->setMethod(HTTP_REQUEST_METHOD_POST);
			$request->setBody($sparql_query);
			$request->addHeader('Content-Type', 'text/plain');
			$response	= $request->sendRequest();
			
			// print $lmdb_url.$service_url;
			
			if (PEAR::isError($response)) {
				// return PEAR error
				Throw new Exception($response->getMessage());
			}
			else {
				// check response code
				if ($request->getResponseCode() == 200) {
					// operation successfully completed
					$response = $request;
				}
				else {
					// url not found => return error
					Throw new Exception('ERROR: [Code: ' .$request->getResponseCode(). ']');
				}
			}
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
		return $response;
	}
	
	// creates sparql queries for communicating with lmdb
	public function sparqlUpdate($video_uri, $annotation_file, $sparql_type, $service_url, $lmdb_url) {
		try {
			$query	 = $this->createN3File($video_uri, $annotation_file, $sparql_type);
			$response = $this->transmitToLMDB($lmdb_url, $service_url, $query);	
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
		return $response;
	}
	
	// creates fragment sparql update (insert/delete) query
	private function createFragmentUpdateQuery($prefix, $video_uri, $fragmenturi, $type, $parameters) {
		try {
		
			// create removal query for fragments
			$fragment_update_query = $prefix. "\n";
			// with graph
			$fragment_update_query .= 'WITH <' .$video_uri. '>'. "\n";
			if ($type != 'insert') {
				// delete fragments
				$fragment_update_query .= 'DELETE {'. "\n";
				$fragment_update_query .= '  <' .$video_uri. '> ma:hasFragment <' .$fragmenturi. '>.'. "\n";
				$fragment_update_query .= '  <' .$fragmenturi. '> rdf:type ma:MediaFragment.'. "\n";
				$fragment_update_query .= '  <' .$fragmenturi. '> ma:locator <' .urldecode($parameters['original']['fragment_uri']). '>.'. "\n";
				$fragment_update_query .= '  <' .$fragmenturi. '> cma:' .$parameters['original']['annotation_type']. ' <' .$parameters['original']['resource_uri']. '>.'. "\n";
				$fragment_update_query .= '}'. "\n";
			}
			if ($type != 'delete') {
				// add updated fragments
				$fragment_update_query .= 'INSERT {'. "\n";
				$fragment_update_query .= '  <' .$video_uri. '> ma:hasFragment <' .$fragmenturi. '>.'. "\n";
				$fragment_update_query .= '  <' .$fragmenturi. '> rdf:type ma:MediaFragment.'. "\n";
				$fragment_update_query .= '  <' .$fragmenturi. '> ma:locator <' .$video_uri.$parameters['updated']['suffix'].'>.'. "\n";
				$fragment_update_query .= '  <' .$fragmenturi. '> cma:' .$parameters['updated']['annotation_type']. ' <' .$parameters['updated']['resource_uri']. '>.'. "\n";
				$fragment_update_query .= '}'. "\n";
			}
			// where
			$fragment_update_query .= 'WHERE {'. "\n";
			if (($type != 'insert') && ($type != 'delete')) {
				$fragment_update_query .= '  <' .$video_uri. '> ma:hasFragment <' .$fragmenturi. '>.'. "\n";
			}
			else {
				$fragment_update_query .= '  <' .$video_uri. '> rdf:type ma:MediaResource.'. "\n";
			}
			$fragment_update_query .= '}'. "\n";
			
			$response = $fragment_update_query;
		
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
		return $response;
		
	}
	
	// creates annotation sparql update (insert/delete) query
	private function createAnnotationUpdateQuery($prefix, $video_uri, $fragmenturi, $annotationuri, $type, $parameters) {
		try {
			// create removal query for annotations
			$annotation_update_query = $prefix. "\n";
			// with graph
			$annotation_update_query .= 'WITH <' .$video_uri. '>'. "\n";
			if ($type != 'insert') {
				// delete fragments
				$annotation_update_query .= 'DELETE {'. "\n";
				$annotation_update_query .= '  <' .$annotationuri. '> oac:target <' .$fragmenturi. '>.'. "\n";
				$annotation_update_query .= '  <' .$annotationuri. '> rdf:type oac:Annotation.'. "\n";
				$annotation_update_query .= '  <' .$annotationuri. '> dct:creator <' .urldecode($parameters['original']['annotation_creator']). '>.'. "\n";
				$annotation_update_query .= '  <' .$annotationuri. '> oac:body <' .$parameters['original']['resource_uri']. '>.'. "\n";
				$annotation_update_query .= '}'. "\n";
			}
			if ($type != 'delete') {
				// add updated annotations
				$annotation_update_query .= 'INSERT {'. "\n";
				$annotation_update_query .= '  <' .$annotationuri. '> oac:target <' .$fragmenturi. '>.'. "\n";
				$annotation_update_query .= '  <' .$annotationuri. '> rdf:type oac:Annotation.'. "\n";
				$annotation_update_query .= '  <' .$annotationuri. '> dct:creator <' .$parameters['updated']['annotation_creator'].'>.'. "\n";
				$annotation_update_query .= '  <' .$annotationuri. '> oac:body <' .$parameters['updated']['resource_uri']. '>.'. "\n";
				$annotation_update_query .= '  <' .$parameters['updated']['annotation_creator'].'> rdf:type foaf:Person.'. "\n";
				$annotation_update_query .= '}'. "\n";
			}
			// where
			$annotation_update_query .= 'WHERE {'. "\n";
			if (($type != 'insert') && ($type != 'delete')) {
				$annotation_update_query .= '  <' .$annotationuri. '> rdf:type oac:Annotation.'. "\n";
			}
			else {
				$annotation_update_query .= '  <' .$video_uri. '> rdf:type ma:MediaResource.'. "\n";
			}
			$annotation_update_query .= '}'. "\n";
			
			$response = $annotation_update_query;
			
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
		return $response;
	}
	
	// calculates video parameters and suffix to a given video and its annotation
	private function calculateVideoParameters($player, $video, $current_annotation) {
		try {
									
			$values	= array('x' => round((($current_annotation->x1)/($player['width']/100)), 0), 'y' => round((($current_annotation->y1)/($player['height']/100)), 0), 'height' => round((($current_annotation->height)/($player['height']/100)), 0), 'width' => round((($current_annotation->width)/($player['width']/100)), 0));
									
			// sidebar
			$sidebar = array('width' => round((($player['width'] - $video['width'])/2), 0), 'height' => round((($player['height'] - $video['height'])/2), 0));
									
			if ($player['width'] == $video['width']) {
				// sidebars top/bottom
				if (($current_annotation->y1 >= $sidebar['height']) && ($current_annotation->y1 <= ($sidebar['height'] + $video['height']))) {
					// y1 in video content
					// y1 needs to get reduced by the height of the top sidebar and converted to percent
					$values['y'] = round((($current_annotation->y1 - $sidebar['height'])/($player['height']/100)), 0);
					// x1, width and height stay untouched
				}
				else if ($current_annotation->y1 < $sidebar['height']) {
					// y1 in top sidebar
					// y1 gets set to 0
					$values['y'] = 0;
					// height needs to get reduced by (sidebar top - y1) and converted to percent
					$values['height'] = round((($current_annotation->height - ($sidebar['height'] - $current_annotation->y1))/($player['height']/100)), 0);
					// x1 and width stay untouched
				}
				else if ($current_annotation->y1 > ($sidebar['height'] + $video['height'])) {
					// y1 in bottom sidebar
					// not allowed => set y1 and height to string => annotation area is not used anymore!
					$values['y'] = '';
					$values['height'] = '';
				}
				if (($current_annotation->y2 >= $sidebar['height']) && ($current_annotation->y2 <= ($sidebar['height'] + $video['height']))) {
					// y2 in video content
					// no action required
				}
				else if ($current_annotation->y2 < $sidebar['height']) {
					// y2 in top sidebar =>  not allowed => set y1 and with to string => annotation area is not used anymore!
					// y1 gets set to 0
					$values['y'] = '';
					$values['height'] = '';
				}
				else if ($current_annotation->y2 > ($sidebar['height'] + $video['height'])) {
					// y2 in bottom sidebar
					// reduce annotation height by sidebar height
					$values['height'] = round((($current_annotation->height - ($sidebar['height'] - $current_annotation->y2))/($player['height']/100)), 0);
				}
			}
			elseif ($player['height'] == $video['height']) {
				// sidebars left/right
				if (($current_annotation->x1 >= $sidebar['width']) && ($current_annotation->x1 <= ($sidebar['width'] + $video['width']))) {
					// x1 in video content
					// x1 needs to get reduced by the width of the left sidebar and converted to percent
					$values['x'] = round((($current_annotation->x1 - $sidebar['width'])/($player['width']/100)), 0);
					// y1, width and height stay untouched
				}
				else if ($current_annotation->x1 < $sidebar['width']) {
					// x1 in left sidebar
					// x1 gets set to 0
					$values['x'] = 0;
					// width needs to get reduced by x1 and converted to percent
					$values['width'] = round((($current_annotation->width - ($sidebar['width'] - $current_annotation->x1))/($player['width']/100)), 0);
					// y1 and height stay untouched
				}
				else if ($current_annotation->x1 > ($sidebar['width'] + $video['width'])) {
					// x1 in right sidebar
					// not allowed => set x1 and with to string => annotation area is not used anymore!
					$values['x'] = '';
					$values['width'] = '';
				}
				if (($current_annotation->x2 >= $sidebar['width']) && ($current_annotation->x2 <= ($sidebar['width'] + $video['width']))) {
					// x2 in video content
					// no action required
				}
				else if ($current_annotation->x2 < $sidebar['width']) {
					// x2 in left sidebar =>  not allowed => set x1 and with to string => annotation area is not used anymore!
					// x1 gets set to 0
					$values['x'] = '';
					$values['width'] = '';
				}
				else if ($current_annotation->x2 > ($sidebar['width'] + $video['width'])) {
					// x2 in right sidebar
					// reduce annotation width by sidebar width
					$values['width'] = round((($current_annotation->width - ($sidebar['width'] - $current_annotation->x2))/($player['width']/100)), 0);
				}
			}
			$suffix	= '#';
			// spatial region
			if ((is_numeric($values['x'])) && (is_numeric($values['y'])) && (is_numeric($values['width']) && ($values['width'] > 0)) && (is_numeric($values['height']) && ($values['height'] > 0))) {
				$suffix	.= 'xywh=percent:' .$values['x']. ',' .$values['y']. ',' .$values['width']. ',' .$values['height'];
			}
			// timestamp(s)
			if ($current_annotation->starttime != '') {
				// spatial region set?
				if (strlen($suffix) > 1) {
					// add &
					$suffix	.= '&';
				}
				$suffix	.= 't='.$this->convertToSeconds($current_annotation->starttime);
				if ($current_annotation->endtime != '') {
					$suffix	.= ','.$this->convertToSeconds($current_annotation->endtime);
				}
			}
			
			$response = $suffix;
		}
		catch (Exception $e) {
			$response = 'ERROR: ' .$e->getMessage();
		}
		return $response;
	}

}
?>