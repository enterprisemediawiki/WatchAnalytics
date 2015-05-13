/*global window:false */
( function ( $, mw ) {
	'use strict';

	$(document).ready( function () {

		$("#firstHeading").append(
			$("#ext-watchanalytics-pagescores-template")[0].innerHTML
		);

		var pageScoresTimeoutId;

		$("#ext-watchanalytics-pagescores").hover(
			function() {

				clearTimeout( pageScoresTimeoutId );

				$(this).find('.ext-watchanalytics-pagescores-right').css({
					"border-radius": "0 4px 4px 0"
				});

				$(this).find('.ext-watchanalytics-pagescores-left')
					.css({
						"border-radius": "4px 0 0 4px"
					})
					.animate(
						{ width: "show" },
						50,
						"linear"
					);

			},
			function() {
				var self = this;

				pageScoresTimeoutId = setTimeout(
					function() {
						$(self).find('.ext-watchanalytics-pagescores-left')
							.animate(
								{ width: "hide" },
								50,
								"linear"
							);

						$(self).find('.ext-watchanalytics-pagescores-right').css({
							"border-radius": "4px"
						});
					},
					2000 
				);
			}
		);

	});

} )( jQuery, mediaWiki );