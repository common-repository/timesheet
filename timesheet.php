<?php
/**
Plugin Name: Timesheet by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/timesheet/
Description: Best timesheet plugin for WordPress. Track employee time, streamline attendance and generate reports.
Author: BestWebSoft
Text Domain: timesheet
Domain Path: /languages
Version: 1.1.6
Author URI: https://bestwebsoft.com/
License: Proprietary
 */

/*
  © Copyright 2021  BestWebSoft  ( https://support.bestwebsoft.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! function_exists( 'tmsht_admin_menu' ) ) {
	/**
	 * Add admin menu
	 */
	function tmsht_admin_menu() {
		global $submenu, $tmsht_options, $current_user, $tmsht_plugin_info, $wp_version;

		$tmsht_pages_display = array(
			'ts_user'      => false,
			'ts_report'    => false,
		);

		foreach ( $tmsht_pages_display as $page => $display_page ) {
			if ( isset( $tmsht_options['display_pages'][ $page ]['user_roles'] ) ) {
				if ( is_multisite() && is_super_admin( $current_user->ID ) ) {
					if ( in_array( 'administrator', $tmsht_options['display_pages'][ $page ]['user_roles'] ) ) {
						$tmsht_pages_display[ $page ] = true;
					}
				} else {
					foreach ( $current_user->caps as $role => $value ) {
						if ( in_array( $role, $tmsht_options['display_pages'][ $page ]['user_roles'] ) ) {
							$tmsht_pages_display[ $page ] = true;
							break;
						}
					}
				}
			}
		}

		$main_page = '';

		if ( $tmsht_pages_display['ts_user'] ) {
			$ts_user_page_hook = add_menu_page( 'Timesheet', 'Timesheet', 'read', 'timesheet_ts_user', 'tmsht_ts_user_page', 'dashicons-clock' );
			$main_page = 'timesheet_ts_user';
			add_submenu_page( $main_page, __( 'My Availability', 'timesheet' ), __( 'My Availability', 'timesheet' ), 'read', 'timesheet_ts_user', 'tmsht_ts_user_page' );

			add_action( 'load-' . $ts_user_page_hook, 'tmsht_add_tabs' );
		}

		if ( $tmsht_pages_display['ts_report'] ) {
			if ( empty( $main_page ) ) {
				add_menu_page( 'Timesheet', 'Timesheet', 'read', 'timesheet_ts_report', 'tmsht_ts_report_page', 'dashicons-clock' );
				$main_page = 'timesheet_ts_report';
			}

			$ts_report_page_hook = add_submenu_page( $main_page, __( 'Team', 'timesheet' ), __( 'Team', 'timesheet' ), 'read', 'timesheet_ts_report', 'tmsht_ts_report_page' );

			add_action( 'load-' . $ts_report_page_hook, 'tmsht_add_tabs' );
		} elseif ( empty( $main_page ) ) {
			add_menu_page( 'Timesheet', 'Timesheet', 'manage_options', 'timesheet_settings', 'tmsht_settings_page', 'dashicons-clock' );
			$main_page = 'timesheet_settings';
		}

		if ( empty( $settings_page_hook ) ) {
			$settings_page_hook = add_submenu_page( $main_page, 'Timesheet ' . __( 'Settings', 'timesheet' ), __( 'Settings', 'timesheet' ), 'manage_options', 'timesheet_settings', 'tmsht_settings_page' );
		}

		add_submenu_page( $main_page, 'BWS Panel', 'BWS Panel', 'manage_options', 'tmsht-bws-panel', 'bws_add_menu_render' );

		add_action( 'load-' . $settings_page_hook, 'tmsht_add_tabs' );

		if ( isset( $submenu[ $main_page ] ) ) {
			$submenu[ $main_page ][] = array(
				'<span style="color:#d86463"> ' . __( 'Upgrade to Pro', 'timesheet' ) . '</span>',
				'manage_options',
				'https://bestwebsoft.com/products/wordpress/plugins/timesheet/?k=3bdf25984ad6aa9d95074e31c5eb9bb3&pn=606&v=' . $tmsht_plugin_info['Version'] . '&wp_v=' . $wp_version,
			);
		}
	}
}

if ( ! function_exists( 'tmsht_add_tabs' ) ) {
	/**
	 * Add help tab on settings page
	 */
	function tmsht_add_tabs() {
		$screen = get_current_screen();
		$args = array(
			'id'            => 'tmsht',
			'section'       => '202101246',
		);
		bws_help_tab( $screen, $args );
	}
}

if ( ! function_exists( 'tmsht_plugins_loaded' ) ) {
	/**
	 * Load textdomain
	 */
	function tmsht_plugins_loaded() {
		load_plugin_textdomain( 'timesheet', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'tmsht_init' ) ) {
	/**
	 * Plugin init function
	 */
	function tmsht_init() {
		global $tmsht_plugin_info;

		$plugin_basename = plugin_basename( __FILE__ );

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( $plugin_basename );

		if ( empty( $tmsht_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$tmsht_plugin_info = get_plugin_data( __FILE__ );
		}

		/* check WordPress version */
		bws_wp_min_version_check( $plugin_basename, $tmsht_plugin_info, '4.5' );

		if ( is_admin() ) {
			tmsht_register_options();
		}
	}
}

if ( ! function_exists( 'tmsht_admin_init' ) ) {
	/**
	 * Plugin admin init function
	 */
	function tmsht_admin_init() {
		global $pagenow, $bws_plugin_info, $tmsht_plugin_info, $tmsht_options;

		/* Add variable for bws_menu */
		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array(
				'id' => '606',
				'version' => $tmsht_plugin_info['Version'],
			);
		}

		/* session timesheet_ts_user & timesheet_ts_report */
		if ( isset( $_REQUEST['page'] ) && ( 'timesheet_ts_user' == $_REQUEST['page'] || 'timesheet_ts_report' == $_REQUEST['page'] ) ) {
			if ( '' == session_id() || ! isset( $_SESSION ) ) {
				session_start();
			}
		}

		if ( 'plugins.php' == $pagenow ) {
			/* Install the option defaults */
			if ( function_exists( 'bws_plugin_banner_go_pro' ) ) {
				bws_plugin_banner_go_pro( $tmsht_options, $tmsht_plugin_info, 'tmsht', 'timesheet', '6316f137e58adf88e055718d7cc85346', '606', 'timesheet' );
			}
		}
	}
}

if ( ! function_exists( 'tmsht_create_tables' ) ) {
	/**
	 * Function to create a new tables in data base
	 */
	function tmsht_create_tables() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		/* Table with legends */
		if ( ! $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}tmsht_legends';" ) ) {
			$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tmsht_legends` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL,
				`color` char(7) NOT NULL,
				`disabled` BOOLEAN NOT NULL DEFAULT '0',
				`all_day` BOOLEAN NOT NULL DEFAULT '0',
				PRIMARY KEY  ( `id` )
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			dbDelta( $sql );

			$default_legends = array(
				array(
					'id'       => 1,
					'name'     => __( 'In Office', 'timesheet' ),
					'color'    => '#94e091',
					'disabled' => 0,
					'all_day'  => 0,
				),
				array(
					'id'       => 2,
					'name'     => __( 'Remote', 'timesheet' ),
					'color'    => '#eded76',
					'disabled' => 0,
					'all_day'  => 0,
				),
				array(
					'id'       => 3,
					'name'     => __( 'Absent', 'timesheet' ),
					'color'    => '#dd8989',
					'disabled' => 0,
					'all_day'  => 0,
				),
				array(
					'id'       => 4,
					'name'     => __( 'Vacation', 'timesheet' ),
					'color'    => '#8da6bf',
					'disabled' => 0,
					'all_day'  => 1,
				),
			);

			foreach ( $default_legends as $legend ) {
				$wpdb->insert(
					"{$wpdb->prefix}tmsht_legends",
					array(
						'id'        => $legend['id'],
						'name'      => $legend['name'],
						'color'     => $legend['color'],
						'disabled'  => $legend['disabled'],
						'all_day'   => $legend['all_day'],
					),
					array( '%d', '%s', '%s', '%d', '%d' )
				);
			}
		}
		/* Table with ts */
		if ( ! $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}tmsht_ts';" ) ) {
			$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tmsht_ts` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`user_id` bigint(20) NOT NULL,
				`time_from` datetime NOT NULL,
				`time_to` datetime NOT NULL,
				`legend_id` int(10) NOT NULL,
				PRIMARY KEY  ( `id` )
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			dbDelta( $sql );
		}
	}
}

if ( ! function_exists( 'tmsht_register_options' ) ) {
	/**
	 * Create plugin options
	 */
	function tmsht_register_options() {
		global $tmsht_plugin_info, $tmsht_options, $wpdb;

		$update_option = false;
		$db_version = '0.2';

		if ( ! get_option( 'tmsht_options' ) ) {
			$default_options = tmsht_get_options_default();
			add_option( 'tmsht_options', $default_options );
		}

		$tmsht_options = get_option( 'tmsht_options' );

		/* Update tables when update plugin and tables changes */
		if ( ! isset( $tmsht_options['plugin_db_version'] ) || $tmsht_options['plugin_db_version'] != $db_version ) {
			tmsht_create_tables();

			/**
			 * Deprecated
			 *
			 * @deprecated since 1.1.0
			 * @todo remove after 02.04.2021
			 */
			if ( isset( $tmsht_options['plugin_option_version'] ) && version_compare( $tmsht_options['plugin_option_version'], '1.1.0', '<' ) ) {
				$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'tmsht_legends` ADD COLUMN `all_day` BOOLEAN NOT NULL DEFAULT "0"' );
				$wpdb->query( 'UPDATE `' . $wpdb->prefix . 'tmsht_legends` SET `all_day` = 1  WHERE `id` IN ( 4 )' );
			}
			/* end deprecated */

			/* update DB version */
			$tmsht_options['plugin_db_version'] = $db_version;
			$update_option                      = true;
		}

		/* Array merge incase this version has added new options */
		if ( ! isset( $tmsht_options['plugin_option_version'] ) || $tmsht_options['plugin_option_version'] != $tmsht_plugin_info['Version'] ) {
			$default_options = tmsht_get_options_default();
			$tmsht_options   = array_merge( $default_options, $tmsht_options );

			/* show pro features */
			$tmsht_options['hide_premium_options'] = array();

			$tmsht_options['plugin_option_version'] = $tmsht_plugin_info['Version'];
			$update_option = true;

			tmsht_plugin_activate();
		}

		if ( $update_option ) {
			update_option( 'tmsht_options', $tmsht_options );
		}
	}
}

if ( ! function_exists( 'tmsht_get_options_default' ) ) {
	/**
	 * Fetch plugin default options
	 *
	 * @return array
	 */
	function tmsht_get_options_default() {
		global $tmsht_plugin_info;

		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/user.php' );
		}

		$user_roles = array_keys( get_editable_roles() );

		$default_options = array(
			'plugin_option_version'   => $tmsht_plugin_info['Version'],
			'display_settings_notice' => 1,
			'suggest_feature_banner'  => 1,
			'ts_timeline_from'        => 0,
			'ts_timeline_to'          => 24,
			'weekends'                => array( 'sat', 'sun' ),
			'edit_past_days'          => 0,
			'date_format_type'        => 'wp',
			'date_format'             => get_option( 'date_format' ),
			'display_pages'           => array(
				'ts_user' => array(
					'user_roles'        => $user_roles,
					'user_ids'          => array(),
					'has_sub_exception' => array(),
				),
				'ts_report' => array(
					'user_roles'        => array( 'administrator' ),
					'user_ids'          => array(),
					'has_sub_exception' => array(),
				),
			),
			'reminder_on_email'       => 0,
			'day_reminder'            => 'fri',
			'time_reminder'           => '18:00',
			'clear_timesheet_period' => '',
			'content_reminder'        => array(
				'subject'   => __( 'Timesheet Reminder', 'timesheet' ),
				'message'   => sprintf( "%s, {user_name},\n\n%s:\n\n{list_days}\n\n{{ts_page_link}%s{/ts_page_link}}\n\n%s", __( 'Hi', 'timesheet' ), __( 'Please complete your timesheet for the following days', 'timesheet' ), __( 'Complete Timesheet Now', 'timesheet' ), __( 'Do not reply to this message. This is an automatic mailing.', 'timesheet' ) ),
			),
			'first_install'           => strtotime( 'now' ),
		);
		return $default_options;
	}
}

if ( ! function_exists( 'tmsht_plugin_activate' ) ) {
	/**
	 * Plugin activate
	 */
	function tmsht_plugin_activate() {
		if ( is_multisite() ) {
			switch_to_blog( 1 );
			register_uninstall_hook( __FILE__, 'tmsht_unistall' );
			restore_current_blog();
		} else {
			register_uninstall_hook( __FILE__, 'tmsht_unistall' );
		}
	}
}

if ( ! function_exists( 'tmsht_admin_scripts_styles' ) ) {
	/**
	 * Style & js on
	 */
	function tmsht_admin_scripts_styles() {
		global $tmsht_plugin_info;

		if ( isset( $_GET['page'] ) ) {

			if ( 'timesheet_settings' == $_GET['page'] ) {
				wp_enqueue_script( 'ts_settings_script', plugins_url( 'js/settings.js', __FILE__ ), array( 'jquery', 'jquery-ui-slider', 'wp-color-picker' ), $tmsht_plugin_info['Version'], true );
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_style( 'jquery-ui', plugins_url( 'css/jquery-ui.css', __FILE__ ), array(), $tmsht_plugin_info['Version'] );
				wp_enqueue_style( 'ts_settings_styles', plugins_url( 'css/settings.css', __FILE__ ), array(), $tmsht_plugin_info['Version'] );

				bws_enqueue_settings_scripts();
			} elseif ( 'timesheet_ts_user' == $_GET['page'] || 'timesheet_ts_report' == $_GET['page'] ) {
				wp_register_script( 'tmsht_datetimepicker_script', plugins_url( 'js/jquery.datetimepicker.full.min.js', __FILE__ ), array( 'jquery' ), $tmsht_plugin_info['Version'], true );
				wp_enqueue_style( 'tmsht_datetimepicker_styles', plugins_url( 'css/jquery.datetimepicker.css', __FILE__ ), array(), $tmsht_plugin_info['Version'] );

				if ( 'timesheet_ts_user' == $_GET['page'] ) {
					wp_enqueue_script( 'tmsht_script', plugins_url( 'js/ts_user_script.js', __FILE__ ), array( 'jquery', 'jquery-ui-selectable', 'jquery-touch-punch', 'tmsht_datetimepicker_script' ), $tmsht_plugin_info['Version'], true );
					wp_enqueue_style( 'tmsht_styles', plugins_url( 'css/ts_user_styles.css', __FILE__ ), array(), $tmsht_plugin_info['Version'] );
				} else {
					wp_enqueue_script( 'tmsht_script', plugins_url( 'js/ts_report_script.js', __FILE__ ), array( 'jquery', 'tmsht_datetimepicker_script' ), $tmsht_plugin_info['Version'], true );
					wp_enqueue_style( 'tmsht_styles', plugins_url( 'css/ts_report_styles.css', __FILE__ ), array(), $tmsht_plugin_info['Version'] );
				}

				$locale = explode( '_', get_locale() );
				$datetime_options = array(
					'locale'         => $locale[0],
					'dayOfWeekStart' => get_option( 'start_of_week' ),
				);

				wp_localize_script( 'tmsht_script', 'tmsht_datetime_options', $datetime_options );
			}
		}
	}
}

if ( ! function_exists( 'tmsht_generate_color' ) ) {
	/**
	 * Generate color
	 */
	function tmsht_generate_color() {
		global $wpdb;

		$wrong_colors = $wpdb->get_col( 'SELECT `color` FROM `' . $wpdb->prefix . 'tmsht_legends`' );
		$wrong_colors = array_merge( $wrong_colors, array( '#ffffff', '#f9f9f9' ) );

		while ( 1 ) {
			$color = sprintf( '#%06x', rand( 0, 16777215 ) );
			if ( ! in_array( $color, $wrong_colors ) ) {
				break;
			}
		}

		return $color;
	}
}

