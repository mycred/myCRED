/**
 * CashCred Withdraw
 * @since 2.0
 * @version 1.0
 */
jQuery(function($){

	$(document).ready(function() {



		if ( $( "#cashcred_pay_method" ).length ) {
			exchange_calculation();
			display_cashcred_gateway_notice();
		}
		 
		$( "#cashcred_point_type" ).change(function() {
			exchange_calculation();
		});
		 
		$( "#cashcred_pay_method" ).change(function() {
			exchange_calculation();
			display_cashcred_gateway_notice();
		});

		$( '.mycred-cashcred-form' ).on( 'keyup change', '#withdraw_points', function( e ){
  
  			exchange_calculation();
			
		});

		
		$( 'body' ).on( 'submit', '.mycred-cashcred-form', function( e ){

	

			withdraw_points = $( "#withdraw_points" ).val();
			if( parseFloat( withdraw_points ) <= 0 ) {
				e.preventDefault();
			}
		});

		$('.cashcred-nav-tabs li').click( function(){
			var id = $(this).attr('id');
			$('.cashcred-nav-tabs li').removeClass('active');
			$('.cashcred-tab').hide();
			$(this).addClass('active');           
			$('#'+ id + 'c').show();
		});

		$('.cashcred-tab').hide();
		$('#tab1c').show();

		var elementType = $('#cashcred_save_settings').prop('nodeName');

		if ( elementType != 'INPUT' ) {
			first_tab_active = $("#cashcred_save_settings option:first").val();
			$('.cashcred_panel').hide();
			$('#panel_'+first_tab_active).show();
		}
		 
		$("select#cashcred_save_settings").change( function(){
			id = $(this).val();
			$('.cashcred_panel').hide();
			$('#panel_'+id).show();
		});
	
	});
		
	function exchange_calculation(){
		
		cashcred_point_type = $( "#cashcred_point_type" ).val();
		cashcred_pay_method = $( "#cashcred_pay_method" ).val();
		withdraw_points     = $( "#withdraw_points" ).val();
		cashcred_fee 		= cashcred_fee_setting( cashcred_point_type, withdraw_points );
		currency_code   = cashcred.exchange[cashcred_pay_method].currency;
		conversion_rate = cashcred.exchange[cashcred_pay_method].point_type[cashcred_point_type];

		if ( typeof conversion_rate === 'undefined' ) {
			conversion_rate = 1;
		}
		
		min = cashcred.exchange[cashcred_pay_method].min;
		max = cashcred.exchange[cashcred_pay_method].max
					 
		amount = withdraw_points * conversion_rate;
		
		$( "#withdraw_points" ).attr({"max" : max , "min" : min });

		$( '.cashcred-min span' ).html( min );
		
		$( "#cashcred_currency_symbol" ).html(currency_code);
		$( "#cashcred_total_amount" ).html(amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
		
	}

	function display_cashcred_gateway_notice() {
		
		if ( cashcred.gateway_notices[ $('#cashcred_pay_method').val() ] ) {

			$('.cashcred_gateway_notice').show();

		}
		else {

			$('.cashcred_gateway_notice').hide();

		}
		
	}

	function cashcred_fee_setting( point_type, withdraw_points ) {

		if ( cashcred_data.cashcred_setting.use == 1 ) {
			var fee = 0;
			var ctype 	= cashcred_data.cashcred_setting.types[point_type];
			var by 		= cashcred_data.cashcred_setting.types[point_type].by;
			var fee_amount 	= cashcred_data.cashcred_setting.types[point_type].amount;
			var max_cap = cashcred_data.cashcred_setting.types[point_type].max_cap;
			var min_cap = cashcred_data.cashcred_setting.types[point_type].min_cap;
			var presentation = cashcred_data.cashcred_setting.types[point_type].presentation;

			//formats
			decimals = cashcred_data.format[point_type].decimals;
			decimal_sep = cashcred_data.format[point_type].separators.decimal;
			thousand_sep = cashcred_data.format[point_type].separators.thousand;
			if( withdraw_points > 0 ) { 
				fee = fee_amount;
				if( by == 'percent' ){
					fee = ( ( fee_amount / 100 ) * withdraw_points );
					fee_amount = fee_amount + '%';
				}

				if( min_cap != 0 )
					fee = ( parseInt(fee) + parseInt(min_cap) );


				if ( max_cap != 0 && fee > max_cap )
					fee = max_cap;

				presentation = presentation.replace( "%fee%", fee_amount );
				presentation = presentation.replace( "%min%", min_cap );
				presentation = presentation.replace( "%max%", max_cap );
				presentation = presentation.replace( "%total%", mycred_number_format( fee, decimals, decimal_sep, thousand_sep ) );

				$('.cashcred-fee').show();
				$( '.cashcred-fee span' ).html( presentation );
			}

		}
		else {

			$('.cashcred-fee').hide();

		}
	}

	function mycred_number_format ( number, decimals, dec_point, thousands_sep ) {
	    
	    // Strip all characters but numerical ones.
	    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
	    var n = !isFinite(+number) ? 0 : +number,
	        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
	        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
	        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
	        s = '',
	        toFixedFix = function (n, prec) {
	            var k = Math.pow(10, prec);
	            return '' + Math.round(n * k) / k;
	        };
	    
	    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
	    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
	    if (s[0].length > 3) {
	        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
	    }
	    if ((s[1] || '').length < prec) {
	        s[1] = s[1] || '';
	        s[1] += new Array(prec - s[1].length + 1).join('0');
	    }

	    return s.join(dec) ;
	}


});




 
