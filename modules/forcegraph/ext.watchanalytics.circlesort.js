/*global window:false */
( function ( $, mw ) {
	'use strict';

	function firstLastReorder (arr) {
		arr = arr.slice(); //create copy
		var newArr = [];

		while( arr.length ) {

			newArr.push( arr.shift() );
			if ( arr.length )
				newArr.push( arr.pop() );

		}

		return newArr;

	}

	function circleReorder (arr) {
		// console.log("input array length: " + arr.length);

		var totalWeight = 0; //arr.reduce(function(a, b) { return a + b; });
		arr.forEach(function(e){ totalWeight += e.weight; });

		var averageWeight = totalWeight / arr.length;

		var numOverAverage = 0;
		arr.forEach(function(e){ if(e.weight > averageWeight) numOverAverage += 1; });


		// slice w/o params copies array
		var descArr = arr.slice().sort(function(a,b){
			if (a.weight === b.weight) return 0;
			if (a.weight >   b.weight) return -1;
			else                       return 1;
		});
		// console.log("descArr length: " + descArr.length);

		var newArr = descArr.splice(0, numOverAverage);
		newArr = firstLastReorder( newArr );

		var lowArr = firstLastReorder( descArr );


		var groupSize = Math.floor(descArr.length / numOverAverage);
		var pointer = newArr.length;
		var direction = -1;

		while ( lowArr.length ) {
			for(var i = 0; i < groupSize; i++) {
				if (lowArr.length)
					newArr.splice(pointer,0, lowArr.pop() );
			}
			pointer += direction;
			if (pointer < 1) {
				pointer = 1;
				direction = 1;
				groupSize = 1;
			}
		}

		return newArr;
	}

	if ( ! mw.watchAnalytics ) {
		mw.watchAnalytics = {};
	}
	mw.watchAnalytics.circleReorder = circleReorder;
} )( jQuery, mediaWiki );