if ( ! function_exists( 'tmsht_settings_page' ) ) {
	/**
	 * Settings page
	 */
	function tmsht_settings_page() {
		if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
			require_once( dirname( __FILE__ ) . '/bws_menu/class-bws-settings.php' );
		}
			require_once( dirname( __FILE__ ) . '/includes/class-tmsht-settings.php' );
		$page = new Tmsht_Settings_Tabs( plugin_basename( __FILE__ ) );
		if ( method_exists( $page, 'add_request_feature' ) ) {
			$page->add_request_feature();
		} ?>
		<div class="wrap">
			<h1>Timesheet <?php esc_html_e( 'Settings', 'timesheet' ); ?></h1>
			<noscript>
				<div class="error below-h2">
					<p><strong><?php esc_html_e( 'WARNING', 'timesheet' ); ?>:</strong> <?php esc_html_e( 'The plugin works correctly only if JavaScript is enabled.', 'timesheet' ); ?></p>
				</div>
			</noscript>
			<?php $page->display_content(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tmsht_ts_user_page' ) ) {
	/**
	 * TS user page
	 */
	function tmsht_ts_user_page() {
		global $wpdb, $tmsht_options, $tmsht_plugin_info, $wp_version, $current_user;

		$message = '';
		$error   = '';

		$week_days_arr = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$day_of_week_start = get_option( 'start_of_week' );

		$date_from = ( isset( $_SESSION['tmsht_ts_user_date_from'] ) && strtotime( $_SESSION['tmsht_ts_user_date_from'] ) ) ? $_SESSION['tmsht_ts_user_date_from'] : date( 'Y-m-d' );
		$date_to = ( isset( $_SESSION['tmsht_ts_user_date_to'] ) && strtotime( $_SESSION['tmsht_ts_user_date_to'] ) ) ? $_SESSION['tmsht_ts_user_date_to'] : date( 'Y-m-d', strtotime( 'next ' . $week_days_arr[ $day_of_week_start ] . ' +6 days' ) );

		$date_period = tmsht_date_period( $date_from, date( 'Y-m-d', strtotime( $date_to . ' +1 day' ) ) );

		$tmsht_legends = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}tmsht_legends`", OBJECT_K );
		/* Convert stdClass items of array( $tmsht_legends ) to associative array */
		$tmsht_legends = json_decode( json_encode( $tmsht_legends ), true );
		$tmsht_legends[-1] = array(
			'name' => __( 'Please select...', 'timesheet' ),
			'color' => 'transparent',
			'disabled' => '0',
		);
		ksort( $tmsht_legends );

		if ( isset( $_POST['tmsht_save_ts'] ) && check_admin_referer( 'tmsht_nonce_save_ts', 'tmsht_nonce_name' ) ) {
			if ( isset( $_POST['tmsht_tr_date'] ) && is_array( $_POST['tmsht_tr_date'] ) ) {
				foreach ( $_POST['tmsht_tr_date'] as $tr_date ) {
					$tr_date = sanitize_text_field( wp_unslash( $tr_date ) );
					if ( date( 'Y-m-d', strtotime( $tr_date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] ) {
						continue;
					}

					$query_results = $wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->prefix}tmsht_ts` WHERE `user_id` = %d AND date(`time_from`) = %s", $current_user->ID, $tr_date ) );

					if ( false === $query_results ) {
						$error = __( 'Data has not been saved.', 'timesheet' );
						break;
					}

					if ( isset( $_POST['tmsht_to_db'][ $tr_date ] ) && is_array( $_POST['tmsht_to_db'][ $tr_date ] ) ) {
						foreach ( $_POST['tmsht_to_db'][ $tr_date ] as $ts_interval ) {
							$ts_interval = sanitize_text_field( wp_unslash( $ts_interval ) );
							$ts_interval_arr = explode( '@', $ts_interval );
							$ts_interval_from = $ts_interval_arr[0];
							$ts_interval_to = $ts_interval_arr[1];
							$legend_id = $ts_interval_arr[2];

							if ( strtotime( $ts_interval_from ) && strtotime( $ts_interval_to ) && array_key_exists( $legend_id, $tmsht_legends ) ) {

								$query_results = $wpdb->insert(
									$wpdb->prefix . 'tmsht_ts',
									array(
										'user_id'   => $current_user->ID,
										'time_from' => $ts_interval_from,
										'time_to'   => $ts_interval_to,
										'legend_id' => $legend_id,
									),
									array( '%d', '%s', '%s', '%d' )
								);

								if ( false === $query_results ) {
									$error = __( 'Data has not been saved.', 'timesheet' );
									break;
								}
							}
						}
					}
				}
				if ( '' == $error ) {
					$message = __( 'Data has been saved.', 'timesheet' );
				}
			} else {
				$error = __( 'Data has not been saved, because there was no change.', 'timesheet' );
			}
		}

		$ts_data = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT `time_from`, `time_to`, `legend_id`
				FROM `' . $wpdb->prefix . 'tmsht_ts`
				WHERE date(`time_from`) >= %s
				AND date(`time_to`) <= %s
				AND `user_id` = %d',
				array(
					$date_from,
					$date_to,
					$current_user->ID,
				)
			),
			ARRAY_A
		);

		foreach ( $ts_data as $key => $value ) {
			$new_key = date( 'Y-m-d', strtotime( $value['time_from'] ) );
			$ts_data[ $new_key ][] = $value;
			unset( $ts_data[ $key ] );
		}

		$bws_hide_premium_options_check = bws_hide_premium_options_check( $tmsht_options );
		if ( isset( $_POST['bws_hide_premium_options'] ) && isset( $_GET['page'] ) && 'timesheet_ts_user' == $_GET['page'] ) {
			$cur_tmsht_options = bws_hide_premium_options( $tmsht_options );
			update_option( 'tmsht_options', $cur_tmsht_options['options'] );
			$bws_hide_premium_options_check = true;
		}
		?>
		<div class="wrap tmsht_wrap">
			<h1><?php esc_html_e( 'My Availability', 'timesheet' ); ?></h1>
			<noscript>
				<div class="error below-h2">
					<p><strong><?php esc_html_e( 'WARNING', 'timesheet' ); ?>:</strong> <?php esc_html_e( 'The plugin works correctly only if JavaScript is enabled.', 'timesheet' ); ?></p>
				</div>
			</noscript>
			<div id="tmsht_save_notice" class="updated fade below-h2" style="display:none;">
				<p>
					<strong><?php esc_html_e( 'Notice', 'timesheet' ); ?></strong>: <?php esc_html_e( 'Timesheet have been changed.', 'timesheet' ); ?>
					<a class="tmsht_save_anchor" href="#tmsht_save_ts_button"><?php esc_html_e( 'Save Changes', 'timesheet' ); ?></a>
				</p>
			</div>
			<div class="updated fade below-h2" 
			<?php
			if ( '' == $message ) {
				echo 'style="display:none"';
			}
			?>
			><p><strong><?php echo esc_html( $message ); ?></strong></p></div>
			<div class="error below-h2" 
			<?php
			if ( '' == $error ) {
				echo 'style="display:none"';
			}
			?>
			><p><strong><?php echo esc_html( $error ); ?></strong></p></div>
			<div class="tmsht_ts_user_filter">
				<div class="tmsht_ts_user_filter_item tmsht_ts_user_filter_item_datepicker">
					<form method="get" action="">
						<input type="hidden" name="page" value="timesheet_ts_user">
						<div class="tmsht_ts_user_filter_block">
							<div class="tmsht_ts_user_filter_title"><strong><?php esc_html_e( 'Date from', 'timesheet' ); ?></strong></div>
							<input id="tmsht_ts_user_date_from" class="tmsht_date_datepicker_input" type="text" name="tmsht_ts_user_date_from" value="<?php echo esc_html( $date_from ); ?>" autocomplete="off">
						</div>
						<div class="tmsht_ts_user_filter_block">
							<div class="tmsht_ts_user_filter_title"><strong><?php esc_html_e( 'Date to', 'timesheet' ); ?></strong></div>
							<input id="tmsht_ts_user_date_to" class="tmsht_date_datepicker_input" type="text" name="tmsht_ts_user_date_to" value="<?php echo esc_html( $date_to ); ?>" autocomplete="off">
						</div>
						<br />
						<div class="tmsht_ts_user_change_dates">
							<input type="submit" class="button-secondary tmsht_date_datepicker_change" value="<?php esc_html_e( 'Change date', 'timesheet' ); ?>">
						</div>
						<?php if ( ! $bws_hide_premium_options_check ) { ?>
							<div style="margin: 4px 4px 0;" class="bws_pro_version_bloc">
								<div class="bws_pro_version_table_bloc">
									<div class="bws_table_bg"></div>
									<div class="tmsht_ts_user_filter_block tmsht_ts_user_export_data">
										<input disabled="disabled" type="submit" class="button-secondary" value="<?php esc_html_e( 'Export', 'timesheet' ); ?>">
									</div>
								</div>
							</div>
						<?php } ?>
					</form>
				</div>
				<div class="tmsht_ts_user_filter_item tmsht_ts_user_filter_item_legend">
					<div class="tmsht_ts_user_filter_title"><strong><?php esc_html_e( 'Status', 'timesheet' ); ?></strong></div>
					<select id="tmsht_ts_user_legend" class="tmsht_ts_user_legend" name="tmsht_ts_user_legend">
						<?php
						$legend_index = 0;
						foreach ( $tmsht_legends as $id => $legend ) {
							if ( 0 == $legend['disabled'] ) {
								?>
								<option value="<?php echo esc_attr( $id ); ?>" data-all-day="<?php echo ! empty( $legend['all_day'] ) ? esc_html( $legend['all_day'] ) : ''; ?>" data-color="<?php echo esc_html( $legend['color'] ); ?>" <?php selected( $legend_index, 0 ); ?>><?php echo esc_html( $legend['name'] ); ?></option>
								<?php
								$legend_index++;
							}
						}
						?>
					</select>
				</div>
				<div class="tmsht_ts_user_filter_item tmsht_ts_user_filter_table_actions">
					<div class="tmsht_ts_user_filter_title">&nbsp;</div>
					<a id="tmsht_transposition_tbl" class="button-secondary hide-if-no-js tmsht_dashicons dashicons dashicons-image-rotate-right" href="#" title="<?php esc_html_e( 'Transposition table', 'timesheet' ); ?>"></a>
				</div>
				<?php if ( ! $bws_hide_premium_options_check ) { ?>
					<div class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">
							<form method="post" action="?page=timesheet_ts_user">
								<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'timesheet' ); ?>"></button>
							</form>
							<div class="bws_table_bg"></div>
							<div class="tmsht_ts_user_filter_item tmsht_ts_user_filter_item_user">
								<div style="margin-bottom: 5px;" class="tmsht_ts_user_filter_title">
									<strong><?php esc_html_e( 'Users', 'timesheet-pro' ); ?></strong>
								</div>
								<div style="margin-bottom: 7px;" class="tmsht_ts_timesheet_user_list_wrap">
									<div class="tmsht_ts_timesheet_user_list">
										<input disabled="disabled" class="tmsht_ts_timesheet_search_user hide-if-no-js" type="text" placeholder="<?php esc_html_e( 'Search user', 'timesheet' ); ?>">
									</div>
								</div>
								<div class="tmsht_ts_timesheet_selected_users_container hide-if-no-js">
									<span style="padding: 3px; background-color: gainsboro;" id="tmsht_ts_timesheet_user_selected_2" class="tmsht_ts_timesheet_user_selected">
										<a title="" href= "">admin</a>
										<label class="tmsht_ts_timesheet_user_uncheck" for="tmsht_ts_timesheet_user_id"></label>
									</span>
									<div class="tmsht_clearfix"></div>
								</div>
							</div>
						</div>
						<?php tmsht_bws_pro_block_links(); ?>
					</div>
				<?php } ?>
			</div>
			<form method="post" action="">
				<div id="tmsht_ts_user_table_area_wrap">
					<div id="tmsht_ts_user_table_area">
						<table id="tmsht_ts_user_table" class="widefat striped tmsht_ts_user_table tmsht_ts_user_table_head_timeline" cellspacing="0" cellpadding="0">
							<thead>
								<tr>
									<td class="tmsht_ts_user_table_td_dateline">&nbsp;</td>
									<?php for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value++ ) { ?>
										<td class="tmsht_ts_user_table_td_timeline"><div class="tmsht_ts_user_time_display"><?php echo esc_html( ( $time_value > 9 ) ? $time_value : '&nbsp;' . $time_value ); ?></div></td>
									<?php } ?>
								</tr>
							</thead>
							<tbody>
								<?php
								$tmsht_tr_index = 0;
								$tmsht_td_index = 0;
								foreach ( $date_period as $date ) {
									$tr_classes = ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) ) ? 'tmsht_ts_user_table_tr tmsht_ts_user_table_tr_today' : 'tmsht_ts_user_table_tr';
									$tmsht_td_dateline_classes = ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) ) ? ' tmsht_ts_user_table_highlight_today' : '';
									if ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) {
										$tmsht_td_dateline_classes .= ' tmsht_ts_user_table_highlight_weekdays';
									}

									$td_readonly = ( date( 'Y-m-d', strtotime( $date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] );
									?>
									<tr class="<?php echo esc_html( $tr_classes ); ?>" data-tr-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( $date ) ) ); ?>">
										<td class="tmsht_ts_user_table_td_dateline">
											<div class="tmsht_ts_user_table_td_dateline_group<?php echo esc_html( $tmsht_td_dateline_classes ); ?>" data-datline-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( $date ) ) ); ?>">
												<div class="tmsht_ts_user_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
												<div class="tmsht_ts_user_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $date ) ) ); ?></div>
											</div>
											<input class="tmsht_tr_date" type="hidden" name="tmsht_tr_date[]" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( $date ) ) ); ?>" disabled="disabled">
										</td>
										<?php
										for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value++ ) {
											$td_timeline_classes = 'tmsht_ts_user_table_td_time';

											if ( $td_readonly ) {
												$td_timeline_classes .= ' tmsht_ts_user_table_td_readonly';
												$tmsht_td_index = -1;
											}

											if ( 0 == $tmsht_td_index ) {
												$td_timeline_classes .= ' tmsht_ts_user_table_td_highlighted';
											}
											?>
											<td class="<?php echo esc_html( $td_timeline_classes ); ?>" data-tr-index="<?php echo esc_attr( $tmsht_tr_index ); ?>" data-td-index="<?php echo esc_attr( $time_value ); ?>" data-td-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( $date ) ) ); ?>" data-td-time-from="<?php printf( '%02d:00', esc_attr( $time_value ) ); ?>" data-td-time-to="<?php printf( '%02d:00', esc_attr( $time_value + 1 ) ); ?>">
												<div class="tmsht_ts_user_table_td_fill_group">
													<?php
													for ( $time_minutes = 0; $time_minutes < 60; $time_minutes += 5 ) {

														$search_date = date( 'Y-m-d', strtotime( $date ) );
														$td_datetime = strtotime( sprintf( '%s %02d:%02d:00', $search_date, $time_value, $time_minutes ) );
														$td_legend_id = -1;
														$td_title = '';

														if ( array_key_exists( $search_date, $ts_data ) ) {
															foreach ( $ts_data[ $search_date ] as $data ) {

																if ( strtotime( $data['time_from'] ) <= $td_datetime && strtotime( $data['time_to'] ) > $td_datetime ) {
																	$td_legend_id = $data['legend_id'];
																	$time_to_adjustment = ( date( 'i', strtotime( $data['time_to'] ) ) == 59 ) ? '24:00' : date( 'H:i', strtotime( $data['time_to'] ) );
																	$td_title = sprintf( '%s (%s - %s)', $tmsht_legends[ $td_legend_id ]['name'], date( 'H:i', strtotime( $data['time_from'] ) ), $time_to_adjustment );
																}
															}
														}
														?>
														<div class="tmsht_ts_user_table_td_fill"
															style="background-color: <?php echo esc_html( $tmsht_legends[ $td_legend_id ]['color'] ); ?>;" data-fill-time-from="<?php printf( '%02d:%02d', esc_attr( $time_value ), esc_attr( $time_minutes ) ); ?>"
															data-fill-time-to="<?php printf( '%02d:%02d', esc_attr( $time_minutes < 55 ? $time_value : $time_value + 1 ), esc_attr( $time_minutes < 55 ? $time_minutes + 5 : 0 ) ); ?>"
															data-legend-id="<?php echo esc_attr( $td_legend_id ); ?>"
															<?php if ( isset( $tmsht_legends[ $td_legend_id ]['all_day'] ) ) { ?>
															data-all-day="<?php echo esc_attr( $tmsht_legends[ $td_legend_id ]['all_day'] ); ?>"
															<?php } ?>
															title="<?php echo esc_html( $td_title ); ?>"></div>
													<?php } ?>
												</div>
												<?php if ( $td_readonly ) { ?>
													<div class="tmsht_ts_user_table_td_readonly_fill"></div>
												<?php } ?>
											</td>
											<?php
											$tmsht_td_index++;
										}
										?>
									</tr>
									<?php
									$tmsht_tr_index++;
								}
								?>
							</tbody>
							<tfoot>
								<tr>
									<td class="tmsht_ts_user_table_td_dateline">&nbsp;</td>
									<?php for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value++ ) { ?>
										<td class="tmsht_ts_user_table_td_timeline"><div class="tmsht_ts_user_time_display"><?php echo esc_html( ( $time_value > 9 ) ? $time_value : '&nbsp;' . $time_value ); ?></div></td>
									<?php } ?>
								</tr>
							</tfoot>
						</table>
						<div id="tmsht_ts_user_table_selection"></div>
					</div>
					<div class="tmsht_ts_user_advanced_container_area">
						<div id="tmsht_ts_user_advanced_container" class="tmsht_ts_user_advanced_container">
							<?php
							foreach ( $tmsht_legends as $ts_legend_id => $ts_legend ) {
								if ( 0 > $ts_legend_id ) {
									continue;
								}
								?>
								<div class="tmsht_ts_user_advanced_box tmsht_maybe_hidden hidden" data-box-id="<?php echo esc_attr( $ts_legend_id ); ?>">
									<div class="tmsht_ts_user_advanced_box_title" style="background-color: <?php echo esc_html( $ts_legend['color'] ); ?>"><?php echo esc_html( $ts_legend['name'] ); ?></div>
									<div class="tmsht_ts_user_advanced_box_content">
										<?php foreach ( $date_period as $date ) { ?>
											<div class="tmsht_ts_user_advanced_box_details tmsht_maybe_hidden hidden" data-details-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( $date ) ) ); ?>">
												<div class="tmsht_ts_user_advanced_box_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
												<div class="tmsht_ts_user_advanced_box_interval_wrap"></div>
											</div>
										<?php } ?>
									</div>
								</div>
							<?php } ?>
							<div class="tmsht_clearfix"></div>
						</div>
						<div id="tmsht_ts_user_advanced_box_details_template" class="hidden">
							<div class="tmsht_ts_user_advanced_box_interval">
								<span class="tmsht_ts_user_advanced_box_interval_from_text">%time_from%</span><input class="tmsht_ts_user_advanced_box_interval_from tmsht_maybe_hidden hidden" type="text" value="%time_from%"> - <span class="tmsht_ts_user_advanced_box_interval_to_text">%time_to%</span><input class="tmsht_ts_user_advanced_box_interval_to tmsht_maybe_hidden hidden" type="text" value="%time_to%">
								<input type="hidden" data-hidden-name="tmsht_to_db[%date%][]" value="%date% %input_time_from%@%date% %input_time_to%@%legend_id%">
							</div>
						</div>
					</div>
					<div class="tmsht_clearfix"></div>
					<input id="tmsht_save_ts_button" class="button-primary" type="submit" name="tmsht_save_ts" value="<?php esc_html_e( 'Save Changes', 'timesheet' ); ?>">
					<?php wp_nonce_field( 'tmsht_nonce_save_ts', 'tmsht_nonce_name' ); ?>
				</div>
				<ul id="tmsht_ts_user_context_menu" data-visible="false">
					<?php if ( ! $bws_hide_premium_options_check ) { ?>
						<li class="tmsht_ts_user_context_menu_item tmsht_ts_user_context_menu_item_disabled" data-action="false">
							<span class="tmsht_ts_user_context_menu_icon dashicons dashicons-clock"></span><span class="tmsht_ts_user_context_menu_text"><?php esc_html_e( 'Edit time', 'timesheet' ); ?> <a class="tmsht_ts_user_context_menu_link" href="https://bestwebsoft.com/products/wordpress/plugins/timesheet/?k=3bdf25984ad6aa9d95074e31c5eb9bb3&amp;pn=606&amp;v=<?php echo esc_attr( $tmsht_plugin_info['Version'] ); ?>&amp;wp_v=<?php echo esc_attr( $wp_version ); ?>" target="_blank">(<?php esc_html_e( 'Available in PRO', 'timesheet' ); ?>)</a></span>
						</li>
					<?php } ?>
					<li class="tmsht_ts_user_context_menu_item tmsht_ts_user_context_menu_item_enabled" data-action="delete">
						<span class="tmsht_ts_user_context_menu_icon dashicons dashicons-dismiss"></span><span class="tmsht_ts_user_context_menu_text"><?php esc_html_e( 'Delete status', 'timesheet' ); ?></span>
					</li>
					<li class="tmsht_ts_user_context_menu_item tmsht_ts_user_context_menu_item_separator tmsht_ts_user_context_menu_item_disabled"></li>
					<?php
					foreach ( $tmsht_legends as $id => $legend ) {
						if ( 0 == (int)$legend['disabled'] && 0 <= $id ) {
							?>
							<li class="tmsht_ts_user_context_menu_item tmsht_ts_user_context_menu_item_enabled" data-legend-id="<?php echo esc_attr( $id ); ?>" data-action="apply_status">
								<span class="tmsht_ts_user_context_menu_icon" style="background: <?php echo esc_html( $legend['color'] ); ?>;"></span><span class="tmsht_ts_user_context_menu_text"><?php echo esc_html( $legend['name'] ); ?></span>
							</li>
							<?php
						}
					}
					?>
				</ul>
			</form>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tmsht_bws_pro_block_links' ) ) {
	/**
	 * Add pro block
	 */
	function tmsht_bws_pro_block_links() {
		global $wp_version, $tmsht_plugin_info;

		$link_key = '3bdf25984ad6aa9d95074e31c5eb9bb3';
		$link_pn = '606';
		$trial_days = false;
		?>
		<div class="bws_pro_version_tooltip">
			<a class="bws_button"
				href="<?php echo esc_url( $tmsht_plugin_info['PluginURI'] ); ?>?k=<?php echo wp_kses_post( $link_key ); ?>&amp;pn=<?php echo wp_kses_post( $link_pn ); ?>&amp;v=<?php echo esc_attr( $tmsht_plugin_info['Version'] ); ?>&amp;wp_v=<?php echo esc_attr( $wp_version ); ?>"
				target="_blank"
				title="<?php echo esc_html( $tmsht_plugin_info['Name'] ); ?>"><?php esc_html_e( 'Upgrade to Pro', 'bestwebsoft' ); ?></a>
			<?php if ( false !== $trial_days ) { ?>
				<span class="bws_trial_info">
						<?php esc_html_e( 'or', 'bestwebsoft' ); ?>
						<a href="<?php echo esc_url( $tmsht_plugin_info['PluginURI'] . '?k=' . $link_key . '&pn=' . $link_pn . '&v=' . $tmsht_plugin_info['Version'] . '&wp_v=' . $wp_version ); ?>"
							target="_blank"
							title="<?php echo esc_html( $tmsht_plugin_info['Name'] ); ?>"><?php esc_html_e( 'Start Your Free Trial', 'bestwebsoft' ); ?></a>
					</span>
			<?php } ?>
			<div class="clear"></div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tmsht_get_users' ) ) {
	/**
	 * Get user by data
	 *
	 * @param bool $add_additional_data Additional data.
	 */
	function tmsht_get_users( $add_additional_data = false ) {
		global $wpdb, $tmsht_options;

		$users = array();

		if ( ! empty( $tmsht_options['display_pages']['ts_user']['user_roles'] ) ) {
			foreach ( $tmsht_options['display_pages']['ts_user']['user_roles'] as $role ) {
				if ( ! empty( $users_in_role_query ) ) {
					$users_in_role_query .= $wpdb->prepare(
						' OR umeta.meta_value LIKE %s',
						'%' . $role . '%'
					);
				} else {
					$users_in_role_query = $wpdb->prepare(
						'umeta.meta_value LIKE %s',
						'%' . $role . '%'
					);
				}
			}

			$data = ( $add_additional_data ) ? 'users.ID, users.user_email, users.display_name' : 'users.ID, users.user_login';

			$users_in_role = $wpdb->get_results(
				'SELECT ' . $data . '
				FROM `' . $wpdb->base_prefix . 'users` AS users,
					`' . $wpdb->base_prefix . 'usermeta` AS umeta
				WHERE users.ID = umeta.user_id
					AND umeta.meta_key = "' . $wpdb->prefix . 'capabilities"
					AND (' . $users_in_role_query . ')
				ORDER BY users.user_login ASC',
				OBJECT_K
			);

			if ( ! empty( $users_in_role ) ) {
				foreach ( $users_in_role as $user_data ) {
					if ( $add_additional_data ) {
						$users[ $user_data->ID ] = array(
							'display_name' => $user_data->display_name,
							'email'        => $user_data->user_email,
						);
					} else {
						$users[ $user_data->ID ] = $user_data->user_login;
					}
				}
			}

			if ( in_array( 'administrator', $tmsht_options['display_pages']['ts_user']['user_roles'] ) && is_multisite() && ! is_main_site() ) {
				$super_admins = get_super_admins();

				foreach ( $super_admins as $super_admin ) {
					$get_user = get_user_by( 'login', $super_admin );
					if ( $get_user && ! array_key_exists( $get_user->ID, $users ) ) {
						if ( $add_additional_data ) {
							$users[ $get_user->ID ] = array(
								'display_name' => $get_user->display_name,
								'user_email'   => $get_user->user_email,
							);
						} else {
							$users[ $get_user->ID ] = $get_user->user_login;
						}
					}
				}
			}
		}
		return $users;
	}
}

