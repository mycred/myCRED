jQuery(function($){

	$(document).ready(function(){
		
		$( 'select#mycred-email-instance' ).change(function(e){
			
			var selectedevent = $(this).find( ':selected' );

			if ( selectedevent.val() == 'central_min_balance' ) {

				$( '#areference-selection' ).show();

			}
			else {

				$( '#areference-selection' ).hide();

			}

		});

	});

});