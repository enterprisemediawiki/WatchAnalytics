/*global window:false */
( function ( $, mw ) {
	'use strict';

	var shakePendingReviews = function ( count, waitTime ) {
		if ( count < 3 ) {
			count++;
			setTimeout(
				function () {
					$("#pt-watchlist").effect(
						'shake',
						{ times: 5, distance: 5 },
						80
					);
					shakePendingReviews ( count, 4000 );
				},
				waitTime
			);
		}
	};
	
	$(document).ready( function () {

		shakePendingReviews( 0, 1000 );
	
	});

} )( jQuery, mediaWiki );