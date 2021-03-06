<?php
/**	Geonames search plugin settings
 *	Annotator Plugin
 *
 */

// geonames 
$geonames	= array('search_url' => 'http://api.geonames.org/searchJSON',
									'resource_url' => 'http://sws.geonames.org/',
									'parameters' => array(
											'maxRows' => '30',
											'style' => 'LONG',
											'lang' => 'en',
											'username' => 'sti2research',
											'q' => '')
									);

// google maps
$gmaps		= array('search_url' => 'http://maps.googleapis.com/maps/api/js',
									'parameters' => array(
										'key' => 'AIzaSyCo9Wug3Lw6-PrVu9Y44lHLaWUvnFZ5_fQ',
										'sensor' => 'false'
									)
								);
$gmapsimg = array('url' => 'http://maps.googleapis.com/maps/api/staticmap',
									'width' => '400',
									'height' => '250',
									'zoom' => '12'
								);

?>