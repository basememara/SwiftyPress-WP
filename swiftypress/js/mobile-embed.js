jQuery(function () {
	jQuery('a').each(function() {
    	var href = jQuery(this).attr('href');

    	if(href && !href.startsWith('#')
			&& (href.indexOf(window.location.host) > -1 || href.startsWith('/'))) {
        	href += (href.match(/\?/) ? '&' : '?') + 'mobileembed=1';
        	jQuery(this).attr('href', href);
    	}
	});
});