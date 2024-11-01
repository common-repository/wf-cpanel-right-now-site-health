<?php
declare( strict_types=1 );
namespace WebFacing\cPanel;

/**
 * Exit if accessed directly
 */
\class_exists( 'WP' ) || exit;

abstract class Main {

	const pf = 'wf_cpanel_';

	const hosts = [
		'proisp.no' => [ 'label' => 'PRO ISP', 'url' => 'https://proisp.eu/', 'port' => 2083 ],
		'proisp.eu' => [ 'label' => 'PRO ISP', 'url' => 'https://proisp.eu/', 'port' => 2083 ],
	];

	const limits = [
		'good'        => 0.00,
		'recommended' => 0.90,
		'critical'    => 0.95,
	];

	public    static \stdClass  $plugin;

	public    static string     $pf;

	protected static ?\WP_User  $dev;

	public    static bool       $is_debug = false;

	protected static \WP_Screen $screen;

	protected static int        $transient_time = 1;

	protected static \stdClass  $plugin1;

	protected static bool       $has_caps = false;

	protected static bool       $is_cpanel = true;

	protected static bool       $has_exec = false;

	protected static array      $hosts = [];

	protected static string     $host_name = '';

	protected static ?string    $host_id;

	protected static ?string    $host_label = null;

	protected static ?string    $host_url;

	protected static ?int       $host_port;

	protected static string     $user_locale = '';

	protected static string     $site_domain = '';

	protected static bool       $is_known_isp = false;

	protected static bool       $is_proisp = false;

	protected static string     $cpanel_user;

	protected static string     $cpanel_subaccounts = '';

	protected static string     $cpanel_version;

	protected static int        $cpanel_user_created;

	protected static int        $cpanel_user_updated;

	protected static bool       $cpanel_data_fresh = false; // important

	protected static array      $cpanel_errors = [];

	protected static ?int       $php_errors = 0;

	protected static \stdClass  $cpanel_user_ids;

	protected static int        $cpanel_token = 0;	// important

	protected static int        $disk_space_max = 0;

	protected static int        $disk_space_used = 0;

	protected static int        $database_used;

	protected static int        $uploads_used;

	protected static array      $limits = [];

	protected static bool       $two_factor_enabled = false;

	protected static string     $main_domain;

	protected static array      $sub_domains = [];

	protected static array      $addon_domains = [];

	protected static array      $parked_domains = [];

	protected static array      $dead_domains = [];

	protected static bool       $php_log = false;

	protected static ?\stdClass $logfile;

	public    static function load(): void {

		if ( ! \function_exists( 'get_plugin_data' ) ) {
			require_once \ABSPATH . 'wp-admin/includes/plugin.php';
		}

		self::$plugin = (object) \get_plugin_data( PLUGIN_FILE );
		self::$plugin1 = new \stdClass;
		self::$plugin1->Name = _x( 'WebFacing – Email Accounts management for cPanel®', 'Plugin Name' );
		self::$plugin1->URI  = _x( 'https://wordpress.org/plugins/wf-cpanel-email-accounts/', 'Plugin URI' );

		if ( $_SERVER['SERVER_ADDR'] ?? '' ) {

			self::$host_name     = \gethostbyaddr( $_SERVER['SERVER_ADDR'] ) ?: '';
			$host_ids = \explode( '.', self::$host_name ?? '' );
			$num_f = \count( $host_ids );
			self::$host_id       = $num_f > 1 && \array_key_exists( $num_f - 1, $host_ids ) ? $host_ids[ $num_f - 2] . '.' . $host_ids[ $num_f - 1] : null;

			self::$is_proisp     = \in_array( self::$host_id, [ 'proisp.no', 'proisp.eu' ], true );
		}

		self::$pf                = \trim( \str_replace( '_', '-', self::pf ) );

		self::$is_debug          = \defined( 'WF_DEBUG' ) && \WF_DEBUG;

		self::$has_exec          = \function_exists( 'shell_exec' ) && \is_callable( 'shell_exec' );

		self::$transient_time    = self::$is_debug ? 5 : 55;

		\add_action( 'plugins_loaded', static function(): void {
			self::$plugin1->active = \class_exists( __NAMESPACE__ . '\Email\Main' );
		} );

		Glance::load();
		Charts::load();
		Health::load();
	}

