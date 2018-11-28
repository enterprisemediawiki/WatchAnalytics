<?php

class WatchAnalyticsHtmlHelper {

	public static function formatListArray( $list, $columns = 1, $listType = 'ol' ) {
		// number of <li> elements
		$numLI = count( $list );

		$totalCount = 1;
		$colCount = 0;

		if ( $columns > 1 ) {
			$perCol = ceil( $numLI / $columns );
		} else {
			$perCol = $numLI + 1;
		}

		$colWidth = floor( 100 / $columns );
		$html = "<table style='width:100%;'><tr><td style='width:$colWidth%;'><$listType>"; // prepend the start of a table

		// $html .= '<pre>' . print_r( $list, true ) . '</pre>';
		foreach ( $list as $li ) {

			$html .= $li;

			$colCount++;
			$totalCount++;
			if ( $colCount == $perCol ) {
				$html .= "</$listType></td><td style='width:$colWidth%;'><$listType start='$totalCount'>"; // start new table cell
				$colCount = 0; // reset column counter
			}

		}

		$html .= "</$listType>";

		// Was: close out table if required
		// Question: when would it not be required? There was an if-statement here
		// but it was removed because tables weren't being closed out if only one
		// item was in the list ($numLI == 1)
		$html .= "</td></tr></table>";

		return $html;
	}

}
