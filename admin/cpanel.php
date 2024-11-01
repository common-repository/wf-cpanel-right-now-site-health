<?php
declare( strict_types=1 );
namespace WebFacing\cPanel;

\date_default_timezone_set( 'UTC' );
\header( 'Content-Type: application/json' );
\header( 'Expires: Sat, 21 Jan 2023 12:34:56 GMT' );
\header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );

$token  = \intval( \filter_input( \INPUT_GET, 'token', \FILTER_SANITIZE_NUMBER_INT ) );
$data   = \json_decode( \shell_exec( 'uapi --output=json Variables get_user_information' ) )->result->data;
$time   = \strtotime( 'first day of last month 00:00:00' );
$did    = \idate( 'Y' );
$secret = ( ( $data->last_modified ?? $time ) - ( $data->created ?? $time ) ) + $time + ( $data->uid ?? $did ) + ( $data->gid ?? $did );

if ( $secret === $token ) {
	echo \shell_exec( 'uapi --output=json ResourceUsage get_usages' );
} else {
	\header( $_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden' );
	echo '{ "result": { "data": { "errors": [ "Secret Token mismatch" ] } } }';
}
