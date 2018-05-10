jQuery(function($) {

	var RecurringSchedule  = $( '#manage-recurring-schedule' );
	var currentBankService = '';
	var wWidth             = $(window).width();
	var dWidth             = wWidth * 0.75;

	function check_form_for_empty_fields() {

		var emptyfields = 0;

		$( '#manage-recurring-schedule-form input.cant-be-empty' ).each(function(index){

			var fieldvalue = $(this).val();
			if ( fieldvalue.length == 0 ) {
				$(this).parent().addClass( 'has-error' );
				emptyfields++;
			}
			else
				$(this).parent().removeClass( 'has-error' );

		});

		if ( emptyfields > 0 )
			return false;

		else return true;

	};

	$(document).ready(function(){

		if ( dWidth < 250 )
			dWidth = wWidth;

		if ( dWidth > 960 )
			dWidth = 960;

		/**
		 * Setup Schedule Modal
		 */
		RecurringSchedule.dialog({
			dialogClass : 'mycred-update-balance mycred-metabox',
			draggable   : true,
			autoOpen    : false,
			title       : Banking.new,
			closeText   : Banking.close,
			modal       : true,
			width       : dWidth,
			height      : 'auto',
			resizable   : false,
			position    : { my: "center", at: "top+25%", of: window },
			show        : {
				effect     : 'fadeIn',
				duration   : 250
			},
			hide        : {
				effect     : 'fadeOut',
				duration   : 250
			}
		});

		RecurringSchedule.on( "dialogclose", function( event, ui ) {

			$( '#manage-recurring-schedule-form' ).empty();
			$( '#mycred-processing' ).show();

		} );

		// Schedule
		$( '#add-new-schedule' ).click(function(e){

			e.preventDefault();

			$(this).blur();

			RecurringSchedule.dialog({ title : Banking.new });
			RecurringSchedule.dialog( 'open' );
			$( '#manage-recurring-schedule-form' ).submit();

		});

		// Form submissions
		$( '#manage-recurring-schedule-form' ).on( 'submit', function(e){

			e.preventDefault();

			if ( check_form_for_empty_fields() === false ) {
				alert( Banking.emptyfields );
				return false;
			}

			$( '#mycred-processing' ).show();

			var thisform = $(this);

			$.ajax({
				type       : 'POST',
				data       : {
					action    : 'run-mycred-bank-service',
					_token    : Banking.token,
					service   : 'payouts',
					form      : thisform.serialize()
				},
				dataType   : 'JSON',
				url        : Banking.ajaxurl,
				beforeSend : function(){
					thisform.slideUp();
				},
				success    : function( response ) {

					if ( response.success === undefined ) {
						location.reload();
						return false;
					}

					$( '#mycred-processing' ).hide();

					thisform.empty().append( response.data.form ).slideDown();

					if ( response.data.table !== false ) {

						var scheduletable = $( '#recurring-schedule-body tr' );
						if ( scheduletable.length == 1 )
							$( '#no-banking-schedules' ).hide();

						$( '#recurring-schedule-body #no-banking-schedules' ).before( response.data.table );

					}
					else {
						var scheduletable = $( '#recurring-schedule-body tr' );
						if ( scheduletable.length == 1 )
							$( '#no-banking-schedules' ).show();
					}

				}
			});

		});

		// View Schedule
		$( '#recurring-schedule-body' ).on( 'click', 'a.view-recurring-schedule', function(e){

			e.preventDefault();

			$(this).blur();

			var scheduleid = $(this).data( 'id' );
			$( '#manage-recurring-schedule-form' ).append( '<input type="hidden" name="schedule_id" value="' + scheduleid + '" />' );

			RecurringSchedule.dialog({ title : $(this).data( 'title' ) });
			RecurringSchedule.dialog( 'open' );
			$( '#manage-recurring-schedule-form' ).submit();

		});

		// Delete Schedule
		$( '#recurring-schedule-body' ).on( 'click', 'a.delete-recurring-schedule', function(e){

			e.preventDefault();

			$(this).blur();

			var scheduleid = $(this).data( 'id' );
			$( '#manage-recurring-schedule-form' ).append( '<input type="hidden" name="remove_token" value="' + scheduleid + '" />' );

			if ( confirm( Banking.confirmremoval ) ) {
				RecurringSchedule.dialog({ title : $(this).data( 'title' ) });
				RecurringSchedule.dialog( 'open' );
				$( '#manage-recurring-schedule-form' ).submit();
			}
			else {
				$( '#manage-recurring-schedule-form' ).empty();
			}

		});

	});

});