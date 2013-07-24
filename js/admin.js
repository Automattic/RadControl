(function($) {

$(document).ready(function() {
	$('#adsense_fallback').click(function() {
		var opts = $('.adsense_opt');
		if( $(this).is(':checked') ) {
			opts.css( { 'opacity': '1.0' } );
			opts.prop( 'disabled', false );
		} else {
			opts.css( { 'opacity': '0.5' } );
			opts.prop( 'disabled', true );
		}
	});

	$('.adsense_opt').each(function() {
		if ( $(this).is(':disabled') ) {
			$(this).css( { 'opacity': '0.5' } );
		}
	});
});

})(jQuery);
