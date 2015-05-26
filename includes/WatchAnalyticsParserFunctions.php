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

	static public function setup ( &$parser ) {
		
		$parser->setFunctionHook(
			'underwatched_categories',
			array(
				'WatchAnalyticsParserFunctions', // class to call function from
				'renderUnderwatchedCategories' // function to call within that class
			),
			SFH_OBJECT_ARGS
		);

		// pages needing watchers
		$parser->setFunctionHook(
			'watchers_needed',
			array(
				'WatchAnalyticsParserFunctions',
				'renderWatchersNeeded'
			),
			SFH_OBJECT_ARGS
		);

		
		return true;
		
	}
	
	static public function processArgs( $frame, $args, $defaults ) {
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

	static public function renderUnderwatchedCategories ( &$parser, $frame, $args ) {

		// @TODO: currently these do nothing. The namespace arg needs to be text
		// provided by the user, so this method needs to convert "Main" to zero, etc
		// $args = self::processArgs( $frame, $args, array(0) );
		// $namespace  = $args[0];
		
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
	
	static public function renderWatchersNeeded ( &$parser, $frame, $args ) {
		global $wgUser;
		
		$wgUserId = $wgUser->getId();
	
		$args = self::processArgs( $frame, $args, array( 0, 60, 3, 10 ) );
		$namespace   = intval( $args[0] );
		$numDays     = intval( $args[1] );
		$maxWatchers = intval( $args[2] );
		$limit       = intval( $args[3] );
		
		$rangeTimestamp = date('YmdHis', time() - ($numDays * 24 * 60 * 60) );
		
		$dbr = wfGetDB( DB_SLAVE );
		
		if ( class_exists( 'Wiretap' ) && false ) {
			$query =
				"SELECT 
					p.page_id AS page_id,
					p.page_title AS page_title,
					p.page_namespace AS page_namespace,
					COUNT(DISTINCT(user_name)) AS unique_hits,
					red.rd_namespace AS redir_to_ns,
					red.rd_title AS redir_to_title,
					redir_page.page_id AS redir_id,
					(
						SELECT COUNT(*)
						FROM watchlist AS watch
						WHERE
							watch.wl_namespace = p.page_namespace
							AND watch.wl_title = p.page_title
					) AS watches
				FROM wiretap AS w
				INNER JOIN page AS p ON 
					p.page_id = w.page_id
				LEFT JOIN redirect AS red ON
					red.rd_from = p.page_id
				LEFT JOIN page AS redir_page ON
					red.rd_namespace = redir_page.page_namespace
					AND red.rd_title = redir_page.page_title
				WHERE
					hit_timestamp > $rangeTimestamp
				GROUP BY
					p.page_namespace, p.page_title
				ORDER BY
					unique_hits DESC
				LIMIT 20";
		}
		else {
			$query = 
				"SELECT page_title, page_namespace FROM (
					SELECT
						p.page_title AS page_title,
						p.page_namespace AS page_namespace,
						SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS num_watches,
						SUM( IF(w.wl_user = $wgUserId, 1, 0) ) AS wg_user_watches,
						p.page_counter / SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS view_watch_ratio
					FROM
						watchlist AS w
					LEFT JOIN page AS p ON
						w.wl_title = p.page_title
						AND w.wl_namespace = p.page_namespace
					LEFT JOIN revision AS r ON
						r.rev_id = p.page_latest
					WHERE
						p.page_namespace = $namespace
						AND p.page_is_redirect = 0
						AND r.rev_timestamp > $rangeTimestamp
					GROUP BY
						p.page_title, p.page_namespace
					ORDER BY
						view_watch_ratio DESC
				) AS tmp 
				WHERE
					num_watches <= $maxWatchers
					AND wg_user_watches = 0
				LIMIT $limit;";

		}

		$result = $dbr->query( $query );
		$output = '';
		while ( $row = $dbr->fetchObject( $result ) ) {
			// $title = Title::makeTitle( $row->page_namespace, $row->page_title );
			// $watchURL = $title->getFullURL( array( 'action' => 'watch' ) );
			// $output .= "* [[$title]] - '''[$watchURL watch]'''\n";
			$output .= Xml::tags(
				'li',
				array(),
				self::makeWatchLink( $row->page_namespace, $row->page_title )
			);
			
		}
		
		$output = Xml::tags('ul',array(),$output);
		
		return array(
			0 => $output,
			'isHTML' => true,
		);
		
	}
	
	static protected function makeWatchLink ( $ns, $titleText ) {
		global $wgContLang, $wgUser;

		$context = RequestContext::getMain();
		
		$nt = Title::makeTitle( $ns, $titleText ); // was: makeTitleSafe
		
		// FIXME: this is from Special:Unwatchedpages. It may not be valid in
		// for a parser function intended to get people to watch more pages.
		// Perhaps the parser function should offer an option as to whether or
		// not to display invalid pages?
		if ( !$nt ) {
			return Html::element( 'span', array( 'class' => 'mw-invalidtitle' ),
				Linker::getInvalidTitleDescription( $context, $ns, $titleText ) );
		}

		$text = $wgContLang->convert( $nt->getPrefixedText() );

		$plink = Linker::linkKnown( $nt, htmlspecialchars( $text ) );
		$token = WatchAction::getWatchToken( $nt, $wgUser );
		$wlink = Linker::linkKnown(
			$nt,
			$context->msg( 'watch' )->escaped(),
			array( 'class' => 'mw-watch-link watch-analytics-watchers-needed-link' ),
			array( 'action' => 'watch', 'token' => $token )
		);
		
		return $context->getLanguage()->specialList( $plink, $wlink );
	}
}
