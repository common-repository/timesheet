(function($){
	$( document ).ready( function () {

		/* Ajax update table */
		function tmsht_ajax_update_timesheet_table() {
            window.history.replaceState( '', '', location.origin + location.pathname + '?page=timesheet_ts_user' + '&tmsht_ts_user_date_from=' + $( '#tmsht_ts_user_date_from' ).val() + '&tmsht_ts_user_date_to=' + $( '#tmsht_ts_user_date_to' ).val() );
			$.ajax( {
				type: "GET",
				url: ajaxurl,
				data: {
					page: 'timesheet_ts_user',
					action: 'tmsht_ts_update_table',
					tmsht_ts_user_date_to: $( "#tmsht_ts_user_date_to" ).val(),
					tmsht_ts_user_date_from: $( "#tmsht_ts_user_date_from" ).val()
				},
				beforeSend: function () {
					$( '.tmsht_ts_user_change_dates' ).addClass( 'tmsht_ts_user_loading' );
				},
				success: function ( data ) {
					$( "#tmsht_ts_user_table tbody" ).html( data );
					tmsht_ts_user_table_selection_refresh();
					$( '.tmsht_ts_user_change_dates' ).removeClass( 'tmsht_ts_user_loading' );
				}
			} );

			$.ajax( {
				type: "GET",
				url: ajaxurl,
				data: {
					page: 'timesheet_ts_user',
					action: 'tmsht_ts_update_advanced_container',
					tmsht_ts_user_date_to: $( "#tmsht_ts_user_date_to" ).val(),
					tmsht_ts_user_date_from: $( "#tmsht_ts_user_date_from" ).val()
				},
				beforeSend: function () {
					$( '.tmsht_ts_user_change_dates' ).addClass( 'tmsht_ts_user_loading' );
				},
				success: function ( data ) {
					$( "#tmsht_ts_user_advanced_container" ).html( data );
					$( '#tmsht_ts_user_table' ).tmsht_ts_user_table_handler();
					$( '.tmsht_ts_user_change_dates' ).removeClass( 'tmsht_ts_user_loading' );
				}
			} );
		}

		var get_legend = function ( legend_id ) {
				var legend_id = legend_id || false,
					$ts_legend = $( '.tmsht_ts_user_legend' ),
					$ts_legend_option = null;

				if ( legend_id !== false ) {
					$ts_legend_option = $ts_legend.find( 'option[value="' + legend_id + '"]' );
				} else {
					$ts_legend_option = $ts_legend.find( 'option:selected' );
				}

				return {
					'id': $ts_legend_option.val(),
					'title': $ts_legend_option.text(),
					'color': $ts_legend_option.attr( 'data-color' ),
					'all_day': $ts_legend_option.attr( 'data-all-day' )
				}
			},
			is_rtl = $( 'body' ).hasClass( 'rtl' );

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
						input_id == 'tmsht_ts_user_date_from'
					) ? 'date_from' : 'date_to',
					$input_date_from = $( '#tmsht_ts_user_date_from' ),
					$input_date_to = $( '#tmsht_ts_user_date_to' );

				if ( date_target == 'date_from' && $input_date_from.val() > $input_date_to.val() ) {
					$input_date_to.val( $input_date_from.val() );
				}

				if ( date_target == 'date_to' && $input_date_from.val() > $input_date_to.val() ) {
					$input_date_from.val( $input_date_to.val() );
				}
				/* Hide notices information block */
				if ( $( '.below-h2' ).is( ":visible" ) ) {
					$( ".below-h2" ).hide();
				}

				/* Function for update timesheet table */
				if ( $( '#tmsht_ts_user_table' ).hasClass( 'tmsht_ts_user_table_head_dateline' ) ) {
					transposition_table();
					tmsht_ajax_update_timesheet_table();
				} else {
					tmsht_ajax_update_timesheet_table();
				}
			}
		}, tmsht_datetime_options );

		$.datetimepicker.setLocale( tmsht_datetime_options['locale'] );

		$( ".tmsht_date_datepicker_input" ).datetimepicker( tmsht_datetime_options );

		$( ".tmsht_date_datepicker_input" ).on( 'click touchstart', function () {
			$( this ).datetimepicker( 'show' );
		} ).on( 'keydown', function () {
			return false;
		} );

		/* Fake selector legends */
		$( '.tmsht_ts_user_legend' ).tmsht_ts_user_select_legend();

		/* Show details table */
		$( '#tmsht_ts_user_table' ).tmsht_ts_user_table_handler();

		$( '.tmsht_save_anchor' ).on( "click", function ( event ) {
			event.preventDefault();
			$( '#tmsht_save_ts_button' ).click();
		} );

		/* On preparation transposition data */
		$( '#tmsht_ts_user_table' ).on( 'set_transposition_data', function () {
			var $tbl = $( '#tmsht_ts_user_table' ),
				$trs = $tbl.find( 'tbody tr' );
			data = {
				'current_date': 'undefined',
				'dates': {}
			}

			$trs.each( function ( index ) {
				var $tr = $( this ),
					tr_date = $tr.attr( 'data-tr-date' );

				if ( $tr.hasClass( 'tmsht_ts_user_table_tr_today' ) ) {
					data.current_date = tr_date;
				}
				data.dates[index] = tr_date;
			} );

			$tbl.data( 'transposition_data', data );

		} ).trigger( 'set_transposition_data' );

		/* On transposition button click */
		$( '#tmsht_transposition_tbl' ).on( 'click', function ( e ) {

			/*hide notices information block*/
			if ( $( '.below-h2' ).is( ":visible" ) ) {
				$( ".below-h2" ).hide();
			}

			var $table = $( '#tmsht_ts_user_table' ),
				count = $table.find( 'tr:first td' ).length - 1,
				trs_data = $table.data( 'transposition_data' ),
				data = {};

			for ( $i = 0; $i <= count; $i ++ ) {
				data[$i] = $table.find( 'thead, tbody' ).children().find( 'td:eq(' + $i + ')' );
			}

			$table.find( 'thead, tbody, tfoot' ).empty();

			for ( $i = 0; $i <= count; $i ++ ) {
				if ( $i == 0 ) {
					$table.find( 'thead' ).append( $( '<tr/>' ).append( data[$i] ) );
				} else {
					$table.find( 'tbody' ).append( $( '<tr/>' ).append( data[$i] ) );
				}
			}

			$tfoot = $table.find( 'thead tr' ).clone();
			$tfoot.find( 'input' ).remove();
			$table.find( 'tfoot' ).append( $tfoot );

			$table.attr( 'class', function () {
				var classes = $( this ).attr( 'class' );

				if ( $( this ).hasClass( 'tmsht_ts_user_table_head_timeline' ) ) {
					return classes.replace( 'tmsht_ts_user_table_head_timeline', 'tmsht_ts_user_table_head_dateline' );
				} else {
					return classes.replace( 'tmsht_ts_user_table_head_dateline', 'tmsht_ts_user_table_head_timeline' );
				}
			} );

			if ( $table.is( '.tmsht_ts_user_table_head_timeline' ) ) {
				$table.find( 'tbody tr' ).each( function ( index ) {
					var $tr = $( this );

					$tr.addClass( 'tmsht_ts_user_table_tr' )
					$tr.attr( 'data-tr-date', trs_data.dates[index] );

					if ( trs_data.dates[index] == trs_data.current_date ) {
						$tr.addClass( 'tmsht_ts_user_table_tr_today' );
					}

				} );
			} else if ( $table.is( '.tmsht_ts_user_table_head_dateline' ) ) {
				var $div = $table.find( 'thead tr td.tmsht_ts_user_table_td_dateline .tmsht_ts_user_table_highlight_today' ),
					index = $div.parent().index(),
					$tds = $table.find( 'tr' ).find( 'td:eq(' + index + ')' ),
					$tds_prev = (
						! is_rtl
					) ? $tds.prev() : $tds.next();

				$tds.addClass( 'tmsht_ts_user_table_td_highlight_today_right_border' );
				$tds_prev.addClass( 'tmsht_ts_user_table_td_highlight_today_right_border' );
			}

			$( '#tmsht_ts_user_table' ).trigger( 'selection' );
			e.preventDefault();
			return false;
		} );

		/* Function transposition table after ajax request */
		function transposition_table() {

			/* Hide notices information block */
			if ( $( '.below-h2' ).is( ":visible" ) ) {
				$( ".below-h2" ).hide();
			}

			var $table = $( '#tmsht_ts_user_table' ),
				count = $table.find( 'tr:first td' ).length - 1,
				trs_data = $table.data( 'transposition_data' ),
				data = {};

			for ( $i = 0; $i <= count; $i ++ ) {
				data[$i] = $table.find( 'thead, tbody' ).children().find( 'td:eq(' + $i + ')' );
			}

			$table.find( 'thead, tbody, tfoot' ).empty();

			for ( $i = 0; $i <= count; $i ++ ) {
				if ( $i == 0 ) {
					$table.find( 'thead' ).append( $( '<tr/>' ).append( data[$i] ) );
				} else {
					$table.find( 'tbody' ).append( $( '<tr/>' ).append( data[$i] ) );
				}
			}

			$tfoot = $table.find( 'thead tr' ).clone();
			$tfoot.find( 'input' ).remove();
			$table.find( 'tfoot' ).append( $tfoot );

			$table.attr( 'class', function () {
				var classes = $( this ).attr( 'class' );

				if ( $( this ).hasClass( 'tmsht_ts_user_table_head_timeline' ) ) {
					return classes.replace( 'tmsht_ts_user_table_head_timeline', 'tmsht_ts_user_table_head_dateline' );
				} else {
					return classes.replace( 'tmsht_ts_user_table_head_dateline', 'tmsht_ts_user_table_head_timeline' );
					;
				}
			} );

			if ( $table.is( '.tmsht_ts_user_table_head_timeline' ) ) {
				$table.find( 'tbody tr' ).each( function ( index ) {
					var $tr = $( this );

					$tr.addClass( 'tmsht_ts_user_table_tr' )
					$tr.attr( 'data-tr-date', trs_data.dates[index] );

					if ( trs_data.dates[index] == trs_data.current_date ) {
						$tr.addClass( 'tmsht_ts_user_table_tr_today' );
					}

				} );
			} else if ( $table.is( '.tmsht_ts_user_table_head_dateline' ) ) {
				var $div = $table.find( 'thead tr td.tmsht_ts_user_table_td_dateline .tmsht_ts_user_table_highlight_today' ),
					index = $div.parent().index(),
					$tds = $table.find( 'tr' ).find( 'td:eq(' + index + ')' ),
					$tds_prev = (
						! is_rtl
					) ? $tds.prev() : $tds.next();

				$tds.addClass( 'tmsht_ts_user_table_td_highlight_today_right_border' );
				$tds_prev.addClass( 'tmsht_ts_user_table_td_highlight_today_right_border' );
			}

			$( '#tmsht_ts_user_table' ).trigger( 'selection' );
			return false;
		}

		/***********************************************************
		 * Actions with TS table
		 ***********************************************************/

		/* Show select area in TS table */
		$( '#tmsht_ts_user_table' ).on( 'selection', function () {
			var $tbl = $( this ),
				$trs = $tbl.find( 'tbody tr' ).has( '.tmsht_ts_user_table_td_highlighted' ),
				$tds = $trs.find( '.tmsht_ts_user_table_td_highlighted' );

			if ( $tds.length > 0 ) {
				var $td_in_first_tr = (
						! is_rtl
					) ? $trs.filter( ':first' ).find( '.tmsht_ts_user_table_td_highlighted:first' ) : $trs.filter( ':first' ).find( '.tmsht_ts_user_table_td_highlighted:last' ),
					$tr_first = $td_in_first_tr.parent(),
					$tds_in_first_tr = $tr_first.find( '.tmsht_ts_user_table_td_highlighted' ),
					tr_index = $tr_first.index(),
					trs_count = $tds.length / $tds_in_first_tr.length,
					select_top = $td_in_first_tr.offset().top - $tbl.offset().top,
					select_left = $td_in_first_tr.offset().left - $tbl.offset().left,
					select_width = 0,
					select_height = 0,
					isWebkit = /(safari|chrome)/.test( navigator.userAgent.toLowerCase() );

				$tds_in_first_tr.each( function () {
					select_width += $( this ).outerWidth();
				} );

				for ( var i = tr_index; i <= tr_index + trs_count; i ++ ) {
					select_height += $tbl.find( 'tbody tr:eq(' + i + ') .tmsht_ts_user_table_td_highlighted:first' ).outerHeight();
				}

				$( '#tmsht_ts_user_table_selection:hidden' ).show();

				$( '#tmsht_ts_user_table_selection:visible' ).css( {
					'top': select_top,
					'left': select_left,
					'width': select_width + 1,
					'height': select_height + 1,
					'margin': ( isWebkit ) ? '' : '-1px 0 0 -1px'
				} );
			}
		} ).trigger( 'selection' );

		/* Refresh table_td_highlighted */
		function tmsht_ts_user_table_selection_refresh() {
			$( '#tmsht_ts_user_table' ).on( 'selection', function () {
				var $tbl = $( this ),
					$trs = $tbl.find( 'tbody tr' ).has( '.tmsht_ts_user_table_td_highlighted' ),
					$tds = $trs.find( '.tmsht_ts_user_table_td_highlighted' );

				if ( $tds.length > 0 ) {
					var $td_in_first_tr = (
							! is_rtl
						) ? $trs.filter( ':first' ).find( '.tmsht_ts_user_table_td_highlighted:first' ) : $trs.filter( ':first' ).find( '.tmsht_ts_user_table_td_highlighted:last' ),
						$tr_first = $td_in_first_tr.parent(),
						$tds_in_first_tr = $tr_first.find( '.tmsht_ts_user_table_td_highlighted' ),
						tr_index = $tr_first.index(),
						trs_count = $tds.length / $tds_in_first_tr.length,
						select_top = $td_in_first_tr.offset().top - $tbl.offset().top,
						select_left = $td_in_first_tr.offset().left - $tbl.offset().left,
						select_width = 0,
						select_height = 0,
						isWebkit = /(safari|chrome)/.test( navigator.userAgent.toLowerCase() );

					$tds_in_first_tr.each( function () {
						select_width += $( this ).outerWidth();
					} );

					for ( var i = tr_index; i <= tr_index + trs_count; i ++ ) {
						select_height += $tbl.find( 'tbody tr:eq(' + i + ') .tmsht_ts_user_table_td_highlighted:first' ).outerHeight();
					}

					$( '#tmsht_ts_user_table_selection:hidden' ).show();

					$( '#tmsht_ts_user_table_selection:visible' ).css( {
						'top': select_top,
						'left': select_left,
						'width': select_width + 1,
						'height': select_height + 1,
						'margin': ( isWebkit ) ? '' : '-1px 0 0 -1px'
					} );
				}
				$( '#tmsht_ts_user_table' ).trigger( 'check_availability' );
			} ).trigger( 'selection' );
		}

		/* Hide select area in TS table */
		$( '#tmsht_ts_user_table' ).on( 'deselection', function () {
			$( this ).find( 'tbody td.tmsht_ts_user_table_td_highlighted' ).removeClass( 'tmsht_ts_user_table_td_highlighted' );
			$( '#tmsht_ts_user_table_selection:visible' ).hide();
		} );

		/* Add selection event in TS table */
		$( '#tmsht_ts_user_table' ).on( 'add_event', function ( event, e ) {
			$( this ).data( 'selecting', e );
		} ).data( 'selecting', false );

		/* Apply status to selected cells in TS table */
		$( '#tmsht_ts_user_table' ).on( 'apply_status', function ( event, legend_id ) {
			var table = $( this ),
				$tds = table.find( '.tmsht_ts_user_table_td_highlighted' );

			if ( table.hasClass( 'tmsht_ts_user_table_head_timeline' ) ) {
				$tds.each( function () {

					var $td = $( this ),
						$tr = $td.parent(),
						$ts_user_table_td_fill = $td.find( '.tmsht_ts_user_table_td_fill' ),
						$td_all = $tr.find( '.tmsht_ts_user_table_td_fill' ),
						legend = get_legend( legend_id );

					if ( 1 == legend[ 'all_day' ] ) {
						$td_all
							.attr( 'data-all-day', legend[ 'all_day' ] )
							.attr( 'data-legend-id', legend.id )
							.css( 'background-color', legend.color )
							.removeAttr( 'data-td-group' )
							.removeAttr( 'title' );
					} else {
						if ( 1 == $td_all.attr( 'data-all-day' ) ) {
							$td_all
								.removeAttr( 'data-all-day' )
								.attr( 'data-legend-id', -1 )
								.css( 'background-color', 'transparent' );
							$ts_user_table_td_fill
								.attr( 'data-legend-id', legend.id )
								.css( 'background-color', legend.color )
								.removeAttr( 'data-td-group' )
								.removeAttr( 'title' );
						} else {
							$ts_user_table_td_fill
								.attr( 'data-legend-id', legend.id )
								.css( 'background-color', legend.color )
								.removeAttr( 'data-td-group' )
								.removeAttr( 'title' );
						}
					}
					$tr.find( '.tmsht_tr_date[disabled="disabled"]' ).attr( 'disabled', false );
				} );
			} else if ( table.hasClass( 'tmsht_ts_user_table_head_dateline' ) ) {
				$tds.each( function () {
					var $td = $( this ),
						$tr = $td.parent(),
						$ts_user_table_td_fill = $td.find( '.tmsht_ts_user_table_td_fill' ),
						$td_all = $tr.find( '.tmsht_ts_user_table_td_fill' ),
						legend = get_legend( legend_id );

					if ( 1 == legend[ 'all_day' ] ) {
						$td_all
							.attr( 'data-all-day', legend[ 'all_day' ] )
							.attr( 'data-legend-id', legend.id )
							.css( 'background-color', legend.color )
							.removeAttr( 'data-td-group' )
							.removeAttr( 'title' );
					} else {
						if ( 1 == $td_all.attr( 'data-all-day' ) ) {
							$td_all
								.removeAttr( 'data-all-day' )
								.attr( 'data-legend-id', -1 )
								.css( 'background-color', 'transparent' );
							$ts_user_table_td_fill
								.attr( 'data-legend-id', legend.id )
								.css( 'background-color', legend.color )
								.removeAttr( 'data-td-group' )
								.removeAttr( 'title' );
						} else {
							$ts_user_table_td_fill
								.attr( 'data-legend-id', legend.id )
								.css( 'background-color', legend.color )
								.removeAttr( 'data-td-group' )
								.removeAttr( 'title' );
						}
					}
					var data_td_date = $td.attr( 'data-td-date' );
					table.find( '.tmsht_tr_date[value="' + data_td_date + '"]' ).attr( 'disabled', false );
				} );
			}

			$( '#tmsht_ts_user_table' ).tmsht_ts_user_table_handler( 'show_details' );
			$( '.updated.fade:not(.bws_visible), .error:not(.bws_visible)' ).css( 'display', 'none' );
			$( '#tmsht_save_notice' ).show();
		} );

		/* Get mobile events in TS table */
		$( '#tmsht_ts_user_table tbody' ).on( 'touchstart', function ( event ) {
			$( this ).data( 'mobile_event', event );
		} ).on( 'touchmove', function ( event ) {
			$( this ).data( 'mobile_event', event );
		} ).on( 'touchend', function ( event ) {
			$( this ).data( 'mobile_event', event );
		} ).data( 'mobile_event', false );

		/* Select cells in TS table */
		$( '#tmsht_ts_user_table tbody' ).selectable( {
			filter: 'td',
			cancel: '.tmsht_ts_user_table_td_readonly',
			appendTo: '#tmsht_ts_user_table_area',
			start: function ( event ) {
				$( '.tmsht_select_legend' ).filter( '[data-status="open"]' ).trigger( 'select.close' );
				$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );
			},
			selecting: function ( event, ui ) {
				var $td = $( ui.selecting );

				if ( $td.hasClass( 'tmsht_ts_user_table_td_readonly' ) ) {
					return false;
				}

				if ( ! $( '#tmsht_ts_user_table' ).data( 'selecting' ) && ! $td.hasClass( 'tmsht_ts_user_table_td_dateline' ) ) {
					$( '#tmsht_ts_user_table' ).trigger( 'deselection' );
				}

				$( '#tmsht_ts_user_table' ).trigger( 'add_event', true );

				if ( $td.is( '.tmsht_ts_user_table_td_time' ) ) {
					$td.addClass( 'tmsht_ts_user_table_td_highlighted' );
				}
			},
			unselecting: function ( event, ui ) {

				var $td = $( ui.unselecting );

				if ( $td.hasClass( 'tmsht_ts_user_table_td_readonly' ) ) {
					return false;
				}

				if ( $td.is( '.tmsht_ts_user_table_td_time' ) ) {
					$td.removeClass( 'tmsht_ts_user_table_td_highlighted' );
				}
			},
			selected: function ( event, ui ) {
				$( ui.selected ).removeClass( 'ui-selected' );
			},
			stop: function ( event, ui ) {
				var mobile_event = $( '#tmsht_ts_user_table tbody' ).data( 'mobile_event' )

				if ( mobile_event.type == 'touchend' ) {
					$( '#tmsht_ts_user_context_menu' ).trigger( 'show_context_menu', mobile_event );
				}

				$( '#tmsht_ts_user_table' )
					.trigger( 'selection' )
					.trigger( 'add_event', false );
			}
		} );

		/***********************************************************
		 * Actions with context menu in TS table
		 ***********************************************************/

		/* On right click in TS table cell */
		$( '#tmsht_ts_user_table' ).on( 'contextmenu', '.tmsht_ts_user_table_td_time', function ( e ) {
			var $td = $( this );

			if ( ! $td.hasClass( 'tmsht_ts_user_table_td_readonly' ) ) {

				/* If clicked not in selected cell */
				if ( ! $td.hasClass( 'tmsht_ts_user_table_td_highlighted' ) ) {
					$( '#tmsht_ts_user_table' ).trigger( 'deselection' );
					$td.addClass( 'tmsht_ts_user_table_td_highlighted' );
					$( '#tmsht_ts_user_table' ).trigger( 'selection' )
				}

				if ( $td.is( '.tmsht_ts_user_table_td_highlighted' ) ) {
					$( '#tmsht_ts_user_context_menu' ).trigger( 'show_context_menu', e );
				} else {
					$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );
				}
			}
			e.preventDefault();
			return false;
		} );

		/* TS table context menu */
		$( '#tmsht_ts_user_context_menu' ).on( 'show_context_menu', function ( event, e ) {
			var $context_menu = $( this ),
				$wp_bar = $( '#wpadminbar' ),
				width = $context_menu.width(),
				ts_table_offset_left = parseInt( $( '#tmsht_ts_user_table' ).offset().left ),
				margin_left = 0,
				coorX = coorY = 0;

			if ( e.type == 'touchend' ) { /* mobile */
				var touch = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
				coorX = touch.clientX + window.scrollX;
				coorY = touch.clientY + window.scrollY;
			} else { /* desktop */
				coorX = e.pageX;
				coorY = e.pageY;
			}

			if ( $wp_bar.css( 'position' ) == 'fixed' ) {
				coorY = coorY - parseInt( $wp_bar.height() );
			}

			if ( ! is_rtl ) {
				var coorX_without_offset  = coorX;
				coorX = coorX - ts_table_offset_left;
				margin_left = (
					$( window ).width() > coorX_without_offset + width
				) ? 0 : - 1 * width;
			} else {
				margin_left = (
					coorX - width < 0
				) ? 0 : - 1 * width;
			}

			$context_menu
				.trigger( 'hide_context_menu' )
				.css( {
					'left': coorX,
					'top': coorY,
					'margin-left': margin_left + 4,
					'margin-top': '2px'
				} )
				.show( 100 )
				.attr( 'data-visible', 'true' );
		} ).on( 'hide_context_menu', function () {
			var $context_menu = $( this );

			$context_menu
				.hide()
				.attr( 'data-visible', 'false' );
		} );

		/* On select item in context menu in TS table */
		$( '.tmsht_ts_user_context_menu_item.tmsht_ts_user_context_menu_item_enabled' ).on( 'click', function () {
			var $context_menu_item = $( this ),
				action = $context_menu_item.attr( 'data-action' );

			switch ( action ) {
				case 'delete':
					$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );
					$( '#tmsht_ts_user_table' ).trigger( 'apply_status', - 1 );
					break;
				case 'apply_status':
					var legend_id = $context_menu_item.attr( 'data-legend-id' );
					$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );
					$( '#tmsht_ts_user_table' ).trigger( 'apply_status', legend_id );
					break
				default:
					break;
			}
		} );

		$( window ).on( 'resize', function () {
			$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );
			$( '#tmsht_ts_user_table' ).trigger( 'deselection' );
		} );

		$( document ).on( 'mouseup', function ( e ) {
			if ( e.button != 0 || $( e.target ).closest( '#tmsht_ts_user_context_menu' ).length ) {
				return;
			}

			$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );
		} );
	} );

	/* Handler fake legend */
	$.fn.tmsht_ts_user_select_legend = function ( target ) {

		var escapeHtml = function ( text ) {
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};

			return text.replace( /[&<>"']/g, function ( m ) {
				return map[m];
			} );
		}

		$( document ).on( 'mouseup', function ( e ) {
			if ( $( e.target ).closest( '.tmsht_select_legend' ).length ) {
				return;
			}

			$( '.tmsht_select_legend' ).filter( '[data-status="open"]' ).trigger( 'select.close' );
		} );

		return this.each( function ( select_index ) {
			if ( ! $( this ).is( 'select' ) ) {
				return;
			}

			var $this_select = $( this ),
				this_select_id = (
					$this_select.attr( 'id' )
				) ? 'id_' + $this_select.attr( 'id' ) + ' ' : '';

			target = target || select_index;

			$this_select.attr( 'data-target', 'tmsht_select_legend_' + target ).on( 'change', function () {
				var index = $( this ).find( 'option:selected' ).index(),
					color = $( this ).find( 'option:selected' ).data( 'color' ),
					name = escapeHtml( $( this ).find( 'option:selected' ).text() ),
					target = $( this ).data( 'target' ),
					$target_select = $( '.' + target ),
					$target_option = $target_select.find( '.tmsht_select_legend_option' ).eq( index );

				$target_select.find( '.tmsht_select_legend_label_color' ).css( 'background-color', color );
				$target_select.find( '.tmsht_select_legend_label_name' ).html( name );
			} );

			var $select = $( '<div/>', {
				'class': this_select_id + 'tmsht_select_legend tmsht_select_legend_' + target + ' tmsht_select_legend_hidden tmsht_unselectable',
				'data-status': 'close'
			} ).bind( 'select.open', function () {
				$( this ).trigger( 'select.close' );
				$( this ).removeClass( 'tmsht_select_legend_hidden' ).addClass( 'tmsht_select_legend_visible' );
				$( this ).find( '.tmsht_select_legend_arrow' ).removeClass( 'tmsht_select_legend_arrow_down' ).addClass( 'tmsht_select_legend_arrow_up' );
				$( this ).attr( 'data-status', 'open' );
			} ).bind( 'select.close', function () {
				$( '.tmsht_select_legend' ).filter( '[data-status="open"]' ).removeClass( 'tmsht_select_legend_visible' ).addClass( 'tmsht_select_legend_hidden' );
				$( '.tmsht_select_legend' ).filter( '[data-status="open"]' ).find( '.tmsht_select_legend_arrow' ).removeClass( 'tmsht_select_legend_arrow_up' ).addClass( 'tmsht_select_legend_arrow_down' );
				$( '.tmsht_select_legend' ).attr( 'data-status', 'close' );
			} ).on( 'click', function () {
				if ( $( this ).attr( 'data-status' ) == 'close' ) {
					$( this ).trigger( 'select.open' );
				} else {
					$( this ).trigger( 'select.close' );
				}
			} ).data( 'status', 'close' );

			var $display = $( '<div/>', {
				'class': 'tmsht_select_legend_display'
			} ).appendTo( $select );

			var $label = $( '<div/>', {
				'class': 'tmsht_select_legend_label',
			} ).appendTo( $display );

			var $label_name = $( '<div/>', {
				'class': 'tmsht_select_legend_label_name',
				'html': escapeHtml( $this_select.find( 'option:selected' ).text() )
			} ).appendTo( $label );

			var $label_color = $( '<div/>', {
				'class': 'tmsht_select_legend_label_color',
				'style': 'background-color: ' + $this_select.find( 'option:selected' ).data( 'color' )
			} ).appendTo( $label );

			var $arrow = $( '<span/>', {
				'class': 'tmsht_select_legend_arrow ' + 'tmsht_select_legend_arrow_down',
			} ).insertAfter( $label );

			var $options_wrap = $( '<ul/>', {
				'class': 'tmsht_select_legend_options_wrap'
			} ).insertAfter( $display );

			$this_select.find( 'option' ).each( function ( index_option ) {
				var $this_option = $( this );

				if ( index_option == 0 ) {
					return true;
				}

				$( '<li/>', {
					'class': 'tmsht_select_legend_option',
					'data-index': index_option,
					'data-id': $this_option.val(),
					'data-color': $this_option.data( 'color' ),
					'data-name': $this_option.text(),
					'title': $this_option.text(),
					'html': $( '<span class="tmsht_select_legend_option_label_color" style="background-color: ' + $this_option.data( 'color' ) + ';"></span><div class="tmsht_select_legend_option_label_name">' + escapeHtml( $this_option.text() ) + '</div>' )
				} ).on( 'mouseenter', function () {
					$( this ).addClass( 'tmsht_select_legend_option_hover' );
				} ).on( 'mouseleave', function () {
					$( this ).removeClass( 'tmsht_select_legend_option_hover' );
				} ).on( 'click', function () {
					var index = $( this ).data( 'index' ),
						legend_id = $( this ).data( 'id' );

					if ( $select.hasClass( 'id_tmsht_ts_user_legend' ) ) {
						$( '#tmsht_ts_user_table' ).trigger( 'apply_status', legend_id );
					}

				} ).appendTo( $options_wrap );
			} );

			$this_select.hide();
			$this_select.after( $select );
		} );
	};

	/* Handler TS table */
	$.fn.tmsht_ts_user_table_handler = (
		function ( method ) {
			var methods = {
				'init': function ( options ) {
					return this.each( function () {
						$( this ).tmsht_ts_user_table_handler( 'show_details' );
					} );
				},
				'show_details': function () {
					return this.each( function () {
						var $ts_table = $( this ),
							tbl_data = {},
							key = 0;

						$trs_date = $ts_table.find( '.tmsht_tr_date' );

						$trs_date.each( function () {

							var date = $( this ).val();

							if ( $ts_table.find( 'td.tmsht_ts_user_table_td_time[data-td-date="' + date + '"] .tmsht_ts_user_table_td_fill[data-legend-id!="-1"]' ).length == 0 ) {
								return true;
							}

							var $tds_fill = $ts_table.find( '.tmsht_ts_user_table_td_time[data-td-date="' + date + '"] .tmsht_ts_user_table_td_fill' );

							$tds_fill.each( function ( index, elem ) {
								var $td_fill = $( elem ),
									legend_id = $td_fill.attr( 'data-legend-id' );
								next_legend_id = $tds_fill.eq( index + 1 ).attr( 'data-legend-id' );

								$td_fill.removeAttr( 'title' );

								if ( legend_id < 0 ) {
									return true;
								}

								tbl_data[legend_id] = tbl_data[legend_id] || {},
									tbl_data[legend_id][date] = tbl_data[legend_id][date] || {};

								tbl_data[legend_id][date][key] = tbl_data[legend_id][date][key] || [];
								tbl_data[legend_id][date][key].push( {
									'time_from': $td_fill.attr( 'data-fill-time-from' ),
									'time_to': $td_fill.attr( 'data-fill-time-to' )
								} );

								$td_fill.attr( 'data-fill-group', key );

								if ( legend_id != next_legend_id ) {
									key ++;
								}
							} );
						} );

						$prepare_box = $( '.tmsht_ts_user_advanced_box' ).addClass( 'hidden' );
						$prepare_box.find( '.tmsht_ts_user_advanced_box_details' ).addClass( 'hidden' );
						$prepare_box.find( '.tmsht_ts_user_advanced_box_interval' ).remove();

						for ( var legend_id in tbl_data ) {

							var $box = $( '.tmsht_ts_user_advanced_box[data-box-id="' + legend_id + '"]' ).removeClass( 'hidden' );

							for ( var date in tbl_data[legend_id] ) {
								var $details = $box.find( '.tmsht_ts_user_advanced_box_details[data-details-date="' + date + '"]' ),
									$wrap = $details.find( '.tmsht_ts_user_advanced_box_interval_wrap' );

								$details.removeClass( 'hidden' );

								for ( var interval in tbl_data[legend_id][date] ) {
									var $interval_template = $( '#tmsht_ts_user_advanced_box_details_template .tmsht_ts_user_advanced_box_interval' ).clone(),
										time_from = tbl_data[legend_id][date][interval][0]['time_from'],
										time_to = tbl_data[legend_id][date][interval][tbl_data[legend_id][date][interval].length - 1]['time_to'],
										group = interval,
										index = $box.find( '.tmsht_ts_user_advanced_box_interval' ).length,
										interval_html = $interval_template.html()
										                                  .replace( /%index%/g, index )
										                                  .replace( /%legend_id%/g, legend_id )
										                                  .replace( /%date%/g, date )
										                                  .replace( /%time_from%/g, time_from )
										                                  .replace( /%time_to%/g, time_to )
										                                  .replace( /%input_time_from%/g, time_from + ':00' )
										                                  .replace( /%input_time_to%/g, (
											                                  time_to != '24:00'
										                                  ) ? time_to + ':00' : '23:59:59' )
										                                  .replace( /data-hidden-name/g, 'name' );

									$interval_template
										.html( interval_html )
										.appendTo( $wrap )
										.attr( 'data-tr-date', date )
										.attr( 'data-details-group', group )
										.on( 'mouseenter', function () {
											var $interval = $( this ),
												group = $interval.attr( 'data-details-group' ),
												$tds_fill = $ts_table.find( '.tmsht_ts_user_table_td_fill[data-fill-group="' + group + '"]' );

											$tds_fill.addClass( 'tmsht_ts_user_highlight' );
										} ).on( 'mouseleave', function () {
										var $tds_fill = $ts_table.find( '.tmsht_ts_user_highlight' );

										$tds_fill.removeClass( 'tmsht_ts_user_highlight' );
									} );

									var group_legend_name = $box.find( '.tmsht_ts_user_advanced_box_title' ).text();
									$tds_fill = $ts_table.find( '.tmsht_ts_user_table_td_fill[data-fill-group="' + group + '"]' );

									$tds_fill.attr( 'title', group_legend_name + ' (' + time_from + ' - ' + time_to + ')' );
								}
							}
						}
					} );
				}
			}

			if ( methods[method] ) {
				return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ) );
			} else if ( typeof method === 'object' || ! method ) {
				return methods.init.apply( this, arguments );
			} else {
				$.error( 'Method ' + method + ' not found!' );
			}
		}
	);
})(jQuery);