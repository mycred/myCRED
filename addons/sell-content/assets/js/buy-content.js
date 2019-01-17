/**
 * myCRED Sell Content
 * @since 1.1
 * @version 1.1
 */
(function($) {

	$( '.mycred-sell-this-wrapper' ).on( 'click', '.mycred-buy-this-content-button', function(){

		var button      = $(this);
		var post_id     = button.data( 'pid' );
		var point_type  = button.data( 'type' );
		var buttonlabel = button.html();
		var content_for_sale = $( '#mycred-buy-content' + post_id );

		$.ajax({
			type : "POST",
			data : {
				action    : 'mycred-buy-content',
				token     : myCREDBuyContent.token,
				postid    : post_id,
				ctype     : point_type
			},
			dataType   : "JSON",
			url        : myCREDBuyContent.ajaxurl,
			beforeSend : function() {

				button.attr( 'disabled', 'disabled' ).html( myCREDBuyContent.working );

			},
			success    : function( response ) {

				if ( response.success === undefined || ( response.success === true && myCREDBuyContent.reload === '1' ) )
					location.reload();

				else {

					if ( response.success ) {
						content_for_sale.fadeOut(function(){
							content_for_sale.removeClass( 'mycred-sell-this-wrapper mycred-sell-entire-content mycred-sell-partial-content' ).empty().append( response.data ).fadeIn();
						});
					}

					else {

						button.removeAttr( 'disabled' ).html( buttonlabel );

						alert( response.data.message );

					}

				}

				console.log( response );

			}
		});

	});

})( jQuery );