if ( ! function_exists( 'tmsht_array_replace' ) ) {
	/**
	 * Function array_replace (PHP 5 >= 5.3.0, PHP 7)
	 *
	 * @since 0.1.6
	 *
	 * @param array $array1 Array with params.
	 * @param array $array2 Array with params.
	 * @return array $array1 Array with params.
	 */
	function tmsht_array_replace( $array1, $array2 ) {
		if ( function_exists( 'array_replace' ) ) {
			return array_replace( $array1, $array2 );
		} else {
			foreach ( $array2 as $key => $value ) {
				$array1[ $key ] = $value;
			}
			return $array1;
		}
	}
}

if ( ! function_exists( 'tmsht_ts_report_page' ) ) {
	/**
	 * Dipslay report page
	 */
	function tmsht_ts_report_page() {
		global $wpdb, $tmsht_options, $current_user, $tmsht_legends;

		$message = '';
		$error   = '';

		$week_days_arr = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$day_of_week_start = get_option( 'start_of_week' );

		$date_preset_quantity_arr = array( 1, 2, 3 );

		$date_preset_units_arr = array(
			'week'  => __( 'Week', 'timesheet' ),
			'month' => __( 'Month', 'timesheet' ),
		);

		$ts_report_group_by_arr = array(
			'date'   => _x( 'Date', 'Group by', 'timesheet' ),
			'user'   => _x( 'User', 'Group by', 'timesheet' ),
		);

		$ts_report_view = array(
			'hourly' => __( 'Hourly', 'timesheet' ),
			'daily'  => __( 'Daily', 'timesheet' ),
		);

		/* Get legends */
		$tmsht_legends = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->prefix . 'tmsht_legends`', OBJECT_K );
		/* Convert stdClass items of array( $tmsht_legends ) to associative array */
		$tmsht_legends = json_decode( json_encode( $tmsht_legends ), true );
		$tmsht_legends[-1] = array(
			'name' => __( 'Blank', 'timesheet' ),
			'color' => 'transparent',
			'disabled' => 1,
		);
		$tmsht_legends[-2] = array(
			'name' => __( 'All statuses', 'timesheet' ),
			'color' => '#444444',
			'disabled' => 0,
		);
		ksort( $tmsht_legends );

		/* Get users */
		$tmsht_users = tmsht_get_users();

		/* Get user meta */
		$ts_report_filters = get_user_meta( $current_user->ID, '_tmsht_ts_report_filters', true );

		if ( empty( $ts_report_filters ) ) {
			$ts_report_filters = array(
				'date' => array(
					'type'   => 'period',
					'preset' => array(),
				),
				'group_by'  => 'date',
				'view'      => 'hourly',
				'legend'    => -2,
				'users'     => array_keys( $tmsht_users ),
			);

			add_user_meta( $current_user->ID, '_tmsht_ts_report_filters', $ts_report_filters );
		}

		/* Apply filters */
		if ( isset( $_POST['tmsht_generate_ts_report'] ) && isset( $_POST['tmsht_apply_ts_report_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmsht_apply_ts_report_field'] ) ), 'tmsht_apply_ts_report_action' ) ) {
			if (
				( isset( $_POST['tmsht_date_filter_type'] ) && 'preset' == sanitize_text_field( wp_unslash( $_POST['tmsht_date_filter_type'] ) ) ) &&
				( isset( $_POST['tmsht_date_preset_unit'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_date_preset_unit'] ) ), $date_preset_units_arr ) ) &&
				isset( $_POST['tmsht_date_preset_quantity'] )
			) {
				$ts_report_filters['date'] = array(
					'type'   => 'preset',
					'preset' => array(
						'quantity' => absint( $_POST['tmsht_date_preset_quantity'] ),
						'unit'     => sanitize_text_field( wp_unslash( $_POST['tmsht_date_preset_unit'] ) ),
					),
				);
			} else {
				$ts_report_filters['date'] = array(
					'type'   => 'period',
					'preset' => array(),
				);
			}

			$ts_report_filters['group_by'] = isset( $_POST['tmsht_ts_report_group_by'] ) && ( array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_group_by'] ) ), $ts_report_group_by_arr ) ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_group_by'] ) ) : 'date';
			$ts_report_filters['legend'] = isset( $_POST['tmsht_ts_report_legend'] ) && ( array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_legend'] ) ), $tmsht_legends ) ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_legend'] ) ) : -2;
			$ts_report_filters['view'] = ( -2 != $ts_report_filters['legend'] && isset( $_POST['tmsht_ts_report_view'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_view'] ) ), $ts_report_view ) ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_view'] ) ) : 'hourly';
			$ts_report_filters['users'] = ( isset( $_POST['tmsht_ts_report_user'] ) && is_array( $_POST['tmsht_ts_report_user'] ) ) ? array_map( 'absint', $_POST['tmsht_ts_report_user'] ) : array_keys( $tmsht_users );
			update_user_meta( $current_user->ID, '_tmsht_ts_report_filters', $ts_report_filters );
		}

		/* Report generation */
		$date_from        = ( isset( $_SESSION['tmsht_ts_report_date_from'] ) && strtotime( $_SESSION['tmsht_ts_report_date_from'] ) && 'period' == $ts_report_filters['date']['type'] ) ? $_SESSION['tmsht_ts_report_date_from'] : date( 'Y-m-d' );
		$filter_date_from = $date_from;
		$date_to          = ( isset( $_SESSION['tmsht_ts_report_date_to'] ) && strtotime( $_SESSION['tmsht_ts_report_date_to'] ) && 'period' == $ts_report_filters['date']['type'] ) ? $_SESSION['tmsht_ts_report_date_to'] : date( 'Y-m-d', strtotime( 'next ' . $week_days_arr[ $day_of_week_start ] . ' +6 days' ) );
		$filter_date_to   = $date_to;

		if ( 'preset' == $ts_report_filters['date']['type'] ) {
			$date_from = date( 'Y-m-d' );
			$date_to = date( 'Y-m-d', strtotime( '+' . $ts_report_filters['date']['preset']['quantity'] . ' ' . $ts_report_filters['date']['preset']['unit'] ) );
		}

		$date_period = tmsht_date_period( $date_from, date( 'Y-m-d', strtotime( $date_to . ' +1 day' ) ) );

		$selected_users = array();

		foreach ( $ts_report_filters['users'] as $user_id ) {
			if ( array_key_exists( $user_id, $tmsht_users ) ) {
				$selected_users[] = $user_id;
			}
		}

		$ts_data = array();

		if ( $selected_users ) {
			$ts_data_query = ( 'hourly' === $ts_report_filters['view'] ) ? 'SELECT `user_id`, `time_from`, `time_to`, `legend_id`' : 'SELECT `user_id`, DATE_FORMAT( `time_from`, "%Y-%m-%d" ) AS `date`';

			$ts_data_query .= $wpdb->prepare(
				' FROM `' . $wpdb->prefix . 'tmsht_ts` WHERE date(`time_from`) >= %s AND date(`time_to`) <= %s',
				$date_from,
				$date_to
			);

			if ( $ts_report_filters['legend'] > 0 ) {
				$ts_data_query .= $wpdb->prepare(
					' AND `legend_id` = %s',
					$ts_report_filters['legend']
				);
			}

			$selected_users_placeholders = implode( ', ', array_fill( 0, count( $selected_users ), '%d' ) );

			$ts_data_query .= $wpdb->prepare(
				' AND `user_id` IN (' . $selected_users_placeholders . ')',
				$selected_users
			);
			if ( 'hourly' != $ts_report_filters['view'] ) {
				$ts_data_query .= ' GROUP BY `user_id`, DAY(`time_from`)';
			}
			$ts_data_query .= ' ORDER BY `user_id` ASC, `time_from` ASC';
			$ts_get_data = $wpdb->get_results( $ts_data_query, ARRAY_A );

			if ( $ts_get_data ) {
				if ( 'hourly' == $ts_report_filters['view'] ) {
					if ( 'date' == $ts_report_filters['group_by'] ) {

						foreach ( $ts_get_data as $data ) {
							$key_date = date( 'Y-m-d', strtotime( $data['time_from'] ) );
							$ts_data[ $key_date ][ $data['user_id'] ][] = $data;
						}

						/* need to create empty array for saving sorting - username ASC */
						$empty_users_data = array();
						foreach ( $selected_users as $user_id ) {
							$empty_users_data[ $user_id ][] = array();
						}

						foreach ( $date_period as $date ) {
							$date_formated = date( 'Y-m-d', strtotime( $date ) );

							if ( isset( $ts_data[ $date_formated ] ) ) {
								$ts_data[ $date_formated ] = tmsht_array_replace( $empty_users_data, $ts_data[ $date_formated ] );
							} else {
								$ts_data[ $date_formated ] = array(
									-1 => array( array() ),
								);
							}
						}

						/* sort by time */
						ksort( $ts_data );
					} else if ( 'user' == $ts_report_filters['group_by'] ) {
						/* need to create empty array first for saving sorting - username ASC */
						foreach ( $selected_users as $user_id ) {
							$ts_data[ $user_id ] = array();
						}

						foreach ( $ts_get_data as $data ) {
							$key_date = date( 'Y-m-d', strtotime( $data['time_from'] ) );
							$ts_data[ $data['user_id'] ][ $key_date ][] = $data;
						}

						$empty_date_data = array();
						foreach ( $date_period as $date ) {
							$date_formated = date( 'Y-m-d', strtotime( $date ) );
							$empty_date_data[ $date_formated ][] = array();
						}

						foreach ( $ts_data as $user_id => $data ) {
							if ( ! empty( $data ) ) {
								$ts_data[ $user_id ] = tmsht_array_replace( $empty_date_data, $ts_data[ $user_id ] );
							}
						}
					}
				} else {
					if ( 'date' == $ts_report_filters['group_by'] ) {

						foreach ( $ts_get_data as $data ) {
							$ts_data[ $data['date'] ][] = $data['user_id'];
						}

						foreach ( $date_period as $date ) {
							$date_formated = date( 'Y-m-d', strtotime( $date ) );

							$exists_data_for_users = isset( $ts_data[ $date_formated ] ) ? array_keys( $ts_data[ $date_formated ] ) : array();

							if ( ! $exists_data_for_users ) {
								$ts_data[ $date_formated ] = array( '-1' );
							}
						}

						/* sort by time */
						ksort( $ts_data );
					} else {
						/* need to create empty array first for saving sorting - username ASC */
						foreach ( $selected_users as $user_id ) {
							$ts_data[ $user_id ] = array();
						}

						foreach ( $ts_get_data as $data ) {
							$ts_data[ $data['user_id'] ][] = $data['date'];
						}
					}
				}
			}
		} else {
			$error = __( 'Select at least one user.', 'timesheet' );
		}
		?>
		<div class="wrap tmsht_wrap">
			<h1>Timesheet <?php esc_html_e( 'Team', 'timesheet' ); ?></h1>
			<noscript>
				<div class="error below-h2">
					<p><strong><?php esc_html_e( 'WARNING', 'timesheet' ); ?>:</strong> <?php esc_html_e( 'The plugin works correctly only if JavaScript is enabled.', 'timesheet' ); ?></p>
				</div>
			</noscript>
			<div class="updated fade below-h2" <?php echo ( '' == $message ) ? 'style="display:none"' : ''; ?>><p><strong><?php echo esc_html( $message ); ?></strong></p></div>
			<div class="error below-h2" <?php echo ( '' == $error ) ? 'style="display:none"' : ''; ?>><p><strong><?php echo esc_html( $error ); ?></strong></p></div>
			<div class="tmsht_container">
				<form method="post" action="">
					<div class="tmsht_ts_report_filter">
						<div class="tmsht_ts_report_filter_item tmsht_ts_report_filter_item_datepicker">
							<div class="tmsht_ts_report_filter_title"><strong><?php esc_html_e( 'Date', 'timesheet' ); ?></strong></div>
							<table>
								<tbody>
									<tr>
										<td>
											<input type="radio" name="tmsht_date_filter_type" value="period" <?php checked( $ts_report_filters['date']['type'], 'period' ); ?>>
										</td>
										<td data-filter-type="period">
											<div class="tmsht_ts_report_filter_block">
												<span><?php echo esc_html_x( 'from', 'date', 'timesheet' ); ?></span>
												<input id="tmsht_ts_report_date_from" class="tmsht_date_datepicker_input" type="text" name="tmsht_ts_report_date_from" value="<?php echo esc_attr( $filter_date_from ); ?>" autocomplete="off">
											</div>
											<div class="tmsht_ts_report_filter_block">
												<span><?php echo esc_html_x( 'to', 'date', 'timesheet' ); ?></span>
												<input id="tmsht_ts_report_date_to" class="tmsht_date_datepicker_input" type="text" name="tmsht_ts_report_date_to" value="<?php echo esc_attr( $filter_date_to ); ?>" autocomplete="off">
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<input type="radio" name="tmsht_date_filter_type" value="preset" <?php checked( $ts_report_filters['date']['type'], 'preset' ); ?>>
										</td>
										<td data-filter-type="preset">
											<select id="tmsht_date_preset_quantity" name="tmsht_date_preset_quantity">
												<?php foreach ( $date_preset_quantity_arr as $date_preset_quantity ) { ?>
													<option value="<?php echo esc_attr( $date_preset_quantity ); ?>" <?php echo 'preset' == $ts_report_filters['date']['type'] && $ts_report_filters['date']['preset']['quantity'] == $date_preset_quantity ? 'selected="selected"' : ''; ?>><?php echo esc_html( $date_preset_quantity ); ?></option>
												<?php } ?>
											</select>
											<select id="tmsht_date_preset_unit" name="tmsht_date_preset_unit">
												<?php foreach ( $date_preset_units_arr as $date_preset_unit_key => $date_preset_unit_name ) { ?>
													<option value="<?php echo esc_attr( $date_preset_unit_key ); ?>" <?php echo 'preset' == $ts_report_filters['date']['type'] && $ts_report_filters['date']['preset']['unit'] == $date_preset_unit_key ? 'selected="selected"' : ''; ?>><?php echo esc_html( $date_preset_unit_name ); ?></option>
												<?php } ?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="tmsht_ts_report_filter_item">
							<div>
								<div class="tmsht_ts_report_filter_title"><strong><?php esc_html_e( 'Group by', 'timesheet' ); ?></strong></div>
								<?php foreach ( $ts_report_group_by_arr as $tmsht_ts_report_group_by_id => $tmsht_ts_report_group_by_type ) { ?>
									<label>
										<input type="radio" name="tmsht_ts_report_group_by" value="<?php echo esc_attr( $tmsht_ts_report_group_by_id ); ?>" <?php checked( $tmsht_ts_report_group_by_id, $ts_report_filters['group_by'] ); ?> /> <?php echo esc_html( $tmsht_ts_report_group_by_type ); ?>
									</label>
									<br>
								<?php } ?>
							</div>
							<br/>
							<div class="tmsht_ts_report_view_filter <?php echo -2 == $ts_report_filters['legend'] ? 'hidden' : ''; ?>">
								<div class="tmsht_ts_report_filter_title"><strong><?php esc_html_e( 'View', 'timesheet' ); ?></strong></div>
								<?php foreach ( $ts_report_view as $ts_report_view_id => $ts_report_view_type ) { ?>
									<label><input type="radio" name="tmsht_ts_report_view" value="<?php echo esc_attr( $ts_report_view_id ); ?>" <?php checked( $ts_report_view_id, $ts_report_filters['view'] ); ?> ?><?php echo esc_html( $ts_report_view_type ); ?></label>
									<br>
								<?php } ?>
							</div>
						</div>
						<div class="tmsht_ts_report_filter_item">
							<div class="tmsht_ts_report_filter_title"><strong><?php esc_html_e( 'Status', 'timesheet' ); ?></strong></div>
							<fieldset>
								<?php
								$legend_index = 0;
								foreach ( $tmsht_legends as $id => $legend ) {
									if ( 0 == $legend['disabled'] ) {
										?>
										<label class="tmsht_ts_report_legend_label"><input class="tmsht_ts_report_legend" type="radio" name="tmsht_ts_report_legend" value="<?php echo esc_attr( $id ); ?>" data-color="<?php echo esc_html( $legend['color'] ); ?>" <?php checked( $id, $ts_report_filters['legend'] ); ?>><span class="tmsht_ts_report_legend_color" style="background-color: <?php echo esc_html( $legend['color'] ); ?>;"></span><span class="tmsht_ts_report_legend_name"><?php echo esc_html( $legend['name'] ); ?></span></label>
										<br>
										<?php
										$legend_index++;
									}
								}
								?>
							</fieldset>
						</div>
						<div class="tmsht_ts_report_filter_item tmsht_ts_report_filter_item_user">
							<div class="tmsht_ts_report_filter_title"><strong><?php esc_html_e( 'Users', 'timesheet' ); ?></strong></div>
							<?php tmsht_report_user_list_display( $tmsht_users, $selected_users ); ?>
						</div>
						<div class="tmsht_clearfix"></div>
						<div class="tmsht_ts_report_generate">
							<input class="button-primary" type="submit" name="tmsht_generate_ts_report" value="<?php echo esc_html_x( 'Apply', 'Apply ts report', 'timesheet' ); ?>">
							<?php wp_nonce_field( 'tmsht_apply_ts_report_action', 'tmsht_apply_ts_report_field' ); ?>
						</div>
					</div>
				</form>
				<?php if ( ! $ts_data ) { ?>
					<table id="tmsht_ts_report_table" class="widefat striped tmsht_ts_report_table tmsht_ts_report_table_head_timeline tmsht_ts_report_table_group_by_<?php echo esc_attr( $ts_report_filters['group_by'] ); ?> tmsht_ts_report_view_<?php echo esc_attr( $ts_report_filters['view'] ); ?> tmsht_ts_report_table_<?php echo ( $ts_data ) ? 'has_data' : 'no_data'; ?> tmsht_ts_report_table_nodata">
						<tbody>
							<tr>
								<td><strong><?php esc_html_e( 'No data to view', 'timesheet' ); ?>.</strong></td>
							</tr>
						</tbody>
					</table>
				<?php } else { ?>
					<table id="tmsht_ts_report_table" class="widefat striped tmsht_ts_report_table tmsht_ts_report_table_head_timeline tmsht_ts_report_table_group_by_<?php echo esc_attr( $ts_report_filters['group_by'] ); ?> tmsht_ts_report_table_<?php echo ( $ts_data ) ? 'has_data' : 'no_data'; ?>">
						<thead>
							<tr>
								<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
								<?php if ( 'hourly' == $ts_report_filters['view'] ) { ?>
									<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
									<?php for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value++ ) { ?>
										<td class="tmsht_ts_report_table_td_timeline"><div class="tmsht_ts_report_time_display"><?php echo esc_html( $time_value > 9 ? $time_value : '&nbsp;' . $time_value ); ?></div></td>
										<?php
									}
								} else {
									if ( 'date' == $ts_report_filters['group_by'] ) {
										foreach ( $ts_data as $ts_key => $ts_value ) {
											$td_timeline_classes = 'tmsht_ts_report_table_td_dateline';
											if ( date( 'Y-m-d' ) === $ts_key ) {
												$td_timeline_classes .= ' tmsht_ts_report_table_td_today tmsht_ts_report_table_highlight_today';
											}
											if ( in_array( strtolower( date( 'D', strtotime( $ts_key ) ) ), $tmsht_options['weekends'] ) ) {
												$td_timeline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
											}
											?>
											<td class="<?php echo esc_html( $td_timeline_classes ); ?>">
												<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $ts_key ) ) ); ?></div>
												<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $ts_key ) ) ); ?></div>
											</td>
											<?php
										}
									} else {
										foreach ( $selected_users as $user_id ) {
											?>
											<td class="tmsht_ts_report_table_td_dateline"><strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong></td>
											<?php
										}
									}
								}
								?>
							</tr>
						</thead>
						<tbody>
							<?php
							if ( 'hourly' == $ts_report_filters['view'] ) {
								if ( 'date' == $ts_report_filters['group_by'] ) {
									$pre_date = '';

									foreach ( $ts_data as $date => $data_per_day ) {
										$user_data_1_per_day = array();
										$user_data_2_per_day = array();
										$i = 0;
										foreach ( $data_per_day as $user_id => $user_data_per_day ) {
											if ( 0 === $i ) {
												$user_data_1_per_day[ $user_id ] = $user_data_per_day;
											} else {
												$user_data_2_per_day[ $user_id ] = $user_data_per_day;
											}
											$i++;
										}
										$is_today = ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) );
										$prev_date = date( 'Y-m-d', strtotime( $date . ' -1 day' ) );
										$next_date = date( 'Y-m-d', strtotime( $date . ' +1 day' ) );
										$user_data_1_per_day_keys = array_keys( $user_data_1_per_day );

										$roll_up_day = -1 === $user_data_1_per_day_keys[0];

										$tr_classes = 'tmsht_ts_report_table_tr ';
										if ( $is_today ) {
											$tr_classes .= ' tmsht_ts_report_table_tr_today_top';
										}
										if ( $is_today && count( $ts_data[ $date ] ) == 1 ) {
											$tr_classes .= ' tmsht_ts_report_table_tr_today_bottom';
										}
										if ( ! $is_today && date( 'Y-m-d' ) !== $prev_date ) {
											$tr_classes .= ' tmsht_ts_report_table_tr_separate_top';
										}
										if ( ! $is_today && date( 'Y-m-d' ) !== $next_date && 1 === count( $ts_data[ $date ] ) ) {
											$tr_classes .= ' tmsht_ts_report_table_tr_separate_bottom';
										}
										if ( $roll_up_day ) {
											$tr_classes .= ' tmsht_ts_report_table_tr_roll_up';
										}

										$merge_td = ( ! $roll_up_day ) ? sprintf( 'rowspan="%d"', count( $data_per_day ) ) : sprintf( 'colspan="%d"', 2 );

										$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
										if ( $is_today ) {
											$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_today';
										}
										if ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) {
											$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
										}

										$td_readonly = ( date( 'Y-m-d', strtotime( $date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] );
										?>
										<tr class="<?php echo esc_html( $tr_classes ); ?>">
											<?php if ( $pre_date != $date ) { ?>
												<td class="<?php echo esc_html( $tmsht_td_dateline_classes ); ?>" <?php echo wp_kses_post( $merge_td ); ?>>
													<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
													<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $date ) ) ); ?></div>
												</td>
												<?php
												$pre_date = $date;
											}
											if ( $roll_up_day ) {
												?>
												<td class="tmsht_ts_report_table_td_roll_up" colspan="<?php echo esc_attr( $tmsht_options['ts_timeline_to'] ); ?>">
													(<?php esc_html_e( 'No data to view', 'timesheet' ); ?>)
												</td>
												<?php
											} else {
												foreach ( $user_data_1_per_day as $user_id => $user_data_1 ) {
													?>
													<td class="tmsht_ts_report_table_td_user">
														<strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong>
													</td>
													<?php
													for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value++ ) {
														tmsht_report_table_single_td( $is_today, $time_value, $date, $td_readonly, $user_data_1 );
													}
												}
											}
											?>
										</tr>
										<?php
										end( $user_data_2_per_day );
										$tmsht_last_user_id = key( $user_data_2_per_day );
										foreach ( $user_data_2_per_day as $user_id => $user_data_2 ) {
											$tr_classes = 'tmsht_ts_report_table_tr';
											if ( $is_today && $tmsht_last_user_id == $user_id ) {
												$tr_classes .= ' tmsht_ts_report_table_tr_today_bottom';
											}
											if ( ! $is_today && $tmsht_last_user_id == $user_id && ! array_key_exists( $next_date, $ts_data ) ) {
												$tr_classes .= ' tmsht_ts_report_table_tr_separate_bottom';
											}
											?>
											<tr class="<?php echo esc_html( $tr_classes ); ?>">
												<td class="tmsht_ts_report_table_td_user">
													<strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong>
												</td>
												<?php
												for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value++ ) {
													tmsht_report_table_single_td( $is_today, $time_value, $date, $td_readonly, $user_data_2 );
												}
												?>
											</tr>
											<?php
										}
									}
								} else if ( 'user' == $ts_report_filters['group_by'] ) {
									end( $ts_data );
									$last_user_id = key( $ts_data );
									$pre_user_id = -1;
									foreach ( $ts_data as $user_id => $user_data ) {
										$user_data_1_per_day = array();
										$user_data_2_per_day = array();
										$i = 0;

										foreach ( $user_data as $date => $user_data_per_day ) {
											if ( 0 === $i ) {
												$user_data_1_per_day[ $date ] = $user_data_per_day;
											} else {
												$user_data_2_per_day[ $date ] = $user_data_per_day;
											}
											$i++;
										}

										$roll_up_day = 0 === count( $user_data_1_per_day );

										$tr_classes = 'tmsht_ts_report_table_tr tmsht_ts_report_table_tr_separate_top';
										if ( 0 === count( $user_data ) ) {
											$tr_classes .= ' tmsht_ts_report_table_tr_separate_bottom';
										}
										if ( $roll_up_day ) {
											$tr_classes .= ' tmsht_ts_report_table_tr_roll_up';
										}

										$merge_td = ( ! $roll_up_day ) ? sprintf( 'rowspan="%d"', count( $user_data ) ) : sprintf( 'colspan="%d"', 2 );
										?>
										<tr class="<?php echo esc_html( $tr_classes ); ?>">
											<?php if ( $pre_user_id != $user_id ) { ?>
												<td class="tmsht_ts_report_table_td_user" <?php echo wp_kses_post( $merge_td ); ?>>
													<strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong>
												</td>
												<?php
												$pre_user_id = $user_id;
											}
											if ( $roll_up_day ) {
												?>
												<td class="tmsht_ts_report_table_td_roll_up" colspan="<?php echo esc_attr( $tmsht_options['ts_timeline_to'] ); ?>">(<?php esc_html_e( 'No data to view', 'timesheet' ); ?>)</td>
												<?php
											} else {
												foreach ( $user_data_1_per_day as $date => $user_data_1 ) {
													$is_today = ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) );
													$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
													if ( $is_today ) {
														$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_today tmsht_ts_report_table_td_today';
													}
													if ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) {
														$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
													}
													$td_readonly = ( date( 'Y-m-d', strtotime( $date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] );
													?>
													<td class="<?php echo esc_html( $tmsht_td_dateline_classes ); ?>">
														<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
														<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $date ) ) ); ?></div>
													</td>
													<?php
												}
												for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value++ ) {
													tmsht_report_table_single_td( $is_today, $time_value, $date, $td_readonly, $user_data_1 );
												}
											}
											?>
										</tr>
										<?php
										end( $user_data_2_per_day );
										$last_date = key( $user_data_2_per_day );
										foreach ( $user_data_2_per_day as $date => $user_data_2 ) {

											$tmsht_tr_dateline_classes = 'tmsht_ts_report_table_tr';
											if ( $date == $last_date && $user_id == $last_user_id ) {
												$tmsht_tr_dateline_classes .= ' tmsht_ts_report_table_tr_separate_bottom';
											}

											$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
											if ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) ) {
												$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_today  tmsht_ts_report_table_td_today';
											}
											if ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) {
												$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
											}
											$td_readonly = ( date( 'Y-m-d', strtotime( $date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] );
											?>
											<tr class="<?php echo esc_html( $tmsht_tr_dateline_classes ); ?>">
												<td class="<?php echo esc_html( $tmsht_td_dateline_classes ); ?>">
													<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
													<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $date ) ) ); ?></div>
												</td>
												<?php
												for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value++ ) {
													$is_today = ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) );
													tmsht_report_table_single_td( $is_today, $time_value, $date, $td_readonly, $user_data_2 );
												}
												?>
											</tr>
											<?php
										}
									}
								}
							} else {
								if ( 'date' == $ts_report_filters['group_by'] ) {
									foreach ( $selected_users as $user_id ) {
										?>
										<tr class="tmsht_ts_report_table_tr">
											<td class="tmsht_ts_report_table_td_user">
												<strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong>
											</td>
											<?php
											foreach ( $ts_data as $ts_key => $ts_value ) {
												$td_readonly = ( 0 === $tmsht_options['edit_past_days'] && $ts_key < date( 'Y-m-d' ) );
												$td_timeline_classes = 'tmsht_ts_report_table_td_time tmsht_ts_report_table_td_time_' . $ts_key;
												if ( date( 'Y-m-d' ) === $ts_key ) {
													$td_timeline_classes .= ' tmsht_ts_report_table_td_today';
												}
												if ( in_array( strtolower( date( 'D', strtotime( $ts_key ) ) ), $tmsht_options['weekends'] ) ) {
													$td_timeline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
												}
												?>
												<td class="<?php echo esc_html( $td_timeline_classes ); ?>" data-td-index="<?php echo esc_attr( $ts_key ); ?>">
													<div class="tmsht_ts_report_table_td_helper tmsht_ts_report_table_td_helper_<?php echo esc_attr( $ts_key ); ?>"></div>
													<div class="tmsht_ts_report_table_td_fill_group">
														<?php
														if ( in_array( $user_id, $ts_value ) ) {
															?>
															<div class="tmsht_ts_report_table_td_fill_full" style="background-color: <?php echo esc_html( $tmsht_legends[ $ts_report_filters['legend'] ]['color'] ); ?>;" title="<?php echo esc_html( $tmsht_legends[ $ts_report_filters['legend'] ]['name'] ); ?>"></div>
															<?php
														}
														?>
													</div>
													<?php
													if ( $td_readonly ) {
														?>
														<div class="tmsht_ts_report_table_td_readonly_fill"></div>
														<?php
													}
													?>
												</td>
												<?php
											}
											?>
										</tr>
										<?php
									}
								} else {
									foreach ( $date_period as $date ) {
										$date_formated = date( 'Y-m-d', strtotime( $date ) );

										$is_today = date( 'Y-m-d' ) === $date_formated;
										$td_readonly = ( 0 === $tmsht_options['edit_past_days'] && date( 'Y-m-d' ) > $date_formated );

										$tr_classes = 'tmsht_ts_report_table_tr';
										if ( $is_today ) {
											$tr_classes .= ' tmsht_ts_report_table_highlight_today';
										}

										$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
										if ( $is_today ) {
											$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_td_today';
										}
										if ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) {
											$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
										}
										?>

										<tr class="<?php echo esc_html( $tr_classes ); ?>">
											<td class="<?php echo esc_html( $tmsht_td_dateline_classes ); ?>">
												<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
												<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $date ) ) ); ?></div>
											</td>
											<?php
											foreach ( $ts_data as $ts_key => $ts_value ) {

												$td_timeline_classes = 'tmsht_ts_report_table_td_time tmsht_ts_report_table_td_time_' . $ts_key;
												if ( $is_today ) {
													$td_timeline_classes .= ' tmsht_ts_report_table_td_today';
												}
												?>
												<td class="<?php echo esc_html( $td_timeline_classes ); ?>" data-td-index="<?php echo esc_attr( $ts_key ); ?>">
													<div class="tmsht_ts_report_table_td_helper tmsht_ts_report_table_td_helper_<?php echo esc_attr( $ts_key ); ?>"></div>
													<div class="tmsht_ts_report_table_td_fill_group">
														<?php
														if ( in_array( $date_formated, $ts_value ) ) {
															?>
															<div class="tmsht_ts_report_table_td_fill_full" style="background-color: <?php echo esc_html( $tmsht_legends[ $ts_report_filters['legend'] ]['color'] ); ?>;" title="<?php echo esc_html( $tmsht_legends[ $ts_report_filters['legend'] ]['name'] ); ?>"></div>
															<?php
														}
														?>
													</div>
													<?php
													if ( $td_readonly ) {
														?>
														<div class="tmsht_ts_report_table_td_readonly_fill"></div>
														<?php
													}
													?>
												</td>
												<?php
											}
											?>
										</tr>
										<?php
									}
								}
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
								<?php if ( 'hourly' == $ts_report_filters['view'] ) { ?>
									<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
									<?php for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value++ ) { ?>
										<td class="tmsht_ts_report_table_td_timeline"><div class="tmsht_ts_report_time_display"><?php echo esc_html( $time_value > 9 ? $time_value : '&nbsp;' . $time_value ); ?></div></td>
										<?php
									}
								} else {
									if ( 'date' == $ts_report_filters['group_by'] ) {
										foreach ( $ts_data as $ts_key => $ts_value ) {
											$td_timeline_classes = 'tmsht_ts_report_table_td_dateline';
											if ( date( 'Y-m-d' ) === $ts_key ) {
												$td_timeline_classes .= ' tmsht_ts_report_table_td_today tmsht_ts_report_table_highlight_today';
											}
											if ( in_array( strtolower( date( 'D', strtotime( $ts_key ) ) ), $tmsht_options['weekends'] ) ) {
												$td_timeline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
											}
											?>
											<td class="<?php echo esc_html( $td_timeline_classes ); ?>">
												<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $ts_key ) ) ); ?></div>
												<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $ts_key ) ) ); ?></div>
											</td>
											<?php
										}
									} else {
										foreach ( $selected_users as $user_id ) {
											?>
											<td class="tmsht_ts_report_table_td_dateline"><strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong></td>
											<?php
										}
									}
								}
								?>
							</tr>
						</tfoot>
					</table>
				<?php } ?>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tmsht_report_table_single_td' ) ) {
	/**
	 * Display single td
	 *
	 * @param bool   $is_today    Flag for today.
	 * @param int    $time_value    Hours value.
	 * @param string $date        Date string.
	 * @param bool   $td_readonly Flag for readonly.
	 * @param array  $user_data   User data.
	 */
	function tmsht_report_table_single_td( $is_today, $time_value, $date, $td_readonly, $user_data ) {
		global $tmsht_legends;

		$td_timeline_classes = 'tmsht_ts_report_table_td_time tmsht_ts_report_table_td_time_' . $time_value;
		if ( $is_today ) {
			$td_timeline_classes .= ' tmsht_ts_report_table_td_today';
		}
		?>
		<td class="<?php echo esc_html( $td_timeline_classes ); ?>" data-td-index="<?php echo esc_attr( $time_value ); ?>" data-td-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" data-td-time="<?php printf( '%02d:00', esc_attr( $time_value ) ); ?>">
			<div class="tmsht_ts_report_table_td_helper tmsht_ts_report_table_td_helper_<?php echo esc_attr( $time_value ); ?>"></div>
			<div class="tmsht_ts_report_table_td_fill_group">
				<?php
				$search_date = date( 'Y-m-d', strtotime( $date ) );
				$td_datetime_from = strtotime( sprintf( '%s %02d:00:00', $search_date, $time_value ) );
				$td_datetime_to = strtotime( sprintf( '%s %02d:59:00', $search_date, $time_value ) );

				$time_legend_array = array();
				$legend_array      = array();

				foreach ( $user_data as $user_data_key => $data ) {

					if ( $data ) {
						if ( strtotime( $data['time_from'] ) > $td_datetime_to && strtotime( $data['time_to'] ) > $td_datetime_from ) {
							break;
						}

						for ( $time_minutes = 0; $time_minutes < 60; $time_minutes += 5 ) {
							$td_datetime = strtotime( sprintf( '%s %02d:%02d:00', $search_date, $time_value, $time_minutes ) );

							if ( strtotime( $data['time_from'] ) <= $td_datetime && strtotime( $data['time_to'] ) > $td_datetime ) {

								$time_to_adjustment = ( date( 'i', strtotime( $data['time_to'] ) ) == 59 ) ? '24:00' : date( 'H:i', strtotime( $data['time_to'] ) );

								$time_legend_array[ $time_minutes ]['td_legend_id'] = $data['legend_id'];
								$legend_array['td_legend_id'][] = $data['legend_id'];

								$time_legend_array[ $time_minutes ]['td_title'] = sprintf( '%s (%s - %s)', $tmsht_legends[ $data['legend_id'] ]['name'], date( 'H:i', strtotime( $data['time_from'] ) ), $time_to_adjustment );
								$legend_array['td_title'][] = $time_legend_array[ $time_minutes ]['td_title'];

							}
						}
						if ( strtotime( $data['time_to'] ) < $td_datetime_to ) {
							unset( $user_data[ $user_data_key ] );
						}
					}
				}

				if ( empty( $time_legend_array ) ) {
					/* Nothing */
				} elseif ( 12 == count( $legend_array['td_legend_id'] ) && 1 == count( array_unique( $legend_array['td_legend_id'] ) ) ) {
					?>
					<div class="tmsht_ts_report_table_td_fill_full" style="background-color: <?php echo sanitize_hex_color( $tmsht_legends[ $legend_array['td_legend_id'][0] ]['color'] ); ?>;" data-fill-time-from="<?php printf( '%02d:%02d', esc_attr( $time_value ), esc_attr( $time_minutes ) ); ?>" data-fill-time-to="<?php printf( '%02d:%02d', esc_attr( $time_minutes < 55 ? $time_value : $time_value + 1 ), esc_attr( $time_minutes < 55 ? $time_minutes + 5 : 0 ) ); ?>" data-legend-id="<?php echo esc_attr( $legend_array['td_legend_id'][0] ); ?>" title="<?php echo esc_attr( $legend_array['td_title'][0] ); ?>"></div>
					<?php
				} else {
					for ( $time_minutes = 0; $time_minutes < 60; $time_minutes += 5 ) {
						if ( ! isset( $time_legend_array[ $time_minutes ] ) ) {
							$time_legend_array[ $time_minutes ] = array(
								'td_legend_id' => -1,
								'td_title' => '',
							);
						}
						?>
						<div class="tmsht_ts_report_table_td_fill" style="background-color: <?php echo sanitize_hex_color( $tmsht_legends[ $time_legend_array[ $time_minutes ]['td_legend_id'] ]['color'] ); ?>;" data-fill-time-from="<?php printf( '%02d:%02d', esc_attr( $time_value ), esc_attr( $time_minutes ) ); ?>" data-fill-time-to="<?php printf( '%02d:%02d', esc_attr( $time_minutes < 55 ? $time_value : $time_value + 1 ), esc_attr( $time_minutes < 55 ? $time_minutes + 5 : 0 ) ); ?>" data-legend-id="<?php echo esc_attr( $time_legend_array[ $time_minutes ]['td_legend_id'] ); ?>" title="<?php echo esc_attr( $time_legend_array[ $time_minutes ]['td_title'] ); ?>"></div>
						<?php
					}
				}
				?>
			</div>
			<?php if ( $td_readonly ) { ?>
				<div class="tmsht_ts_report_table_td_readonly_fill"></div>
			<?php } ?>
		</td>
		<?php
	}
}