	public    static function admin(): void {

		\add_action( 'init', static function(): void {
			self::$dev = \defined( 'WF_DEV_EMAIL' ) ? ( \get_user_by( 'email', \WF_DEV_EMAIL ) ?: null ) : null;

			if ( \is_null( self::$dev ) ) {
				$bits = \explode( '/', \rtrim( self::$plugin->AuthorURI, ' /' ) );
				self::$dev = \get_user_by( 'login', \end( $bits ) ) ?: null;
			}
		} );

		\add_action( 'current_screen', static function(): void {
			self::$screen = \get_current_screen();

			if ( \in_array( self::$screen->id, [ 'dashboard','site-health' ], true ) ) {
				self::init_data();
			}
		} );

		\add_action( 'admin_notices', static function(): void {

			if ( self::$is_debug && self::$screen->id !== 'site-health' && self::$dev instanceof \WP_User && self::$dev->ID === \get_current_user_id() ) {
				self::init_data(); ?>
				<div class="notice notice-info is-dismissible">
					<p>
						<?php echo self::$plugin->Name, ' ', self::$plugin->Version; ?><br/>
							<?=\constant(__NAMESPACE__.'\PLUGIN_BASENAME')?><!--br/-->
							<?php var_dump( \wp_list_pluck( self::cpanel_uapi_result( 'ServerInformation', 'get_information' ), 'value', 'name' )/*['Memory Used']*/ ); ?>
					</p>
				</div>
<?php
			}
		} );

		\add_action( 'admin_footer', static function(): void {
			global $wpdb;

			\do_action( 'qm/debug', 'Data fresh: {fresh}.', [ 'fresh' => self::$cpanel_data_fresh ] );

			/**
			  *	Remove expired transients.
			  */
			if ( self::$screen->id === 'dashboard' && \idate( 's' ) % ( self::$is_debug ? 2 : 12 ) === 0 ) {
				$pf        = self::$pf;
				$threshold = \time() - ( 2 * self::$transient_time );
				$sql       = "
					DELETE FROM `T1`, `T2`
					USING `{$wpdb->options}` AS `T1`
					JOIN `{$wpdb->options}` AS `T2` ON `T2`.`option_name` = REPLACE( `T1`.`option_name`, '_timeout', '' )
					WHERE ( `T1`.`option_name` LIKE '\_transient\_timeout\_{$pf}%' OR `T1`.`option_name` LIKE '\_site\_transient\_timeout\_{$pf}%' )
					AND CONVERT( `T1`.`option_value`, UNSIGNED ) < {$threshold}
				;";
				$rows = \ceil( $wpdb->query( $sql ) / 2 );
				\do_action( 'qm/debug', 'Deleted {rows} expired transients.', [ 'rows' => $rows ] );
			}
		} );

		Glance::admin();
		Charts::admin();
		Health::admin();
	}

