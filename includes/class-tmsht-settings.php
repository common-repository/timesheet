<?php
/**
 * Class for the plugin settings page
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'Tmsht_Settings_Tabs' ) ) {
	/**
	 * Displays the content on the plugin settings page
	 */
	class Tmsht_Settings_Tabs extends Bws_Settings_Tabs {
		/**
		 * Days array for settings
		 *
		 * @var array
		 */
		public $days_arr;
		/**
		 * Date formats array for settings
		 *
		 * @var array
		 */
		public $date_formats;
		/**
		 * Roles array for settings
		 *
		 * @var array
		 */
		public $all_roles;
		/**
		 * Period array for settings
		 *
		 * @var array
		 */
		public $period_arr;
		/**
		 * Constructor.
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename Plugin basename.
		 */
		public function __construct( $plugin_basename ) {
			global $tmsht_options, $tmsht_plugin_info;

			$tabs = array(
				'settings'      => array( 'label' => __( 'Settings', 'timesheet' ) ),
				'display'       => array( 'label' => __( 'Display', 'timesheet' ) ),
				'notifications' => array( 'label' => __( 'Reminders', 'timesheet' ) ),
				'reports'       => array(
					'label' => __( 'Reports', 'timesheet' ),
					'is_pro' => 1,
				),
				'import-export' => array(
					'label' => __( 'Import / Export', 'timesheet' ),
					'is_pro' => 1,
				),
				'misc'          => array( 'label' => __( 'Misc', 'timesheet' ) ),
				'license'       => array( 'label' => __( 'License Key', 'timesheet' ) ),
			);

			parent::__construct(
				array(
					'plugin_basename'    => $plugin_basename,
					'plugins_info'       => $tmsht_plugin_info,
					'prefix'             => 'tmsht',
					'default_options'    => tmsht_get_options_default(),
					'options'            => $tmsht_options,
					'tabs'               => $tabs,
					'wp_slug'            => 'timesheet',
					'link_key'           => '3bdf25984ad6aa9d95074e31c5eb9bb3',
					'link_pn'            => '606',
					'doc_link'           => 'https://bestwebsoft.com/documentation/timesheet/timesheet-user-guide/',
				)
			);

			$this->days_arr     = array( __( 'Mon', 'timesheet' ), __( 'Tue', 'timesheet' ), __( 'Wed', 'timesheet' ), __( 'Thu', 'timesheet' ), __( 'Fri', 'timesheet' ), __( 'Sat', 'timesheet' ), __( 'Sun', 'timesheet' ) );
			$this->period_arr   = array( __( 'month', 'timesheet' ), __( '6 month', 'timesheet' ), __( 'year', 'timesheet' ) );
			$this->date_formats = array(
				'wp'     => get_option( 'date_format' ),
				'custom' => $this->options['date_format'],
			);

			$this->all_roles    = get_editable_roles();
			ksort( $this->all_roles );

			add_filter( get_parent_class( $this ) . '_additional_restore_options', array( $this, 'additional_restore_options' ) );

			add_action( get_parent_class( $this ) . '_display_metabox', array( $this, 'display_metabox' ) );
			add_action( get_parent_class( $this ) . '_additional_misc_options_affected', array( $this, 'additional_misc_options_affected' ) );
		}

		/**
		 * Save plugin options to the database
		 *
		 * @access public
		 * @return array    The action results
		 */
		public function save_options() {
			global $wpdb;

			$message = '';
			$notice   = '';
			$error   = '';

			if ( isset( $_POST['tmsht_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmsht_nonce_field'] ) ), 'tmsht_action' ) ) {

				/* Takes all the changed settings on the plugin's admin page and saves them in array 'tmsht_options'. */
				if ( isset( $_POST['tmsht_add_ts_legend'] ) ) {

					$ts_legend_name = ( isset( $_POST['tmsht_add_ts_legend_name'] ) ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_add_ts_legend_name'] ) ) : '';
					$ts_legend_color = ( isset( $_POST['tmsht_add_ts_legend_color'] ) ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_add_ts_legend_color'] ) ) : '';

					if ( empty( $ts_legend_name ) ) {
						$error = __( 'Please enter status name.', 'timesheet' );
					}

					if ( ! preg_match( '/^#?([a-f0-9]{6}|[a-f0-9]{3})$/', $ts_legend_color ) ) {
						if ( empty( $error ) ) {
							$error .= '<br>' . __( 'Please choose status color.', 'timesheet' );
						} else {
							$error .= __( 'Please choose status color.', 'timesheet' );
						}
					}

					if ( empty( $error ) && $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `{$wpdb->prefix}tmsht_legends` WHERE `name` = %s OR `color` = %s", $ts_legend_name, $ts_legend_color ) ) ) {
						$error = sprintf( __( 'Status with name "%1$s" or with color %2$s already exists.', 'timesheet' ), $ts_legend_name, $ts_legend_color );
					}

					if ( empty( $error ) && in_array( $ts_legend_color, array( '#ffffff', '#f9f9f9' ) ) ) {
						$error = sprintf( __( 'The status with the color %s can not be saved. Please choose a different color.', 'timesheet' ), $ts_legend_color );
					}

					if ( empty( $error ) ) {
						$query_results = $wpdb->insert(
							$wpdb->prefix . 'tmsht_legends',
							array(
								'name'  => $ts_legend_name,
								'color' => $ts_legend_color,
							),
							array( '%s', '%s' )
						);

						if ( $query_results ) {
							unset( $ts_legend_name, $ts_legend_color );
							$message = __( 'Status has been successfully added.', 'timesheet' );
						} else {
							$error = __( 'Status has not been added.', 'timesheet' );
						}
					} else {
						$error .= '<br>' . __( 'Status has not been added.', 'timesheet' );
					}
				} else {
					/* Set timeline */
					$this->options['ts_timeline_from'] = ( isset( $_POST['tmsht_ts_timeline_from'] ) && $_POST['tmsht_ts_timeline_from'] >= 0 && $_POST['tmsht_ts_timeline_from'] <= 23 ) ? intval( $_POST['tmsht_ts_timeline_from'] ) : 0;
					$this->options['ts_timeline_to'] = ( isset( $_POST['tmsht_ts_timeline_to'] ) && $_POST['tmsht_ts_timeline_to'] <= 24 && $_POST['tmsht_ts_timeline_to'] >= 1 ) ? intval( $_POST['tmsht_ts_timeline_to'] ) : 24;

					if ( $this->options['ts_timeline_from'] == $this->options['ts_timeline_to'] ) {
						if ( $this->options['ts_timeline_to'] < 24 ) {
							$this->options['ts_timeline_to']++;
						} else {
							$this->options['ts_timeline_from']--;
						}
					}

					/* Set weekends */
					if ( ! empty( $_POST['tmsht_weekends'] ) ) {
						$tmsht_weekends = (array) $_POST['tmsht_weekends'];
						foreach ( $tmsht_weekends as $weekend ) {
							if ( ! in_array( ucfirst( $weekend ), $this->days_arr ) ) {
								unset( $weekend );
							}
						}
						$this->options['weekends'] = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $tmsht_weekends ) );
					} else {
						$this->options['weekends'] = array();
					}

					/*Set clear period*/
					if ( isset( $_POST['tmsht_clear_timesheet_period'] ) && in_array( $_POST['tmsht_clear_timesheet_period'], $this->period_arr ) ) {
						$this->options['clear_timesheet_period'] = sanitize_text_field( wp_unslash( $_POST['tmsht_clear_timesheet_period'] ) );
						if ( ! wp_next_scheduled( 'tmsht_clear_period_timesheet' ) ) {
							wp_schedule_event( time(), 'tmsht_weekly', 'tmsht_clear_period_timesheet' );
						}
					} else {
						$this->options['clear_timesheet_period'] = '';
						wp_clear_scheduled_hook( 'tmsht_clear_period_timesheet' );
					}

					/* Enable/disable legends */

					$ts_legend_ids = ( isset( $_POST['tmsht_ts_legend_id'] ) && is_array( $_POST['tmsht_ts_legend_id'] ) ) ? array_map( 'intval', $_POST['tmsht_ts_legend_id'] ) : array();
					$ts_legend_all_days = ( isset( $_POST['tmsht_ts_legend_all_day'] ) && is_array( $_POST['tmsht_ts_legend_all_day'] ) ) ? array_map( 'intval', $_POST['tmsht_ts_legend_all_day'] ) : array();
					$ts_legend_ids_hidden = ( isset( $_POST['tmsht_ts_legend_id_hidden'] ) && is_array( $_POST['tmsht_ts_legend_id_hidden'] ) ) ? array_map( 'intval', $_POST['tmsht_ts_legend_id_hidden'] ) : array();

					foreach ( $ts_legend_ids_hidden as $legend_id ) {
						$color = ( isset( $_POST['tmsht_ts_legend_color'][ $legend_id ] ) && preg_match( '/^#?([a-f0-9]{6}|[a-f0-9]{3})$/', trim( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_legend_color'][ $legend_id ] ) ) ) ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['tmsht_ts_legend_color'][ $legend_id ] ) ) ) : false;
						$disabled = ( ! in_array( $legend_id, $ts_legend_ids ) ) ? 1 : 0;
						$all_day = ( ! in_array( $legend_id, $ts_legend_all_days ) ) ? 0 : 1;

						if ( $color ) {
							$wpdb->update(
								$wpdb->prefix . 'tmsht_legends',
								array(
									'color'     => $color,
									'disabled'  => $disabled,
									'all_day'   => $all_day,
								),
								array( 'id' => $legend_id ),
								array( '%s', '%d', '%d' )
							);
						} else {
							$wpdb->update(
								$wpdb->prefix . 'tmsht_legends',
								array(
									'disabled'  => $disabled,
									'all_day'   => $all_day,
								),
								array( 'id' => $legend_id ),
								array( '%d', '%d' )
							);
						}
					}

					/* Set date format */
					if ( isset( $_POST['tmsht_date_format_type'] ) ) {
						switch ( $_POST['tmsht_date_format_type'] ) {
							case 'custom':
								$this->options['date_format_type'] = sanitize_text_field( wp_unslash( $_POST['tmsht_date_format_type'] ) );
								$this->options['date_format'] = ( isset( $_POST['tmsht_date_format_code'] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['tmsht_date_format_code'] ) ) ) : '';
								break;
							case 'wp':
								$this->options['date_format_type'] = $_POST['tmsht_date_format_type'];
								$this->options['date_format'] = get_option( 'date_format' );
								break;
							default:
								break;
						}
					}

					$this->options['edit_past_days'] = ( ! empty( $_POST['tmsht_edit_past_days'] ) ) ? 1 : 0;
					$this->options['reminder_on_email'] = ( ! empty( $_POST['tmsht_reminder_on_email'] ) ) ? 1 : 0;
					$this->options['day_reminder'] = ( isset( $_POST['tmsht_day_reminder'] ) && in_array( ucfirst( sanitize_text_field( wp_unslash( $_POST['tmsht_day_reminder'] ) ) ), $this->days_arr ) ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_day_reminder'] ) ) : $this->default_options['day_reminder'];
					$this->options['time_reminder'] = ( isset( $_POST['tmsht_time_reminder'] ) && preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', sanitize_text_field( wp_unslash( $_POST['tmsht_time_reminder'] ) ) ) ) ? sanitize_text_field( wp_unslash( $_POST['tmsht_time_reminder'] ) ) : $this->default_options['time_reminder'];

					if ( isset( $_POST['tmsht_reminder_change_state'] ) ) {

						wp_clear_scheduled_hook( 'tmsht_reminder_to_email' );

						if ( $this->options['reminder_on_email'] ) {

							if ( ! wp_next_scheduled( 'tmsht_reminder_to_email' ) ) {
								$current_offset = get_option( 'gmt_offset' );
								$tzstring = get_option( 'timezone_string' );

								/* Remove old Etc mappings. Fallback to gmt_offset. */
								if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
									$tzstring = '';
								}

								/* Create a UTC+- zone if no timezone string exists */
								if ( empty( $tzstring ) ) {
									if ( 0 == $current_offset ) {
										$tzstring = '+0';
									} elseif ( $current_offset < 0 ) {
										$tzstring = $current_offset;
									} else {
										$tzstring = '+' . $current_offset;
									}
								}

								$time = strtotime( sprintf( '%s %s:00 %s', ucfirst( $this->options['day_reminder'] ), $this->options['time_reminder'], $tzstring ) );

								if ( time() > $time ) {
									$time = strtotime( sprintf( '%s %s:00 %s +1 week', ucfirst( $this->options['day_reminder'] ), $this->options['time_reminder'], $tzstring ) );
								}

								wp_schedule_event( $time, 'tmsht_weekly', 'tmsht_reminder_to_email' );
							}
						}
					}

					$this->options['content_reminder']['subject'] = ( isset( $_POST['tmsht_reminder_subject'] ) ) ? wp_strip_all_tags( wp_kses_post( $_POST['tmsht_reminder_subject'] ) ) : $this->default_options['content_reminder']['subject'];
					$this->options['content_reminder']['message'] = ( isset( $_POST['tmsht_reminder_message'] ) ) ? wp_strip_all_tags( wp_kses_post( $_POST['tmsht_reminder_message'] ) ) : $this->default_options['content_reminder']['message'];

					/* Display TS user page for */
					$display_ts_user_page_for = ( isset( $_POST['tmsht_display_ts_user_page_for'] ) && is_array( $_POST['tmsht_display_ts_user_page_for'] ) ) ? $_POST['tmsht_display_ts_user_page_for'] : array();
					$tmsht_ts_user_roles = array();
					foreach ( $display_ts_user_page_for as $role ) {
						if ( array_key_exists( $role, $this->all_roles ) ) {
							$tmsht_ts_user_roles[] = sanitize_text_field( wp_unslash( $role ) );
						}
					}

					$this->options['display_pages']['ts_user']['user_roles'] = $tmsht_ts_user_roles;

					/* Display TS report page for */
					$display_ts_report_page_for = ( isset( $_POST['tmsht_display_ts_report_page_for'] ) && is_array( $_POST['tmsht_display_ts_report_page_for'] ) ) ? $_POST['tmsht_display_ts_report_page_for'] : array();
					$ts_report_roles = array();
					foreach ( $display_ts_report_page_for as $role ) {
						if ( array_key_exists( $role, $this->all_roles ) ) {
							$ts_report_roles[] = sanitize_text_field( wp_unslash( $role ) );
						}
					}

					$this->options['display_pages']['ts_report']['user_roles'] = $ts_report_roles;

					/* Save settings if no errors */
					if ( empty( $error ) ) {
						update_option( 'tmsht_options', $this->options );
						$message = __( 'Settings saved.', 'timesheet' );
					}
				}
			}

			return compact( 'message', 'notice', 'error' );
		}

		/**
		 * Displays 'settings' menu-tab
		 *
		 * @access public
		 */
		public function tab_settings() {
			global $wpdb;

			$legends = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->prefix . 'tmsht_legends`', ARRAY_A ); ?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Timesheet Settings', 'timesheet' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>		
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Timeline', 'timesheet' ); ?></th>
					<td>
						<div id="tmsht_timeline_settings_wrap">
							<div id="tmsht_timeline_settings"><?php echo esc_html_x( 'from', 'timeline', 'timesheet' ); ?> <input id="tmsht_ts_timeline_from" type="number" name="tmsht_ts_timeline_from" value="<?php echo esc_attr( $this->options['ts_timeline_from'] ); ?>" maxlength="2" min="0" max="23"> <?php echo esc_html_x( 'to', 'timeline', 'timesheet' ); ?> <input id="tmsht_ts_timeline_to" type="number" name="tmsht_ts_timeline_to" value="<?php echo esc_attr( $this->options['ts_timeline_to'] ); ?>" maxlength="2" min="1" max="24"> <?php echo esc_html_x( 'hours', 'timeline', 'timesheet' ); ?></div>
							<div id="tmsht_timeline_slider_wrap">
								<div id="tmsht_timeline_slider"></div>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Weekends', 'timesheet' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $this->days_arr as $day ) { ?>
								<label class="tmsht_label_weekends">
									<input type="checkbox" name="tmsht_weekends[]" value="<?php echo esc_attr( strtolower( $day ) ); ?>" <?php echo in_array( strtolower( $day ), $this->options['weekends'] ) ? 'checked="checked"' : ''; ?> /> 
									<?php echo esc_html( $day ); ?>
								</label>
							<?php } ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Statuses', 'timesheet' ); ?></th>
					<td>
						<input id="tmsht_add_ts_legend_name" class="bws_no_bind_notice" type="text" name="tmsht_add_ts_legend_name" value="<?php echo isset( $ts_legend_name ) ? esc_html( $ts_legend_name ) : ''; ?>" maxlength="100" placeholder="<?php esc_html_e( 'Name', 'timesheet' ); ?>" /> 
						<input id="tmsht_add_ts_legend_color" class="bws_no_bind_notice" type="text" name="tmsht_add_ts_legend_color" value="<?php echo esc_attr( isset( $ts_legend_color ) ? $ts_legend_color : tmsht_generate_color() ); ?>" data-default-color="#000000" />
						<p>
							<input class="button-secondary bws_no_bind_notice" type="submit" name="tmsht_add_ts_legend" value="<?php esc_html_e( 'Add status', 'timesheet' ); ?>" />
						</p>
						<table class="widefat striped tmsht_ts_legends_table">
							<thead>
								<tr>
									<td class="tmsht_ts_legend_id_cell"><?php esc_html_e( 'Enabled', 'timesheet' ); ?></td>
									<td class="tmsht_ts_legend_all_day_cell"><?php esc_html_e( 'All Day', 'timesheet' ); ?></td>
									<td class="tmsht_ts_legend_name_cell"><?php echo esc_html_x( 'Name', 'Settings status table header', 'timesheet' ); ?></td>
									<td class="tmsht_ts_legend_color_cell"><?php echo esc_html_x( 'Color', 'Settings status table header', 'timesheet' ); ?></td>
								</tr>
							</thead>
							<tbody>
								<?php
								if ( $legends ) {
									foreach ( $legends as $legend ) {
										?>
										<tr>
											<td class="tmsht_ts_legend_id_cell" data-column-title="<?php esc_html_e( 'Enabled', 'timesheet' ); ?>">
												<input class="tmsht_ts_legend_id" type="checkbox" name="tmsht_ts_legend_id[<?php echo esc_attr( $legend['id'] ); ?>]" value="<?php echo esc_attr( $legend['id'] ); ?>" <?php checked( $legend['disabled'], 0 ); ?> />
												<input type="hidden" name="tmsht_ts_legend_id_hidden[<?php echo esc_attr( $legend['id'] ); ?>]" value="<?php echo esc_attr( $legend['id'] ); ?>" />
											</td>
											<td class="tmsht_ts_legend_all_day_cell" data-column-title="<?php esc_html_e( 'All Day', 'timesheet' ); ?>">
												<input class="tmsht_ts_legend_all_day" type="checkbox" name="tmsht_ts_legend_all_day[<?php echo esc_attr( $legend['id'] ); ?>]" value="<?php echo esc_attr( $legend['id'] ); ?>" <?php checked( $legend['all_day'] ); ?> />
											</td>
											<td class="tmsht_ts_legend_name_cell" data-column-title="<?php echo esc_html_x( 'Name', 'Settings legend table header', 'timesheet' ); ?>">
												<?php echo esc_html( $legend['name'] ); ?>
											</td>
											<td class="tmsht_ts_legend_color_cell" data-column-title="<?php echo esc_html_x( 'Color', 'Settings status table header', 'timesheet' ); ?>">
												<input class="tmsht_ts_legend_color" type="text" name="tmsht_ts_legend_color[<?php echo esc_attr( $legend['id'] ); ?>]" value="<?php echo esc_attr( $legend['color'] ); ?>" data-default-color="<?php echo esc_attr( $legend['color'] ); ?>" />
											</td>
										</tr>
										<?php
									}
								} else {
									?>
									<tr>
										<td class="tmsht_ts_no_legends" colspan="3" data-column-title="<?php esc_html_e( 'Statuses', 'timesheet' ); ?>"><?php esc_html_e( 'No statuses', 'timesheet' ); ?></td>
									</tr>
								<?php } ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="tmsht_ts_legend_id_cell"><?php esc_html_e( 'Enabled', 'timesheet' ); ?></td>
									<td class="tmsht_ts_legend_all_day_cell"><?php esc_html_e( 'All Day', 'timesheet' ); ?></td>
									<td class="tmsht_ts_legend_name_cell"><?php echo esc_html_x( 'Name', 'Settings status table header', 'timesheet' ); ?></td>
									<td class="tmsht_ts_legend_color_cell"><?php echo esc_html_x( 'Color', 'Settings status table header', 'timesheet' ); ?></td>
								</tr>
							</tfoot>
						</table>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Date Format', 'timesheet' ); ?></th>
					<td>
						<table class="tmsht_format_i18n">
							<tbody>
								<tr>
									<td>
										<label>
											<input id="tmsht_date_format_type_wp" type="radio" name="tmsht_date_format_type" data-date-format-code="<?php echo esc_attr( $this->date_formats['wp'] ); ?>" data-date-format-display="<?php echo esc_attr( date_i18n( $this->date_formats['wp'] ) ); ?>" value="wp" <?php checked( $this->options['date_format_type'], 'wp' ); ?>><?php esc_html_e( 'WordPress default', 'timesheet' ); ?>
										</label>
									</td>
									<td>
										<label for="tmsht_date_format_type_wp">
											<code><?php echo esc_html( $this->date_formats['wp'] ); ?></code>
										</label>
									</td>
								</tr>
								<tr>
									<td>
										<label>
											<input id="tmsht_date_format_type_custom" type="radio" name="tmsht_date_format_type" data-date-format-code="<?php echo esc_attr( $this->date_formats['custom'] ); ?>" data-date-format-display="<?php echo esc_attr( date_i18n( $this->date_formats['custom'] ) ); ?>" value="custom" <?php checked( $this->options['date_format_type'], 'custom' ); ?>><?php esc_html_e( 'Custom', 'timesheet' ); ?>
										</label>
									</td>
									<td>
										<input id="tmsht_date_format_code" class="small-text" type="text" name="tmsht_date_format_code" max-length="25" value="<?php echo esc_attr( $this->date_formats['custom'] ); ?>">
										<span id="tmsht_date_format_display"><?php echo esc_html( date_i18n( $this->date_formats['custom'] ) ); ?></span>
										<span id="tmsht_date_format_spinner" class="spinner"></span>
									</td>
								</tr>
							</tbody>
						</table>
						<span class="bws_info"><a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank"><?php esc_html_e( 'Documentation on date and time formatting', 'timesheet' ); ?></a>.</span>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Edit Overdue Timesheets', 'timesheet' ); ?></th>
					<td>
						<input type="checkbox" name="tmsht_edit_past_days" value="1" <?php checked( $this->options['edit_past_days'], 1 ); ?> /> <span class="bws_info"><?php esc_html_e( 'Enable to allow overdue timesheets editing.', 'timesheet' ); ?></span>
					</td>
				</tr>
			</table>
			<?php wp_nonce_field( 'tmsht_action', 'tmsht_nonce_field' ); ?>
			<?php
		}

		/**
		 * Displays 'notifications' menu-tab
		 *
		 * @access public
		 */
		public function tab_notifications() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Reminders Settings', 'timesheet' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Email Reminder', 'timesheet' ); ?></th>
					<td>
						<label>
							<input id="tmsht_reminder_on_email" type="checkbox" name="tmsht_reminder_on_email" value="1" <?php checked( $this->options['reminder_on_email'], 1 ); ?> class="bws_option_affect" data-affect-show=".tmsht_reminder_settings" />
							<span class="bws_info"><?php esc_html_e( 'Enable to send an email reminder to a user if his work schedule isn\'t filled out.', 'timesheet' ); ?></span>
						</label>
					</td>
				</tr>
				<tr class="tmsht_reminder_settings">
					<th></th>
					<td>
						<span><?php echo esc_html_x( 'Every', 'email reminder', 'timesheet' ); ?>&nbsp;</span>
						<select id="tmsht_day_reminder" name="tmsht_day_reminder">
							<?php foreach ( $this->days_arr as $day ) { ?>
								<option value="<?php echo esc_attr( strtolower( $day ) ); ?>" <?php selected( strtolower( $day ), $this->options['day_reminder'] ); ?>> <?php echo esc_html( $day ); ?></option>
							<?php } ?>
						</select>
						<span>&nbsp;<?php echo esc_html_x( 'at', 'email reminder', 'timesheet' ); ?>&nbsp;</span>
						<input id="tmsht_time_reminder" type="text" class="small-text" name="tmsht_time_reminder" maxlength="5" value="<?php echo esc_attr( $this->options['time_reminder'] ); ?>" />
						<input id="tmsht_reminder_change_state" type="hidden" name="tmsht_reminder_change_state" value="1">
					</td>
				</tr>
				<tr class="tmsht_reminder_settings">
					<th></th>
					<td>
						<p><?php echo esc_html_x( 'Subject', 'email reminder', 'timesheet' ); ?></p>
						<input id="tmsht_reminder_subject" type="text" name="tmsht_reminder_subject" value="<?php echo esc_html( $this->options['content_reminder']['subject'] ); ?>">
						<p><?php echo esc_html_x( 'Message', 'email reminder', 'timesheet' ); ?></p>
						<textarea id="tmsht_reminder_message" name="tmsht_reminder_message"><?php echo esc_html( $this->options['content_reminder']['message'] ); ?></textarea>
						<div class="bws_info">
							<?php esc_html_e( 'Allowed Variables', 'timesheet' ); ?>:<br>
							{user_name} - <?php esc_html_e( 'the username', 'timesheet' ); ?><br>
							{list_days} - <?php esc_html_e( 'days that are not filled by the user', 'timesheet' ); ?><br>
							{ts_page} - <?php esc_html_e( 'the link to TS user page in the Dashboard', 'timesheet' ); ?><br>
							{ts_page_link}Your text{/ts_page_link} - <?php esc_html_e( 'the link to TS user page in the Dashboard with your text', 'timesheet' ); ?>
						</div>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Displays 'display' menu-tab
		 *
		 * @access public
		 */
		public function tab_display() {
			global $wpdb;
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Display Settings', 'timesheet' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr>
					<th>
						<?php esc_html_e( 'My Availability', 'timesheet' ); ?>
					</th>
					<td>
						<div id="tmsht_display_ts_user_page_for_wrap">
							<ul id="tmsht_display_ts_user_page_for">
								<?php
								foreach ( $this->all_roles as $role => $details ) {

									$users_in_role = $wpdb->get_results(
										$wpdb->prepare(
											'SELECT users.ID, users.user_login
											FROM `' . $wpdb->base_prefix . 'users` AS users, `' . $wpdb->base_prefix . 'usermeta` AS umeta
											WHERE users.ID = umeta.user_id
												AND umeta.meta_key = %s
												AND umeta.meta_value LIKE %s
											ORDER BY users.ID ASC
											LIMIT 3',
											$wpdb->prefix . 'capabilities',
											'%' . $role . '%'
										),
										OBJECT_K
									);

									if ( 'administrator' == $role && $this->is_multisite && ! is_main_site() ) {
										$super_admins = get_super_admins();

										foreach ( $super_admins as $super_admin ) {
											$get_user = get_user_by( 'login', $super_admin );
											if ( $get_user ) {
												$add_user = array(
													'ID'           => $get_user->ID,
													'user_login'   => $get_user->user_login,
												);
												$users_in_role[ $get_user->ID ] = (object) $add_user;
											}
										}
									}

									ksort( $users_in_role );

									if ( count( $users_in_role ) > 0 ) {
										$checked_attr = ( in_array( $role, $this->options['display_pages']['ts_user']['user_roles'] ) ) ? 'checked="checked"' : '';
										?>
										<li>
											<label>
												<input type="checkbox" name="tmsht_display_ts_user_page_for[]" value="<?php echo esc_attr( $role ); ?>" <?php echo esc_html( $checked_attr ); ?>> 
												<?php echo esc_html( translate_user_role( $details['name'] ) ); ?>
											</label>
											<?php if ( ! $this->hide_pro_tabs ) { ?>
												<div id="tmsht_display_ts_user_page_for_users_wrap">
													<div class="bws_pro_version_bloc">
														<div class="bws_pro_version_table_bloc">
															<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'timesheet' ); ?>"></button>
															<div class="bws_table_bg"></div>
															<ul class="tmsht_display_ts_user_page_for_users">
																<?php foreach ( $users_in_role as $user ) { ?>
																	<li><label><input type="checkbox" <?php echo esc_html( $checked_attr ); ?> disabled="disabled"> <?php echo esc_html( $user->user_login ); ?></label></li>
																<?php } ?>
																<li><label><input type="checkbox" <?php echo esc_html( $checked_attr ); ?> disabled="disabled">...</label></li>
															</ul>
														</div>
														<?php $this->bws_pro_block_links(); ?>
													</div>
												</div>
											<?php } ?>
										</li>
										<?php
									}
								}
								?>
							</ul>
						</div>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e( 'Team', 'timesheet' ); ?>
					</th>
					<td>
						<div id="tmsht_display_ts_report_page_for_wrap">
							<ul id="tmsht_display_ts_report_page_for">
								<?php
								foreach ( $this->all_roles as $role => $details ) {

									$users_in_role = $wpdb->get_results(
										$wpdb->prepare(
											'SELECT users.ID, users.user_login
											FROM `' . $wpdb->base_prefix . 'users` AS users, `' . $wpdb->base_prefix . 'usermeta` AS umeta
											WHERE users.ID = umeta.user_id
											AND umeta.meta_key = %s
											AND umeta.meta_value LIKE %s
											ORDER BY users.ID ASC
											LIMIT 3',
											$wpdb->prefix . 'capabilities',
											'%' . $role . '%'
										),
										OBJECT_K
									);

									if ( 'administrator' == $role && $this->is_multisite && ! is_main_site() ) {
										$super_admins = get_super_admins();

										foreach ( $super_admins as $super_admin ) {
											$get_user = get_user_by( 'login', $super_admin );
											if ( $get_user ) {
												$add_user = array(
													'ID'           => $get_user->ID,
													'user_login'   => $get_user->user_login,
												);
												$users_in_role[ $get_user->ID ] = (object) $add_user;
											}
										}
									}

									ksort( $users_in_role );

									if ( count( $users_in_role ) > 0 ) {
										$checked_attr = ( in_array( $role, $this->options['display_pages']['ts_report']['user_roles'] ) ) ? 'checked="checked"' : '';
										?>
										<li>
											<label>
												<input type="checkbox" name="tmsht_display_ts_report_page_for[]" value="<?php echo esc_attr( $role ); ?>" <?php echo esc_html( $checked_attr ); ?>> 
												<?php echo esc_html( translate_user_role( $details['name'] ) ); ?>
											</label>
											<?php if ( ! $this->hide_pro_tabs ) { ?>
												<div id="tmsht_display_ts_report_page_for_users_wrap">
													<div class="bws_pro_version_bloc">
														<div class="bws_pro_version_table_bloc">
															<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'timesheet' ); ?>"></button>
															<div class="bws_table_bg"></div>
															<ul class="tmsht_display_ts_report_page_for_users">
																<?php foreach ( $users_in_role as $user ) { ?>
																	<li><label><input type="checkbox" <?php echo esc_html( $checked_attr ); ?> disabled="disabled"><?php echo esc_html( $user->user_login ); ?></label></li>
																<?php } ?>
																<li><label><input type="checkbox" <?php echo esc_html( $checked_attr ); ?> disabled="disabled">...</label></li>
															</ul>
														</div>
														<?php $this->bws_pro_block_links(); ?>
													</div>
												</div>
											<?php } ?>
										</li>
										<?php
									}
								}
								?>
							</ul>
						</div>
					</td>
				</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'timesheet' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th><?php esc_html_e( 'Edit Team', 'timesheet' ); ?></th>
								<td>
									<div id="tmsht_display_ts_report_page_for_wrap">
										<ul id="tmsht_display_ts_report_page_for">
											<?php
											foreach ( $this->all_roles as $role => $details ) {
												if ( count( $users_in_role ) > 0 ) {
													?>
													<li>
														<label>
															<input disabled="disabled" type="checkbox" name="tmsht_display_ts_report_page_for[]" value="<?php echo esc_attr( $role ); ?>" > 
															<?php echo esc_html( translate_user_role( $details['name'] ) ); ?>
														</label>
														<div id="tmsht_display_ts_report_page_for_users_wrap">
															<ul class="tmsht_display_ts_report_page_for_users">
																<?php foreach ( $users_in_role as $user ) { ?>
																	<li><label><input type="checkbox" disabled="disabled"><?php echo esc_attr( $user->user_login ); ?></label></li>
																<?php } ?>
																<li><label><input type="checkbox" disabled="disabled">...</label></li>
															</ul>
														</div>
													</li>
													<?php
												}
											}
											?>
										</ul>
									</div>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>
			<?php
		}

		/**
		 * Displays 'reports' menu-tab
		 *
		 * @access public
		 */
		public function tab_reports() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Reports Settings', 'timesheet' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<div class="updated bws-notice inline">
				<p><strong><?php esc_html_e( 'Note', 'timesheet' ); ?>:</strong> <?php esc_html_e( 'This is your personal reports settings.', 'timesheet' ); ?></p>
			</div>
			<div class="bws_pro_version_bloc">
				<div class="bws_pro_version_table_bloc">
					<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'timesheet' ); ?>"></button>
					<div class="bws_table_bg"></div>
					<table class="form-table bws_pro_version">
						<tr>
							<th><?php esc_html_e( 'Email Reports', 'timesheet' ); ?></th>
							<td>
								<label>
									<input disabled="disabled" type="checkbox" name="tmsht_send_email_report" value="1" /> <span class="bws_info"><?php esc_html_e( 'Enable to receive reports by email.', 'timesheet' ); ?></span>
								</label>
							</td>
						</tr>
						<tr>
							<th></th>
							<td>
								<span><?php echo esc_html_x( 'Every', 'email reminder', 'timesheet' ); ?>&nbsp;</span>
								<select disabled="disabled" name="tmsht_email_report_day">
									<?php foreach ( $this->days_arr as $day ) { ?>
										<option value="<?php echo esc_attr( strtolower( $day ) ); ?>"> <?php echo esc_html( $day ); ?></option>
									<?php } ?>
								</select>
								<span>&nbsp;<?php echo esc_html_x( 'at', 'email reminder', 'timesheet' ); ?>&nbsp;</span>
								<input disabled="disabled" class="small-text" type="text" name="tmsht_email_report_time" maxlength="5" value="18:00">
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Include User Timesheets', 'timesheet' ); ?></th>
							<td>
								<input disabled="disabled" type="text" placeholder="<?php esc_html_e( 'Search user', 'timesheet' ); ?>">
							</td>
						</tr>
					</table>
				</div>
				<?php $this->bws_pro_block_links(); ?>
			</div>

			<?php
		}

		/**
		 * Displays 'import_export' menu-tab
		 *
		 * @access public
		 */
		public function tab_import_export() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Import/Export', 'timesheet' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<div class="bws_pro_version_bloc">
				<div class="bws_pro_version_table_bloc">
					<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'timesheet' ); ?>"></button>
					<div class="bws_table_bg"></div>
					<table class="form-table bws_pro_version">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Export Data', 'timesheet' ); ?></th>
							<td>
								<fieldset>
									<label><input disabled="disabled" type="radio" name="tmsht_format_export" value="csv" checked="checked" /><?php esc_html_e( 'CSV file format', 'timesheet' ); ?></label><br />
									<label><input disabled="disabled" type="radio" name="tmsht_format_export" value="xml" /><?php esc_html_e( 'XML file format', 'timesheet' ); ?></label><br />
								</fieldset>
								<input disabled="disabled" type="submit" name="tmsht_export_submit" class="button" value="<?php esc_html_e( 'Export', 'timesheet' ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Import Data', 'timesheet' ); ?></th>
							<td>
								<fieldset>
									<label><input disabled="disabled" type="radio" name="tmsht_method_insert" value="current_change" checked="checked" /><?php esc_html_e( 'Add and replace current data', 'timesheet' ); ?></label><br />
									<label><input disabled="disabled" type="radio" name="tmsht_method_insert" value="clear_data" /><?php esc_html_e( 'Clear old and add new data', 'timesheet' ); ?> </label><br />
									<label><input disabled="disabled" type="radio" name="tmsht_method_insert" value="missing_exists" /><?php esc_html_e( 'Add missing data', 'timesheet' ); ?></label><br />
								</fieldset>
								<label><input disabled="disabled" name="tmsht_import_file_upload" type="file" /></label><br />
								<input  disabled="disabled"type="submit" name="tmsht_import_submit" class="button" value="<?php esc_html_e( 'Import', 'timesheet' ); ?>" />
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Export Button', 'timesheet' ); ?></th>
							<td>
								<input type="checkbox" disabled="disabled" name="tmsht_export_button" value="1" checked="checked" /> <span class="bws_info"><?php esc_html_e( 'Enable to display an export button in "My Availability".', 'timesheet' ); ?></span>
							</td>
						</tr>
						<<tr>
							<th><?php esc_html_e( 'Export File Format', 'timesheet-pro' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="radio" disabled="disabled" name="tmsht_file_format_type" value="csv" checked="checked"><?php esc_html_e( 'CSV', 'timesheet' ); ?>
									</label>
									<br />
									<label>
										<input type="radio" disabled="disabled" name="tmsht_file_format_type" value="pdf"><?php esc_html_e( 'PDF', 'timesheet' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
					</table>
				</div>
				<?php $this->bws_pro_block_links(); ?>
			</div>
			<?php
		}

		/**
		 * Displays 'Clear Timesheet'
		 *
		 * @access public
		 */
		public function additional_misc_options_affected() {
			?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Clear Timesheet', 'timesheet' ); ?></th>
				<td>
					<?php esc_html_e( 'Every', 'timesheet' ); ?>
					<select name="tmsht_clear_timesheet_period">
						<option value=""> - </option>
						<?php foreach ( $this->period_arr as $period ) { ?>
							<option value="<?php echo esc_attr( $period ); ?>" <?php selected( $period, $this->options['clear_timesheet_period'] ); ?>> <?php echo esc_html( $period ); ?></option>
						<?php } ?>
					</select>
					<div class="bws_info"><?php esc_html_e( 'This will clear users timesheet.', 'timesheet' ); ?></div>
				</td>
			</tr>
			<?php
		}

		/**
		 * Displays metabox 'Timesheet Shortcode'
		 *
		 * @access public
		 */
		public function display_metabox() {
			if ( ! $this->hide_pro_tabs ) {
				?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'timesheet' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<div class="postbox">
							<h3 class="hndle">
								<?php esc_html_e( 'Timesheet Shortcode', 'timesheet' ); ?>
							</h3>
							<div class="inside">
								<?php esc_html_e( 'Add the "My Availability" to your pages or posts using the following shortcode:', 'timesheet' ); ?>
								<?php bws_shortcode_output( '[bws_timesheet_user]' ); ?>
							</div>
							<div class="inside">
							<?php esc_html_e( 'Add the "Team" to your pages or posts using the following shortcode:', 'timesheet' ); ?>
								<?php bws_shortcode_output( '[bws_timesheet_report]' ); ?>
							</div>
						</div>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
				<?php
			}
		}

		/**
		 * Custom functions for "Restore plugin options to defaults"
		 *
		 * @access public
		 * @param array $default_options Default options.
		 * @return array $default_options
		 */
		public function additional_restore_options( $default_options ) {
			global $wpdb;
			$wpdb->query( 'DROP TABLE `' . $wpdb->prefix . 'tmsht_legends`' );
			tmsht_create_tables();
			return $default_options;
		}
	}
}
