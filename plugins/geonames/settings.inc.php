<?php
/**
 * Geonames plugin settings
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Cores/Plugins/Geonames
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */
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
									'width' => '420',
									'height' => '300',
									'zoom' => '12'
								);

?>