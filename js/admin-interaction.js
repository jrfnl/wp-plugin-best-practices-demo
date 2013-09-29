jQuery(document).ready(function($) {
	
	// Collapsible debug information on the settings page
	jQuery('.demo_quote_page_demo-quotes-plugin-settings #dqp-debug-info').accordion({
		active: false,
		collapsible: true,
/*		icons: {
			header: 'ui-icon-circle-triangle-e',
			activeHeader: 'ui-icon-circle-triangle-s'
		},*/
		heightStyle: 'content'
	});
});