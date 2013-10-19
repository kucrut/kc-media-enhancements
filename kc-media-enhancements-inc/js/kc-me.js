/** global jQuery, kcmeTaxTerms **/

(function($) {
	'use strict';

	var split = function ( val ) {
		return val.split( /,\s*/ );
	}

	var extractLast = function( term ) {
		return split( term ).pop();
	};

	var inputs = 'input.tax-terms';

	$('body')
		.on('keydown', inputs, function(e) {
			if ( e.keyCode === $.ui.keyCode.TAB && $(this).data('ui-autocomplete').menu.active ) {
				e.preventDefault();
			}
		})
		.on('focus', inputs, function() {
			var $el = $(this);

			if ( $el.data('ui-autocomplete') )
				return;

			$el.autocomplete({
				minLength : 0,
				source    : function( request, response ) {
					// delegate back to autocomplete, but extract the last term
					response( $.ui.autocomplete.filter( kcmeTaxTerms[ $el.data('taxonomy') ], extractLast( request.term ) ) );
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function( event, ui ) {
					var terms = split( this.value );
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					terms.push( '' );
					this.value = terms.join( ', ' );

					return false;
				},
				change : function() {
					$(this).trigger('change');
				}
			});
		});
}(jQuery));
