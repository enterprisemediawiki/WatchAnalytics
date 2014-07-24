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

} )( jQuery, mediaWiki );