if ( ! function_exists( 'tmsht_report_user_list_display' ) ) {
	/**
	 * Display user list on report page
	 *
	 * @param array $tmsht_users    User array.
	 * @param array $selected_users Selected users.
	 */
	function tmsht_report_user_list_display( $tmsht_users, $selected_users ) {
		?>
		<div class="tmsht_ts_report_user_list_wrap">
			<div class="tmsht_ts_report_user_list">
				<input class="tmsht_ts_report_search_user hide-if-no-js" type="text" placeholder="<?php esc_html_e( 'Search user', 'timesheet' ); ?>">
				<noscript class="tmsht_ts_report_user_list_container_noscript">
					<div class="tmsht_ts_report_user_list_container">
						<?php if ( count( $tmsht_users ) > 0 ) { ?>
							<label class="tmsht_ts_report_user_label hide-if-no-js"><input class="tmsht_ts_report_user_checkbox_all" type="checkbox" value="-1" <?php checked( count( $tmsht_users ), count( $selected_users ) ); ?>><strong><?php echo esc_html_x( 'All users', 'All users', 'timesheet' ); ?></strong></label>
							<div class="tmsht_ts_report_user_block">
								<ul class="tmsht_ts_report_user_select">
									<?php foreach ( $tmsht_users as $user_id => $user_login ) { ?>
										<li class="tmsht_ts_report_user" data-username="<?php echo esc_attr( $user_login ); ?>"><label class="tmsht_ts_report_user_label"><input id="tmsht_ts_report_user_id_<?php echo esc_attr( $user_id ); ?>" class="tmsht_ts_report_user_checkbox" type="checkbox" name="tmsht_ts_report_user[]" value="<?php echo esc_attr( $user_id ); ?>" 
											<?php
											if ( in_array( $user_id, $selected_users ) ) {
												echo 'checked="checked"';
											}
											?>
										><?php echo esc_attr( $user_login ); ?></label></li>
									<?php } ?>
									<li class="tmsht_ts_report_user_search_results hidden"><?php echo esc_html_x( 'No results', 'Search user', 'timesheet' ); ?></li>
								</ul>
								<div class="tmsht_clearfix"></div>
							</div>
						<?php } else { ?>
							<div class="tmsht_ts_report_user_block">
								<ul class="tmsht_ts_report_user_select">
									<li class="tmsht_ts_report_no_users"><?php echo esc_html_x( 'No users to select', 'Search user', 'timesheet' ); ?></li>
								</ul>
								<div class="tmsht_clearfix"></div>
							</div>
						<?php } ?>
					</div>
				</noscript>
			</div>
		</div>
		<div class="tmsht_ts_report_selected_users_container hide-if-no-js">
			<?php
			foreach ( $selected_users as $selected_user_id ) {
				if ( isset( $tmsht_users[ $selected_user_id ] ) ) {
					?>
					<span id="tmsht_ts_report_user_selected_<?php echo esc_attr( $selected_user_id ); ?>" class="tmsht_ts_report_user_selected"><?php echo esc_html( $tmsht_users[ $selected_user_id ] ); ?><label class="tmsht_ts_report_user_uncheck" for="tmsht_ts_report_user_id_<?php echo esc_attr( $selected_user_id ); ?>"></label></span>
					<?php
				}
			}
			?>
			<div class="tmsht_clearfix"></div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tmsht_ajax_report_user_list_display' ) ) {
	/**
	 * Display user list on report page with ajax
	 *
	 * @param array $tmsht_users    User array.
	 * @param array $selected_users Selected users.
	 */
	function tmsht_ajax_report_user_list_display( $tmsht_users, $selected_users ) {
		?>
		<?php
		foreach ( $selected_users as $selected_user_id ) {
			if ( isset( $tmsht_users[ $selected_user_id ] ) ) {
				?>
				<span id="tmsht_ts_report_user_selected_<?php echo esc_attr( $selected_user_id ); ?>" class="tmsht_ts_report_user_selected"><?php echo esc_html( $tmsht_users[ $selected_user_id ] ); ?><label class="tmsht_ts_report_user_uncheck" for="tmsht_ts_report_user_id_<?php echo esc_attr( $selected_user_id ); ?>"></label></span>
				<?php
			}
		}
		?>
		<div class="tmsht_clearfix"></div>
		<?php
	}
}

if ( ! function_exists( 'tmsht_date_period' ) ) {
	/**
	 * Create date period
	 *
	 * @param string $date_from Date from.
	 * @param string $date_to   Date to.
	 */
	function tmsht_date_period( $date_from, $date_to ) {
		$period = array();

		$date_start = new DateTime( $date_from );
		$date_end = new DateTime( $date_to );

		while ( $date_start < $date_end ) {
			$period[] = $date_start->format( 'Y-m-d' );
			$date_start->modify( '+1 day' );
		}
		return $period;
	}
}

if ( ! function_exists( 'tmsht_replacement_content_reminder' ) ) {
	/**
	 * Replace by mask
	 *
	 * @param string $string String for replace.
	 * @param array  $params Params for replace.
	 */
	function tmsht_replacement_content_reminder( $string = '', $params = array() ) {

		if ( empty( $string ) || ! $params ) {
			return false;
		}

		$replacement = array(
			'{user_name}' => $params['user']['display_name'],
			'{list_days}' => $params['days'],
			'{ts_page}'   => sprintf( '<a href="%1$s" target="_blank">%1$s</a>', admin_url( 'admin.php?page=timesheet_ts_user' ) ),
		);

		$string = preg_replace( '|\{\{ts_page_link\}(.*)\{\/ts_page_link\}\}|', sprintf( '<a href="%1$s" target="_blank">%2$s</a>', admin_url( 'admin.php?page=timesheet_ts_user' ), '\\1' ), $string );
		$string = str_replace( array_keys( $replacement ), $replacement, $string );

		return nl2br( $string );
	}
}

if ( ! function_exists( 'tmsht_reminder_to_email' ) ) {
	/**
	 * Send email with reminder
	 */
	function tmsht_reminder_to_email() {
		global $wpdb, $tmsht_options;

		if ( ! $tmsht_options ) {
			tmsht_register_options();
		}

		$required_days_arr = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );

		/* Get users */
		$tmsht_users = tmsht_get_users( true );

		$day_of_week_start = get_option( 'start_of_week' );
		$date_from = date( 'Y-m-d', strtotime( 'next ' . $required_days_arr[ $day_of_week_start ] ) );
		$date_to = date( 'Y-m-d', strtotime( $date_from . ' +7 days' ) );

		$date_period = tmsht_date_period( $date_from, $date_to );

		foreach ( $tmsht_options['weekends'] as $weekend ) {
			$key = array_search( ucfirst( $weekend ), $required_days_arr );
			if ( is_int( $key ) ) {
				unset( $required_days_arr[ $key ] );
			}
		}

		foreach ( $tmsht_users as $user_id => $user ) {

			$blank_days = array();

			foreach ( $date_period as $date ) {

				$ts_get_data = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT `id`
							FROM `' . $wpdb->prefix . 'tmsht_ts`
							WHERE date(`time_from`) = %s
								AND `user_id` = %d',
						date( 'Y-m-d', strtotime( $date ) ),
						$user_id
					),
					ARRAY_A
				);

				if ( $ts_get_data ) {
					continue;
				}

				$tmsht_short_day_name = date( 'D', strtotime( $date ) );
				$tmsht_full_day_name = date_i18n( 'l', strtotime( $date ) );
				$tmsht_date = date_i18n( $tmsht_options['date_format'], strtotime( $date ) );

				if ( in_array( $tmsht_short_day_name, $required_days_arr ) ) {
					$blank_days[] = sprintf( '%s (%s)', $tmsht_date, $tmsht_full_day_name );
				}
			}

			if ( $blank_days ) {

				$tmsht_list_days = '<ul>';
				foreach ( $blank_days as $day ) {
					$tmsht_list_days .= sprintf( '<li>%s</li>', $day );
				}
				$tmsht_list_days .= '</ul>';

				$params = array(
					'user' => $user,
					'days' => $tmsht_list_days,
				);

				$message = tmsht_replacement_content_reminder( $tmsht_options['content_reminder']['message'], $params );

				$headers = "MIME-Version: 1.0\r\n";
				$headers .= "Content-type: text/html; charset=utf-8\r\n";

				wp_mail( $user['email'], $tmsht_options['content_reminder']['subject'], $message, $headers );
			}
		}
	}
}

