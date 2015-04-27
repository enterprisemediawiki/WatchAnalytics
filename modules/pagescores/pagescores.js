/*global window:false */
( function ( $, mw ) {
	'use strict';

	$(document).ready( function () {

		$("#firstHeading").append(
			'<div class="ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-danger"><div class="ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-left">Review Status</div><div class="ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-right">0</div></div><div class="ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-warning"><div class="ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-left">Watch Quality</div><div class="ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-right">0.6</div></div>'
		);
	
	});

} )( jQuery, mediaWiki );