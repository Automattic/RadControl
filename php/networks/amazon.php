<?php
echo '
		<script type="text/javascript" src="http://c.amazon-adsystem.com/aax2/amzn_ads.js"></script>
		<script type="text/javascript">
		try {
				amznads.getAds("3033");
		} catch(e) { /* ignore */ }
		</script>
		<script type="text/javascript">
		var amznKeys = amznads.getKeys();
		if (typeof amznKeys != "undefined" && amznKeys != "") { for (var i =0; i < amznKeys.length; i++) { var key = amznKeys[i]; GA_googleAddAttr("amzn", key);} }
		document.close();
		</script>
';
