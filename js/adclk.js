(function($) {

window.wa_adclk = {

	hovering: false,
	recorded: false,
	theme: wa_adclk.theme,
	slot: wa_adclk.slot,
	network: ( typeof window.wa_adclk_network === "undefined" ) ? "" : window.wa_adclk_network,

	impression: function() {
		var stat_gif = document.location.protocol + "//stats.wordpress.com/g.gif?v=wpcom-no-pv";
		stat_gif += "&x_ads_imp_theme=" + this.theme;
		stat_gif += "&x_ads_imp_placement=" + this.slot;
		stat_gif += "&x_ads_imp_network=" + this.network;
		stat_gif += "&x_ads_imp_theme_network=" + this.theme + "_" + this.network;
		new Image().src = stat_gif + "&baba=" + Math.random();
		return true;
	},

	click: function() {
		if ( this.recorded ) { return true; } // no double counting
		var stat_gif = document.location.protocol + "//stats.wordpress.com/g.gif?v=wpcom-no-pv";
		stat_gif += "&x_ads_click_theme=" + this.theme;
		stat_gif += "&x_ads_click_placement=" + this.slot;
		stat_gif += "&x_ads_click_network=" + this.network;
		stat_gif += "&x_ads_click_theme_network=" + this.theme + "_" + this.network;

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
