<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
// Add files
class AddFile {
	public static function addFiles( $path, $filename, $ext, $state = false ) {
		$file = $path.'/'.$filename.'.'.$ext;

		if($state == false):
			require WOO_TRACKER__PLUGIN_DIR . '/'.$file;
		else:
			return plugins_url( $file, dirname(__FILE__) );
		endif;
	}
}
// ends
