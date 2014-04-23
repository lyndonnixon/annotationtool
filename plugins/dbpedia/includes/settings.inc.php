<?php
class DBPedia_settings {
	public $search = array('url' => 'http://admin:pass123@connectme.salzburgresearch.at/CMF/stanbol/config/entityhub/site/dbpedia/find',
												 'parameters' => array(
												 		'limit'  => 20,
														'offset' => 0,
														'ldpath' => 'redirect=<http://dbpedia.org/ontology/wikiPageRedirects>::xsd:anyURI;name=rdfs:label[@en]::xsd:string;comment=rdfs:comment[@en]::xsd:string;'
												 )
										);
	public $max_redirect_depth = 3;
	public $browse = array('url' => 'http://admin:pass123@connectme.salzburgresearch.at/CMF/stanbol/config/entityhub/sites/entity');
}
?>