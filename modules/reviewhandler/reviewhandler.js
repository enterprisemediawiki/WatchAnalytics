/*global window:false */
( function ( $, mw ) {
	'use strict';

	$(document).ready( function () {

		$("#content").prepend(
			$("#ext-watchanalytics-review-handler-template")[0].innerHTML
		);

		$('.watch-analytics-unreview').click( function( event ) {
			event.preventDefault();
			var button = this;
			var title = $( button ).attr( 'pending-title' );
			var notificaitonTimestamp = $( button ).attr( 'timestamp' );

			new mw.Api().postWithToken( 'edit', {
				action: 'setnotificationtimestamp',
				timestamp: notificaitonTimestamp,
				titles: title
			} ).done( function ( data ) {

				var rowLines = $('#watch-analytics-review-handler' );

				rowLines.html("<strong>Review deferred!</strong>");

				rowLines.fadeOut( 700, function() {
					rowLines.remove();
				});

			} );

		});

	});

} )( jQuery, mediaWiki );
