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

class WatchAnalyticsParserFunctions {

	static function setup ( &$parser ) {
		
		$parser->setFunctionHook(
			'underwatched_categories', // getUnderwatchedCategories
			array(
				'WatchAnalyticsParserFunctions', // class to call function from
				'renderUnderwatchedCategories' // function to call within that class
			),
			SFH_OBJECT_ARGS
		);

		return true;
		
	}
	
	static function processArgs( $frame, $args, $defaults ) {
		$new_args = array();
		$num_args = count($args);
		$num_defaults = count($defaults);
		$count = ($num_args > $num_defaults) ? $num_args : $num_defaults;
		
		for ($i=0; $i<$count; $i++) {
			if ( isset($args[$i]) )
				$new_args[$i] = trim( $frame->expand($args[$i]) );
			else
				$new_args[$i] = $defaults[$i];
		}
		return $new_args;
	}

	static function renderUnderwatchedCategories ( &$parser, $frame, $args ) {

		$args = self::processArgs( $frame, $args, array(0) );
		$namespace  = $args[0];
		
		$dbr = wfGetDB( DB_SLAVE );
		
		$query = "
			SELECT * FROM (
				SELECT 
					p.page_namespace,
					p.page_title,
					SUM(IF(w.wl_title IS NOT NULL, 1, 0)) AS num_watches,
					SUM(IF(w.wl_title IS NOT NULL AND w.wl_notificationtimestamp IS NULL, 1, 0)) AS num_reviewed,
					SUM(IF(w.wl_title IS NOT NULL AND w.wl_notificationtimestamp IS NULL, 0, 1)) * 100 / COUNT(*) AS percent_pending,
					MAX(TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP())) AS max_pending_minutes,
					AVG(TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP())) AS avg_pending_minutes,
					(SELECT group_concat(cl_to SEPARATOR ';') as subq_categories FROM categorylinks WHERE cl_from = p.page_id) AS categories
				FROM `watchlist` `w`
				RIGHT JOIN `page` `p` ON ((p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title))
				WHERE 
					p.page_namespace = 0
					AND p.page_is_redirect = 0
				GROUP BY p.page_title, p.page_namespace
				ORDER BY num_watches, num_reviewed
			) tmp
			WHERE num_watches < 2";

		$result = $dbr->query( $query );

		$output = "{| class=\"wikitable sortable\"\n";
		$output .= "! Category !! Number of Under-watched pages\n";
		
		$categories = array();
		while ( $row = $dbr->fetchObject( $result ) ) {
			$pageCategories = explode( ';' , $row->categories );

			foreach ( $pageCategories as $cat ) {			
				if ( isset ( $categories[ $cat ] ) ) {
					$categories[ $cat ]++;
				}
				else {
					$categories[ $cat ] = 1;
				}
			}
		}
		
		arsort( $categories );

		foreach ( $categories as $cat => $numUnderwatchedPages ) {
		
			if ( $cat === '' ) {
				$catLink = "''Uncategorized''";
			}
			else {
				$catTitle = Category::newFromName( $cat )->getTitle();
				$catLink = "[[:$catTitle|" . $catTitle->getText() . "]]";
			}
			
			$output .= "|-\n";
			$output .= "| $catLink || $numUnderwatchedPages\n";
		}
		
		$output .= '|}[[Category:Pages using beta WatchAnalytics features]]';
		
		return $output;
	}
	
}