	protected static function init_data(): void {

		self::$has_caps        = \current_user_can( 'upload_files' ) || ( \is_multisite() && \is_super_admin() );

		self::$is_cpanel = self::$has_exec && ! \is_null( \shell_exec( 'uapi' ) );

		if ( self::$is_cpanel ) {

			self::$cpanel_version  = self::cpanel_version();

			self::$hosts = \apply_filters( self::pf . 'hosts', self::hosts ) ?? self::hosts;

			foreach ( self::$hosts as &$host ) {
				if ( $host['url'] == 'https://proisp.eu/' && \in_array( \get_user_locale( \wp_get_current_user() ), [ 'nb_NO', 'nn_NO' ] ) ) {
					$host['url'] = 'https://proisp.no/';
				}
			}

			$exists = \array_key_exists( self::$host_id, self::$hosts );
			self::$host_label    = $exists ? self::$hosts[ self::$host_id ]['label'] : null;

			self::$host_url      = $exists ? __( self::$hosts[ self::$host_id ]['url'] ) : '';

			self::$host_port     = $exists ? self::$hosts[ self::$host_id ]['port'] : null;

			self::$user_locale   = \explode( '_', \get_user_locale( \wp_get_current_user() ) )[0];

			self::$cpanel_user         = self::cpanel_user() ?? '';

			self::$cpanel_user_ids     = self::cpanel_user_ids();

			self::$cpanel_user_created = self::cpanel_user_created();

			self::$cpanel_user_updated = self::cpanel_user_updated();

			if ( \in_array( self::$screen->id, [ 'dashboard','site-health' ], true ) ) {

				self::$cpanel_errors       = self::cpanel_errors();

				self::$cpanel_token        = self::$cpanel_token ?: self::cpanel_token();

				self::$limits = \apply_filters( self::pf . 'limits', self::limits ) ?? self::limits;

				$uploads_used = self::$has_exec ? \explode( "\t", \exec( 'du -sh ' . \wp_upload_dir()['basedir'] ) )[0] : 0;
				$uploads_used = $uploads_used ?: \get_dirsize( \wp_upload_dir()['basedir'] );
				self::$uploads_used = self::to_bytes( $uploads_used . 'B' );

				self::$disk_space_max      = self::cpanel_total_disk_limit();

				self::$disk_space_used     = self::cpanel_total_disk_used();

				self::$database_used       = self::cpanel_databases_disk_used();

				self::$php_log             = ! empty( @\ini_get( 'log_errors' ) );

				if ( self::$php_log ) {
					self::$logfile          = new \stdClass;
					self::$logfile->Path    = @\ini_get( 'error_log' );
					self::$php_log          = ! empty( self::$logfile->Path );

					if ( self::$php_log ) {
						self::$logfile->ShortPath  = \substr( self::$logfile->Path, \similar_text( \ABSPATH, self::$logfile->Path ) );
						self::$logfile->ShortPath  = '~/' . ( \strlen( self::$logfile->ShortPath ) > \strlen( \basename( self::$logfile->Path ) ) ? self::$logfile->ShortPath : \basename( self::$logfile->Path ) );

						if ( \file_exists( self::$logfile->Path ) ) {
							self::$logfile->Size       = \filesize( self::$logfile->Path );

							self::$php_errors          = self::$has_exec ? (int) \shell_exec( "tac '".self::$logfile->Path."' | head -n 100 | grep '^\[' | grep -c 'PHP Fatal error'" ) : false;
						} else {
							self::$logfile->Size = 0;
							self::$php_errors    = 0;
						}
					}
				} else {
					self::$logfile = null;
				}
			}
		}
	}

	protected static function cpanel_uapi( string $module, string $function, ?array $params = [], string $output = 'json' ): ?string {
		$paramst = '';
		foreach( $params as $name => $value ) {
			if ( \strlen( $name ) ) {
				$paramst .= " " . $name . ( \is_null( $value) ? "" : '=' . ( \is_integer( $value ) ? $value : "'" . \urlencode( $value ) . "'" ) );
			}
		}
		return self::$has_exec && self::$is_cpanel ? \shell_exec( 'uapi --output=' . $output . ' ' . $module . ' ' . $function . $paramst ) : null;
	}

	protected static function cpanel_uapi_result( string $module, string $function, ?array $params = [] )/*: \stdClass | array*/ {
		$transient_name = self::$pf . $module . '-' . $function . ( \count( $params ) ? '-' . \md5( \serialize( $params ) ) : '' );
		$result = \json_decode( \get_transient( $transient_name ) ?: '' );
		$data_fresh = ! (
			( \is_string( $result ) && \strlen( $result ) > 0     ) ||
			( \is_object( $result ) &&   isset( $result->result ) ) ||
			( \is_array(  $result ) &&  \count( $result ) > 0     ) ||
		false );
		if ( $data_fresh ) {
			$json_result = self::cpanel_uapi( $module, $function, $params );
			\set_transient( $transient_name, $json_result, self::$transient_time );
			$result = \json_decode( $json_result );
		}
		self::$cpanel_data_fresh = self::$cpanel_data_fresh || $data_fresh;
		return $result->result->data;
	}

