<?php
/**
 * Settings
 *
 * All settings of the annotation tool are specified here. This file also provides all settings for JS/jQuery implementation.
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */
class Settings {
	
  /**
   * Current version
   *
   * @var string Version of the annotation tool
   */
	public $current_version = 'v2.4';
	
	/**
   * Resource plugins
   *
   * @var array Settings of every available search resource plugin.
   */
	public $resourceplugins =
		array(
			array(
				'name' => 'dbpedia',
				'url' => 'dbpedia.org',
				'loader' => 'plugins/dbpedia/dbpedia.data-lookup.php',
				'parameters' => array(
					'http://www.w3.org/2000/01/rdf-schema#label',
					'http://dbpedia.org/ontology/abstract'
				),
				'search_source' => 'http://dev.iks-project.eu:8081/entityhub/site/dbpedia/find?limit=20&offset=0&name=',
				'browse_source' => 'http://dev.iks-project.eu:8081/entityhub/site/dbpedia/entity?id='
			),
			array( 
				'name' => 'geonames',
				'url' => 'sws.geonames.org',
				'loader' => 'plugins/geonames/geonames.data-lookup.php',
				'parameters' => array(
					'name',
					'map'
				)
			)
		);
		
	/**
   * Annotations path
   *
   * @var string Specifies where annotations get stored on the server (relative from base path)
   */
	public $annotation_path	= 'annotations/';
	
	/**
   * Log file
   *
   * @var string Path and filename of the general log file on the server (relative from base path)
   */
	public $log_file = 'log/general.log';
	
	/**
   * Log list
   *
   * @var string Path and filename of the queries log file on the server (relative from base path)
   */
	public $log_list = 'log/queries.log';
	
	/**
   * Download path
   *
   * @var string Specifies where downloads get stored on the server (relative from base path)
   */
	public $download_path	= 'downloads/';
	
	/**
   * Fragment URI
   *
   * @var string Base URI for annotation fragments (based on ConnectME ontology)
   */
	public $fragment_uri = 'http://connectme.at/fragment/';
	
	/**
   * Annotation URI
   *
   * @var string Base URI for annotations (based on ConnectME ontology)
   */
	public $annotation_uri = 'http://connectme.at/annotation/';
	
	/**
   * Annotation prefixes
   *
   * @var array List of all prefixes which are needed for generating Semantic Web Triples based on the ConnectME onotology and represented by N3, turtle or RDF/XML serializations.
   */
	public $ann_prefix = array('ma'	=> '<http://www.w3.org/ns/ma-ont#>',
								'cma'	=> '<http://connectme.at/ontology#>',
								'rdfs'	=> '<http://www.w3.org/2000/01/rdf-schema#>',
								'xsd'	=> '<http://www.w3.org/2001/XMLSchema#>',
								'dct'	=> '<http://purl.org/dc/terms/>',
								'geo'	=> '<http://www.w3.org/2003/01/geo/wgs84_pos#>',
								'oac'	=> '<http://www.openannotation.org/ns/>',
								'foaf' => '<http://xmlns.com/foaf/0.1/>',
								'rdf' => '<http://www.w3.org/1999/02/22-rdf-syntax-ns#>');

	/**
   * Active plugins
   *
   * @var array All activated plugins (path relative to plugin directory) have to get added here. Pre-annotations should NOT get included!
   */
	public $active_plugins = array('dbpedia', 'geonames');
	
	/**
   * LMDB default
   *
   * @var array Default settings for CMF/LMDB. Gets used if no options are available in the administration.inc.php file or if that file is invalid!
   */
	public $lmdb_default = array('name' => 'STI ConnectME Framework', 'username' => 'admin', 'password' => 'pass123',	'url' => 'http://188.40.162.36:8080/CMF/', 'selected' => 1);
	
	/**
   * LMDB operations
   *
   * @var array Available operations of the CMF/LMDB which get gets accessed via web services (currenly only SPARQL in use!).
   */
	public $lmdb_operations	=
		array(
			'sparql' =>
			array(
				'insert' => 'sparql/update',
				'delete' => 'sparql/update',
				'search' => 'sparql/search'
			)
		);
	
	/**
   * Geonames
   *
   * @var array All required settings for performing geonames search and parse its response.
   */
	public $geonames	= array('search_url' => 'http://api.geonames.org/searchJSON',
						'resource_url' => 'http://sws.geonames.org/',
						'label' => 'Geonames',
						'parameters' => array(
								'maxRows' => '10',
								'style' => 'LONG',
								'lang' => 'en',
								'username' => 'sti2research',
								'q' => ''));
	
	/**
   * Google maps
   *
   * @var array All required settings for performing a google maps search.
   */
	public $gmaps		= array('search_url' => 'http://maps.googleapis.com/maps/api/js', 'parameters' => array(
							'key' => 'AIzaSyCo9Wug3Lw6-PrVu9Y44lHLaWUvnFZ5_fQ',
							'sensor' => 'false'));
	
	/**
   * Check values
   *
   * @var array List of all values which have to get checked before creating a annotation JSON of the current annotation.
   */
	public $check_values = array('starttime', 'endtime', 'uri', 'x1', 'x2', 'width', 'height', 'type', 'annotationtype');
	
	/**
   * Available languages
   *
   * @var array Available languages of the annotation tool
   */
	public $available_languages = array('en', 'de');
	
	/**
   * Default language
   *
   * @var string Default language of the annotation tool.
   */
	public $default_language = 'en';
	
	/**
   * Available annotation types
   *
   * @var array Types for annotations (annotation for linked data content, bookmark for html content)
   */
	public $available_annotation_types = array('http://www.openannotation.org/ns/Annotation', 'http://www.w3.org/ns/openannotation/extensions/Bookmark');
	
	/**
   * Available serializers
   *
   * @var array Available serializers for linked data
   */
	public $available_serializers = array('n3' => 'NTriples', 'turtle' => 'Turtle', 'rdfxml' => 'RDFXML');
	
	/**
   * Serializers header
   *
   * @var array Headers for each serializer
   */
	public $serializers_header = array('n3' => 'text/plain', 'turtle' => 'text/turtle', 'rdfxml' => 'application/rdf+xml');
	
}
?>