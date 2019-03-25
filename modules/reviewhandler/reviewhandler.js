/*global window:false */
( function ( $, mw ) {
	'use strict';

	$(document).ready( function () {
		$("#content").prepend(
			$("#ext-watchanalytics-review-handler-template")[0].innerHTML
		);
		if ( $(window).scrollTop() == 0 ) {
			$('#watch-analytics-review-handler').css("display", "block");
		} else {
			// Show nav button to go to top of page
			$("#watch-analytics-go-to-top-button").css("display", "block");
		}

		$(window).scroll( function () {
			var bannerHeight = $('#watch-analytics-review-handler').height() + $('#mw-head').height();

			if ( $(window).scrollTop() > bannerHeight ) {
				// If scrolled past review banner, show nav button
				$("#watch-analytics-go-to-top-button").css("display", "block");
			} else {
				// Don't show nav button when review banner is visible
				$("#watch-analytics-go-to-top-button").css("display", "none");
				$('#watch-analytics-review-handler').css("display", "block");
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