if ( ! function_exists( 'tmsht_delete_user' ) ) {
	/**
	 * Delete user data if user was deleted
	 *
	 * @param int $user_id User ID.
	 */
	function tmsht_delete_user( $user_id ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'tmsht_ts',
			array( 'user_id' => $user_id )
		);
	}
}

if ( ! function_exists( 'tmsht_action_links' ) ) {
	/**
	 * Add Settings link
	 *
	 * @param array  $links Links array.
	 * @param string $file  Plugin file.
	 * @return array  $links.
	 */
	function tmsht_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin ) {
				$this_plugin = plugin_basename( __FILE__ );
			}
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=timesheet_settings">' . __( 'Settings', 'timesheet' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

if ( ! function_exists( 'tmsht_links' ) ) {
	/**
	 * Add Settings, FAQ and Support links
	 *
	 * @param array  $links Links array.
	 * @param string $file  Plugin file.
	 * @return array $links.
	 */
	function tmsht_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ! is_network_admin() ) {
				$links[]    = '<a href="admin.php?page=timesheet_settings">' . __( 'Settings', 'timesheet' ) . '</a>';
			}
				$links[]    = '<a href="https://support.bestwebsoft.com/hc/en-us/sections/202101246" target="_blank">' . __( 'FAQ', 'timesheet' ) . '</a>';
				$links[]    = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'timesheet' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'tmsht_plugin_banner' ) ) {
	/**
	 * Display banner
	 */
	function tmsht_plugin_banner() {
		global $hook_suffix, $tmsht_plugin_info;

		if ( 'plugins.php' == $hook_suffix ) {
			bws_plugin_banner_to_settings( $tmsht_plugin_info, 'tmsht_options', 'timesheet', 'admin.php?page=timesheet_settings' );
		}

		if ( isset( $_GET['page'] ) && 'timesheet_settings' == $_GET['page'] ) {
			bws_plugin_suggest_feature_banner( $tmsht_plugin_info, 'tmsht_options', 'timesheet' );
		}
	}
}

if ( ! function_exists( 'tmsht_add_weekly' ) ) {
	/**
	 * Add weekly period to cron times
	 *
	 * @param array $schedules Schedules array.
	 * @return array $schedules
	 */
	function tmsht_add_weekly( $schedules ) {
		$schedules['tmsht_weekly'] = array(
			'interval' => 604800,
			'display'  => __( 'Once Weekly', 'timesheet' ),
		);
		return $schedules;
	}
}

if ( ! function_exists( 'tmsht_unistall' ) ) {
	/**
	 * Uistall plugin, remove data and tables
	 */
	function tmsht_unistall() {
		global $wpdb;

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$all_plugins = get_plugins();

		if ( ! array_key_exists( 'timesheet-pro/timesheet-pro.php', $all_plugins ) ) {
			if ( is_multisite() ) {
				$old_blog = $wpdb->blogid;
				/* Get all blog ids */
				$blogids = $wpdb->get_col( 'SELECT `blog_id` FROM ' . $wpdb->blogs );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );

					$meta_key = '_tmsht_ts_report_filters';
					$users = get_users(
						array(
							'blog_id' => $blog_id,
							'meta_key' => $meta_key,
						)
					);

					foreach ( $users as $user ) {
						delete_user_meta( $user->ID, $meta_key );
					}

					delete_option( 'tmsht_options' );
					$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . 'tmsht_legends`, `' . $wpdb->prefix . 'tmsht_ts`;' );
				}
				switch_to_blog( $old_blog );
			} else {
				$meta_key = '_tmsht_ts_report_filters';
				$users = get_users(
					array(
						'blog_id' => get_current_blog_id(),
						'meta_key' => $meta_key,
					)
				);

				foreach ( $users as $user ) {
					delete_user_meta( $user->ID, $meta_key );
				}

				delete_option( 'tmsht_options' );
				$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . 'tmsht_legends`, `' . $wpdb->prefix . 'tmsht_ts`;' );
			}
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

