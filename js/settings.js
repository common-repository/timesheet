( function( $ ) {
	$( document ).ready( function() {

		/* Timeline slider */
		$( '#tmsht_timeline_slider' ).slider({
			'min'    : 0,
			'max'    : 24,
			'range'  : true,
			'create' : function( event, ui ) {
				var $this = $( this ),
					timeline_from = $( 'input[name="tmsht_ts_timeline_from"]' ).val(),
					timeline_to = $( 'input[name="tmsht_ts_timeline_to"]' ).val();
				$this.slider( 'values', [ timeline_from, timeline_to ] );
				$( 'input[name="tmsht_ts_timeline_from"]' ).on( 'change mousewheel', function ( e ) {
					var timeline_from = parseInt( $( 'input[name="tmsht_ts_timeline_from"]' ).val() ),
						timeline_to = parseInt( $( 'input[name="tmsht_ts_timeline_to"]' ).val() );

					if ( timeline_from >= timeline_to ) {
						timeline_from = timeline_to - 1;
						$( 'input[name="tmsht_ts_timeline_from"]' ).val( timeline_from );
						e.preventDefault();
						return false;
					}

					$this.slider( 'values', 0, timeline_from );
					$this.slider( 'values', 1, timeline_to );
				});
				$( 'input[name="tmsht_ts_timeline_to"]' ).on( 'change mousewheel', function ( e ) {
					var timeline_from = parseInt( $( 'input[name="tmsht_ts_timeline_from"]' ).val() ),
						timeline_to = parseInt( $( 'input[name="tmsht_ts_timeline_to"]' ).val() );

					if ( timeline_from >= timeline_to ) {
						timeline_to = timeline_from + 1;
						$( 'input[name="tmsht_ts_timeline_to"]' ).val( timeline_to );
						e.preventDefault();
						return false;
					}

					$this.slider( 'values', 0, timeline_from );
					$this.slider( 'values', 1, timeline_to );
				});
			},
			'slide' : function( event, ui ) {
				var $this = $( this ),
					timeline_from = ui.values[0],
					timeline_to = ui.values[1];
					if ( timeline_from >= timeline_to ) {
						return false;
					}

				$( 'input[name="tmsht_ts_timeline_from"]' ).val( timeline_from ).trigger( 'change' );
				$( 'input[name="tmsht_ts_timeline_to"]' ).val( timeline_to ).trigger( 'change' );
			}
		});

		/* Timeline inputs */
		$( 'input[name="tmsht_ts_timeline_from"], input[name="tmsht_ts_timeline_to"]' ).on( 'keypress', function( e ) {
    		var charCode = ( e.which ) ? e.which : e.keyCode;
    		return ! ( charCode > 31 && ( charCode < 48 || charCode > 57 ) );
		});

		$( 'input[name="tmsht_date_format_type"]' ).on( 'change', function() {
			if ( $( this ).val() != 'custom' ) {
				$( '#tmsht_date_format_code' ).val( $( this ).attr( 'data-date-format-code' ) );
				$( '#tmsht_date_format_display' ).text( $( this ).attr( 'data-date-format-display' ) );
			}
		});

		/* Colorpicker */
		$( '#tmsht_add_ts_legend_color, .tmsht_ts_legend_color' ).wpColorPicker();

		/* Date format */
		$( '#tmsht_date_format_code' ).on( 'mousedown', function() {
			$( '#tmsht_date_format_type_custom' ).trigger( 'click' );
		}).on( 'change', function() {
			$( '#tmsht_date_format_spinner' ).addClass( 'is-active' );
			$.post(ajaxurl, {
				action: 'date_format',
				date : $( this ).val()
			}, function( date ) {
				$( '#tmsht_date_format_spinner' ).removeClass( 'is-active' );
				$( '#tmsht_date_format_display' ).text( date );
			} );
		});

		$( '#tmsht_reminder_change_state' ).attr( 'disabled', true );

		$( '#tmsht_reminder_on_email, #tmsht_day_reminder, #tmsht_time_reminder' ).on( 'change paste', function() {
			$( '#tmsht_reminder_change_state' ).attr( 'disabled', false );
		});
	});
})(jQuery);