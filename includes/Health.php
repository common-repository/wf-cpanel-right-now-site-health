<?php
declare( strict_types=1 );
namespace WebFacing\cPanel;

/**
 * Exit if accessed directly
 */
\class_exists( __NAMESPACE__ . '\Main' ) || exit;

abstract class Health extends Main {

	public    static function load(): void {

		if ( self::$is_proisp ) {

			\add_filter( 'wp_update_https_url', static function( string $update_url ): string {
				$update_url = _x( 'https://www.proisp.eu/guides/install-free-ssl-certificate-autossl/', 'Site Health Status' );
				return $update_url;
			} );
		}
	}

	public    static function admin(): void {

		\add_action( 'current_screen', static function(): void {

			if ( ! self::$is_cpanel ) return;

			if ( self::$screen->id === 'site-health' ) {
				self::init_data();
			}
		} );

		\add_filter( 'site_status_tests', static function( array $tests ): array {

			if ( self::$is_cpanel && self::$disk_space_max && self::$disk_space_used ) {
				$tests['direct']['disk-space'] = [
					'label' => _x( 'Disk usage', 'Site Health Status Label' ),
					'test'  => [ __CLASS__, 'disk_space_test' ],
				];
			}

			if ( \str_starts_with( \get_home_url(), 'https://' ) ) {
				$tests['direct']['https-only'] = [
					'label' => _x( 'Security', 'Site Health Info' ),
					'test'  => [ __CLASS__, 'https_only_test' ],
				];
			}
//
//			$tests['direct']['keys-salts'] = [
//				'label' => _x( 'Security', 'Site Health Info' ),
//				'test'  => [ __CLASS__, 'keys_salts_test' ],
//			];

			if ( self::$is_cpanel ) {
				$tests['direct']['other-plugin'] = [
					'label' => _x( 'Recommended plugin', 'Site Health Status Label' ),
					'test'  => [ __CLASS__, 'other_plugin_test' ],
				];
			}
			return $tests;
		} );

		\add_filter( 'debug_information', static function( array $debug_info ): array {
			global $wpdb, $table_prefix;;

			/// Dummies
			$text = _x( 'MySQL® Disk Usage',     'Site Health Status Label' );
			$text = _x( 'Entry Processes',       'Site Health Status Label' );
			$text = _x( 'CPU Usage',             'Site Health Status Label' );
			$text = _x( 'I/O Usage',             'Site Health Status Label' );
			$text = _x( 'Physical Memory Usage', 'Site Health Status Label' );
			$text = _x( 'IOPS',                  'Site Health Status Label' );
			$text = _x( 'Number of Processes',   'Site Health Status Label' );
			$text = _x( 'ok',                  '  Site Health Status - Usage status' );

			if ( self::$is_cpanel ) {

				if ( self::$php_log ) {

					if ( \is_multisite() ) {
						$debug_info['wp-paths-sizes'] = [ 'label' => \__( 'Directories and Sizes' ) ];
					} else {
						$total_size = $debug_info['wp-paths-sizes']['fields']['total_size'];
						unset ( $debug_info['wp-paths-sizes']['fields']['total_size'] );
					}
					$debug_info['wp-paths-sizes']['fields']['error_log'] = [
						'label'    => self::$is_debug ?
							_x( 'PHP Debug Log File', 'Site Health Info' ) :
							_x( 'PHP Error Log File', 'Site Health Info' ),
						'value'    => ( \is_null( self::$logfile->Size ) ? _x( 'N/A', 'Site Health Info' ) : \size_format( self::$logfile->Size, 1 ) ) . ' &nbsp; ' . self::$logfile->ShortPath,
						'debug'    => \is_null( self::$logfile->Size ) ? 'N/A' : \size_format( self::$logfile->Size, 2 ),
						'private'  => false,
					];

					if ( ! \is_multisite() ) {
						$debug_info['wp-paths-sizes']['fields']['total_size'] = $total_size;
					}
				}

				$debug_info['wp-server']['fields']['errors'] = [
					'label'   => _x( 'cPanel® Server errors last 24 hours', 'Site Health Info' ),
					'value'   => \count( self::$cpanel_errors ),
					'debug'   => \count( self::$cpanel_errors ) ?: __( '(none)' ),
					'private' => false,
				];
			}

			$debug_info['wp-server']['fields']['php-errors'] = [
				'label' => _x( 'PHP Errors lately', 'Site Health Info' ),
				'value'   => self::$php_log ?
					self::$php_errors :
					_x( 'PHP Logging not enabled', 'Site Health Info' ),
				'debug'   => self::$php_errors ?? 'undefined',
				'private' => false,
			];

			if ( ! \str_contains( $debug_info['wp-constants']['label'], __( 'WebFacing™' ) ) ) {
				$debug_info['wp-constants']['label'] = \str_replace( ' ', ' &amp; ' . __( 'WebFacing™' ) . ' ', $debug_info['wp-constants']['label'] );
			}

			$debug_info['wp-constants']['fields']['WP_LOCAL_DEV' ] = [
				'label'   => 'WP_LOCAL_DEV',
				'value'   => \defined( 'WP_LOCAL_DEV' ) ?
					( self::is_bool( \WP_LOCAL_DEV ) ?
						( \WP_LOCAL_DEV ?
							_x(  'Enabled', 'Site Health Info' ) :
							_x( 'Disabled', 'Site Health Info' )
						) :
						\WP_LOCAL_DEV . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'no' ) . ')',
				'debug'   => \defined( 'WP_LOCAL_DEV' ) ? \WP_LOCAL_DEV : 'undefined',
				'private' => false,
			];

			$debug_info['wp-constants']['fields']['WP_START_TIMESTAMP' ] = [
				'label'   => 'WP_START_TIMESTAMP',
				'value'   => \defined( 'WP_START_TIMESTAMP' ) ?
					( \is_float( \WP_START_TIMESTAMP ) ?
						\number_format_i18n( \WP_START_TIMESTAMP, 3 ) .
							\wp_date( ' \(Y-m-d\TH:i:s\) ', \current_time( 'timestamp', \WP_START_TIMESTAMP ) ) :
						\WP_START_TIMESTAMP . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ),
				'debug'   => \defined( 'WP_START_TIMESTAMP' ) ? \WP_START_TIMESTAMP : 'undefined',
				'private' => true,
			];

			$debug_info['wp-constants']['fields']['WP_HOME'] = [
				'label'   => 'WP_HOME',
				'value'   => \defined( 'WP_HOME' ) ?
					\WP_HOME :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . \get_home_url() . ')',
				'debug'   => \defined( 'WP_HOME' ) ? \WP_HOME : 'undefined',
				'private' => false,
			];

			$debug_info['wp-constants']['fields']['WP_SITEURL'] = [
				'label'   => 'WP_SITEURL',
				'value'   => \defined( 'WP_SITEURL' ) ?
					\WP_SITEURL :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . \get_site_url() . ')',
				'debug'   => \defined( 'WP_SITEURL' ) ? \WP_SITEURL : 'undefined',
				'private' => false,
			];

			$debug_info['wp-constants']['fields']['RELOCATE' ] = [
				'label'   => 'RELOCATE',
				'value'   => \defined( 'RELOCATE' ) ?
					( self::is_bool( \RELOCATE ) ?
						( \RELOCATE ?
							_x(  'Enabled', 'Site Health Info' ) . ' ▮':
							_x( 'Disabled', 'Site Health Info' )
						) :
						\RELOCATE . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'no' ) . ')',
				'debug'   => \defined( 'RELOCATE' ) ? \RELOCATE : 'undefined',
				'private' => false,
			];

			$debug_info['wp-constants']['fields']['DB_HOST'] = [
				'label'   => 'DB_HOST',
				'value'   => \defined( 'DB_HOST' ) ?
					\DB_HOST :
					_x( 'Undefined', 'Site Health Info' ),
				'debug'   => \defined( 'DB_HOST' ) ? \DB_HOST : 'undefined',
				'private' => false,
			];

			if ( \is_super_admin() ) {
				$debug_info['wp-constants']['fields']['DB_NAME'] = [
					'label'   => 'DB_NAME',
					'value'   => \defined( 'DB_NAME' ) ?
						\DB_NAME :
						_x( 'Undefined', 'Site Health Info' ),
					'debug'   => \defined( 'DB_NAME' ) ? \DB_NAME : 'undefined',
					'private' => false,
				];

				$debug_info['wp-constants']['fields']['DB_USER'] = [
					'label'   => 'DB_USER',
					'value'   => \defined( 'DB_USER' ) ?
						\DB_USER :
						_x( 'Undefined', 'Site Health Info' ),
					'debug'   => \defined( 'DB_USER' ) ? \DB_USER : 'undefined',
					'private' => false,
				];

				$debug_info['wp-constants']['fields']['DB_PASSWORD'] = [
					'label'   => 'DB_PASSWORD',
					'value'   => \defined( 'DB_PASSWORD' ) ?
						\DB_PASSWORD :
						_x( 'Undefined', 'Site Health Info' ),
					'debug'   => \defined( 'DB_PASSWORD' ) ? \DB_PASSWORD : 'undefined',
					'private' => true,
				];

				$debug_info['wp-constants']['fields']['table_prefix'] = [
					'label'   => '$table_prefix',
					'value'   => $table_prefix . ' (' . $table_prefix . 'options)',
					'debug'   => $table_prefix,
					'private' => false,
				];

				if ( ! \defined( 'MULTISITE' ) || ! \MULTISITE ) {
					$debug_info['wp-constants']['fields']['CUSTOM_USER_TABLE'] = [
						'label'   => 'CUSTOM_USER_TABLE',
						'value'   => \defined( 'CUSTOM_USER_TABLE' ) ?
							\CUSTOM_USER_TABLE :
							_x( 'Undefined', 'Site Health Info' ) . ' (' . $wpdb->users . ')',
						'debug'   => \defined( 'CUSTOM_USER_TABLE' ) ? \CUSTOM_USER_TABLE : 'undefined',
						'private' => false,
					];

					$debug_info['wp-constants']['fields']['CUSTOM_USER_META_TABLE'] = [
						'label'   => 'CUSTOM_USER_META_TABLE',
						'value'   => \defined( 'CUSTOM_USER_META_TABLE' ) ?
							\CUSTOM_USER_META_TABLE :
							_x( 'Undefined', 'Site Health Info' ) . ' (' . $wpdb->usermeta . ')',
						'debug'   => \defined( 'CUSTOM_USER_META_TABLE' ) ? \CUSTOM_USER_META_TABLE : 'undefined',
						'private' => false,
					];
				}
			}

			$debug_info['wp-constants']['fields']['CUSTOM_TAGS' ] = [
				'label'   => 'CUSTOM_TAGS',
				'value'   => \defined( 'CUSTOM_TAGS' ) ?
					( self::is_bool( \CUSTOM_TAGS ) ?
						( \CUSTOM_TAGS ?
							_x(  'Enabled', 'Site Health Info' ) :
							_x( 'Disabled', 'Site Health Info' )
						) :
						\CUSTOM_TAGS . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'no' ) . ')',
				'debug'   => \defined( 'CUSTOM_TAGS' ) ? \CUSTOM_TAGS : 'undefined',
				'private' => false,
			];
//
//			$debug_info['wp-constants']['fields']['WP_USE_THEMES' ] = [
//				'label'   => 'WP_USE_THEMES',
//				'value'   => \defined( 'WP_USE_THEMES' ) ?
//					( self::is_bool( \WP_USE_THEMES ) ?
//						( \WP_USE_THEMES ?
//							_x( 'Enabled', 'Site Health Info' ) :
//							_x( 'Disabled', 'Site Health Info' ) . ' ▮'
//						) :
//						\WP_USE_THEMES . ' ▮'
//					) :
//					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'yes' ) . ')',
//				'debug'   => \defined( 'WP_USE_THEMES' ) ? \WP_USE_THEMES : 'undefined',
//				'private' => false,
//			];

//			if ( ! \defined( 'WP_USE_THEMES' ) || \WP_USE_THEMES ) {
			$debug_info['wp-constants']['fields']['WP_DEFAULT_THEME' ] = [
				'label'   => 'WP_DEFAULT_THEME',
				'value'   => \defined( 'WP_DEFAULT_THEME' ) ?
					\WP_DEFAULT_THEME . ' (' . ( \wp_get_theme( \WP_DEFAULT_THEME )->exists() ? '' : __( 'not' ) . ' ' ) . __( 'installed' ) . ')' :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'WP_DEFAULT_THEME' ) ? \WP_DEFAULT_THEME : 'undefined',
				'private' => false,
			];
//			}

			$debug_info['wp-constants']['fields']['DISABLE_WP_CRON' ] = [
				'label'   => 'DISABLE_WP_CRON',
				'value'   => \defined( 'DISABLE_WP_CRON' ) ?
					( self::is_bool( \DISABLE_WP_CRON ) ?
						( \DISABLE_WP_CRON ?
							_x(    'Yes, disabled', 'Site Health Info' ) :
							_x( 'No, not disabled', 'Site Health Info' )
						) :
						\DISABLE_WP_CRON . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'not disabled' ) . ')',
				'debug'   => \defined( 'DISABLE_WP_CRON' ) ? \DISABLE_WP_CRON : 'undefined',
				'private' => false,
			];

			if ( ! ( \defined( 'DISABLE_WP_CRON' ) && \DISABLE_WP_CRON ) ) {
				$debug_info['wp-constants']['fields']['ALTERNATE_WP_CRON' ] = [
					'label'   => 'ALTERNATE_WP_CRON',
					'value'   => \defined( 'ALTERNATE_WP_CRON' ) ?
						( self::is_bool( \ALTERNATE_WP_CRON ) ?
							( \ALTERNATE_WP_CRON ?
								_x(  'Enabled', 'Site Health Info' ) :
								_x( 'Disabled', 'Site Health Info' )
							) :
							\ALTERNATE_WP_CRON . ' ▮'
						) :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'no' ) . ')',
					'debug'   => \defined( 'ALTERNATE_WP_CRON' ) ? \ALTERNATE_WP_CRON : 'undefined',
					'private' => false,
				];

				$default = \MINUTE_IN_SECONDS;
				$debug_info['wp-constants']['fields']['WP_CRON_LOCK_TIMEOUT' ] = [
					'label'   => 'WP_CRON_LOCK_TIMEOUT',
					'value'   => \defined( 'WP_CRON_LOCK_TIMEOUT' ) ?
						( \is_bool( \WP_CRON_LOCK_TIMEOUT ) && ! \WP_CRON_LOCK_TIMEOUT ?
							_x( 'Disabled', 'Site Health Info' ) . ' (' . __( 'none' ) . ' )' :
							( \is_numeric( \WP_CRON_LOCK_TIMEOUT ) ?
								( \WP_CRON_LOCK_TIMEOUT . ' (s)' .
									( \WP_CRON_LOCK_TIMEOUT === $default ?
										'' :
										' (' . __( 'default:' ) . ' ' . $default . ')'
									)
								) :
								\WP_CRON_LOCK_TIMEOUT . ' ▮'
							)
						) :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . \MINUTE_IN_SECONDS . 's)',
					'debug'   => \defined( 'AUTOSAVE_INTERVAL' ) ? \AUTOSAVE_INTERVAL : 'undefined',
				];
			}

			$default = \trailingslashit( \get_option( 'siteurl' ) ) . 'wp-content';
			$debug_info['wp-constants']['fields']['WP_CONTENT_URL' ] = [
				'label'   => 'WP_CONTENT_URL',
				'value'   => \defined( 'WP_CONTENT_URL' ) ?
					\WP_CONTENT_URL :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . $default . ')',
				'debug'   => \defined( 'WP_CONTENT_URL' ) ? \WP_CONTENT_URL : 'undefined',
			];

			$default = \WP_CONTENT_DIR . \DIRECTORY_SEPARATOR . 'uploads' . ( \is_multisite() && ! \is_main_site() ? \DIRECTORY_SEPARATOR . 'sites' . \DIRECTORY_SEPARATOR . \get_current_blog_id() : '' );
			$debug_info['wp-constants']['fields']['UPLOADS' ] = [
				'label'   => 'UPLOADS',
				'value'   => \defined( 'UPLOADS' ) ?
					\UPLOADS :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . $default . ')',
				'debug'   => \defined( 'UPLOADS' ) ? \UPLOADS : 'undefined',
			];

			$debug_info['wp-constants']['fields']['WP_PLUGIN_URL' ] = [
				'label'   => 'WP_PLUGIN_URL',
				'value'   => \defined( 'WP_PLUGIN_URL' ) ?
					\WP_PLUGIN_URL :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'WP_PLUGIN_URL' ) ? \WP_PLUGIN_URL : 'undefined',
			];

