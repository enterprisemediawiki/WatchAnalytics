/*global window:false */
( function ( $, mw ) {
	'use strict';

	$(document).ready( function () {

		$("#content").prepend(
			$("#ext-watchanalytics-review-handler-template")[0].innerHTML
		);

		// FIXME this should hide unreview banner and reset timestamp notification
		// $('.watch-analytics-unreview').click( function( event ) {
		// 	event.preventDefault();
		// 	var button = this;
		// 	var title = $( button ).attr( 'pending-title' ),
		// 		namespace = $( button ).attr( 'pending-namespace' );
		//
		// 	new mw.Api().postWithToken( 'edit', {
		// 		action: 'setnotificationtimestamp',
		// 		titles: new mw.Title( title, namespace ).getPrefixedText()
		// 	} ).done( function ( data ) {
		//
		// 		var rowLines = $( button ).closest( '.pendingreviews-row' ).add(
		// 			$( button ).closest( '.pendingreviews-row' ).next()
		// 		);
		//
		// 		rowLines.fadeOut( 500, function() {
		// 			rowLines.remove();
		// 		});
		//
		// 	} );
		//
		// });

	});

} )( jQuery, mediaWiki );
