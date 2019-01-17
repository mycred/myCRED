var myCREDCharts = {};
jQuery(function($){

	$(document).ready(function(){

		$.each( myCREDStats.charts, function(elementid, data){

			console.log( 'Generating canvas#' + elementid );
			console.log( data );
			myCREDCharts[ elementid ] = new Chart( $( 'canvas#' + elementid ).get(0).getContext( '2d' ), data );

		});

	});

});