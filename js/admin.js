(function($) {

$(document).ready(function() {
	var leader = $('#leaderboard'),
		leader_mobile = $('#leaderboard_mobile');

	leader.click( function() {
		if( $(this).is(':checked') ) {
			leader_mobile.css( { 'opacity': '1.0' } );
			leader_mobile.prop( 'disabled', false );
		} else {
			leader_mobile.css( { 'opacity': '0.5' } );
			leader_mobile.prop( 'disabled', true );
		}
	});

	if( leader.is(':not(:checked)') ) {
		leader_mobile.css( { 'opacity': '0.5' } );
		leader_mobile.prop( 'disabled', true );
	}
});

})(jQuery);