if ( ! function_exists( 'tmsht_ts_user_table_update' ) ) {
	/**
	 * Update data timesheet table
	 */
	function tmsht_ts_user_table_update() {
		global $wpdb, $tmsht_options, $tmsht_plugin_info, $wp_version, $current_user;

		$message = '';
		$error   = '';

		$week_days_arr     = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$day_of_week_start = get_option( 'start_of_week' );

		$date_from = ( isset( $_GET['tmsht_ts_user_date_from'] ) && strtotime( sanitize_text_field( wp_unslash( $_GET['tmsht_ts_user_date_from'] ) ) ) ) ? sanitize_text_field( wp_unslash( $_GET['tmsht_ts_user_date_from'] ) ) : date( 'Y-m-d' );
		$date_to   = ( isset( $_GET['tmsht_ts_user_date_to'] ) && strtotime( sanitize_text_field( wp_unslash( $_GET['tmsht_ts_user_date_to'] ) ) ) ) ? sanitize_text_field( wp_unslash( $_GET['tmsht_ts_user_date_to'] ) ) : date( 'Y-m-d', strtotime( 'next ' . $week_days_arr[ $day_of_week_start ] . ' +6 days' ) );

		/* Session data for page=tmsht_ts_user */

		if ( isset( $_GET['tmsht_ts_user_date_from'] ) ) {
			$_SESSION['tmsht_ts_user_date_from'] = sanitize_text_field( wp_unslash( $_GET['tmsht_ts_user_date_from'] ) );
		}

		if ( isset( $_GET['tmsht_ts_user_date_to'] ) ) {
			$_SESSION['tmsht_ts_user_date_to'] = sanitize_text_field( wp_unslash( $_GET['tmsht_ts_user_date_to'] ) );
		}

		$date_period = tmsht_date_period( $date_from, date( 'Y-m-d', strtotime( $date_to . ' +1 day' ) ) );

		$tmsht_legends = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->prefix . 'tmsht_legends`', OBJECT_K );
		/* Convert stdClass items of array( $tmsht_legends ) to associative array */
		$tmsht_legends        = json_decode( json_encode( $tmsht_legends ), true );
		$tmsht_legends[- 1] = array(
			'name'     => __( 'Please select...', 'timesheet' ),
			'color'    => 'transparent',
			'disabled' => 0,
		);
		ksort( $tmsht_legends );

		if ( isset( $_POST['tmsht_save_ts'] ) && check_admin_referer( 'tmsht_nonce_save_ts', 'tmsht_nonce_name' ) ) {
			if ( isset( $_POST['tmsht_tr_date'] ) && is_array( $_POST['tmsht_tr_date'] ) ) {

				foreach ( $_POST['tmsht_tr_date'] as $tr_date ) {

					$tr_date = sanitize_text_field( wp_unslash( $tr_date ) );

					if ( date( 'Y-m-d', strtotime( $tr_date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] ) {
						continue;
					}

					$query_results = $wpdb->query(
						$wpdb->prepare(
							'DELETE FROM `' . $wpdb->prefix . 'tmsht_ts` 
								WHERE `user_id` = %d AND date(`time_from`) = %s',
							$current_user->ID,
							$tr_date
						)
					);

					if ( false === $query_results ) {
						$error = __( 'Data has not been saved.', 'timesheet' );
						break;
					}

					if ( isset( $_POST['tmsht_to_db'][ $tr_date ] ) && is_array( $_POST['tmsht_to_db'][ $tr_date ] ) ) {
						foreach ( $_POST['tmsht_to_db'][ $tr_date ] as $ts_interval ) {
							$ts_interval = sanitize_text_field( wp_unslash( $ts_interval ) );
							$ts_interval_arr  = explode( '@', $ts_interval );
							$ts_interval_from = $ts_interval_arr[0];
							$ts_interval_to   = $ts_interval_arr[1];
							$legend_id        = $ts_interval_arr[2];

							if ( strtotime( $ts_interval_from ) && strtotime( $ts_interval_to ) && array_key_exists( $legend_id, $tmsht_legends ) ) {

								$query_results = $wpdb->insert(
									$wpdb->prefix . 'tmsht_ts',
									array(
										'user_id'   => $current_user->ID,
										'time_from' => $ts_interval_from,
										'time_to'   => $ts_interval_to,
										'legend_id' => $legend_id,
									),
									array( '%d', '%s', '%s', '%d' )
								);

								if ( false === $query_results ) {
									$error = __( 'Data has not been saved.', 'timesheet' );
									break;
								}
							}
						}
					}
				}
				if ( '' == $error ) {
					$message = __( 'Data has been saved.', 'timesheet' );
				}
			} else {
				$error = __( 'Data has not been saved, because there was no change.', 'timesheet' );
			}
		}

		$ts_data = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT `time_from`, `time_to`, `legend_id`
				FROM `' . $wpdb->prefix . 'tmsht_ts`
				WHERE date(`time_from`) >= %s
				AND date(`time_to`) <= %s
				AND `user_id` = %d',
				$date_from,
				$date_to,
				$current_user->ID
			),
			ARRAY_A
		);

		foreach ( $ts_data as $key => $value ) {
			$new_key               = date( 'Y-m-d', strtotime( $value['time_from'] ) );
			$ts_data[ $new_key ][] = $value;
			unset( $ts_data[ $key ] );
		}

		?>
		<?php
		$tmsht_tr_index = 0;
		$tmsht_td_index = 0;
		foreach ( $date_period as $date ) {
			$tr_classes                = ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) ) ? 'tmsht_ts_user_table_tr tmsht_ts_user_table_tr_today' : 'tmsht_ts_user_table_tr';
			$tmsht_td_dateline_classes = ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) ) ? ' tmsht_ts_user_table_highlight_today' : '';
			if ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) {
				$tmsht_td_dateline_classes .= ' tmsht_ts_user_table_highlight_weekdays';
			}

			$td_readonly = ( date( 'Y-m-d', strtotime( $date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] );
			?>
			<tr class="<?php echo esc_html( $tr_classes ); ?>" data-tr-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( $date ) ) ); ?>">
				<td class="tmsht_ts_user_table_td_dateline">
					<div class="tmsht_ts_user_table_td_dateline_group<?php echo esc_html( $tmsht_td_dateline_classes ); ?>"
						 data-datline-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( $date ) ) ); ?>">
						<div class="tmsht_ts_user_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
						<div class="tmsht_ts_user_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $date ) ) ); ?></div>
					</div>
					<input class="tmsht_tr_date" type="hidden" name="tmsht_tr_date[]" value="<?php echo esc_html( date( 'Y-m-d', strtotime( $date ) ) ); ?>" disabled="disabled" />
				</td>
				<?php
				for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value ++ ) {
					$td_timeline_classes = 'tmsht_ts_user_table_td_time';

					if ( $td_readonly ) {
						$td_timeline_classes .= ' tmsht_ts_user_table_td_readonly';
						$tmsht_td_index      = - 1;
					}

					if ( 0 === $tmsht_td_index ) {
						$td_timeline_classes .= ' tmsht_ts_user_table_td_highlighted';
					}
					?>
					<td class="<?php echo esc_html( $td_timeline_classes ); ?>" data-tr-index="<?php echo esc_attr( $tmsht_tr_index ); ?>"
						data-td-index="<?php echo esc_attr( $time_value ); ?>"
						data-td-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( $date ) ) ); ?>"
						data-td-time-from="<?php printf( '%02d:00', esc_attr( $time_value ) ); ?>"
						data-td-time-to="<?php printf( '%02d:00', esc_attr( $time_value + 1 ) ); ?>">
						<div class="tmsht_ts_user_table_td_fill_group">
							<?php
							for ( $time_minutes = 0; $time_minutes < 60; $time_minutes += 5 ) {

								$search_date  = date( 'Y-m-d', strtotime( $date ) );
								$td_datetime  = strtotime( sprintf( '%s %02d:%02d:00', $search_date, $time_value, $time_minutes ) );
								$td_legend_id = - 1;
								$td_title     = '';

								if ( array_key_exists( $search_date, $ts_data ) ) {
									foreach ( $ts_data[ $search_date ] as $data ) {

										if ( strtotime( $data['time_from'] ) <= $td_datetime && strtotime( $data['time_to'] ) > $td_datetime ) {
											$td_legend_id       = $data['legend_id'];
											$time_to_adjustment = ( date( 'i', strtotime( $data['time_to'] ) ) == 59 ) ? '24:00' : date( 'H:i', strtotime( $data['time_to'] ) );
											$td_title           = sprintf( '%s (%s - %s)', $tmsht_legends[ $td_legend_id ]['name'], date( 'H:i', strtotime( $data['time_from'] ) ), $time_to_adjustment );
										}
									}
								}
								?>
								<div class="tmsht_ts_user_table_td_fill"
								 style="background-color: <?php echo sanitize_hex_color( $tmsht_legends[ $td_legend_id ]['color'] ); ?>;"
								 data-fill-time-from="<?php printf( '%02d:%02d', esc_attr( $time_value ), esc_attr( $time_minutes ) ); ?>"
								 data-fill-time-to="<?php printf( '%02d:%02d', esc_attr( $time_minutes < 55 ? $time_value : $time_value + 1 ), esc_attr( $time_minutes < 55 ? $time_minutes + 5 : 0 ) ); ?>"
								 data-legend-id="<?php echo esc_attr( $td_legend_id ); ?>"
								 title="<?php echo esc_html( $td_title ); ?>"></div>
							<?php } ?>
						</div>
						<?php if ( $td_readonly ) { ?>
							<div class="tmsht_ts_user_table_td_readonly_fill"></div>
						<?php } ?>
					</td>
					<?php
					$tmsht_td_index ++;
				}
				?>
			</tr>
			<?php
			$tmsht_tr_index ++;
		}
		?>
		<?php wp_die(); ?>
		<?php
	}
}

if ( ! function_exists( 'tmsht_ts_user_advanced_container_update' ) ) {
	/**
	 * Update data timesheet advanced_container
	 */
	function tmsht_ts_user_advanced_container_update() {
		global $wpdb, $tmsht_options, $current_user;

		$message = '';
		$error   = '';

		$week_days_arr     = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$day_of_week_start = get_option( 'start_of_week' );

		$date_from = ( isset( $_GET['tmsht_ts_user_date_from'] ) && strtotime( sanitize_text_field( wp_unslash( $_GET['tmsht_ts_user_date_from'] ) ) ) ) ? sanitize_text_field( wp_unslash( $_GET['tmsht_ts_user_date_from'] ) ) : date( 'Y-m-d' );
		$date_to   = ( isset( $_GET['tmsht_ts_user_date_to'] ) && strtotime( sanitize_text_field( wp_unslash( $_GET['tmsht_ts_user_date_to'] ) ) ) ) ? sanitize_text_field( wp_unslash( $_GET['tmsht_ts_user_date_to'] ) ) : date( 'Y-m-d', strtotime( 'next ' . $week_days_arr[ $day_of_week_start ] . ' +6 days' ) );

		$date_period = tmsht_date_period( $date_from, date( 'Y-m-d', strtotime( $date_to . ' +1 day' ) ) );

		$tmsht_legends = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->prefix . 'tmsht_legends`', OBJECT_K );
		/* Convert stdClass items of array( $tmsht_legends ) to associative array */
		$tmsht_legends        = json_decode( json_encode( $tmsht_legends ), true );
		$tmsht_legends[- 1] = array(
			'name'     => __( 'Please select...', 'timesheet' ),
			'color'    => 'transparent',
			'disabled' => 0,
		);
		ksort( $tmsht_legends );

		if ( isset( $_POST['tmsht_save_ts'] ) && check_admin_referer( 'tmsht_nonce_save_ts', 'tmsht_nonce_name' ) ) {
			if ( isset( $_POST['tmsht_tr_date'] ) && is_array( $_POST['tmsht_tr_date'] ) ) {

				foreach ( $_POST['tmsht_tr_date'] as $tr_date ) {
					$tr_date = sanitize_text_field( wp_unslash( $tr_date ) );

					if ( date( 'Y-m-d', strtotime( $tr_date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] ) {
						continue;
					}

					$query_results = $wpdb->query(
						$wpdb->prepare(
							'DELETE FROM `' . $wpdb->prefix . 'tmsht_ts`
								WHERE `user_id` = %d
									AND date(`time_from`) = %s',
							$current_user->ID,
							$tr_date
						)
					);

					if ( false === $query_results ) {
						$error = __( 'Data has not been saved.', 'timesheet' );
						break;
					}

					if ( isset( $_POST['tmsht_to_db'][ $tr_date ] ) && is_array( $_POST['tmsht_to_db'][ $tr_date ] ) ) {
						foreach ( $_POST['tmsht_to_db'][ $tr_date ] as $ts_interval ) {
							$ts_interval = sanitize_text_field( wp_unslash( $ts_interval ) );
							$ts_interval_arr  = explode( '@', $ts_interval );
							$ts_interval_from = $ts_interval_arr[0];
							$ts_interval_to   = $ts_interval_arr[1];
							$legend_id        = $ts_interval_arr[2];

							if ( strtotime( $ts_interval_from ) && strtotime( $ts_interval_to ) && array_key_exists( $legend_id, $tmsht_legends ) ) {

								$query_results = $wpdb->insert(
									$wpdb->prefix . 'tmsht_ts',
									array(
										'user_id'   => $current_user->ID,
										'time_from' => $ts_interval_from,
										'time_to'   => $ts_interval_to,
										'legend_id' => $legend_id,
									),
									array( '%d', '%s', '%s', '%d' )
								);

								if ( false === $query_results ) {
									$error = __( 'Data has not been saved.', 'timesheet' );
									break;
								}
							}
						}
					}
				}
				if ( '' == $error ) {
					$message = __( 'Data has been saved.', 'timesheet' );
				}
			}
		}

		$ts_data = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT `time_from`, `time_to`, `legend_id`
				FROM `' . $wpdb->prefix . 'tmsht_ts`
				WHERE date(`time_from`) >= %s
				AND date(`time_to`) <= %s
				AND `user_id` = %d',
				$date_from,
				$date_to,
				$current_user->ID
			),
			ARRAY_A
		);

		foreach ( $ts_data as $key => $value ) {
			$new_key               = date( 'Y-m-d', strtotime( $value['time_from'] ) );
			$ts_data[ $new_key ][] = $value;
			unset( $ts_data[ $key ] );
		}
		?>
		<?php
		foreach ( $tmsht_legends as $ts_legend_id => $ts_legend ) {
			if ( $ts_legend_id < 0 ) {
				continue;
			}
			?>
			<div class="tmsht_ts_user_advanced_box tmsht_maybe_hidden hidden"
				 data-box-id="<?php echo esc_attr( $ts_legend_id ); ?>">
				<div class="tmsht_ts_user_advanced_box_title"
					 style="background-color: <?php echo sanitize_hex_color( $ts_legend['color'] ); ?>"><?php echo esc_html( $ts_legend['name'] ); ?></div>
				<div class="tmsht_ts_user_advanced_box_content">
					<?php foreach ( $date_period as $date ) { ?>
						<div class="tmsht_ts_user_advanced_box_details tmsht_maybe_hidden hidden"
							data-details-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( $date ) ) ); ?>">
							<div class="tmsht_ts_user_advanced_box_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
							<div class="tmsht_ts_user_advanced_box_interval_wrap"></div>
						</div>
					<?php } ?>
				</div>
			</div>
		<?php } ?>
		<?php wp_die(); ?>
		<?php
	}
}

