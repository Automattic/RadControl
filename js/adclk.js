(function($) {

window.wa_adclk = {

	hovering: false,
	recorded: false,
	slot: wa_adclk.slot,
	network: ( typeof window.wa_adclk_network === "undefined" ) ? "" : window.wa_adclk_network,

	click: function() {
		if ( this.recorded ) { return true; } // no double counting
		var stat_gif = document.location.protocol + "//stats.wordpress.com/g.gif?v=wpcom-no-pv";
		stat_gif += "&x_adcontrol_click_placement=" + this.slot; // TODO for other than belowpost
		stat_gif += "&x_adcontrol_click_network=" + this.network;

		new Image().src = stat_gif + "&baba=" + Math.random();
		this.recorded = true;

		var now=new Date(); var end=now.getTime() + 250;
		while(true){now=new Date();if(now.getTime()>end){break;}}
		return true;
	}
};

$(document).ready( function() {
	function hover_yes() { wa_adclk.hovering = true; }
	function hover_no() { wa_adclk.hovering = false; }

	$(".wpa").click( wa_adclk.click );
	$(".wpa iframe").hover( hover_yes, hover_no );
	$(".wpa object").hover( hover_yes, hover_no );

	$(window).blur( function() {
		if ( wa_adclk.hovering ) { wa_adclk.click(); }
	});
});

})(jQuery);
