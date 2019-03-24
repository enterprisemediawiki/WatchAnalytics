/*global window:false */
( function ( $, mw ) {
	'use strict';

	$(document).ready( function () {

		$("#content").prepend(
			$("#ext-watchanalytics-review-handler-template")[0].innerHTML
		);

		$(window).scroll( function () {
			var bannerHeight = $('#watch-analytics-review-handler').height() + $('#mw-head').height();

			if ( $(window).scrollTop() > bannerHeight ) {
				$("#watch-analytics-go-to-top-button").css("display", "block");
			} else {
				$("#watch-analytics-go-to-top-button").css("display", "none");
			}

		});

		$("#watch-analytics-go-to-top-button").click( function( event ) {
			event.preventDefault();
			$(window).scrollTop(0);

		});

		$(".watch-analytics-unreview").click( function( event ) {
			event.preventDefault();
			var button = this;
			var title = $( button ).attr( 'pending-title' );
			var notificaitonTimestamp = $( button ).attr( 'timestamp' );

			new mw.Api().postWithToken( 'edit', {
				action: 'setnotificationtimestamp',
				timestamp: notificaitonTimestamp,
				titles: title
			} ).done( function ( data ) {

				var rowLines = $("#watch-analytics-review-handler" );

				rowLines.html("<strong>Review deferred!</strong>");

				rowLines.fadeOut( 700, function() {
					rowLines.remove();
				});

			} );

		});

		$("#watch-analytics-unreview.pendingreviews-green-button.pendingreviews-accept-change").click( function( event ) {
			event.preventDefault();
			var button = this;
			var rowLines = $('#watch-analytics-review-handler' );

			rowLines.fadeOut( 700, function() {
				rowLines.remove();
			});

			rowLines.css("background-color", "#00af89");
			rowLines.html("<strong>Page reviewed!</strong>");

			rowLines.fadeOut( 700, function() {
				rowLines.remove();
			});

		});

	});

} )( jQuery, mediaWiki );
