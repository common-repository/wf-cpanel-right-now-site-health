<?php
namespace WebFacing\cPanel;

/**
 * Exit if accessed directly
 */
\class_exists( __NAMESPACE__ . '\Main' ) || exit;

function __( string $text ): string {
	return \__( $text, Main::$plugin->TextDomain );
}

function _x( string $text, string $context ): string {
	return \_x( $text, $context, Main::$plugin->TextDomain );
}

function _n( string $singular, string $plural, int $number ): string {
	return \_n( $singular, $plural, $number, Main::$plugin->TextDomain );
}

function _nx( string $singular, string $plural, int $number, string $context ): string {
	return \_nx( $singular, $plural, $number, $context, Main::$plugin->TextDomain );
}

function _e( string $text ): void {
	\_e( $text, Main::$plugin->TextDomain );
}

function _ex( string $text, string $context ): void {
	\_ex( $text, $context, Main::$plugin->TextDomain );
}