	protected static function nocache_cpanel_uapi_result( string $module, string $function, ?array $params = [] )/*: \stdClass | array*/ {
		$json_result = self::cpanel_uapi( $module, $function, $params, 'json' );
		$result = \json_decode( $json_result );
		return $result->result->data;
	}

	protected static function cpanel_user(): ?string {
		return self::cpanel_uapi_result( 'Variables', 'get_user_information' )->user;
	}

	protected static function cpanel_user_created(): int {
		return \intval( self::cpanel_uapi_result( 'Variables', 'get_user_information' )->created ?? '' );
	}

	protected static function cpanel_user_updated(): ?int {
		return \intval( self::cpanel_uapi_result( 'Variables', 'get_user_information' )->last_modified ?? '' );
	}

	protected static function cpanel_user_ids(): \stdClass {
		$data = self::cpanel_uapi_result( 'Variables', 'get_user_information' );
		return (object)[ 'uid' => $data->uid, 'gid' => $data->gid ];
	}

	protected static function cpanel_plan(): ?string {
		return self::cpanel_uapi_result( 'Variables', 'get_user_information' )->plan;
	}

	protected static function cpanel_quotas(): ?\stdClass {
		return self::cpanel_uapi_result( 'Quota', 'get_local_quota_info' );
	}

	protected static function cpanel_databases( string $vendor = 'Mysql' ): ?array {
		return self::cpanel_uapi_result( $vendor, 'list_databases' );
	}

	protected static function cpanel_total_disk_used(): int {
		return \intval( self::cpanel_quotas()->bytes_used ?? 0 );
	}

	protected static function cpanel_total_disk_limit(): ?int {
		return self::cpanel_quotas()->byte_limit;
	}

	protected static function cpanel_databases_disk_used(): ?int {
		$disk = 0;
		foreach ( [ 'Mysql', 'Postgresql' ] as $vendor ) {
			$dbs = self::cpanel_databases( $vendor ) ?? [];
			foreach ( $dbs as $db ) {
				$disk += $db->disk_usage;
			}
		}
		return $disk;
	}

	protected static function cpanel_usages(): ?array {
		$usages = self::cpanel_uapi_result( 'ResourceUsage', 'get_usages' ) ?? [];
		$retain = [
			'cachedmysqldiskusage',
			'lvememphy',
			'lvenproc',
			'lveep',
			'lvecpu',
			'lveiops',
			'lveio',
		];
		$kept = [];
		foreach ( $usages as $usage ) {
			if ( \in_array( $usage->id, $retain, true ) ) {
				$use = new \stdClass;
				$use->description   = $usage->description;
				$use->usage         = $usage->usage;
				$use->maximum       = $usage->maximum;
				$use->formatter     = $usage->formatter === 'format_bytes' ? 'size_format' : ( $usage->id === 'lvecpu' ? static function( /*string|int*/ $v ): string { return "{$v}%"; } : ( $usage->formatter === 'format_bytes_per_second' ? static function( /*string|int*/ $v ): string { return "{$v} B/s"; } : 'intval' ) );
				$use->error         = $usage->error ?? 'ok';
				$kept[ $usage->id ] = $use;
			}
		}
		return $kept;
	}

	protected static function get_usages(): string {
		$html = \PHP_EOL . '<dl>';
		foreach ( self::cpanel_usages() as $usage ) {
			$html .= \PHP_EOL . '<dt>' . $usage->description . ':</dt>';
//			$html .= \PHP_EOL . '<dd>' . \call_user_func( $usage->formatter, $usage->usage ) . ' of ' . \call_user_func( $usage->formatter, $usage->maximum ) . '</dd>';
			$html .= \PHP_EOL . '<dd>' . $usage->formatter( $usage->usage ) . ' of ' . $usage->formatter( $usage->maximum ) . '</dd>';
		}
		$html .= \PHP_EOL . '</dl>';
		return $html;
	}

