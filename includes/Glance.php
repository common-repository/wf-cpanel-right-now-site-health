<?php
declare( strict_types=1 );
namespace WebFacing\cPanel;

/**
 * Exit if accessed directly
 */
\class_exists( __NAMESPACE__ . '\Main' ) || exit;

abstract class Glance extends Main {

	public    static function load(): void {
	}

	public    static function admin(): void {

		\add_filter( 'dashboard_glance_items', static function( ?array $elements ): array {
			$elements = $elements ?? [];

			if ( self::$is_cpanel && self::$has_caps ) {
//				self::init_data();
				$href = 'https://' . self::$host_name . ( self::$host_port ? ':' . self::$host_port : '' ) . '?locale=' . self::$user_locale;

				if ( self::$disk_space_used ) {
					$class = self::$disk_space_max ?
						( self::$disk_space_used / self::$disk_space_max > self::$limits['critical'] ?
							'critical' :
							( self::$disk_space_used / self::$disk_space_max > self::$limits['recommended'] ?
								'recommended' :
								'good'
							)
						) :
						'good'
					;
					$title = self::$uploads_used ?
						( ' title="' . \sprintf( \esc_html( __( 'Maximum allowed for your account %1$s: %2$s.' ) ), \current_user_can( 'manage_options' ) ?
							self::$cpanel_user :
							'', self::$disk_space_max ?
								\size_format( self::$disk_space_max ) :
								_x( 'N/A', 'Site Health Info' ) ) .
							( self::$database_used ?
								' ' . \sprintf( __( 'Databases use %s.' ), \size_format( self::$database_used ) ) :
								'' ) .
							( self::$uploads_used ?
								' ' . \sprintf( __( 'Your uploaded files use %s.' ), \size_format( self::$uploads_used ) ) :
								'' ) .
							( self::$php_log ?
								' ' . \sprintf( __( 'Your log file now uses %s.' ), \size_format( self::$logfile->Size ) ) :
								'' ) .
						'"' ) :
					'';
					$elements[] = '<a href="' . $href . '" class="disk-count ' . $class . '"' . $title . '>' . \size_format( self::$disk_space_used, 1 ) . ' ' . __( 'disk space used on cPanel®' ) . '</a>';
				}
			}
			return $elements;
		}, 111 );

		\add_action( 'rightnow_end', static function(): void {

			if ( self::$has_caps ) {

				$proisp_packages = [
					'prostart'      => 'Pro Start',
					'promedium'     => 'Pro Medium',
					'propremium'    => 'Pro Premium',
					'enterprise10'  => 'Enterprise 10',
					'enterprise30'  => 'Enterprise 30',
					'enterprise60'  => 'Enterprise 60',
					'enterprise100' => 'Enterprise 100',
				];
				$proisp_package  = self::$is_proisp ? $proisp_packages[ self::cpanel_plan() ] : '';

				if ( self::$host_label ) {

					if ( self::$is_proisp ) {
						echo \PHP_EOL, \wpautop( \sprintf( __( 'Hosted at <a href="%1$s">%2$s</a> using a <strong>%3$s</strong> account with %4$s.' ), self::$host_url, self::$host_label, $proisp_package, self::$disk_space_max ? \size_format( self::$disk_space_max ) : _x( 'N/A', 'Site Health Info' ) ) );
					} elseif ( self::$is_known_isp ) {
						echo \PHP_EOL, \wpautop( \sprintf( __( 'Hosted at <a href="%1$s">%2$s</a>.' ), self::$host_url, self::$host_label ) );
					}
				}

				if ( self::$is_cpanel && \current_user_can( 'install_plugins' ) && \current_user_can( 'activate_plugins' ) ) {

					if ( true || ! self::$plugin1->active ) {
						$pf = self::$pf; ?>
						<p id="<?=$pf?>rnsh-promo" data-id="<?=\idate('y')?>" style="display: none;">
							<!--button aria-label="<?=__('Dismiss promo')?>" id="<?=$pf?>rnsh-promo" class="dismiss" style="float: right;" title="<?php _ex( 'Dismiss', 'Button Title' ); ?>">×</button!-->
							<small><strong><?php \printf(
						_x( 'Important message from the author of the &laquo;%1$s&raquo;:',
								'At a Glance note, %1$s = Plugin Name,' ),
							self::$plugin->Name,
						); ?>
							</strong><br/><strong><mark><?php _ex( 'This plugin is now finally closed. Please deactivate and delete it.', 'At a Glance note' ); ?></mark></strong><?php

//						if ( ! \is_multisite() ) {
//							echo ' &nbsp; | &nbsp; <a href="', \add_query_arg( [ 's' => \urlencode( self::$plugin1->Name ), 'tab' => 'search', 'type' => 'term' ], \admin_url( 'plugin-install.php' ) ), '" title="', __( 'Install' ), ' &laquo;', _x( self::$plugin1->Name, 'Plugin Name' ), '&raquo;.">', __( 'Install' ), '</a>';
//						} ?></small></p>
						<script>
							( function() {
								var notice, noticeId, storedNoticeId, dismissButton;
								notice = document.querySelector( '#<?=$pf?>rnsh-promo' );

								if ( ! notice ) {
									return;
								}

								dismissButton = document.querySelector( '#<?=$pf?>rnsh-promo .dismiss' );
								noticeId = notice.getAttribute( 'data-id' );
								storedNoticeId = localStorage.getItem( '<?=$pf?>rnsh-promo' );

								// This means that the user hasn't already dismissed
								// this specific notice. Let's display it.
								if ( noticeId !== storedNoticeId ) {
									notice.style.display = 'block';
								}

								dismissButton.addEventListener( 'click', function() {
									// Hide the notice
									notice.style.display = 'none';

									// Add the current id to localStorage
									localStorage.setItem( '<?=$pf?>rnsh-promo', noticeId );
								} );
							}() );
						</script>
<?php				}
				}
			}
		} );

		/*
		 * Custom Icon for Disk space in "At a Glance" widget
		 */
		\add_action( 'admin_head', static function(): void {

			if ( self::$has_caps && self::$screen->id === 'dashboard' ) { ?>
				<style>
					#dashboard_right_now li a.disk-count.recommended {
						background-color: inherit;
						color: brown;
					}
					#dashboard_right_now li a.disk-count.critical {
						background-color: inherit;
						color: red;
						font-weight: bold;
					}
					#dashboard_right_now li a.disk-count.critical:before {
						background-color: inherit;
						color: red;
					}
					#dashboard_right_now li a.disk-count:before {
						content: '\f17e';
						margin-left: -1px;
					}
				</style>
<?php		}
		} );
	}

	protected static function init_data(): void {

		self::$is_known_isp  = \in_array( self::$host_id, \array_keys( self::hosts ), true );
	}
}