			$debug_info['wp-constants']['fields']['WPMU_PLUGIN_DIR' ] = [
				'label'   => 'WPMU_PLUGIN_DIR',
				'value'   => \defined( 'WPMU_PLUGIN_DIR' ) ?
					\WPMU_PLUGIN_DIR :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'WPMU_PLUGIN_DIR' ) ? \WPMU_PLUGIN_DIR : 'undefined',
			];

			$debug_info['wp-constants']['fields']['WPMU_PLUGIN_URL' ] = [
				'label'   => 'WPMU_PLUGIN_URL',
				'value'   => \defined( 'WPMU_PLUGIN_URL' ) ?
					\WPMU_PLUGIN_URL :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'WPMU_PLUGIN_URL' ) ? \WPMU_PLUGIN_URL : 'undefined',
			];

			$debug_info['wp-constants']['fields']['TEMPLATEPATH' ] = [
				'label'   => 'TEMPLATEPATH',
				'value'   => \defined( 'TEMPLATEPATH' ) ?
					\TEMPLATEPATH :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'TEMPLATEPATH' ) ? \TEMPLATEPATH : 'undefined',
			];

			$debug_info['wp-constants']['fields']['STYLESHEETPATH' ] = [
				'label'   => 'STYLESHEETPATH',
				'value'   => \defined( 'STYLESHEETPATH' ) ?
					\STYLESHEETPATH :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'STYLESHEETPATH' ) ? \STYLESHEETPATH : 'undefined',
			];

			$debug_info['wp-constants']['fields']['WP_TEMP_DIR' ] = [
				'label'   => 'WP_TEMP_DIR',
				'value'   => \defined( 'WP_TEMP_DIR' ) ?
					\WP_TEMP_DIR :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . \get_temp_dir() . ')',
				'debug'   => \defined( 'WP_TEMP_DIR' ) ? \WP_TEMP_DIR : 'undefined',
			];

			$default = false;
			$debug_info['wp-constants']['fields']['COOKIE_DOMAIN' ] = [
				'label'   => 'COOKIE_DOMAIN',
				'value'   => \defined( 'COOKIE_DOMAIN' ) ?
					( \is_bool( \COOKIE_DOMAIN ) && ! \COOKIE_DOMAIN ?
						_x( 'Disabled', 'Site Health Info' ) :
						\COOKIE_DOMAIN
					) . ( \COOKIE_DOMAIN === $default ?
						'' :
						' (' . __( 'default:' ) . ' ' . _x( 'Disabled', 'Site Health Info' ) . ')'
						) :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'COOKIE_DOMAIN' ) ? \COOKIE_DOMAIN : 'undefined',
				'private' => false,
			];

			$default = \preg_replace( '|https?://[^/]+|i', '', \trailingslashit( \get_option( 'home' ) ) );
			$debug_info['wp-constants']['fields']['COOKIEPATH' ] = [
				'label'   => 'COOKIEPATH',
				'value'   => \defined( 'COOKIEPATH' ) ?
					( \is_bool( \COOKIEPATH ) && ! \COOKIEPATH ?
						_x( 'Disabled', 'Site Health Info' ) . ' ▮' :
						\COOKIEPATH .
							( \COOKIEPATH === $default ?
								'' :
								' (' . __( 'default:' ) . ' ' . $default . ')'
							)
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'COOKIEPATH' ) ? \COOKIEPATH : 'undefined',
				'private' => false,
			];

			$default = \preg_replace( '|https?://[^/]+|i', '', \trailingslashit( \get_option( 'siteurl' ) ) );
			$debug_info['wp-constants']['fields']['SITECOOKIEPATH' ] = [
				'label'   => 'SITECOOKIEPATH',
				'value'   => \defined( 'SITECOOKIEPATH' ) ?
					( \is_bool( \SITECOOKIEPATH ) && ! \SITECOOKIEPATH ?
						_x( 'Disabled', 'Site Health Info' ) . ' ▮' :
						\SITECOOKIEPATH .
							( \SITECOOKIEPATH === $default ?
								'' :
								' (' . __( 'default:' ) . ' ' . $default . ')'
							)
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'SITECOOKIEPATH' ) ? \SITECOOKIEPATH : 'undefined',
				'private' => false,
			];

			$default = \SITECOOKIEPATH . 'wp-admin';
			$debug_info['wp-constants']['fields']['ADMIN_COOKIE_PATH' ] = [
				'label'   => 'ADMIN_COOKIE_PATH',
				'value'   => \defined( 'ADMIN_COOKIE_PATH' ) ?
					( \is_bool( \ADMIN_COOKIE_PATH ) && ! \ADMIN_COOKIE_PATH ?
						_x( 'Disabled', 'Site Health Info' ) . ' ▮' :
						\ADMIN_COOKIE_PATH .
							( \ADMIN_COOKIE_PATH === $default ?
								'' :
								' (' . __( 'default:' ) . ' ' . $default . ')'
							)
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'ADMIN_COOKIE_PATH' ) ? \ADMIN_COOKIE_PATH : 'undefined',
				'private' => false,
			];

			$default = \preg_replace( '|https?://[^/]+|i', '', \WP_PLUGIN_URL );
			$debug_info['wp-constants']['fields']['PLUGINS_COOKIE_PATH' ] = [
				'label'   => 'PLUGINS_COOKIE_PATH',
				'value'   => \defined( 'PLUGINS_COOKIE_PATH' ) ?
					( \is_bool( \PLUGINS_COOKIE_PATH ) && ! \PLUGINS_COOKIE_PATH ?
						_x( 'Disabled', 'Site Health Info' ) :
						\PLUGINS_COOKIE_PATH .
							( \PLUGINS_COOKIE_PATH === $default ?
								'' :
								' (' . __( 'default:' ) . ' ' . $default . ')'
							)
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'PLUGINS_COOKIE_PATH' ) ? \PLUGINS_COOKIE_PATH : 'undefined',
				'private' => false,
			];

			$siteurl = \get_site_option( 'siteurl' );
			$default = $siteurl ? \md5( $siteurl ) : '';
			$debug_info['wp-constants']['fields']['COOKIEHASH' ] = [
				'label'   => 'COOKIEHASH',
				'value'   => \defined( 'COOKIEHASH' ) ?
					\COOKIEHASH .
						( \COOKIEHASH === $default ?
							'' :
							' (' . __( 'default:' ) . ' ' . $default . ')'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'COOKIEHASH' ) ? \COOKIEHASH : 'undefined',
				'private' => \COOKIEHASH !== $default,
			];

			$default = 'wordpress_test_cookie';
			$debug_info['wp-constants']['fields']['TEST_COOKIE' ] = [
				'label'   => 'TEST_COOKIE',
				'value'   => \defined( 'TEST_COOKIE' ) ?
					\TEST_COOKIE .
						( \TEST_COOKIE === $default ?
							'' :
							' (' . __( 'default:' ) . ' ' . $default . ')'
						) . ' {' .
						( empty( $_COOKIE[ \TEST_COOKIE ] ) ?
							__( 'not' ) . ' ' :
							''
						) . __( 'set' )  . '}' :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'TEST_COOKIE' ) ? \TEST_COOKIE : 'undefined',
				'private' => false,
			];

			if ( \is_ssl() ) {
				$default = 'wordpress_sec_' . \COOKIEHASH;
				$debug_info['wp-constants']['fields']['SECURE_AUTH_COOKIE' ] = [
					'label'   => 'SECURE_AUTH_COOKIE',
					'value'   => \defined( 'SECURE_AUTH_COOKIE' ) ?
						\SECURE_AUTH_COOKIE .
							( \SECURE_AUTH_COOKIE === $default ?
								'' :
								' (' . __( 'default:' ) .
							' ' . $default . ')' ) . ' {' .
							( empty( $_COOKIE[ \SECURE_AUTH_COOKIE ] ) ?
								__( 'not' ) . ' ' :
								''
							) . __( 'set' )  . '}' :
						_x( 'Undefined', 'Site Health Info' ) . ' ▮',
					'debug'   => \defined( 'SECURE_AUTH_COOKIE' ) ? \SECURE_AUTH_COOKIE : 'undefined',
					'private' => \SECURE_AUTH_COOKIE !== $default,
				];
			} else {

				$default =  'wordpress_' . \COOKIEHASH;
				$debug_info['wp-constants']['fields']['AUTH_COOKIE' ] = [
					'label'   => 'AUTH_COOKIE',
					'value'   => \defined( 'AUTH_COOKIE' ) ?
						\AUTH_COOKIE .
							( \AUTH_COOKIE === $default ?
								'' :
								' (' . __( 'default:' ) .
							' ' . $default . ')' ) . ' {' .
							( empty( $_COOKIE[ \AUTH_COOKIE ] ) ?
								__( 'not' ) . ' ' :
								''
							) . __( 'set' )  . '}' :
						_x( 'Undefined', 'Site Health Info' ) . ' ▮',
					'debug'   => \defined( 'AUTH_COOKIE' ) ? \AUTH_COOKIE : 'undefined',
					'private' => \AUTH_COOKIE !== $default,
				];
			}

			$default = 'wordpress_logged_in_' . \COOKIEHASH;
			$debug_info['wp-constants']['fields']['LOGGED_IN_COOKIE' ] = [
				'label'   => 'LOGGED_IN_COOKIE',
				'value'   => \defined( 'LOGGED_IN_COOKIE' ) ?
						\LOGGED_IN_COOKIE .
							( \LOGGED_IN_COOKIE === $default ?
								'' :
								' (' . __( 'default:' ) .
							' ' . $default . ')' ) . ' {' .
							( empty( $_COOKIE[ \LOGGED_IN_COOKIE ] ) ?
								__( 'not' ) . ' ' :
								''
							) . __( 'set' )  . '}' :
					_x( 'Undefined', 'Site Health Info' ) . ' ▮',
				'debug'   => \defined( 'LOGGED_IN_COOKIE' ) ? \LOGGED_IN_COOKIE : 'undefined',
				'private' => \LOGGED_IN_COOKIE !== $default,
			];

			if ( ! \defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) || ! \WP_DISABLE_FATAL_ERROR_HANDLER ) {
				$default = 'wordpress_rec_' . \COOKIEHASH;

				$debug_info['wp-constants']['fields']['RECOVERY_MODE_COOKIE' ] = [
					'label'   => 'RECOVERY_MODE_COOKIE',
					'value'   => \defined( 'RECOVERY_MODE_COOKIE' ) ?
							\RECOVERY_MODE_COOKIE .
								( \RECOVERY_MODE_COOKIE === $default ?
									'' :
									' (' . __( 'default:' ) .
								' ' . $default . ')'
								) . ' {' . ( empty( $_COOKIE[ \RECOVERY_MODE_COOKIE ] ) ?
									__( 'not' ) . ' ' :
									''
								) . __( 'set' )  . '}' :
						_x( 'Undefined', 'Site Health Info' ) . ' ▮',
					'debug'   => \defined( 'RECOVERY_MODE_COOKIE' ) ? \RECOVERY_MODE_COOKIE : 'undefined',
					'private' => \RECOVERY_MODE_COOKIE !== $default,
				];

				$debug_info['wp-constants']['fields']['RECOVERY_MODE_EMAIL' ] = [
					'label'   => 'RECOVERY_MODE_EMAIL',
					'value'   => \defined( 'RECOVERY_MODE_EMAIL' ) ?
						\RECOVERY_MODE_EMAIL . ( \is_email( \RECOVERY_MODE_EMAIL ) ? '' : ' ▮' ) :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . \get_option( 'admin_email' ) . ')',
					'debug'   => \defined( 'RECOVERY_MODE_EMAIL' ) ? \RECOVERY_MODE_EMAIL : 'undefined',
					'private' => false,
				];
			}

			$default = \is_ssl();
			$debug_info['wp-constants']['fields']['FORCE_SSL_ADMIN' ] = [
				'label'   => 'FORCE_SSL_ADMIN',
				'value'   => \defined( 'FORCE_SSL_ADMIN' ) ?
					( self::is_bool( \FORCE_SSL_ADMIN ) ?
						( \FORCE_SSL_ADMIN ?
							_x(  'Enabled', 'Site Health Info' ) :
							_x( 'Disabled', 'Site Health Info' )
						) :
						\FORCE_SSL_ADMIN . ( \FORCE_SSL_ADMIN === $default ?
							'' :
							' (' . __( 'default:' ) . ' ' . ( $default ?
								_x(  'Enabled', 'Site Health Info' ) :
								_x( 'Disabled', 'Site Health Info' ) . ') ▮'
							)
						)
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'no' ) . ')' . ( $default ? ' ▮' : '' ),
				'debug'   => \defined( 'FORCE_SSL_ADMIN' ) ? \FORCE_SSL_ADMIN : 'undefined',
				'private' => false,
			];

			if ( ! ( \defined( 'FORCE_SSL_ADMIN' ) && \FORCE_SSL_ADMIN ) ) {
				$debug_info['wp-constants']['fields']['FORCE_SSL_LOGIN' ] = [
					'label'   => 'FORCE_SSL_LOGIN',
					'value'   => \defined( 'FORCE_SSL_LOGIN' ) ?
						( self::is_bool( \FORCE_SSL_LOGIN ) ?
							( \FORCE_SSL_LOGIN ?
								_x(  'Enabled', 'Site Health Info' ) :
								_x( 'Disabled', 'Site Health Info' )
							) :
							\FORCE_SSL_LOGIN . ( \FORCE_SSL_LOGIN === $default ?
								'' :
								' (' . __( 'default:' ) . ' ' . ( $default ?
									_x(  'Enabled', 'Site Health Info' ) :
									_x( 'Disabled', 'Site Health Info' ) . ') ▮'
								)
							)
						) :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'no' ) . ')' . ( $default ? ' ▮' : '' ),
					'debug'   => \defined( 'FORCE_SSL_LOGIN' ) ? \FORCE_SSL_LOGIN : 'undefined',
					'private' => false,
				];
			}

			$default = false;
			$debug_info['wp-constants']['fields']['SAVEQUERIES' ] = [
				'label'   => 'SAVEQUERIES',
				'value'   => \defined( 'SAVEQUERIES' ) ?
					( self::is_bool( \SAVEQUERIES ) ?
						( \SAVEQUERIES ?
							_x(  'Enabled', 'Site Health Info' ) . ' ▮' :
							_x( 'Disabled', 'Site Health Info' )
						) :
						\SAVEQUERIES . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'no' ) . ')',
				'debug'   => \defined( 'SAVEQUERIES' ) ? \SAVEQUERIES : 'undefined',
				'private' => false,
			];

			$debug_info['wp-constants']['fields']['ENFORCE_GZIP' ] = [
				'label'   => 'ENFORCE_GZIP',
				'value'   => \defined( 'ENFORCE_GZIP' ) ?
					( self::is_bool( \ENFORCE_GZIP ) ?
						( \ENFORCE_GZIP ?
							_x(  'Enabled', 'Site Health Info' ) :
							_x( 'Disabled', 'Site Health Info' )
						) :
						\ENFORCE_GZIP . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'not' ) . ')',
				'debug'   => \defined( 'ENFORCE_GZIP' ) ? \ENFORCE_GZIP : 'undefined',
				'private' => false,
			];

			if ( \defined( 'WP_CACHE' ) && \WP_CACHE && \wp_using_ext_object_cache() ) {

				$debug_info['wp-constants']['fields']['WP_CACHE_KEY_SALT' ] = [
					'label'   => 'WP_CACHE_KEY_SALT',
					'value'   => \defined( 'WP_CACHE_KEY_SALT' ) ?
						\WP_CACHE_KEY_SALT :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'none' ) . ')',
					'debug'   => \defined( 'WP_CACHE_KEY_SALT' ) ? \WP_CACHE_KEY_SALT : 'undefined',
					'private' => \defined( 'WP_CACHE_KEY_SALT' ) && ! empty( \WP_CACHE_KEY_SALT ),
				];
			}

			$debug_info['wp-constants']['fields']['WP_DISABLE_FATAL_ERROR_HANDLER' ] = [
				'label'   => 'WP_DISABLE_FATAL_ERROR_HANDLER',
				'value'   => \defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) ?
					( self::is_bool( \WP_DISABLE_FATAL_ERROR_HANDLER ) ?
						( \WP_DISABLE_FATAL_ERROR_HANDLER ?
							_x(    'Yes, disabled', 'Site Health Info' ) :
							_x( 'No, not disabled', 'Site Health Info' )
						) :
						\WP_DISABLE_FATAL_ERROR_HANDLER . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'enabled' ) . ')',
				'debug'   => \defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) ? \WP_DISABLE_FATAL_ERROR_HANDLER : 'undefined',
				'private' => false,
			];

			$debug_info['wp-constants']['fields']['DISALLOW_UNFILTERED_HTML' ] = [
				'label'   => 'DISALLOW_UNFILTERED_HTML',
				'value'   => \defined( 'DISALLOW_UNFILTERED_HTML' ) ?
					( self::is_bool( \DISALLOW_UNFILTERED_HTML ) ?
						( \DISALLOW_UNFILTERED_HTML ?
							_x(    'Yes, disallowed', 'Site Health Info' ) :
							_x( 'No, not disallowed', 'Site Health Info' )
						) :
						\DISALLOW_UNFILTERED_HTML . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'not disallowed' ) . ')',
				'debug'   => \defined( 'DISALLOW_UNFILTERED_HTML' ) ? \DISALLOW_UNFILTERED_HTML : 'undefined',
				'private' => false,
			];

			$debug_info['wp-constants']['fields']['ALLOW_UNFILTERED_UPLOADS' ] = [
				'label'   => 'ALLOW_UNFILTERED_UPLOADS',
				'value'   => \defined( 'ALLOW_UNFILTERED_UPLOADS' ) ?
					( self::is_bool( \ALLOW_UNFILTERED_UPLOADS ) ?
						( \ALLOW_UNFILTERED_UPLOADS ?
							_x(    'Yes, allowed', 'Site Health Info' ) :
							_x( 'No, not allowed', 'Site Health Info' )
						) :
						\ALLOW_UNFILTERED_UPLOADS . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'not allowed' ) . ')',
				'debug'   => \defined( 'ALLOW_UNFILTERED_UPLOADS' ) ? \ALLOW_UNFILTERED_UPLOADS : 'undefined',
				'private' => false,
			];

			$debug_info['wp-constants']['fields']['WP_ALLOW_REPAIR' ] = [
				'label'   => 'WP_ALLOW_REPAIR',
				'value'   => \defined( 'WP_ALLOW_REPAIR' ) ?
					( self::is_bool( \WP_ALLOW_REPAIR ) ?
						( \WP_ALLOW_REPAIR ?
							_x(    'Yes, allowed', 'Site Health Info' ) :
							_x( 'No, not allowed', 'Site Health Info' )
						) :
						\WP_ALLOW_REPAIR . ( \WP_ALLOW_REPAIR ? ' ▮' : '' )
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'not allowed' ) . ')',
				'debug'   => \defined( 'WP_ALLOW_REPAIR' ) ? \WP_ALLOW_REPAIR : 'undefined',
				'private' => false,
			];

			$debug_info['wp-constants']['fields']['CORE_UPGRADE_SKIP_NEW_BUNDLED' ] = [
				'label'   => 'CORE_UPGRADE_SKIP_NEW_BUNDLED',
				'value'   => \defined( 'CORE_UPGRADE_SKIP_NEW_BUNDLED' ) ?
					( self::is_bool( \CORE_UPGRADE_SKIP_NEW_BUNDLED ) ?
						( \CORE_UPGRADE_SKIP_NEW_BUNDLED ?
							_x( 'Yes, do not install', 'Site Health Info' ) :
							_x(         'No, install', 'Site Health Info' )
						) :
						\CORE_UPGRADE_SKIP_NEW_BUNDLED . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'install' ) . ')',
				'debug'   => \defined( 'CORE_UPGRADE_SKIP_NEW_BUNDLED' ) ? \CORE_UPGRADE_SKIP_NEW_BUNDLED : 'undefined',
				'private' => false,
			];

			$debug_info['wp-constants']['fields']['WP_HTTP_BLOCK_EXTERNAL' ] = [
				'label'   => 'WP_HTTP_BLOCK_EXTERNAL',
				'value'   => \defined( 'WP_HTTP_BLOCK_EXTERNAL' ) ?
					( self::is_bool( \WP_HTTP_BLOCK_EXTERNAL ) ?
						( \WP_HTTP_BLOCK_EXTERNAL ?
							_x(     'Yes, blocked', 'Site Health Info' ) . ' ▮' :
							_x( 'No, none blocked', 'Site Health Info' )
						) :
						\WP_HTTP_BLOCK_EXTERNAL . ( \WP_HTTP_BLOCK_EXTERNAL ? ' ▮' : '' )
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'none blocked' ) . ')',
				'debug'   => \defined( 'WP_HTTP_BLOCK_EXTERNAL' ) ? \WP_HTTP_BLOCK_EXTERNAL : 'undefined',
			];

			if ( \defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && \WP_HTTP_BLOCK_EXTERNAL ) {

				$default = 'wordpress.org';
				$debug_info['wp-constants']['fields']['WP_ACCESSIBLE_HOSTS' ] = [
					'label'   => 'WP_ACCESSIBLE_HOSTS',
					'value'   => \defined( 'WP_ACCESSIBLE_HOSTS' ) ?
						\WP_ACCESSIBLE_HOSTS . ( \str_contains( \strval( \WP_ACCESSIBLE_HOSTS ), $default ) ? '' : ' ▮' ) :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'all' ) . ')',
					'debug'   => \defined( 'WP_ACCESSIBLE_HOSTS' ) ? \WP_ACCESSIBLE_HOSTS : 'undefined',
					'private' => false,
				];
			}

			$debug_info['wp-constants']['fields']['DISALLOW_FILE_MODS' ] = [
				'label'   => 'DISALLOW_FILE_MODS',
				'value'   => \defined( 'DISALLOW_FILE_MODS' ) ?
					( self::is_bool( \DISALLOW_FILE_MODS ) ?
						( \DISALLOW_FILE_MODS ?
							_x( 'Yes, not allowed', 'Site Health Info' ) . ' ▮' :
							_x(      'No, allowed', 'Site Health Info' )
						) :
						\DISALLOW_FILE_MODS . ( \DISALLOW_FILE_MODS ? ' ▮' : '' )
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'not disallowed' ) . ')',
				'debug'   => \defined( 'DISALLOW_FILE_MODS' ) ? \DISALLOW_FILE_MODS : 'undefined',
				'private' => false,
			];

			if ( ! ( \defined( 'DISALLOW_FILE_MODS' ) && \DISALLOW_FILE_MODS ) ) {

				$debug_info['wp-constants']['fields']['AUTOMATIC_UPDATER_DISABLED' ] = [
					'label'   => 'AUTOMATIC_UPDATER_DISABLED',
					'value'   => \defined( 'AUTOMATIC_UPDATER_DISABLED' ) ?
						( self::is_bool( \AUTOMATIC_UPDATER_DISABLED ) ?
							( \AUTOMATIC_UPDATER_DISABLED ?
								_x( 'Yes, disabled', 'Site Health Info' ) :
								_x(   'No, enabled', 'Site Health Info' )
							) :
							\AUTOMATIC_UPDATER_DISABLED . ' ▮'
						) :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'not disabled' ) . ')',
					'debug'   => \defined( 'AUTOMATIC_UPDATER_DISABLED' ) ? \AUTOMATIC_UPDATER_DISABLED : 'undefined',
					'private' => false,
				];

				$debug_info['wp-constants']['fields']['FS_METHOD' ] = [
					'label'   => 'FS_METHOD',
					'value'   => \defined( 'FS_METHOD' ) ?
						\FS_METHOD :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'direct' ) . ')',
					'debug'   => \defined( 'FS_METHOD' ) ? \FS_METHOD : 'undefined',
					'private' => false,
				];

				$debug_info['wp-constants']['fields']['FS_CHMOD_DIR' ] = [
					'label'   => 'FS_CHMOD_DIR',
					'value'   => \defined( 'FS_CHMOD_DIR' ) ?
						'0' . \decoct( \FS_CHMOD_DIR ) :
						_x( 'Undefined', 'Site Health Info' ) . ' (0' . \decoct( 0755 & ~\umask() ) . ')',
					'debug'   => \defined( 'FS_CHMOD_DIR' ) ? \FS_CHMOD_DIR : 'undefined',
					'private' => false,
				];

				$debug_info['wp-constants']['fields']['FS_CHMOD_FILE' ] = [
					'label'   => 'FS_CHMOD_FILE',
					'value'   => \defined( 'FS_CHMOD_FILE' ) ?
						'0' . \decoct( \FS_CHMOD_FILE ) :
						_x( 'Undefined', 'Site Health Info' ) . ' (0' . \decoct( 0644 & ~\umask() ) . ')',
					'debug'   => \defined( 'FS_CHMOD_FILE' ) ? \FS_CHMOD_FILE : 'undefined',
					'private' => false,
				];

				if ( ! ( \defined( 'AUTOMATIC_UPDATER_DISABLED' ) && \AUTOMATIC_UPDATER_DISABLED ) ) {

					$debug_info['wp-constants']['fields']['WP_AUTO_UPDATE_CORE' ] = [
						'label'   => 'WP_AUTO_UPDATE_CORE',
						'value'   => \defined( 'WP_AUTO_UPDATE_CORE' ) ?
							( self::is_bool( \WP_AUTO_UPDATE_CORE ) ?
								( \WP_AUTO_UPDATE_CORE ?
									_x(  'Enabled', 'Site Health Info' ) :
									_x( 'Disabled', 'Site Health Info' ) . ' ▮'
								) :
								\WP_AUTO_UPDATE_CORE . ' ▮'
							) :
							_x( 'Undefined', 'Site Health Info' ) . ' (minor)',
						'debug'   => \defined( 'WP_AUTO_UPDATE_CORE' ) ? \WP_AUTO_UPDATE_CORE : 'undefined',
						'private' => false,
					];
				}

				$debug_info['wp-constants']['fields']['DISALLOW_FILE_EDIT' ] = [
					'label'   => 'DISALLOW_FILE_EDIT',
					'value'   => \defined( 'DISALLOW_FILE_EDIT' ) ?
						( self::is_bool( \DISALLOW_FILE_EDIT ) ?
							( \DISALLOW_FILE_EDIT ?
								_x( 'Yes, disallowed', 'Site Health Info' ) :
								_x(     'No, allowed', 'Site Health Info' )
							) :
							\DISALLOW_FILE_EDIT . ' ▮'
						) :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'not disallowed' ) . ')',
					'debug'   => \defined( 'DISALLOW_FILE_EDIT' ) ? \DISALLOW_FILE_EDIT : 'undefined',
					'private' => false,
				];

				$debug_info['wp-constants']['fields']['IMAGE_EDIT_OVERWRITE' ] = [
					'label'   => 'IMAGE_EDIT_OVERWRITE',
					'value'   => \defined( 'IMAGE_EDIT_OVERWRITE' ) ?
						( self::is_bool( \IMAGE_EDIT_OVERWRITE ) ?
							( \IMAGE_EDIT_OVERWRITE ?
								_x(  'Enabled', 'Site Health Info' ) :
								_x( 'Disabled', 'Site Health Info' )
							) :
							\IMAGE_EDIT_OVERWRITE . ' ▮'
						) :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'no overwrite' ) . ')',
					'debug'   => \defined( 'IMAGE_EDIT_OVERWRITE' ) ? \IMAGE_EDIT_OVERWRITE : 'undefined',
					'private' => false,
				];
			}

			$debug_info['wp-constants']['fields']['MEDIA_TRASH' ] = [
				'label'   => 'MEDIA_TRASH',
				'value'   => \defined( 'MEDIA_TRASH' ) ?
					( self::is_bool( \MEDIA_TRASH ) ?
						( \MEDIA_TRASH ?
							_x(  'Enabled', 'Site Health Info' ) :
							_x( 'Disabled', 'Site Health Info' )
						) :
						\MEDIA_TRASH . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'allowed' ) . ')',
				'debug'   => \defined( 'MEDIA_TRASH' ) ? \MEDIA_TRASH : 'undefined',
				'private' => false,
			];

			$default = \MINUTE_IN_SECONDS;
			$debug_info['wp-constants']['fields']['AUTOSAVE_INTERVAL' ] = [
				'label'   => 'AUTOSAVE_INTERVAL',
				'value'   => \defined( 'AUTOSAVE_INTERVAL' ) ?
					( \is_bool( \AUTOSAVE_INTERVAL ) && ! \AUTOSAVE_INTERVAL ?
						_x( 'Disabled', 'Site Health Info' ) . ' (' . __( 'none' ) . ' )' :
						( \is_numeric( \AUTOSAVE_INTERVAL ) ?
							( \AUTOSAVE_INTERVAL . ' (s)' .
								( \AUTOSAVE_INTERVAL === $default ?
									'' :
									' (' . __( 'default:' ) . ' ' . $default . ')'
								)
							) :
							\AUTOSAVE_INTERVAL . ' ▮'
						)
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . \MINUTE_IN_SECONDS . 's)',
				'debug'   => \defined( 'AUTOSAVE_INTERVAL' ) ? \AUTOSAVE_INTERVAL : 'undefined',
			];

			$debug_info['wp-constants']['fields']['WP_POST_REVISIONS' ] = [
				'label'   => 'WP_POST_REVISIONS',
				'value'   => \defined( 'WP_POST_REVISIONS' ) ?
					( \is_bool( \WP_POST_REVISIONS ) && ! \WP_POST_REVISIONS ?
						_x( 'Disabled', 'Site Health Info' ) . ' (0)' :
						( \is_numeric( \WP_POST_REVISIONS ) ?
							\WP_POST_REVISIONS :
							\WP_POST_REVISIONS . ' ▮'
						)
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (∞)',
				'debug'   => \defined( 'WP_POST_REVISIONS' ) ? \WP_POST_REVISIONS : 'undefined',
				'private' => false,
			];

			$default = \MONTH_IN_SECONDS / \DAY_IN_SECONDS;
			$debug_info['wp-constants']['fields']['EMPTY_TRASH_DAYS' ] = [
				'label'   => 'EMPTY_TRASH_DAYS',
				'value'   => \defined( 'EMPTY_TRASH_DAYS' ) ?
					( \is_bool( \EMPTY_TRASH_DAYS ) && ! \EMPTY_TRASH_DAYS ?
						_x( 'Disabled', 'Site Health Info' ) . ' (∞)' :
						( \is_numeric( \EMPTY_TRASH_DAYS ) ?
							\EMPTY_TRASH_DAYS .
								( \EMPTY_TRASH_DAYS === $default ?
									'' :
									' (' . __( 'default:' ) . ' ' . $default . ')'
								) :
							\EMPTY_TRASH_DAYS . ' ▮'
						)
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' , $default . ')',
				'debug'   => \defined( 'EMPTY_TRASH_DAYS' ) ? \EMPTY_TRASH_DAYS : 'undefined',
			];

			$debug_info['wp-constants']['fields']['MULTISITE' ] = [
				'label'   => 'MULTISITE',
				'value'   => \defined( 'MULTISITE' ) ?
					( self::is_bool( \MULTISITE ) ?
						( \MULTISITE ?
							_x(  'Enabled', 'Site Health Info' ) :
							_x( 'Disabled', 'Site Health Info' )
						) :
						\MULTISITE . ' ▮'
					) :
					_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'off' ) . ')',
				'debug'   => \defined( 'MULTISITE' ) ? \MULTISITE : 'undefined',
				'private' => false,
			];

			if ( \defined( 'MULTISITE' ) && \MULTISITE ) {
				$debug_info['wp-constants']['fields']['DO_NOT_UPGRADE_GLOBAL_TABLES' ] = [
					'label'   => 'DO_NOT_UPGRADE_GLOBAL_TABLES',
					'value'   => \defined( 'DO_NOT_UPGRADE_GLOBAL_TABLES' ) ?
						( self::is_bool( \DO_NOT_UPGRADE_GLOBAL_TABLES ) ?
							( \DO_NOT_UPGRADE_GLOBAL_TABLES ?
								_x(  'Enabled', 'Site Health Info' ) :
								_x( 'Disabled', 'Site Health Info' )
							) :
							\DO_NOT_UPGRADE_GLOBAL_TABLES . ' ▮'
						) :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'upgrade' ) . ')',
					'debug'   => \defined( 'DO_NOT_UPGRADE_GLOBAL_TABLES' ) ? \DO_NOT_UPGRADE_GLOBAL_TABLES : 'undefined',
					'private' => false,
				];
			} else {
				$debug_info['wp-constants']['fields']['ALLOW_MULTISITE' ] = [
					'label'   => 'ALLOW_MULTISITE',
					'value'   => \defined( 'ALLOW_MULTISITE' ) ?
						( self::is_bool( \ALLOW_MULTISITE ) ?
							( \ALLOW_MULTISITE ?
								_x(    'Allowed', 'Site Health Info' ) :
								_x( 'Disallowed', 'Site Health Info' )
							) :
							\ALLOW_MULTISITE . ' ▮'
						) :
						_x( 'Undefined', 'Site Health Info' ) . ' (' . __( 'not allowed' ) . ')',
					'debug'   => \defined( 'ALLOW_MULTISITE' ) ? \ALLOW_MULTISITE : 'undefined',
					'private' => false,
				];
			}

			$debug_info['wp-constants']['fields']['WF_DEBUG'] = [
				'label'   => 'WF_DEBUG',
				'value'   => \defined( 'WF_DEBUG' ) ?
					( \WF_DEBUG ?
						_x(  'Enabled', 'Site Health Info' ) :
						_x( 'Disabled', 'Site Health Info' )
					) :
					_x( 'Undefined', 'Site Health Info' )  . ' (' . __( 'no' ) . ')',
				'debug'   => \defined( 'WF_DEBUG' ) ? \WF_DEBUG : 'undefined',
				'private' => false,
			];

			if ( self::$is_debug ) {

				if ( \defined( 'WF_DEV_LOGIN' ) ) {
					$debug_info['wp-constants']['fields']['WF_DEV_LOGIN'] = [
						'label'   => 'WF_DEV_LOGIN',
						'value'   => \WF_DEV_LOGIN . ' ▮',
						'debug'   => \WF_DEV_LOGIN,
						'private' => true,
					];
				}

				$debug_info['wp-constants']['fields']['WF_DEV_EMAIL'] = [
					'label'   => 'WF_DEV_EMAIL',
					'value'   => \defined( 'WF_DEV_EMAIL' ) ?
						\WF_DEV_EMAIL . ( \is_email( \WF_DEV_EMAIL ) ? '' : ' ▮' ) :
						_x( 'Undefined', 'Site Health Info' ),
					'debug'   => \defined( 'WF_DEV_EMAIL' ) ? \WF_DEV_EMAIL : 'undefined',
					'private' => true,
				];
			}

			if ( self::$is_cpanel ) {

				$plugin_auto    =
					( ! \defined( 'DISALLOW_FILE_MODS' )         || ! \DISALLOW_FILE_MODS         ) &&
					( ! \defined( 'AUTOMATIC_UPDATER_DISABLED' ) || ! \AUTOMATIC_UPDATER_DISABLED ) &&
					\in_array( \constant( __NAMESPACE__ . '\PLUGIN_BASENAME' ), (array) \get_network_option( null, 'auto_update_plugins', [] ), true )
				;
				$debug_info[ self::$plugin->TextDomain ] = [
					'label'  => _x( 'Your cPanel® Account &mdash; Disk Space &amp; Resources', 'Site Health Info label' ),
					'description' => \sprintf( _x( 'This is information about your cPanel® resource usage and the %1$s plugin.', 'Site Health Info Description' ), '&laquo;'. self::$plugin->Name . '&raquo;' ),
					'fields' => [
						'plugin_version'      => [
							'label'    => _x( 'Plugin version', 'Site Health Info' ),
							'value'    => self::$plugin->Version,
							'debug'    => self::$plugin->Version,
							'private'  => false,
						],
						'plugin_auto_updated' => [
							'label'    => ' &mdash; ' . _x( 'Auto Updated', 'Site Health Info' ),
							'value'    => $plugin_auto ? __( 'Yes' ) : __( 'No' ),
							'debug'    => $plugin_auto,
							'private'  => ! self::$is_debug,
						],

						'max_space'            => [
							'label'    => _x( 'Max disk space', 'Site Health Info' ),
							'value'    => \is_null( self::$disk_space_max ) ? _x( 'N/A', 'Site Health Info' ) : \size_format( self::$disk_space_max,  0 ),
							'debug'    => \is_null( self::$disk_space_max ) ? 'N/A' : \size_format( self::$disk_space_max, 0 ),
							'private'  => false,
						],

						'used_space'           => [
							'label'    => _x( ' &ndash; Used disk space &ndash; total', 'Site Health Info' ),
							'value'    => \is_null( self::$disk_space_used ) ? _x( 'N/A', 'Site Health Info' ) : \size_format( self::$disk_space_used, 1 ),
							'debug'    => \is_null( self::$disk_space_used ) ? 'N/A' : \size_format( self::$disk_space_used, 2 ),
							'private'  => false,
						],

						'dbs_used'             => [
							'label'    => _x( ' &ndash;&ndash; Databases', 'Site Health Info' ),
							'value'    => \is_null( self::$database_used ) ? _x( 'N/A', 'Site Health Info' ) : \size_format( self::$database_used, 1 ),
							'debug'    => \is_null( self::$database_used ) ? 'N/A' : \size_format( self::$database_used, 2 ),
							'private'  => false,
						],

					] + self::info_usages()/* + [

						'proisp'                => [
							'label' => _x( 'At PRO ISP?', 'Site Health Info' ),
							'value'    => self::$is_proisp ? __( 'Yes' ) : __( 'No' ),
							'debug'    => self::$is_proisp,
							'private'  => ! self::$is_debug,
						],
					],*/
				];
			}
			return $debug_info;
		} );
	}

	public    static function disk_space_test(): array {

		$result = [
			'label'       => _x( 'Your server has enough disk space', 'Site Health Status' ),
			'status'      => 'good',
			'badge'       => [
				'label'   => _x( 'Disk Usage', 'Site Health Status' ),
				'color'   => 'blue',
			],
			'description' => \wpautop( \sprintf( _x( 'In internet services providing (ISPs) or pure web hosting, disk space is the amount of space actually used or available on the server for storing the content of your site. This content includes posts, pages, images, videos, logs, other files, preferences, settings, configurations, and whatever else stored on as files or in databases. In case a full ISP, it is also used to store emails, including their full content and attachments. The amount of used disk space tend to grow over time.</p><p>The maximum amount depend on the subscribed package or plan typically from 1GB to over 100GB. When your available disk space is exhausted, your site may break or fail in strange, unpredictable ways. Deleting redundant temporary files and oher "garbage" may rectify it short term. Upgrading your plan/package/account is a more sustainable solution.</p><p>Disk space used is %1$s out of %2$s available. Your uploaded media files takes up %3$s.', 'Site Health Info Test Description' ), self::$disk_space_used ? \size_format( self::$disk_space_used, 1 ) : _x( 'N/A', 'Site Health Status' ), self::$disk_space_max ? \size_format( self::$disk_space_max ) : _x( 'N/A', 'Site Health Status' ), self::$uploads_used ? \size_format( self::$uploads_used, 1 ) : _x( 'N/A', 'Site Health Status' ) ) ),
			'actions'     => ( self::$is_cpanel ? '<a href="https://' . self::$host_name . ( self::$host_port ? ':' . self::$host_port : '' ) . '?locale=' . self::$user_locale . '">' . _x( 'Your cPanel Server', 'Site Health Status' ) . '</a>' : '' ) . ( self::$host_label ? ( self::$host_url ? ' &nbsp; | &nbsp; <a href="' . self::$host_url . '">' : '' ) . self::$host_label . ( self::$host_url ? '</a>' : '' ) : '' ),
			'test'        => 'disk-space',
		];

		if ( self::$disk_space_used / self::$disk_space_max > self::$limits['recommended'] ) {
			$result['label'  ]      = _x( 'You are close to reaching the quota on your server', 'Site Health Status' );
			$result['status' ]      = 'recommended';
			$result['badge'  ]['color'] = 'orange';
			$result['description'] .= \wpautop( _x( 'You are advised to inspect your server or consult your host for further advice or upgrade.', 'Site Health Info' ) . '%s' );
			$result['description']  = \str_replace( '%s', self::$is_cpanel ? ' ' . _x( 'See links below.', 'Site Health Status' ) : '', $result['description'] );
		}

		if ( self::$disk_space_used / self::$disk_space_max > self::$limits['critical'] ) {
			$result['label'  ]      = _x( 'You are very close to reaching the quota on your server', 'Site Health Info' );
			$result['status' ]      = 'critical';
			$result['badge'  ]['color'] = 'red';
			$result['actions']     .= ' &nbsp; | &nbsp; <mark>' . _x( 'Immediate action is necessary to keep normal site behaviour, and to allow for new content.', 'Site Health Info' ) . '</mark>';
		}
		return $result;
	}

	public    static function https_only_test(): array {

		$result = [
			'label'       => _x( 'Your site only accepts secure requests (https).', 'Site Health Status' ),
			'status'      => 'good',
			'badge'       => [
				'label'   => _x( 'Security', 'Site Health Info Label' ),
				'color'   => 'blue',
			],
			'description' => \wpautop( _x( 'You should ensure that visitors to your web site always use a secure connection. When visitors use an insecure connection it can be because used an old link or bookmark, or just typed in the domain. Using https instead of https means that communications between your browser and a website is encrypted via the use of TLS (Transport Layer Security). Even if your website doesn\'t handle sensitive data, it\'s a good idea to make sure your website always loads securely over https.', 'Site Health Status Description' ) ),
			'actions'     => '',
			'test'        => 'https-only',
		];
		$home_url = \get_home_url( null, '/', 'http' );
		$response = \wp_remote_get( $home_url, [ 'method' => 'HEAD', 'redirection' => 0 ] );
		$status = (int) \wp_remote_retrieve_response_code( $response );

		if ( \floor( $status / 100 ) === 2 ) {
			$result['description'] .= \wpautop( _x( 'This situation can and should be fixed by forwarding all http requests to a https version of the requested URL. See link below.', 'Site Health Status' ) ) . \wpautop( \sprintf( _x( 'Response status for <code>%1$s</code> is %2$s.', 'Site Health Status, %1$s = HTTP status' ), $home_url, $status ) );
			$result['label'  ]          = _x( 'Your site also accepts insecure requests (http).', 'Site Health Status' );
			$result['status' ]          = 'recommended';
			$result['badge'  ]['color'] = 'orange';
			$text = _x( 'Force all traffic to your site to use https', 'Site Health Status' ) . ( self::$host_label ? ' - ' . self::$host_label : '' ) . '.';
			$url  = self::$is_cpanel ? \esc_url( _x( 'https://www.proisp.eu/guides/force-https-domain/', 'Site Health Status' ) ) : _x( 'https://stackoverflow.com/questions/4083221/how-to-redirect-all-http-requests-to-https', 'Site Health Status' );
			$tip  = __( 'Opens in a new tab.' );
			$result['actions']     .= \sprintf( '<a href="%1$s" target="_blank" rel="noopener noreferrer" title="%2$s">%3$s', $url, $tip, $text ) . '<span class="dashicons dashicons-external" aria-hidden="true"></span></a>';
		}
		return $result;
	}

	public static function errors_test(): array {

		$result = [
			'label'       => _x( 'You have no errors logged last 24 hours', 'Site Health Status' ),
			'status'      => 'good',
			'badge'       => [
				'label'   => _x( 'Server Errors', 'Site Health Info' ),
				'color'   => 'blue',
			],
			'description' => \wpautop(
				_x( 'Server errors may indicate exhausted server resources, like maximum allowed physical memory, CPU utlization or entry processes. Also issues with your theme, plugins or just some broken links you have added may cause some, but less severe errors. It may also indicate your site is targeted by spamming or hacking robots, probing for vulnerable code. A high frequency of such errors may reduce the servers performance.', 'Site Health Status Description' )
			),
			'actions'     => '',
			'test'        => 'cpanel-errors',
		];

		if ( \count( self::$cpanel_errors ) ) {

			$result['label'  ]          = \sprintf( _x( 'You have %d server errors logged last 24 hours.', 'Site Health Status Label' ),
				\count( self::$cpanel_errors )
			);
			$result['status']           = \count( self::$cpanel_errors ) > 24 ? 'critical' : 'recommended';
			$result['badge'  ]['color'] = 'purple';
			$list = '';
			$result['description']     .= '<ul style="font-family: monospace;"><li>' . \implode( '</li><li>', \array_map( 'trim', \wp_list_pluck( self::$cpanel_errors, 'entry' ) ) ) . '</li></ul>';
			$result['actions']         .= \sprintf(
				_x( 'Identify possible causes to these errors. If internal server errors occur, you may have serious software bugs, or you may need to add more resources by upgrading your hosting plan. See also <a href="%1$s">this FAQ</a> for more tips.', 'Site Health Status Actions' ),
				_x( 'https://www.proisp.eu/faq/#Linux+web+hostingError+messages', 'Site Health Actions FAQ' )
			);
		}
		return $result;
	}

	public static function keys_salts_test(): array {
		$keys   = [ \AUTH_KEY,  \SECURE_AUTH_KEY,  \LOGGED_IN_KEY,  \NONCE_KEY  ];
		$salts  = [ \AUTH_SALT, \SECURE_AUTH_SALT, \LOGGED_IN_SALT, \NONCE_SALT ];
		$status =
			! \in_array( 'put your unique phrase here', $keys  )   &&
			! \in_array( 'put your unique phrase here', $salts )   &&
			\count( $keys  ) === \count( \array_unique( $keys  ) ) &&
			\count( $salts ) === \count( \array_unique( $salts ) )
		;

		$result = [
			'label'       => $status ?
				_x( 'Your security keys and salts are unique', 'Site Health Status' ) :
				_x( 'Your security keys and salts are not unique or are just defaults', 'Site Health Status' ),
			'status'      => $status ? 'good' : 'recommended',
			'badge'       => [
				'label'   => _x( 'Security', 'Site Health Info' ),
				'color'   => $status ? 'blue' : 'purple',
			],
			'description' => \wpautop( _x( 'Ensure that all your defined security keys and salts as set in <code>wp-config.php</code> are unique and not the defaults from <code>wp-config-sample.php</code>.', 'Site Health Status Description' ) ),
			'actions'     => $status ? '' : \sprintf( _x( 'Edit your %s', 'Site Health Info Action, %s = wp-config.php' ), '<code>wp-config.php</code> and <a href="https://api.wordpress.org/secret-key/1.1/salt/">replace the current keys/salts section with this</a>.' ),
			'test'        => 'keys-salts',
		];
		return $result;
	}

	protected static function info_usages(): array {
		$arr = [];

		foreach ( self::cpanel_usages() ?? [] as $key => $usage ) {

			$arr[ $key ] = [
				'label'   => _x( $usage->description, 'Site Health Status Label' ),
				'value'   => \sprintf(
					_x( '%1$s of %2$s &ndash; %3$s',
						'%1$s = used, %2$s = limit, %3$s = ok or error message.'
					),
					\call_user_func( $usage->formatter, $usage->usage   ),
					\call_user_func( $usage->formatter, $usage->maximum ),
					_x( $usage->error, 'Site Health Status - Usage status' )
				),
				'debug' => \sprintf(
					'%1$s / %2$s',
					\call_user_func( $usage->formatter, $usage->usage   ),
					\call_user_func( $usage->formatter, $usage->maximum )
				),
				'private' => true,
			];
		}
		return $arr;
	}

	public    static function other_plugin_test(): array {

		$result = [
			'label'       => self::$plugin1->Name,
			'status'      => 'good',
			'badge'       => [
				'label'   => _x( 'cPanel®', 'Site Health Status badge' ),
				'color'   => 'blue',
			],
			'description' => \wpautop(
				_x( 'You have installed and activated the other recommended cPanel® plugin.', 'Site Health Status description' )
			),
			'actions'     => '',
			'test'        => 'other-plugin',
		];

		if ( ! \class_exists( 'WebFacing\cPanel\Email\Main' ) ) {

			$result['label'  ]          = _x( 'It looks like you haven\'t installed or haven\'t activated the other recommended cPanel® plugin.', 'Site Health Status label' );
			$result['status']          = 'recommended';
			$result['badge' ]['color'] = 'purple';

			$result['description']      = \sprintf(
				/* translators: 1: Plugin Name, 2: Plugin URI */
				_x( 'Also check out this complementary, or replacement, <a href="%2$s">&laquo;%1$s&raquo;</a> plugin, which also let\'s you monitor disk space used. When installed you may find this plugin mostly redundant, and it will soon be discontinued anyway.', 'Site Health Status description' ),
				self::$plugin1->Name,
				self::$plugin1->URI
			);

			$result['actions'] = ! \is_multisite() && \current_user_can( 'install_plugins' ) && \current_user_can( 'activate_plugins' ) ?
				'<a href="' . \add_query_arg( [ 's' => \urlencode( self::$plugin1->Name ), 'tab' => 'search', 'type' => 'term' ], \admin_url( 'plugin-install.php' ) ) . '" title="' . __( 'Install' ) . ' &laquo;' . self::$plugin1->Name . '&raquo;.">
					<button class="button button-secondary">' . __( 'Install' ) . '</button>
				</a>' :
				( \is_super_admin() ?
					'<a href="' . \add_query_arg( [ 's' => \urlencode( self::$plugin1->Name ), 'tab' => 'search', 'type' => 'term' ], \network_admin_url( 'plugin-install.php' ) ) . '" title="' . __( 'Install' ) . ' &laquo;' . self::$plugin1->Name . '&raquo;.">
						<button class="button button-secondary">' . __( 'Install' ) . '</button>
					</a>' :
				'' );
		}
		return $result;
	}

	protected static function is_bool( $value ): bool {
		return
			\is_bool( $value ) ||
			( \is_int(    $value ) && \in_array( $value, \range( 0, 1 ),                  true ) ) ||
			( \is_string( $value ) && \in_array( $value, [ '', '0', '1', 'true', 'yes' ], true ) )
		;
	}
}
