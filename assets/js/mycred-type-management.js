/**
 * myCRED Management Scripts
 * @since 1.3
 * @version 1.4
 */
jQuery(function($) {

	var wWidth     = $(window).width();
	var dWidth     = wWidth * 0.75;

	/**
	 * Make sure new point type key is
	 * correctly formatted. Only lowercase letters and underscores
	 * are allowed. Warn user if needed.
	 */
	$( '#mycred-new-ctype-key-value' ).on( 'change', function(){

		var ctype_key = $(this).val();
		var re        = /^[a-z_]+$/;
		if ( ! re.test( ctype_key ) ) {
			$(this).css( 'border-color', 'red' );
			$( '#mycred-ctype-warning' ).css( 'color', 'red' );
		}
		else {
			$(this).css( 'border-color', 'green' );
			$( '#mycred-ctype-warning' ).css( 'color', '' );
		}

	});

	/**
	 * Empty Log AJAX Caller
	 */
	var mycred_action_empty_log = function( button ) {

		var label = button.val();
		$.ajax({
			type       : "POST",
			data       : {
				action    : 'mycred-action-empty-log',
				token     : myCREDmanage.token,
				type      : button.attr( 'data-type' )
			},
			dataType   : "JSON",
			url        : myCREDmanage.ajaxurl,
			beforeSend : function() {
				button.attr( 'value', myCREDmanage.working );
				button.attr( 'disabled', 'disabled' );
			},
			success    : function( response ) {

				if ( response.success ) {
					$( 'input#mycred-manage-table-rows' ).val( response.data );
					button.val( myCREDmanage.done );
					button.removeClass( 'button-primary' );
				}
				else {
					button.val( label );
					button.removeAttr( 'disabled' );
					alert( response.data );
				}

			}
		});

	};

	/**
	 * Empty Log Trigger
	 */
	$( '#mycred-manage-action-empty-log' ).click(function(){

		// Confirm action
		if ( confirm( myCREDmanage.confirm_log ) )
			mycred_action_empty_log( $(this) );

	});

	/**
	 * Reset Balance AJAX Caller
	 */
	var mycred_action_reset_balance = function( button ) {

		var label = button.val();

		$.ajax({
			type       : "POST",
			data       : {
				action    : 'mycred-action-reset-accounts',
				token     : myCREDmanage.token,
				type      : button.attr( 'data-type' )
			},
			dataType   : "JSON",
			url        : myCREDmanage.ajaxurl,
			beforeSend : function() {
				button.attr( 'value', myCREDmanage.working );
				button.attr( 'disabled', 'disabled' );
			},
			success    : function( response ) {

				console.log( response );
				if ( response.success ) {
					button.text( response.data );
					button.removeClass( 'button-primary' );
				}
				else {
					button.text( label );
					button.removeAttr( 'disabled' );
					alert( response.data );
				}

			}
		});

	};

	/**
	 * Reset Balance Trigger
	 */
	$( '#mycred-manage-action-reset-accounts' ).click(function(){

		// Confirm action
		if ( confirm( myCREDmanage.confirm_reset ) )
			mycred_action_reset_balance( $(this) );

	});

	$(document).ready( function() {

		if ( dWidth < 250 )
			dWidth = wWidth;

		if ( dWidth > 960 )
			dWidth = 960;

		/**
		 * Export Balances Modal
		 */
		$( '#export-points' ).dialog({
			dialogClass : 'mycred-export-points',
			draggable   : true,
			autoOpen    : false,
			title       : myCREDmanage.export_title,
			closeText   : myCREDmanage.export_close,
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

		/**
		 * Export balances Modal Trigger
		 */
		$( '#mycred-export-users-points' ).click( function() {

			$(this).blur();

			$( '#export-points' ).dialog( 'open' );

		});

	});

	/**
	 * Export Balances AJAX Caller
	 */
	var mycred_action_export_balances = function( button ) {

		var label = button.val();
		$.ajax({
			type       : "POST",
			data       : {
				action    : 'mycred-action-export-balances',
				token     : myCREDmanage.token,
				identify  : $( '#mycred-export-identify-by' ).val(),
				log_temp  : $( '#mycred-export-log-template' ).val(),
				type      : button.attr( 'data-type' )
			},
			dataType   : "JSON",
			url        : myCREDmanage.ajaxurl,
			beforeSend : function() {
				button.attr( 'value', myCREDmanage.working );
				button.attr( 'disabled', 'disabled' );
			},
			success    : function( response ) {

				if ( response.success ) {
					setTimeout(function(){
						window.location.href = response.data;
						button.val( myCREDmanage.done );
					}, 2000 );
					setTimeout(function(){
						button.removeAttr( 'disabled' );
						button.val( label );
					}, 4000 );
				}
				else {
					button.val( label );
					button.before( response.data );
				}

			}
		});

	};

	/**
	 * Balance Export Trigger
	 */
	$( '#mycred-run-exporter' ).click(function(){

		mycred_action_export_balances( $(this) );

	});

	/**
	 * Generate Key AJAX Caller
	 */
	var mycred_generate_key = function() {

		$.ajax({
			type     : "POST",
			data     : {
				action  : 'mycred-action-generate-key',
				token   : myCREDmanage.token
			},
			dataType : "JSON",
			url      : myCREDmanage.ajaxurl,
			success  : function( response ) {
				$( '#myCRED-remote-key' ).val( response.data );
				$( '#mycred-length-counter' ).text( response.data.length );
			}
		});

	};

	/**
	 * Generate Key Trigger
	 */
	$( '#mycred-generate-api-key' ).click(function(){
		mycred_generate_key();
	});

	/**
	 * Key Length Indicator
	 */
	$( '#myCRED-remote-key' ).change(function(){
		$( '#mycred-length-counter' ).text( $(this).val().length );
	});

	/**
	 * Key Length Indicator
	 */
	$( '#myCRED-remote-key' ).keyup(function(){
		$( '#mycred-length-counter' ).text( $(this).val().length );
	});

	/**
	 * Adjust Decimals AJAX Caller
	 */
	var mycred_adjust_max_decimals = function( button, label, decval ) {

		$.ajax({
			type     : "POST",
			data     : {
				action   : 'mycred-action-max-decimals',
				token    : myCREDmanage.token,
				decimals : decval
			},
			dataType : "JSON",
			url      : myCREDmanage.ajaxurl,
			beforeSend : function() {
				button.attr( 'value', myCREDmanage.working );
				button.attr( 'disabled', 'disabled' );
			},
			success  : function( response ) {

				if ( response.success ) {
					button.val( response.data.label );
					setTimeout(function(){
						window.location.href = response.data.url;
					}, 4000 );
				}
				else {
					button.val( response.data );
					setTimeout(function(){
						button.removeAttr( 'disabled' );
						button.val( label );
					}, 4000 );
				}

			}
		});

	};

	/**
	 * Show / Hide Update Button
	 */
	$( '#mycred-adjust-decimal-places' ).change(function(){

		var originaldec = $(this).data( 'org' );
		var newvalue    = $(this).val();

		if ( originaldec != newvalue )
			$( '#mycred-update-log-decimals' ).show();
		else
			$( '#mycred-update-log-decimals' ).hide();

	});

	/**
	 * Update Log Decimals Trigger
	 */
	$( '#mycred-update-log-decimals' ).click(function(){

		if ( confirm( myCREDmanage.decimals ) ) {
			mycred_adjust_max_decimals( $(this), $(this).val(), $( '#mycred-adjust-decimal-places' ).val() );
		}

	});

	var clearing_cache = false;

	/**
	 * Cache Clearing
	 */
	var mycred_clear_the_cache = function( button, label ) {

		if ( clearing_cache ) return false;

		clearing_cache = true;

		$.ajax({
			type     : "POST",
			data     : {
				action   : 'mycred-action-clear-cache',
				token    : myCREDmanage.cache,
				ctype    : button.attr( 'data-type' ),
				cache    : button.attr( 'data-cache' )
			},
			dataType : "JSON",
			url      : myCREDmanage.ajaxurl,
			beforeSend : function() {
				button.html( myCREDmanage.working );
				button.attr( 'disabled', 'disabled' );
			},
			success  : function( response ) {

				alert( response.data );
				button.html( label );

			},
			complete : function() {
				clearing_cache = false;
			}
		});

	};

	/**
	 * Clear Cache Trigger
	 */
	$( 'button.clear-type-cache-button' ).click(function(){

		mycred_clear_the_cache( $(this), $(this).html() );

	});

});