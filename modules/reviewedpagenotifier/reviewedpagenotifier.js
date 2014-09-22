/*global window:false */
( function ( $, mw ) {
	'use strict';

	$(document).ready( function () {

		$("#reviewed-page-notifier")
			.hide()
			.html( $("#reviewed-page-notifier-template").html() )
			.fadeIn( 1000 );

	});

} )( jQuery, mediaWiki );