jQuery(function($){

	$(document).ready(function(){
		$( 'h1 .page-title-action, .wrap .page-title-action' ).remove();
		$( '#titlewrap #title' ).attr( 'readonly', 'readonly' ).addClass( 'readonly' );

		//fee
		$('#cashcred-pending-payment-points').keyup( function(){
			var cashcred_point_type = $( "#cashcred-pending-payment-point_type" ).val();
			var amount = $(this).val();
			
			cashcred_fee_setting( cashcred_point_type, amount )

		});
	});

	function cashcred_fee_setting( point_type, withdraw_points ) {

		if ( cashcred_data.use == 1 ) {
			var fee = 0;
			var ctype 	= cashcred_data.types[point_type];
			var by 		= cashcred_data.types[point_type].by;
			var fee_amount 	= cashcred_data.types[point_type].amount;
			var max_cap = cashcred_data.types[point_type].max_cap;
			var min_cap = cashcred_data.types[point_type].min_cap;
			
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

				$( '#cashcred-pending-payment-fee' ).val( fee );
			}

		}
		
	}

});