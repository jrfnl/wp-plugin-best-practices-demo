jQuery(document).ready(function() {
	
	var includeSection = jQuery('.demo_quote_page_demo-quotes-plugin-settings .dqp-include-group');

	/* Set initial 'include' checkbox enabled/disabled state based on 'parents' */
	dqp_checkbox_set_state();

	/* Monitor 'include' checkbox changes */
	includeSection.on('change', 'input', dqp_checkbox_set_state );


	function dqp_checkbox_set_state() {
		var elms = includeSection.find('.has-parents');
		elms.each( function() {
			var disable = false;
			var classes = jQuery( this ).attr('class');
			classes = classes.split(' ');
			for( var i=0; i < classes.length; i++ ) {
				if( classes[i].indexOf('dqp_include_') == 0 && jQuery('#'+classes[i]).is(':checked') ) {
					disable = true;
					break;
				}
			}
			if( disable ) {
				jQuery( this ).find('input').attr({ 'disabled': 'disabled' });
	            jQuery( this ).css('color', '#888888');
			}
			else {
	            jQuery( this ).find('input').removeAttr('disabled');
	            jQuery( this ).css('color', '#333333');
			}
		});
	}



	// Collapsible debug information on the settings page
	jQuery('.demo_quote_page_demo-quotes-plugin-settings #dqp-debug-info').accordion({
		active: false,
		collapsible: true,
		icons: {
			header: 'ui-icon-circle-triangle-e',
			activeHeader: 'ui-icon-circle-triangle-s'
		},
		heightStyle: 'content'
	});
});