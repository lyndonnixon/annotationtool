<?php
/**	lang.inc.php, v1.0
 *
 *	Loads selected language file or default one
 */

// required classes
require_once(__ROOT__. '/core/class/core.class.php');

class Language {
	
	public function __construct() {
		$core = new Core();
		// look for language cookie
		if (isset($_COOKIE['lang']) && ($_COOKIE['lang'] != '')) {
			// language has been selected
			// try to load language file
			if (file_exists($core->settings->base_path.'core/lang/lang.' .$_COOKIE['lang']. '.json')) {
				$file_content = '';
				$file = fopen($core->settings->base_path.'core/lang/lang.' .$_COOKIE['lang']. '.json', 'r');
					
				while (!feof($file)) {
					$file_content .= fgets($file, 4096);
				}
					
				fclose($file);
				
				$language = json_decode(stripslashes($file_content));
				$selected_lang = $_COOKIE['lang'];
				
			}
			// if file is not available load default language file
			else {
				if (file_exists($core->settings->base_path.'core/lang/lang.en.json')) {
					$file_content = '';
					$file = fopen($core->settings->base_path.'core/lang/lang.en.json', 'r');
						
					while (!feof($file)) {
						$file_content .= fgets($file, 4096);
					}
						
					fclose($file);
					
					$language = json_decode(stripslashes($file_content));
					$selected_lang = 'en';
					
				}
			}
			
		}
		else {
			// no language selected => load default language
			if (file_exists($core->settings->base_path.'core/lang/lang.en.json')) {
				$file_content = '';
				$file = fopen($core->settings->base_path.'core/lang/lang.en.json', 'r');
					
				while (!feof($file)) {
					$file_content .= fgets($file, 4096);
				}
					
				fclose($file);
				
				$language = json_decode(stripslashes($file_content));
				$selected_lang = 'en';
				
			}
		}
		$language->main_menu->LANGUAGE->flag = $selected_lang;
		return $language;
	}
}
?>