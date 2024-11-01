(function( $ ){
	$( document ).ready( function () {

		/* Ajax update table */
		function tmsht_ajax_update_report_table() {
			var tmsht_ts_report_user = [];

			$( ".tmsht_ts_report_user_label input:checked" ).each( function () {
				if ( $( this ).val() == - 1 ) {
					return true;
				} else {
					tmsht_ts_report_user[tmsht_ts_report_user.length] = $( this ).val();
				}
			} );

			$.ajax( {
				type: "POST",
				url: ajaxurl,
				data: {
					page: 'timesheet_ts_report',
					action: 'tmsht_ts_update_report_table',
					tmsht_ts_report_date_to: $( "#tmsht_ts_report_date_to" ).val(),
					tmsht_ts_report_date_from: $( "#tmsht_ts_report_date_from" ).val(),
					tmsht_date_filter_type: $( "input[name='tmsht_date_filter_type']:checked" ).val(),
					tmsht_date_preset_quantity: $( "#tmsht_date_preset_quantity" ).val(),
					tmsht_date_preset_unit: $( "#tmsht_date_preset_unit" ).val(),
					tmsht_generate_ts_report: 'Apply',
					tmsht_ts_report_group_by: $( "input[name='tmsht_ts_report_group_by']:checked" ).val(),
					tmsht_ts_report_legend: $( "input[name='tmsht_ts_report_legend']:checked" ).val(),
					tmsht_ts_report_view: $( "input[name='tmsht_ts_report_view']:checked" ).val(),
					tmsht_ts_report_user: tmsht_ts_report_user
				},
				beforeSend: function () {
					$( '.tmsht_ts_report_generate' ).addClass( 'tmsht_ts_report_loading' );
				},
				success: function ( data ) {
					$( "#tmsht_ts_report_table" ).html( data );

					if ( $( '#tmsht_ts_report_table strong' ).text() == 'No data to view.' ) {
						$( '#tmsht_ts_report_table' ).addClass( "tmsht_ts_report_table_nodata" );
					} else {
						$( '#tmsht_ts_report_table' ).removeClass( "tmsht_ts_report_table_nodata" );
					}
					$( '#tmsht_ts_report_table' ).removeClass( 'tmsht_ts_report_table_no_data' );
					$( '#tmsht_ts_report_table' ).addClass( 'tmsht_ts_report_table_has_data' );
					tmsht_ajax_td_helper();
					$( '.tmsht_ts_report_generate' ).removeClass( 'tmsht_ts_report_loading' );
				}
			} );
		}

		function tmsht_ajax_update_report_users() {

			var tmsht_ts_report_user = [];

			$( ".tmsht_ts_report_user_label input:checked" ).each( function () {
				if ( $( this ).val() == - 1 ) {
					return true;
				} else {
					tmsht_ts_report_user[tmsht_ts_report_user.length] = $( this ).val();
				}
			} );

			$.ajax( {
				type: "POST",
				url: ajaxurl,
				data: {
					page: 'timesheet_ts_report',
					action: 'tmsht_ts_update_report_users',
					tmsht_ts_report_user: tmsht_ts_report_user,
					tmsht_generate_ts_report: 'Apply',
					tmsht_ts_report_group_by: $( "input[name='tmsht_ts_report_group_by']:checked" ).val(),
					tmsht_ts_report_legend: $( "input[name='tmsht_ts_report_legend']:checked" ).val(),
					tmsht_ts_report_view: $( "input[name='tmsht_ts_report_view']:checked" ).val()
				},
				beforeSend: function () {
					$( '.tmsht_ts_report_generate' ).addClass( 'loading' );
				},
				success: function ( data ) {
					$( ".tmsht_ts_report_selected_users_container" ).html( data );
					$( '.tmsht_ts_report_generate' ).removeClass( 'loading' );
				}
			} );
		}

		/* Events for ajax requests */
		$( "input[name='tmsht_date_filter_type']" ).on( 'change', function () {
			$( "input[name='tmsht_date_filter_type'][checked='checked']" ).removeAttr( "checked" );
			$( "input[name='tmsht_date_filter_type']:checked" ).attr( 'checked', 'checked' );
			tmsht_ajax_update_report_table();
		} );
		$( "form #tmsht_date_preset_quantity" ).on( 'change', function () {
			tmsht_ajax_update_report_table();
		} );
		$( "form #tmsht_date_preset_unit" ).on( 'change', function () {
			tmsht_ajax_update_report_table();
		} );
		$( "input[name='tmsht_ts_report_group_by']" ).on( 'change', function () {
			$( "input[name='tmsht_ts_report_group_by'][checked='checked']" ).removeAttr( "checked" );
			$( "input[name='tmsht_ts_report_group_by']:checked" ).attr( 'checked', 'checked' );
			tmsht_ajax_update_report_table();
		} );
		$( "input[name='tmsht_ts_report_legend']" ).on( 'change', function () {
			$( "input[name='tmsht_ts_report_legend'][checked='checked']" ).removeAttr( "checked" );
			$( "input[name='tmsht_ts_report_legend']:checked" ).attr( 'checked', 'checked' );
			tmsht_ajax_update_report_table();
		} );
		$( "input[name='tmsht_ts_report_view']" ).on( 'change', function () {
			$( "input[name='tmsht_ts_report_view'][checked='checked']" ).removeAttr( "checked" );
			$( "input[name='tmsht_ts_report_view']:checked" ).attr( 'checked', 'checked' );
			tmsht_ajax_update_report_table();
		} );

		/* Start user selector */
		$( 'noscript.tmsht_ts_report_user_list_container_noscript' ).before( $( 'noscript.tmsht_ts_report_user_list_container_noscript' ).text() );

		$( '.tmsht_ts_report_user_list' ).addClass( 'tmsht_ts_report_user_list_closed' );

		$( '.tmsht_ts_report_search_user' ).on( 'keyup', function () {
			var value = $( this ).val(),
				$checkbox_all = $( '.tmsht_ts_report_user_checkbox_all' );

			$( '.tmsht_ts_report_user' ).show();

			if ( value.length > 0 ) {
				$( '.tmsht_ts_report_user' ).not( '[data-username^="' + value + '"]' ).hide();
			}

			if ( $( '.tmsht_ts_report_user:visible' ).length == 0 ) {
				$( '.tmsht_ts_report_user_search_results' ).removeClass( 'hidden' );
				$checkbox_all.attr( 'disabled', true );
				$checkbox_all.parent().css( 'opacity', 0.7 );
			} else {
				$( '.tmsht_ts_report_user_search_results' ).addClass( 'hidden' );
				$checkbox_all.attr( 'disabled', false )
				$checkbox_all.parent().css( 'opacity', 1 );
			}

			var $checkboxes = $( '.tmsht_ts_report_user_checkbox:visible' ),
				$checkboxes_checked = $checkboxes.filter( ':visible:checked' );

			if ( $checkboxes.length != $checkboxes_checked.length || $checkboxes.length == 0 ) {
				$checkbox_all.attr( 'checked', false );
			} else {
				$checkbox_all.attr( 'checked', true );
			}

		} ).on( 'focus', function () {
			$( '.tmsht_ts_report_user_list' )
				.removeClass( 'tmsht_ts_report_user_list_closed' )
				.addClass( 'tmsht_ts_report_user_list_opened' );
		} );

		$( document ).on( 'click', function ( e ) {
			if ( $( e.target ).closest( '.tmsht_ts_report_search_user, .tmsht_ts_report_user_list_container' ).length ) {
				return;
			}

			$( '.tmsht_ts_report_user_list' )
				.removeClass( 'tmsht_ts_report_user_list_opened' )
				.addClass( 'tmsht_ts_report_user_list_closed' );
		} );

		$( '.tmsht_ts_report_user_checkbox_all' ).on( 'change', function () {
			var $this = $( this );

			$( '.tmsht_ts_report_user_checkbox:visible' ).attr( 'checked', function () {
				return $this.is( ':checked' );
			} ).trigger( 'count' );
			tmsht_ajax_update_report_table();
			tmsht_ajax_update_report_users();
		} );

		$( '.tmsht_ts_report_user_checkbox' ).on( 'change', function () {
			var filter = $( '.tmsht_ts_report_user_list' ).hasClass( 'tmsht_ts_report_user_list_closed' ) ? '' : ':visible',
				$checkboxes = $( '.tmsht_ts_report_user_checkbox' + filter ),
				$checkboxes_checked = $checkboxes.filter( filter + ':checked' ),
				$checkbox_all = $( '.tmsht_ts_report_user_checkbox_all' );
			$( this ).trigger( 'count' );

			$checkbox_all.attr( 'checked', function () {
				return $checkboxes.length == $checkboxes_checked.length;
			} );

			tmsht_ajax_update_report_table();
			tmsht_ajax_update_report_users();

		} ).on( 'count', function () {
			var $checkboxes = $( '.tmsht_ts_report_user_checkbox' ),
				$checkboxes_checked = $checkboxes.filter( ':checked' ),
				$users_container = $( '.tmsht_ts_report_selected_users_container' );

			$users_container.empty();
			$checkboxes_checked.each( function () {
				var $selected_user = $( '<span/>', {
					'id': 'tmsht_ts_report_user_selected_' + $( this ).val(),
					'class': 'tmsht_ts_report_user_selected',
					'html': $( this ).parent().text() + '<label class="tmsht_ts_report_user_uncheck" for="tmsht_ts_report_user_id_' + $( this ).val() + '"></label>'
				} );

				$users_container.append( $selected_user );
			} );

			$users_container.append( '<div class="tmsht_clearfix"></div>' );
		} );
		/* end User selector */

		if ( $( 'body' ).hasClass( 'timesheet_page_timesheet_ts_report' ) ) {

			var is_rtl = $( 'body' ).hasClass( 'rtl' );

			/* Users filter position */
			$( window ).on( 'resize', function () {
				var filters_width = 0;
				$( '.tmsht_ts_report_filter_item' ).each( function () {
					var $filter_item = $( this );
					filters_width += $filter_item.innerWidth();
				} );

				if ( $( '.tmsht_ts_report_filter' ).width() > filters_width ) {
					if ( ! is_rtl ) {
						$( '.tmsht_ts_report_filter_item_user' ).css( 'float', 'right' );
					} else {
						$( '.tmsht_ts_report_filter_item_user' ).css( 'float', 'left' );
					}
				} else {
					if ( ! is_rtl ) {
						$( '.tmsht_ts_report_filter_item_user' ).css( 'float', 'left' );
					} else {
						$( '.tmsht_ts_report_filter_item_user' ).css( 'float', 'right' );
					}
				}
			} ).trigger( 'resize' );

			/* Date picker */
			tmsht_datetime_options = tmsht_datetime_options || {};

			tmsht_datetime_options = $.extend( {
				'timepicker': false,
				'format': (
					Boolean( tmsht_datetime_options['timepicker'] )
				) ? 'Y-m-d H:s' : 'Y-m-d',
				'closeOnDateSelect': true,
				'scrollInput': false,
				'onSelectDate': function ( $dtp, current, input ) {
					var input_id = current.attr( 'id' ),
						date_target = (
							input_id == 'tmsht_ts_report_date_from'
						) ? 'date_from' : 'date_to',
						$input_date_from = $( '#tmsht_ts_report_date_from' ),
						$input_date_to = $( '#tmsht_ts_report_date_to' );

					if ( date_target == 'date_from' && $input_date_from.val() > $input_date_to.val() ) {
						$input_date_to.val( $input_date_from.val() );
					}

					if ( date_target == 'date_to' && $input_date_from.val() > $input_date_to.val() ) {
						$input_date_from.val( $input_date_to.val() );
					}
					/* Function update report table */
					tmsht_ajax_update_report_table();
				}
			}, tmsht_datetime_options );

			$.datetimepicker.setLocale( tmsht_datetime_options['locale'] );

			$( ".tmsht_date_datepicker_input" ).datetimepicker( tmsht_datetime_options );

			$( ".tmsht_date_datepicker_input" ).on( 'click touchstart', function ( e ) {
				$( this ).datetimepicker( 'show' );
			} ).on( 'keydown', function () {
				return false;
			} );
			/* Date picker */

			/* Date filter */
			$( '.tmsht_ts_report_filter_item_datepicker input[type="text"], .tmsht_ts_report_filter_item_datepicker select' ).on( 'focus', function () {
				var filter_type = $( this ).parents( 'td' ).attr( 'data-filter-type' );
				$( '.tmsht_ts_report_filter_item_datepicker input[value="' + filter_type + '"]' ).attr( 'checked', true );
			} );

			$( '.tmsht_ts_report_legend' ).on( 'change', function () {
				if ( '-2' == $( '.tmsht_ts_report_legend:checked' ).val() ) {
					$( '.tmsht_ts_report_view_filter' ).addClass( 'hidden' );
				} else {
					$( '.tmsht_ts_report_view_filter' ).removeClass( 'hidden' );
				}
			} );

			/* start td helper */
			if ( ! $( 'body' ).hasClass( 'mobile' ) ) {
				$( '.tmsht_ts_report_table_has_data td.tmsht_ts_report_table_td_time' ).on( 'mouseenter', function () {
					var $td = $( this ),
						td_index = $( this ).attr( 'data-td-index' );

					$( '.tmsht_ts_report_table_has_data tr:hover .tmsht_ts_report_table_td_helper' ).addClass( 'tmsht_ts_report_table_td_helper_hover' );
					$( '.tmsht_ts_report_table_has_data tr .tmsht_ts_report_table_td_helper_' + td_index ).addClass( 'tmsht_ts_report_table_td_helper_hover' );

				} ).on( 'mouseleave', function () {
					$( '#tmsht_ts_report_table tbody tr td.tmsht_ts_report_table_td_time' ).find( '.tmsht_ts_report_table_td_helper_hover' ).removeClass( 'tmsht_ts_report_table_td_helper_hover' );
				} );
			}
			/* end td helper */

			/* Refresh td helper after ajax request */
			function tmsht_ajax_td_helper() {
				if ( ! $( 'body' ).hasClass( 'mobile' ) ) {
					$( '.tmsht_ts_report_table_has_data td.tmsht_ts_report_table_td_time' ).on( 'mouseenter', function () {
						var $td = $( this ),
							td_index = $( this ).attr( 'data-td-index' );

						$( '.tmsht_ts_report_table_has_data tr:hover .tmsht_ts_report_table_td_helper' ).addClass( 'tmsht_ts_report_table_td_helper_hover' );
						$( '.tmsht_ts_report_table_has_data tr .tmsht_ts_report_table_td_helper_' + td_index ).addClass( 'tmsht_ts_report_table_td_helper_hover' );

					} ).on( 'mouseleave', function () {
						$( '#tmsht_ts_report_table tbody tr td.tmsht_ts_report_table_td_time' ).find( '.tmsht_ts_report_table_td_helper_hover' ).removeClass( 'tmsht_ts_report_table_td_helper_hover' );
					} );
				}
			}
		}
	} );
})(jQuery);