/*global window:false */
( function ( $, mw ) {
	'use strict';

	$(document).ready( function () {

		$("#firstHeading").append(
			$("#ext-watchanalytics-pagescores")[0].innerHTML
		);
	
	});

} )( jQuery, mediaWiki );