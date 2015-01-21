/*global window:false */
( function ( $, mw ) {
	'use strict';

	$(document).ready( function () {
		$('.pendingreviews-row').hover(
			function () {
				var rowNum = $( this ).attr( 'pendingreviews-row-count' );
				$( '.pendingreviews-row-' + rowNum ).addClass( 'pendingreviews-row-hover' );
			},
			function () {
				var rowNum = $( this ).attr( 'pendingreviews-row-count' );
				$( '.pendingreviews-row-' + rowNum ).removeClass( 'pendingreviews-row-hover' );
			}
		);
	});
	
	$('.pendingreviews-accept-deletion').click( function( event ) {
		event.preventDefault();
		var button = this;
		var title = $( button ).attr( 'pending-title' ),
			namespace = $( button ).attr( 'pending-namespace' );
			
		new mw.Api().postWithToken( 'edit', {
			action: 'setnotificationtimestamp',
			titles: new mw.Title( title, namespace ).getPrefixedText()
		} ).done( function ( data ) {

			var rowLines = $( button ).closest( '.pendingreviews-row' ).add(
				$( button ).closest( '.pendingreviews-row' ).next()
			);
			
			rowLines.fadeOut( 500, function() {
				rowLines.remove();
			});
			
		} );
		
	});

	$('.pendingreviews-watch-suggest-link').click( function ( event ) {

		event.preventDefault();

		var button = this;
		var titleText = $( button ).attr( 'suggest-title-prefixed-text' ),
			thanks = $( button ).attr( 'thanks-msg' );

		new mw.Api().postWithToken( 'watch', {
			action: 'watch',
			title: titleText
		} ).done( function ( data ) {

			$( button ).closest( 'li' ).html( thanks );
						
		} );

	});

} )( jQuery, mediaWiki );