if ( ! function_exists( 'tmsht_ts_user_report_table_update' ) ) {
	/**
	 * Update ajax report table
	 */
	function tmsht_ts_user_report_table_update() {
		global $wpdb, $tmsht_options, $current_user, $tmsht_legends;
		$week_days_arr     = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$day_of_week_start = get_option( 'start_of_week' );

		$date_preset_units_arr = array(
			'week'  => __( 'Week', 'timesheet' ),
			'month' => __( 'Month', 'timesheet' ),
		);

		$ts_report_group_by_arr = array(
			'date' => _x( 'Date', 'Group by', 'timesheet' ),
			'user' => _x( 'User', 'Group by', 'timesheet' ),
		);

		$ts_report_view = array(
			'hourly' => __( 'Hourly', 'timesheet' ),
			'daily'  => __( 'Daily', 'timesheet' ),
		);

		/* Get legends */
		$tmsht_legends = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->prefix . 'tmsht_legends`', OBJECT_K );
		/* Convert stdClass items of array( $tmsht_legends ) to associative array */
		$tmsht_legends        = json_decode( json_encode( $tmsht_legends ), true );
		$tmsht_legends[- 1] = array(
			'name'     => __( 'Blank', 'timesheet' ),
			'color'    => 'transparent',
			'disabled' => 1,
		);
		$tmsht_legends[- 2] = array(
			'name'     => __( 'All statuses', 'timesheet' ),
			'color'    => '#444444',
			'disabled' => 0,
		);
		ksort( $tmsht_legends );

		/* Get users */
		$tmsht_users = tmsht_get_users();

		/* Get user meta */
		$ts_report_filters = get_user_meta( $current_user->ID, '_tmsht_ts_report_filters', true );

		if ( empty( $ts_report_filters ) ) {
			$ts_report_filters = array(
				'date'     => array(
					'type'   => 'period',
					'preset' => array(),
				),
				'group_by' => 'date',
				'view'     => 'hourly',
				'legend'   => - 2,
				'users'    => array_keys( $tmsht_users ),
			);

			add_user_meta( $current_user->ID, '_tmsht_ts_report_filters', $ts_report_filters );
		}

		/* Apply filters */
		if ( isset( $_POST['tmsht_generate_ts_report'] ) && isset( $_POST['tmsht_apply_ts_report_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmsht_apply_ts_report_field'] ) ), 'tmsht_apply_ts_report_action' ) ) {
			if (
				( isset( $_POST['tmsht_date_filter_type'] ) && 'preset' == sanitize_text_field( wp_unslash( $_POST['tmsht_date_filter_type'] ) ) ) &&
				( isset( $_POST['tmsht_date_preset_unit'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_date_preset_unit'] ) ), $date_preset_units_arr ) ) &&
				isset( $_POST['tmsht_date_preset_quantity'] )
			) {
				$ts_report_filters['date'] = array(
					'type'   => 'preset',
					'preset' => array(
						'quantity' => intval( $_POST['tmsht_date_preset_quantity'] ),
						'unit'     => sanitize_text_field( wp_unslash( $_POST['tmsht_date_preset_unit'] ) ),
					),
				);
			} else {
				$ts_report_filters['date'] = array(
					'type'   => 'period',
					'preset' => array(),
				);
			}

			$ts_report_filters['group_by'] = isset( $_POST['tmsht_ts_report_group_by'] ) && ( array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_group_by'] ) ), $ts_report_group_by_arr ) ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_group_by'] ) ) : 'date';
			$ts_report_filters['legend']   = isset( $_POST['tmsht_ts_report_legend'] ) && ( array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_legend'] ) ), $tmsht_legends ) ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_legend'] ) ) : - 2;
			$ts_report_filters['view']     = isset( $_POST['tmsht_ts_report_view'] ) && ( - 2 != $ts_report_filters['legend'] && array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_view'] ) ), $ts_report_view ) ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_view'] ) ) : 'hourly';
			$ts_report_filters['users']    = ( isset( $_POST['tmsht_ts_report_user'] ) && is_array( $_POST['tmsht_ts_report_user'] ) ) ? array_map( 'absint', $_POST['tmsht_ts_report_user'] ) : array_keys( $tmsht_users );
			update_user_meta( $current_user->ID, '_tmsht_ts_report_filters', $ts_report_filters );
		}

		/* Report generation */
		$date_from = ( isset( $_POST['tmsht_ts_report_date_from'] ) && strtotime( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_date_from'] ) ) ) && 'period' == $ts_report_filters['date']['type'] ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_date_from'] ) ) : date( 'Y-m-d' );
		$date_to   = ( isset( $_POST['tmsht_ts_report_date_to'] ) && strtotime( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_date_to'] ) ) ) && 'period' == $ts_report_filters['date']['type'] ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_date_to'] ) ) : date( 'Y-m-d', strtotime( 'next ' . $week_days_arr[ $day_of_week_start ] . ' +6 days' ) );

		$filter_date_from = $date_from;
		$filter_date_to   = $date_to;

		/* Session data for tmsht_ts_user */
		if ( isset( $_POST['tmsht_ts_report_date_from'] ) ) {
			$_SESSION['tmsht_ts_report_date_from'] = sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_date_from'] ) );
		}
		if ( isset( $_POST['tmsht_ts_report_date_to'] ) ) {
			$_SESSION['tmsht_ts_report_date_to'] = sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_date_to'] ) );
		}
		if ( 'preset' == $ts_report_filters['date']['type'] ) {
			$date_from = date( 'Y-m-d' );
			$date_to   = date( 'Y-m-d', strtotime( '+' . $ts_report_filters['date']['preset']['quantity'] . ' ' . $ts_report_filters['date']['preset']['unit'] ) );
		}

		$date_period = tmsht_date_period( $date_from, date( 'Y-m-d', strtotime( $date_to . ' +1 day' ) ) );
		$selected_users = array();

		foreach ( $ts_report_filters['users'] as $user_id ) {
			if ( array_key_exists( $user_id, $tmsht_users ) ) {
				$selected_users[] = $user_id;
			}
		}

		$ts_data = array();

		if ( $selected_users ) {
			$ts_data_query = ( 'hourly' == $ts_report_filters['view'] ) ? 'SELECT `user_id`, `time_from`, `time_to`, `legend_id`' : "SELECT `user_id`, DATE_FORMAT( `time_from`, '%Y-%m-%d' ) AS `date`";

			$ts_data_query .= ' FROM `' . $wpdb->prefix . 'tmsht_ts` WHERE date(`time_from`) >= %s AND date(`time_to`) <= %s';
			$ts_data_query_prepare = array( $date_from, $date_to );

			if ( $ts_report_filters['legend'] > 0 ) {
				$ts_data_query .= ' AND `legend_id` = %s';
				$ts_data_query_prepare[] = $ts_report_filters['legend'];
			}

			$ts_data_query .= ' AND `user_id` IN (' . implode( ',', $selected_users ) . ')';
			if ( 'hourly' != $ts_report_filters['view'] ) {
				$ts_data_query .= ' GROUP BY `user_id`, DAY(`time_from`)';
			}
			$ts_data_query .= ' ORDER BY `user_id` ASC, `time_from` ASC';
			$ts_get_data   = $wpdb->get_results(
				$wpdb->prepare(
					$ts_data_query,
					$ts_data_query_prepare
				),
				ARRAY_A
			);

			if ( $ts_get_data ) {
				if ( 'hourly' == $ts_report_filters['view'] ) {
					if ( 'date' == $ts_report_filters['group_by'] ) {

						foreach ( $ts_get_data as $data ) {
							$key_date                                   = date( 'Y-m-d', strtotime( $data['time_from'] ) );
							$ts_data[ $key_date ][ $data['user_id'] ][] = $data;
						}

						/* need to create empty array for saving sorting - username ASC */
						$empty_users_data = array();
						foreach ( $selected_users as $user_id ) {
							$empty_users_data[ $user_id ][] = array();
						}

						foreach ( $date_period as $date ) {
							$date_formated = date( 'Y-m-d', strtotime( $date ) );

							if ( isset( $ts_data[ $date_formated ] ) ) {
								$ts_data[ $date_formated ] = tmsht_array_replace( $empty_users_data, $ts_data[ $date_formated ] );
							} else {
								$ts_data[ $date_formated ] = array(
									- 1 => array( array() ),
								);
							}
						}

						/* sort by time */
						ksort( $ts_data );
					} else if ( 'user' == $ts_report_filters['group_by'] ) {
						/* need to create empty array first for saving sorting - username ASC */
						foreach ( $selected_users as $user_id ) {
							$ts_data[ $user_id ] = array();
						}

						foreach ( $ts_get_data as $data ) {
							$key_date                                   = date( 'Y-m-d', strtotime( $data['time_from'] ) );
							$ts_data[ $data['user_id'] ][ $key_date ][] = $data;
						}

						$empty_date_data = array();
						foreach ( $date_period as $date ) {
							$date_formated                       = date( 'Y-m-d', strtotime( $date ) );
							$empty_date_data[ $date_formated ][] = array();
						}

						foreach ( $ts_data as $user_id => $data ) {
							if ( ! empty( $data ) ) {
								$ts_data[ $user_id ] = tmsht_array_replace( $empty_date_data, $ts_data[ $user_id ] );
							}
						}
					}
				} else {
					if ( 'date' == $ts_report_filters['group_by'] ) {

						foreach ( $ts_get_data as $data ) {
							$ts_data[ $data['date'] ][] = $data['user_id'];
						}

						foreach ( $date_period as $date ) {
							$date_formated = date( 'Y-m-d', strtotime( $date ) );

							$exists_data_for_users = isset( $ts_data[ $date_formated ] ) ? array_keys( $ts_data[ $date_formated ] ) : array();

							if ( ! $exists_data_for_users ) {
								$ts_data[ $date_formated ] = array( '-1' );
							}
						}

						/* sort by time */
						ksort( $ts_data );
					} else {
						/* need to create empty array first for saving sorting - username ASC */
						foreach ( $selected_users as $user_id ) {
							$ts_data[ $user_id ] = array();
						}

						foreach ( $ts_get_data as $data ) {
							$ts_data[ $data['user_id'] ][] = $data['date'];
						}
					}
				}
			}
		}
		?>
		<?php if ( ! $ts_data ) { ?>
			<strong><?php esc_html_e( 'No data to view', 'timesheet' ); ?>.</strong>
		<?php } else { ?>
			<thead>
			<tr>
				<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
				<?php if ( 'hourly' == $ts_report_filters['view'] ) { ?>
					<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
					<?php for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value ++ ) { ?>
						<td class="tmsht_ts_report_table_td_timeline">
							<div class="tmsht_ts_report_time_display"><?php echo esc_html( $time_value > 9 ? $time_value : '&nbsp;' . $time_value ); ?></div>
						</td>
						<?php
					}
				} else {
					if ( 'date' == $ts_report_filters['group_by'] ) {
						foreach ( $ts_data as $ts_key => $ts_value ) {
							$td_timeline_classes = 'tmsht_ts_report_table_td_dateline';
							if ( date( 'Y-m-d' ) === $ts_key ) {
								$td_timeline_classes .= ' tmsht_ts_report_table_td_today tmsht_ts_report_table_highlight_today';
							}
							if ( in_array( strtolower( date( 'D', strtotime( $ts_key ) ) ), $tmsht_options['weekends'] ) ) {
								$td_timeline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
							}
							?>
							<td class="<?php echo esc_html( $td_timeline_classes ); ?>">
								<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $ts_key ) ) ); ?></div>
								<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $ts_key ) ) ); ?></div>
							</td>
							<?php
						}
					} else {
						foreach ( $selected_users as $user_id ) {
							?>
							<td class="tmsht_ts_report_table_td_dateline">
								<strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong></td>
							<?php
						}
					}
				}
				?>
			</tr>
			</thead>
			<tbody>
			<?php
			if ( 'hourly' == $ts_report_filters['view'] ) {
				if ( 'date' == $ts_report_filters['group_by'] ) {
					$pre_date = '';

					foreach ( $ts_data as $date => $data_per_day ) {
						$user_data_1_per_day = array();
						$user_data_2_per_day = array();
						$i                   = 0;
						foreach ( $data_per_day as $user_id => $user_data_per_day ) {
							if ( 0 === $i ) {
								$user_data_1_per_day[ $user_id ] = $user_data_per_day;
							} else {
								$user_data_2_per_day[ $user_id ] = $user_data_per_day;
							}
							$i ++;
						}
						$is_today                 = ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) );
						$prev_date                = date( 'Y-m-d', strtotime( $date . ' -1 day' ) );
						$next_date                = date( 'Y-m-d', strtotime( $date . ' +1 day' ) );
						$user_data_1_per_day_keys = array_keys( $user_data_1_per_day );

						$roll_up_day = ( -1 === $user_data_1_per_day_keys[0] );

						$tr_classes = 'tmsht_ts_report_table_tr ';
						if ( $is_today ) {
							$tr_classes .= ' tmsht_ts_report_table_tr_today_top';
						}
						if ( $is_today && 1 === count( $ts_data[ $date ] ) ) {
							$tr_classes .= ' tmsht_ts_report_table_tr_today_bottom';
						}
						if ( ! $is_today && date( 'Y-m-d' ) !== $prev_date ) {
							$tr_classes .= ' tmsht_ts_report_table_tr_separate_top';
						}
						if ( ! $is_today && date( 'Y-m-d' ) !== $next_date && 1 === count( $ts_data[ $date ] ) ) {
							$tr_classes .= ' tmsht_ts_report_table_tr_separate_bottom';
						}
						if ( $roll_up_day ) {
							$tr_classes .= ' tmsht_ts_report_table_tr_roll_up';
						}

						$merge_td = ( ! $roll_up_day ) ? sprintf( 'rowspan="%d"', count( $data_per_day ) ) : sprintf( 'colspan="%d"', 2 );

						$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
						if ( $is_today ) {
							$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_today';
						}
						if ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) {
							$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
						}

						$td_readonly = ( date( 'Y-m-d', strtotime( $date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] );
						?>
						<tr class="<?php echo esc_html( $tr_classes ); ?>">
							<?php if ( $pre_date != $date ) { ?>
								<td class="<?php echo esc_html( $tmsht_td_dateline_classes ); ?>" <?php echo esc_html( $merge_td ); ?>>
									<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
									<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $date ) ) ); ?></div>
								</td>
								<?php
								$pre_date = $date;
							}
							if ( $roll_up_day ) {
								?>
								<td class="tmsht_ts_report_table_td_roll_up"
									 colspan="<?php echo esc_attr( $tmsht_options['ts_timeline_to'] ); ?>">
									(<?php esc_html_e( 'No data to view', 'timesheet' ); ?>)
								</td>
								<?php
							} else {
								foreach ( $user_data_1_per_day as $user_id => $user_data_1 ) {
									?>
									<td class="tmsht_ts_report_table_td_user">
										<strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong>
									</td>
									<?php
									for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value ++ ) {
										tmsht_report_table_single_td( $is_today, $time_value, $date, $td_readonly, $user_data_1 );
									}
								}
							}
							?>
						</tr>
						<?php
						end( $user_data_2_per_day );
						$tmsht_last_user_id = key( $user_data_2_per_day );
						foreach ( $user_data_2_per_day as $user_id => $user_data_2 ) {
							$tr_classes = 'tmsht_ts_report_table_tr';
							if ( $is_today && $tmsht_last_user_id == $user_id ) {
								$tr_classes .= ' tmsht_ts_report_table_tr_today_bottom';
							}
							if ( ! $is_today && $tmsht_last_user_id == $user_id && ! array_key_exists( $next_date, $ts_data ) ) {
								$tr_classes .= ' tmsht_ts_report_table_tr_separate_bottom';
							}
							?>
							<tr class="<?php echo esc_html( $tr_classes ); ?>">
								<td class="tmsht_ts_report_table_td_user">
									<strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong>
								</td>
								<?php
								for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value ++ ) {
									tmsht_report_table_single_td( $is_today, $time_value, $date, $td_readonly, $user_data_2 );
								}
								?>
							</tr>
							<?php
						}
					}
				} else if ( 'user' == $ts_report_filters['group_by'] ) {
					end( $ts_data );
					$last_user_id = key( $ts_data );
					$pre_user_id  = - 1;
					foreach ( $ts_data as $user_id => $user_data ) {
						$user_data_1_per_day = array();
						$user_data_2_per_day = array();
						$i                   = 0;

						foreach ( $user_data as $date => $user_data_per_day ) {
							if ( 0 === $i ) {
								$user_data_1_per_day[ $date ] = $user_data_per_day;
							} else {
								$user_data_2_per_day[ $date ] = $user_data_per_day;
							}
							$i ++;
						}

						$roll_up_day = ( 0 === count( $user_data_1_per_day ) );

						$tr_classes = 'tmsht_ts_report_table_tr tmsht_ts_report_table_tr_separate_top';
						if ( 0 === count( $user_data ) ) {
							$tr_classes .= ' tmsht_ts_report_table_tr_separate_bottom';
						}
						if ( $roll_up_day ) {
							$tr_classes .= ' tmsht_ts_report_table_tr_roll_up';
						}

						$merge_td = ( ! $roll_up_day ) ? sprintf( 'rowspan="%d"', count( $user_data ) ) : sprintf( 'colspan="%d"', 2 );
						?>
						<tr class="<?php echo esc_html( $tr_classes ); ?>">
							<?php if ( $pre_user_id != $user_id ) { ?>
								<td class="tmsht_ts_report_table_td_user" <?php echo esc_html( $merge_td ); ?>>
									<strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong>
								</td>
								<?php
								$pre_user_id = $user_id;
							}
							if ( $roll_up_day ) {
								?>
								<td class="tmsht_ts_report_table_td_roll_up"
									 colspan="<?php echo esc_attr( $tmsht_options['ts_timeline_to'] ); ?>">
									(<?php esc_html_e( 'No data to view', 'timesheet' ); ?>)
								</td>
								<?php
							} else {
								foreach ( $user_data_1_per_day as $date => $user_data_1 ) {
									$is_today                  = ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) );
									$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
									if ( $is_today ) {
										$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_today tmsht_ts_report_table_td_today';
									}
									if ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) {
										$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
									}
									$td_readonly = ( date( 'Y-m-d', strtotime( $date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] );
									?>
									<td class="<?php echo esc_html( $tmsht_td_dateline_classes ); ?>">
										<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
										<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $date ) ) ); ?></div>
									</td>
									<?php
								}
								for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value ++ ) {
									tmsht_report_table_single_td( $is_today, $time_value, $date, $td_readonly, $user_data_1 );
								}
							}
							?>
						</tr>
						<?php
						end( $user_data_2_per_day );
						$last_date = key( $user_data_2_per_day );
						foreach ( $user_data_2_per_day as $date => $user_data_2 ) {

							$tmsht_tr_dateline_classes = 'tmsht_ts_report_table_tr';
							if ( $date == $last_date && $user_id == $last_user_id ) {
								$tmsht_tr_dateline_classes .= ' tmsht_ts_report_table_tr_separate_bottom';
							}

							$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
							if ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) ) {
								$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_today  tmsht_ts_report_table_td_today';
							}
							if ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) {
								$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
							}
							$td_readonly = ( date( 'Y-m-d', strtotime( $date ) ) < date( 'Y-m-d' ) && 0 === $tmsht_options['edit_past_days'] );
							?>
							<tr class="<?php echo esc_html( $tmsht_tr_dateline_classes ); ?>">
								<td class="<?php echo esc_html( $tmsht_td_dateline_classes ); ?>">
									<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
									<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $date ) ) ); ?></div>
								</td>
								<?php
								for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value ++ ) {
									$is_today = ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) );
									tmsht_report_table_single_td( $is_today, $time_value, $date, $td_readonly, $user_data_2 );
								}
								?>
							</tr>
							<?php
						}
					}
				}
			} else {
				if ( 'date' == $ts_report_filters['group_by'] ) {
					foreach ( $selected_users as $user_id ) {
						?>
						<tr class="tmsht_ts_report_table_tr">
							<td class="tmsht_ts_report_table_td_user">
								<strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong>
							</td>
							<?php
							foreach ( $ts_data as $ts_key => $ts_value ) {
								$td_readonly         = ( 0 === $tmsht_options['edit_past_days'] && $ts_key < date( 'Y-m-d' ) );
								$td_timeline_classes = 'tmsht_ts_report_table_td_time tmsht_ts_report_table_td_time_' . $ts_key;
								if ( date( 'Y-m-d' ) === $ts_key ) {
									$td_timeline_classes .= ' tmsht_ts_report_table_td_today';
								}
								if ( in_array( strtolower( date( 'D', strtotime( $ts_key ) ) ), $tmsht_options['weekends'] ) ) {
									$td_timeline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
								}
								?>
								<td class="<?php echo esc_html( $td_timeline_classes ); ?>" data-td-index="<?php echo esc_attr( $ts_key ); ?>">
									<div class="tmsht_ts_report_table_td_helper tmsht_ts_report_table_td_helper_<?php echo esc_attr( $ts_key ); ?>"></div>
									<div class="tmsht_ts_report_table_td_fill_group">
										<?php
										if ( ! in_array( $user_id, $ts_value ) ) {
											/* Nothing */
										} else {
											?>
											<div class="tmsht_ts_report_table_td_fill_full"
											 style="background-color: <?php echo sanitize_hex_color( $tmsht_legends[ $ts_report_filters['legend'] ]['color'] ); ?>;"
											 title="<?php echo esc_html( $tmsht_legends[ $ts_report_filters['legend'] ]['name'] ); ?>"></div>
										<?php } ?>
									</div>
									<?php if ( $td_readonly ) { ?>
										<div class="tmsht_ts_report_table_td_readonly_fill"></div>
									<?php } ?>
								</td>
							<?php } ?>
						</tr>
						<?php
					}
				} else {
					foreach ( $date_period as $date ) {
						$date_formated = date( 'Y-m-d', strtotime( $date ) );

						$is_today    = ( date( 'Y-m-d' ) === $date_formated );
						$td_readonly = ( 0 === $tmsht_options['edit_past_days'] && date( 'Y-m-d' ) > $date_formated );

						$tr_classes = 'tmsht_ts_report_table_tr';
						if ( $is_today ) {
							$tr_classes .= ' tmsht_ts_report_table_highlight_today';
						}

						$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
						if ( $is_today ) {
							$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_td_today';
						}
						if ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) {
							$tmsht_td_dateline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
						}
						?>

						<tr class="<?php echo esc_html( $tr_classes ); ?>">
							<td class="<?php echo esc_html( $tmsht_td_dateline_classes ); ?>">
								<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $date ) ) ); ?></div>
								<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $date ) ) ); ?></div>
							</td>
							<?php
							foreach ( $ts_data as $ts_key => $ts_value ) {

								$td_timeline_classes = 'tmsht_ts_report_table_td_time tmsht_ts_report_table_td_time_' . $ts_key;
								if ( $is_today ) {
									$td_timeline_classes .= ' tmsht_ts_report_table_td_today';
								}
								?>
								<td class="<?php echo esc_attr( $td_timeline_classes ); ?>" data-td-index="<?php echo esc_attr( $ts_key ); ?>">
									<div class="tmsht_ts_report_table_td_helper tmsht_ts_report_table_td_helper_<?php echo esc_attr( $ts_key ); ?>"></div>
									<div class="tmsht_ts_report_table_td_fill_group">
										<?php
										if ( ! in_array( $date_formated, $ts_value ) ) {
											/* Nothing */
										} else {
											?>
											<div class="tmsht_ts_report_table_td_fill_full"
											 style="background-color: <?php echo sanitize_hex_color( $tmsht_legends[ $ts_report_filters['legend'] ]['color'] ); ?>;"
											 title="<?php echo esc_html( $tmsht_legends[ $ts_report_filters['legend'] ]['name'] ); ?>"></div>
										<?php } ?>
									</div>
									<?php if ( $td_readonly ) { ?>
										<div class="tmsht_ts_report_table_td_readonly_fill"></div>
									<?php } ?>
								</td>
							<?php } ?>
						</tr>
						<?php
					}
				}
			}
			?>
			</tbody>
			<tfoot>
			<tr>
				<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
				<?php if ( 'hourly' == $ts_report_filters['view'] ) { ?>
					<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
					<?php for ( $time_value = $tmsht_options['ts_timeline_from']; $time_value <= ( $tmsht_options['ts_timeline_to'] - 1 ); $time_value ++ ) { ?>
						<td class="tmsht_ts_report_table_td_timeline">
							<div class="tmsht_ts_report_time_display"><?php echo esc_html( $time_value > 9 ? $time_value : '&nbsp;' . $time_value ); ?></div>
						</td>
						<?php
					}
				} else {
					if ( 'date' == $ts_report_filters['group_by'] ) {
						foreach ( $ts_data as $ts_key => $ts_value ) {
							$td_timeline_classes = 'tmsht_ts_report_table_td_dateline';
							if ( date( 'Y-m-d' ) === $ts_key ) {
								$td_timeline_classes .= ' tmsht_ts_report_table_td_today tmsht_ts_report_table_highlight_today';
							}
							if ( in_array( strtolower( date( 'D', strtotime( $ts_key ) ) ), $tmsht_options['weekends'] ) ) {
								$td_timeline_classes .= ' tmsht_ts_report_table_highlight_weekdays';
							}
							?>
							<td class="<?php echo esc_html( $td_timeline_classes ); ?>">
								<div class="tmsht_ts_report_formatted_date"><?php echo esc_html( date_i18n( $tmsht_options['date_format'], strtotime( $ts_key ) ) ); ?></div>
								<div class="tmsht_ts_report_weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $ts_key ) ) ); ?></div>
							</td>
							<?php
						}
					} else {
						foreach ( $selected_users as $user_id ) {
							?>
							<td class="tmsht_ts_report_table_td_dateline">
								<strong><?php echo esc_html( $tmsht_users[ $user_id ] ); ?></strong></td>
							<?php
						}
					}
				}
				?>
			</tr>
			</tfoot>
		<?php } ?>
		<?php wp_die(); ?>
		<?php
	}
}

