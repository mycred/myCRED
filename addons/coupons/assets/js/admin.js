jQuery(document).ready(function (){
	
	var $ = jQuery;
	
	$(document).on( 'click', '.mycred-addmore-button', function(event) {

        $(this).closest('.form').find('.mycred-border').last().after( mycred_coupon_object.html );

    }); 

    jQuery(document).on('click', '#mycred-check', function(){
        if ($(this).prop("checked") == true) {
            $(".mycred-coupon-form").slideDown();
        } else {
            $(".mycred-coupon-form").slideUp();
           
        }
    });

     $(document).on( 'click', '.close-button', function() { 
        var container = $(this).closest('.form');
        if ( container.find('.mycred-border').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this step?");
            if (dialog == true) {
                $(this).closest('.mycred-border').remove();
            } 
        }
    });


	$( document ).on('change', '.mycred-select-coupon-rewards', function() {

		var _this = jQuery(this);
		var value = _this.val();

		$.post(
	        ajaxurl,
	        {
	            action: 'mycred_change_dropdown',
	            value: value
	        },function( response ) {
	            response = JSON.parse( response );
	            
	            var container = _this.closest('.form');
	            var selected = [];
	            
	            if( value == 'mycred_coupon_badges' ){
		            
		            var ele = _this.closest('.mycred-border').find( ".mycred-select-ids" ).attr( 'name', 'mycred_coupon[reward][ids][]' );
		            container.find('.mycred-select-ids').not(ele).each(function () {
	                	selected.push( jQuery(this).val() );
	           		 });

		        }else{
					 
					 var ele = _this.closest('.mycred-border').find( ".mycred-select-ids" ).attr( 'name', 'mycred_coupon[reward][ids][]' );
					 container.find('.mycred-select-ids').not(ele).each(function () {
	                	selected.push( jQuery(this).val() );
	           		 });
				}

				ele.html('');
	         	jQuery.each( response, function( index ){

	         		ele.append( '<option value=' + response[index][0] + '>' + response[index][1] + '</option>' );

	            });
	         });

		if( value ==  'mycred_coupon_ranks'){
			$(this).closest('.mycred-border').find( "#change-text" ).text( "Ranks: " );

		}else{
			$(this).closest('.mycred-border').find( "#change-text" ).text( "Badges :" );
		}

	});
})