	protected static function cpanel_main_domain(): ?string {
		return self::cpanel_uapi_result( 'DomainInfo', 'list_domains' )->main_domain;
	}

	protected static function cpanel_two_factor(): bool {
		return \boolval( self::cpanel_uapi_result( 'TwoFactorAuth', 'get_user_configuration' )->is_enabled );
	}

	protected static function cpanel_list_domains( string $domain_type = 'addon_domains' ): ?array {
		return self::cpanel_uapi_result( 'DomainInfo', 'list_domains' )->$domain_type;
	}

	protected static function cpanel_dead_domains(): ?array {
		return self::cpanel_uapi_result( 'Variables', 'get_user_information', [ 'name' => 'dead_domains' ] )->dead_domains;
	}

	protected static function cpanel_version(): ?string {
		return self::cpanel_uapi_result( 'Variables', 'get_server_information' )->version;
	}

	protected static function cpanel_errors( string $domain = '' ): array {
		$domain = $domain ?: self::$site_domain;
		$results = self::cpanel_uapi_result( 'Stats', 'get_site_errors', [ 'domain' => $domain ] );
		foreach ( $results ?? [] as $key => $result ) {
			$pos = \strpos( $result->entry, '20' );
			$results[ $key ]->date = \strtotime( \substr( $result->entry, $pos, 19 ) );
		}
		$results = \array_filter( $results ?? [], function( \stdClass $obj ): bool {
			$entry = \strtolower( $obj->entry );
			return ! \str_contains( $entry, self::$site_domain );
		} );
		return \array_filter( $results ?? [], function( \stdClass $obj ): bool {
			$entry = \strtolower( $obj->entry );
			return \intval( $obj->date ) > \current_time( 'U' ) - \DAY_IN_SECONDS &&
				( self::$is_debug || \str_contains( $entry, ' [error] ' ) );
		} );
	}

	protected static function cpanel_token(): int {
		$time = \strtotime( 'first day of last month 00:00:00' );
		$did = \idate( 'Y' );
		return ( ( self::$cpanel_user_updated ?? $time ) - ( self::$cpanel_user_created ?? $time ) ) + $time + ( self::$cpanel_user_ids->uid ?? $did ) + ( self::$cpanel_user_ids->gid ?? $did );
	}

	protected static function cpanel_subaccounts(): array {
		$text = _x( 'email',   'cPanel® Service' );
		$text = _x( 'ftp',     'cPanel® Service' );
		$text = _x( 'webdisk', 'cPanel® Service' );
		$accounts = [];

		foreach ( (array) self::cpanel_uapi_result( 'UserManager', 'list_users' ) as $account ) {

			if ( ! \str_starts_with( $account->username, self::$cpanel_user ) ) {
				$o_account = new \stdClass;
				$o_account->name = $account->real_name ?? $account->full_username;

				foreach ( (array) $account->services as $sname => $service ) {
					if ( $service->enabled ?? false ) {
						$o_account->services[] = _x( $sname, 'cPanel® Service' );
					}
				}
				$accounts[ $account->username ] = $o_account;
			}
		}
		return $accounts;
	}

	protected static function to_bytes( string $from ): ?int {
		$from   = \str_replace( 'BB', 'B', $from );
		$units  = [ 'B', 'KB', 'MB', 'GB', 'TB', 'PB' ];
		$number = \intval( \str_replace( ' ', '', \substr( $from, 0, -2 ) ) );
		$suffix = \strtoupper( \substr( $from, -2 ) );

		if ( \is_numeric( \substr( $suffix, 0, 1 ) ) ) {
			return (int ) \preg_replace( '/[^\d]/', '', $from );
		}

		$exponent = \array_flip( $units )[ $suffix ] ?? null;

		if ( $exponent === null ) {
			return null;
		}
		return $number * ( \KB_IN_BYTES ** $exponent );
	}
}
