(function($) {

$(document).ready(function() {
	$('#adsense_fallback').click(function() {
		var opts = $('.adsense_fallback_opt');
		if( $(this).is(':checked') ) {
			opts.css( { 'opacity': '1.0' } );
			opts.prop( 'disabled', false );
		} else {
			opts.css( { 'opacity': '0.5' } );
			opts.prop( 'disabled', true );
		}
	});

	$('#adsense_leader').click(function() {
		var opts = $('.adsense_leader_opt');
		if( $(this).is(':checked') ) {
			opts.css( { 'opacity': '1.0' } );
			opts.prop( 'disabled', false );
		} else {
			opts.css( { 'opacity': '0.5' } );
			opts.prop( 'disabled', true );
		}
	});

	$('.adsense_fallback_opt').each(function() {
		if ( $(this).is(':disabled') ) {
			$(this).css( { 'opacity': '0.5' } );
		}
	});

	$('.adsense_leader_opt').each(function() {
		if ( $(this).is(':disabled') ) {
			$(this).css( { 'opacity': '0.5' } );
		}
	});
});

})(jQuery);
