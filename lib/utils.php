<?php
namespace WebFacing\cPanel;

/**
 * Exit if accessed directly
 */
\class_exists( __NAMESPACE__ . '\Main' ) || exit;

function error_log( string $message, int $message_type = 0 , ?string $destination = null, ?string $extra_headers = null ): bool {

	if ( Main::$is_debug ) {
		return \error_log( \plugin_basename( PLUGIN_FILE ) . ' ' . $message, $message_type, $destination, $extra_headers );
	} else {
		return true;
	}
}