if ( ! function_exists( 'tmsht_clear_ts' ) ) {
	/**
	 * Clear data from ts table
	 */
	function tmsht_clear_ts() {
		global $wpdb, $tmsht_options;

		if ( empty( $tmsht_options ) ) {
			tmsht_register_options();
		}

		$period = $tmsht_options['clear_timesheet_period'];

		$interval = array(
			'month' => date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s' ) . ' -1 month' ) ),
			'6 month' => date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s' ) . ' -6 month' ) ),
			'year' => date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s' ) . ' -1 year' ) ),
		);

		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM `' . $wpdb->prefix . 'tmsht_ts` WHERE `time_to` <= %s',
				$interval[ $period ]
			)
		);
	}
}

if ( ! function_exists( 'tmsht_ts_report_users_update' ) ) {
	/**
	 * Update ajax report users
	 */
	function tmsht_ts_report_users_update() {
		global $wpdb, $current_user, $tmsht_legends;

		$week_days_arr     = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$day_of_week_start = get_option( 'start_of_week' );

		$date_preset_units_arr = array(
			'week'  => __( 'Week', 'timesheet' ),
			'month' => __( 'Month', 'timesheet' ),
		);

		$ts_report_group_by_arr = array(
			'date' => _x( 'Date', 'Group by', 'timesheet' ),
			'user' => _x( 'User', 'Group by', 'timesheet' ),
		);

		$ts_report_view = array(
			'hourly' => __( 'Hourly', 'timesheet' ),
			'daily'  => __( 'Daily', 'timesheet' ),
		);

		/* Get legends */
		$tmsht_legends = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}tmsht_legends`", OBJECT_K );
		/* Convert stdClass items of array( $tmsht_legends ) to associative array */
		$tmsht_legends        = json_decode( json_encode( $tmsht_legends ), true );
		$tmsht_legends[- 1] = array(
			'name'     => __( 'Blank', 'timesheet' ),
			'color'    => 'transparent',
			'disabled' => 1,
		);
		$tmsht_legends[- 2] = array(
			'name'     => __( 'All statuses', 'timesheet' ),
			'color'    => '#444444',
			'disabled' => 0,
		);
		ksort( $tmsht_legends );

		/* Get users */
		$tmsht_users = tmsht_get_users();

		/* Get user meta */
		$ts_report_filters = get_user_meta( $current_user->ID, '_tmsht_ts_report_filters', true );

		if ( empty( $ts_report_filters ) ) {
			$ts_report_filters = array(
				'date'     => array(
					'type'   => 'period',
					'preset' => array(),
				),
				'group_by' => 'date',
				'view'     => 'hourly',
				'legend'   => - 2,
				'users'    => array_keys( $tmsht_users ),
			);

			add_user_meta( $current_user->ID, '_tmsht_ts_report_filters', $ts_report_filters );
		}

		/* Apply filters */
		if ( isset( $_POST['tmsht_generate_ts_report'] ) && isset( $_POST['tmsht_apply_ts_report_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmsht_apply_ts_report_field'] ) ), 'tmsht_apply_ts_report_action' ) ) {
			if (
				( isset( $_POST['tmsht_date_filter_type'] ) && 'preset' == sanitize_text_field( wp_unslash( $_POST['tmsht_date_filter_type'] ) ) ) &&
				( isset( $_POST['tmsht_date_preset_unit'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_date_preset_unit'] ) ), $date_preset_units_arr ) ) &&
				isset( $_POST['tmsht_date_preset_quantity'] )
			) {
				$ts_report_filters['date'] = array(
					'type'   => 'preset',
					'preset' => array(
						'quantity' => intval( $_POST['tmsht_date_preset_quantity'] ),
						'unit'     => sanitize_text_field( wp_unslash( $_POST['tmsht_date_preset_unit'] ) ),
					),
				);
			} else {
				$ts_report_filters['date'] = array(
					'type'   => 'period',
					'preset' => array(),
				);
			}

			$ts_report_filters['group_by'] = isset( $_POST['tmsht_ts_report_group_by'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_group_by'] ) ), $ts_report_group_by_arr ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_group_by'] ) ) : 'date';
			$ts_report_filters['legend']   = isset( $_POST['tmsht_ts_report_legend'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_legend'] ) ), $tmsht_legends ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_legend'] ) ) : - 2;
			$ts_report_filters['view']     = isset( $_POST['tmsht_ts_report_view'] ) && - 2 != $ts_report_filters['legend'] && array_key_exists( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_view'] ) ), $ts_report_view ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_ts_report_view'] ) ) : 'hourly';
			$ts_report_filters['users']    = ( isset( $_REQUEST['tmsht_ts_report_user'] ) && is_array( $_REQUEST['tmsht_ts_report_user'] ) ) ? $_REQUEST['tmsht_ts_report_user'] : array_keys( $tmsht_users );
			update_user_meta( $current_user->ID, '_tmsht_ts_report_filters', $ts_report_filters );
		}

		/* Report generation */
		$date_from = ( isset( $_REQUEST['tmsht_ts_report_date_from'] ) && strtotime( sanitize_text_field( wp_unslash( $_REQUEST['tmsht_ts_report_date_from'] ) ) ) && 'period' == $ts_report_filters['date']['type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tmsht_ts_report_date_from'] ) ) : date( 'Y-m-d' );
		$date_to   = ( isset( $_REQUEST['tmsht_ts_report_date_to'] ) && strtotime( sanitize_text_field( wp_unslash( $_REQUEST['tmsht_ts_report_date_to'] ) ) ) && 'period' == $ts_report_filters['date']['type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tmsht_ts_report_date_to'] ) ) : date( 'Y-m-d', strtotime( 'next ' . $week_days_arr[ $day_of_week_start ] . ' +6 days' ) );

		$filter_date_from = $date_from;
		$filter_date_to   = $date_to;

		/* Session data for page=tmsht_ts_report */
		if ( isset( $_REQUEST['tmsht_ts_report_date_from'] ) ) {
			$_SESSION['tmsht_ts_report_date_from'] = sanitize_text_field( wp_unslash( $_REQUEST['tmsht_ts_report_date_from'] ) );
		}
		if ( isset( $_REQUEST['tmsht_ts_report_date_to'] ) ) {
			$_SESSION['tmsht_ts_report_date_to'] = sanitize_text_field( wp_unslash( $_REQUEST['tmsht_ts_report_date_to'] ) );
		}

		if ( 'preset' == $ts_report_filters['date']['type'] ) {
			$date_from = date( 'Y-m-d' );
			$date_to   = date( 'Y-m-d', strtotime( '+' . $ts_report_filters['date']['preset']['quantity'] . ' ' . $ts_report_filters['date']['preset']['unit'] ) );
		}

		$date_period = tmsht_date_period( $date_from, date( 'Y-m-d', strtotime( $date_to . ' +1 day' ) ) );

		$selected_users = array();

		foreach ( $ts_report_filters['users'] as $user_id ) {
			if ( array_key_exists( $user_id, $tmsht_users ) ) {
				$selected_users[] = $user_id;
			}
		}

		$ts_data = array();

		if ( isset( $_GET['user'] ) ) {
			$user_selected_single = get_user_by( 'login', sanitize_text_field( wp_unslash( $_GET['user'] ) ) );
			if ( isset( $user_selected_single->ID ) ) {
				$selected_users                = array( $user_selected_single->ID );
				$ts_report_filters['group_by'] = 'user';
			}
		}

		if ( $selected_users ) {
			$ts_data_query = ( 'hourly' == $ts_report_filters['view'] ) ? 'SELECT `user_id`, `time_from`, `time_to`, `legend_id`' : "SELECT `user_id`, DATE_FORMAT( `time_from`, '%Y-%m-%d' ) AS `date`";

			$ts_data_query .= ' FROM `' . $wpdb->prefix . 'tmsht_ts` WHERE date(`time_from`) >= %s AND date(`time_to`) <= %s';
			$ts_data_query_prepare = array( $date_from, $date_to );

			if ( $ts_report_filters['legend'] > 0 ) {
				$ts_data_query .= ' AND `legend_id` = %d';
				$ts_data_query_prepare[] = $ts_report_filters['legend'];
			}

			$ts_data_query .= ' AND `user_id` IN (' . implode( ',', $selected_users ) . ')';
			if ( 'hourly' != $ts_report_filters['view'] ) {
				$ts_data_query .= ' GROUP BY `user_id`, DAY(`time_from`)';
			}
			$ts_data_query .= ' ORDER BY FIELD(user_id,' . implode( ',', $selected_users ) . '), `time_from` ASC';
			$ts_get_data   = $wpdb->get_results(
				$wpdb->prepare(
					$ts_data_query,
					$ts_data_query_prepare
				),
				ARRAY_A
			);

			if ( $ts_get_data ) {
				if ( 'hourly' == $ts_report_filters['view'] ) {
					if ( 'date' == $ts_report_filters['group_by'] ) {

						foreach ( $ts_get_data as $data ) {
							$key_date                                   = date( 'Y-m-d', strtotime( $data['time_from'] ) );
							$ts_data[ $key_date ][ $data['user_id'] ][] = $data;
						}

						/* need to create empty array for saving sorting - username ASC */
						$empty_users_data = array();
						foreach ( $selected_users as $user_id ) {
							$empty_users_data[ $user_id ][] = array();
						}

						foreach ( $date_period as $date ) {
							$date_formated = date( 'Y-m-d', strtotime( $date ) );

							if ( isset( $ts_data[ $date_formated ] ) ) {
								$ts_data[ $date_formated ] = tmsht_array_replace( $empty_users_data, $ts_data[ $date_formated ] );
							} else {
								$ts_data[ $date_formated ] = array(
									- 1 => array( array() ),
								);
							}
						}

						/* sort by time */
						ksort( $ts_data );
					} else if ( 'user' == $ts_report_filters['group_by'] ) {
						/* need to create empty array first for saving sorting - username ASC */
						foreach ( $selected_users as $user_id ) {
							$ts_data[ $user_id ] = array();
						}

						foreach ( $ts_get_data as $data ) {
							$key_date                                   = date( 'Y-m-d', strtotime( $data['time_from'] ) );
							$ts_data[ $data['user_id'] ][ $key_date ][] = $data;
						}

						$empty_date_data = array();
						foreach ( $date_period as $date ) {
							$date_formated                       = date( 'Y-m-d', strtotime( $date ) );
							$empty_date_data[ $date_formated ][] = array();
						}

						foreach ( $ts_data as $user_id => $data ) {
							if ( ! empty( $data ) ) {
								$ts_data[ $user_id ] = tmsht_array_replace( $empty_date_data, $ts_data[ $user_id ] );
							}
						}
					}
				} else {
					if ( 'date' == $ts_report_filters['group_by'] ) {

						foreach ( $ts_get_data as $data ) {
							$ts_data[ $data['date'] ][] = $data['user_id'];
						}

						foreach ( $date_period as $date ) {
							$date_formated = date( 'Y-m-d', strtotime( $date ) );

							$exists_data_for_users = isset( $ts_data[ $date_formated ] ) ? array_keys( $ts_data[ $date_formated ] ) : array();

							if ( ! $exists_data_for_users ) {
								$ts_data[ $date_formated ] = array( '-1' );
							}
						}

						/* sort by time */
						ksort( $ts_data );
					} else {
						/* need to create empty array first for saving sorting - username ASC */
						foreach ( $selected_users as $user_id ) {
							$ts_data[ $user_id ] = array();
						}

						foreach ( $ts_get_data as $data ) {
							$ts_data[ $data['user_id'] ][] = $data['date'];
						}
					}
				}
			}
		}
		?>
		<?php tmsht_ajax_report_user_list_display( $tmsht_users, $selected_users ); ?>
		<?php wp_die(); ?>
		<?php
	}
}

register_activation_hook( __FILE__, 'tmsht_plugin_activate' );
/* Calling a function add administrative menu. */
add_action( 'admin_menu', 'tmsht_admin_menu' );
/* Initialization */
add_action( 'plugins_loaded', 'tmsht_plugins_loaded' );
add_action( 'init', 'tmsht_init' );
add_action( 'admin_init', 'tmsht_admin_init' );
/* Adding stylesheets */
add_action( 'admin_enqueue_scripts', 'tmsht_admin_scripts_styles' );
/*Delete Timesheet notes by times period*/
add_action( 'tmsht_clear_period_timesheet', 'tmsht_clear_ts' );
/* delete ts data, when user was deleted */
add_action( 'delete_user', 'tmsht_delete_user' );
/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'tmsht_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'tmsht_links', 10, 2 );
add_action( 'admin_notices', 'tmsht_plugin_banner' );

add_action( 'tmsht_reminder_to_email', 'tmsht_reminder_to_email' );
add_filter( 'cron_schedules', 'tmsht_add_weekly' );
/* Update user table */
add_action( 'wp_ajax_tmsht_ts_update_table', 'tmsht_ts_user_table_update' );
/* Update user advanced container */
add_action( 'wp_ajax_tmsht_ts_update_advanced_container', 'tmsht_ts_user_advanced_container_update' );
/* Update report table */
add_action( 'wp_ajax_tmsht_ts_update_report_table', 'tmsht_ts_user_report_table_update' );
/* Update report users list */
add_action( 'wp_ajax_tmsht_ts_update_report_users', 'tmsht_ts_report_users_update' );
