jQuery( document ).ready( function() {

	jQuery( '.demo_quotes_widget' ).on( 'click', '.dqpw-quote-next', function( event ) {
		var parent, parentId, data;

		event.preventDefault();

		parent   = jQuery( this ).parents().filter( '.demo_quotes_widget' );
		parentId = parent.attr( 'id' );
		parentId = parentId.substr( parentId.lastIndexOf( '-' ) + 1 );

		data     = {
			action: 'demo_quotes_widget_next',
			currentQuote: i18nDemoQuotes.currentQuote[parentId],
			dqpwNonce: i18nDemoQuotes.dqpwNonce
		};

		jQuery.post(
			i18nDemoQuotes.ajaxurl,
			data,
			function( response ) {
				var res = wpAjax.parseAjaxResponse( response, 'ajax-response' );
				jQuery.each( res.responses, function() {
					parent.find( '.dqpw-quote-wrapper' ).replaceWith( this.supplemental.quote );
					i18nDemoQuotes.currentQuote[parentId] = this.supplemental.quoteid;
				});
			}
		);

        return false;
	});
});
