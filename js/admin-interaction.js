jQuery( document ).ready( function() {

	var includeSection = jQuery( '.demo_quote_page_demo-quotes-plugin-settings .dqp-include-group' );

	/* Set initial 'include' checkbox enabled/disabled state based on 'parents' */
	dqpCheckboxSetState();

	/* Monitor 'include' checkbox changes */
	includeSection.on( 'change', 'input', dqpCheckboxSetState );

	/* Function to set 'include' checkbox enabled/disabled state based on 'parents' */
	function dqpCheckboxSetState() {
		var elms = includeSection.find( '.has-parents' );
		if ( elms.length > 0 ) {
			elms.each( function() {
				var disable, classes, i;

				disable = false;
				classes = jQuery( this ).attr( 'class' );
				classes = classes.split( ' ' );
				for ( i = 0; i < classes.length; i++ ) {
					if ( 0 === classes[i].indexOf( 'dqp_include_' ) && jQuery( '#' + classes[i] ).is( ':checked' ) ) {
						disable = true;
						break;
					}
				}
				if ( disable ) {
					jQuery( this ).find( 'input' ).attr({ 'disabled': 'disabled' });
		            jQuery( this ).css( 'color', '#888888' );
				}
				else {
		            jQuery( this ).find( 'input' ).removeAttr( 'disabled' );
		            jQuery( this ).css( 'color', '#333333' );
				}
			});
		}
	}



	// Collapsible debug information on the settings page
	jQuery( '.demo_quote_page_demo-quotes-plugin-settings #dqp-debug-info' ).accordion({
		active: false,
		collapsible: true,
		icons: {
			header: 'ui-icon-circle-triangle-e',
			activeHeader: 'ui-icon-circle-triangle-s'
		},
		heightStyle: 'content'
	});
});
