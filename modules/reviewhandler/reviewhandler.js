/*global window:false */
( function ( $, mw ) {
	'use strict';

	$(document).ready( function () {

		$("#content").prepend(
			$("#ext-watchanalytics-review-handler-template")[0].innerHTML
		);

	});

} )( jQuery, mediaWiki );