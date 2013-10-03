jQuery(document).ready(function() {
	
	jQuery('.demo_quotes_widget').on( 'click', '.dqpw-quote-next', function( event ) {
		event.preventDefault();
		var parent = jQuery( this ).parents().filter('.demo_quotes_widget');
		var parentId = parent.attr('id');
		parentId = parentId.substr( parentId.lastIndexOf('-')+1 );

		var data = {
			action: 'demo_quotes_widget_next',
			currentQuote: i18n_demo_quotes.currentQuote[parentId],
			dqpwNonce: i18n_demo_quotes.dqpwNonce
		};

		jQuery.post(
			i18n_demo_quotes.ajaxurl,
			data,
			function( response ) {
				var res = wpAjax.parseAjaxResponse(response, 'ajax-response');
				jQuery.each( res.responses, function() {
					parent.find('.dqpw-quote-wrapper').replaceWith(this.supplemental.quote);
					i18n_demo_quotes.currentQuote[parentId] = this.supplemental.quoteid;
				});
			}
		);

        return false;
	});
});