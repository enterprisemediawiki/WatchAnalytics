<?php
/**
 * MediaWiki Extension: WatchAnalytics
 * http://www.mediawiki.org/wiki/Extension:WatchAnalytics
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * This program is distributed WITHOUT ANY WARRANTY.
 */

/**
 *
 * @file
 * @ingroup Extensions
 * @author James Montalvo
 * @licence MIT License
 */

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/WatchAnalytics/WatchAnalytics.php" );
EOT;
	exit( 1 );
}

class WatchAnalyticsHtmlHelper {

	static public function formatListArray ( $list, $columns = 1, $listType = 'ol' ) {
		
		// number of <li> elements
		$numLI = count( $list );

		$totalCount = 1;
		$colCount = 0;

		$html = "<$listType>";

		if ( $columns > 1 ) {
			$perCol = ceil( $numLI / $columns );
		}
		else {
			$perCol = $numLI + 1;
		}

		$colWidth = floor( 100 / $columns );
		$html = "<table style='width:100%;'><tr><td style='width:$colWidth%;'>" . $html; // prepend the start of a table

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

		if ( $numLI > $perCol ) {
			$html .= "</td></tr></table>"; // close out table if required
		}

		return $html;

	}

}
