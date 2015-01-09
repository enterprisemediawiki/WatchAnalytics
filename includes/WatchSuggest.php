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

class WatchSuggest {

	/**
	 * @var int $limit: maximum number of database rows to return
	 * @todo FIXME: who/what sets this?
	 * @example 20
	 */
	// public $limit;

	
	public function __construct ( User $user ) {
	
		$this->mUser = $user;

	}

	/**
	 * Handles something.
	 * 
	 * @return bool
	 */
	public function getWatchSuggestionList () {

		#
		#
		#
		#	WARNING! Heinous code below...here be dragons...
		#
		#	(this code is in development and will be cleaned up after it's functional)
		#
		#
		#


		$html = '';

		$dbr = wfGetDB( DB_SLAVE );

		$userId = $this->mUser->getId();

		// SELECT
		// 	p.page_id AS p_id,
		// 	w.wl_title AS p_title
		// FROM page AS p
		// LEFT JOIN watchlist AS w
		// 	ON (
		// 		w.wl_namespace = p.page_namespace
		// 		AND w.wl_title = p.page_title
		// 	)
		// WHERE
		// 	w.wl_user = $userId
		// 	AND p.page_namespace = 0
		$userWatchlist = $dbr->select(
			array(
				'p' => 'page',
				'w' => 'watchlist',
			),
			array(
				'p.page_id AS p_id',
				'w.wl_title AS p_title',
 			),
			"w.wl_user=$userId AND p.page_namespace=0",
			__METHOD__,
			array(), // options
			array(
				'w' => array(
					'LEFT JOIN', 
					'w.wl_namespace = p.page_namespace AND w.wl_title = p.page_title'
				)
			)
		);

		$pagesIds = array();
		$pageTitles = array();
		while ( $row = $userWatchlist->fetchObject() ) {
			$userWatchlistPageIds[] = $row->p_id;
			$userWatchlistPageTitles[] = $row->p_title;
		}

		$ids = $dbr->makeList( $userWatchlistPageIds );
		$titles = $dbr->makeList( $userWatchlistPageTitles );

		// $html .= "<pre>" . print_r( $pageIds, true ) . "</pre>";
		// $html .= "<pre>" . print_r( $titles, true ) . "</pre>";



		// SELECT
		// 	pl.pl_from AS pl_from_id,
		// 	p_to.page_id AS pl_to_id
		// FROM pagelinks AS pl
		// INNER JOIN page AS p_to
		// 	ON (
		// 		pl.pl_namespace = p_to.page_namespace
		// 		AND pl.pl_title = p_to.page_title
		// 	)
		// WHERE
		// 	pl.pl_from IN ( <LIST OF all p_id found above> )
		// 	OR ( pl.pl_namespace = 0 AND pl.pl_title IN ( <LIST OF ALL p_title found above> ) )
		$where = 
			"pl.pl_from IN ($ids) " . 
			" OR ( pl.pl_namespace = 0 AND pl.pl_title IN ($titles) )";


		$linkedPagesResult = $dbr->select(
			array(
				'pl' => 'pagelinks',
				'p_to' => 'page',
			),
			array(
				'pl.pl_from AS pl_from_id',
				'p_to.page_id AS pl_to_id',
 			),
			$where,
			__METHOD__,
			array(), // options
			array(
				'p_to' => array(
					'INNER JOIN', 
					'pl.pl_namespace = p_to.page_namespace AND pl.pl_title = p_to.page_title'
				),
			)
		);
		$linkedPages = array();
		while ( $row = $linkedPagesResult->fetchObject() ) {
			if ( ! isset( $linkedPages[ $row->pl_from_id ] ) ) {
				$linkedPages[ $row->pl_from_id ] = 1;
			}
			else {
				$linkedPages[ $row->pl_from_id ]++;				
			}

			if ( ! isset( $linkedPages[ $row->pl_to_id ] ) ) {
				$linkedPages[ $row->pl_to_id ] = 1;
			}
			else {
				$linkedPages[ $row->pl_to_id ]++;				
			}			
		}

		$linkedPagesToKeep = array();
		$linkedPagesList = array();
		foreach ( $linkedPages as $pageId => $numLinks ) {
			if ( ! in_array( $pageId, $userWatchlistPageIds ) ) {
				$linkedPagesToKeep[ $pageId ] = array( 'num_links' => $numLinks );
				$linkedPagesList[] = $pageId;
			}
		}

		// arsort( $linkedPagesToKeep );
		// $html .= "<h1>linked pages before watch query</h1><pre>" . print_r( $linkedPagesToKeep, true ) . "</pre>";

		// $html .= 
		// 	"linked: " . count( $linkedPages ) 
		// 	. '<br />linked to keep:' . count($linkedPagesToKeep)
		// 	. '<br />watchlist: ' . count($userWatchlistPageIds);

		unset( $linkedPages );

		// arsort( $linkedPagesToKeep );

		$pageWatchQuery = new PageWatchesQuery;
		$queryInfo = $pageWatchQuery->getQueryInfo( 'p.page_id IN (' . $dbr->makeList( $linkedPagesList ) . ')' );
		$queryInfo['options'][ 'ORDER BY' ] = 'num_watches ASC';

		$pageWatchStats = $dbr->select(
			$queryInfo['tables'],
			array(
				'p.page_id AS page_id',
				'p.page_counter AS num_views',
				$pageWatchQuery->sqlNumWatches, // 'SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS num_watches'
			),
			$queryInfo['conds'],
			__METHOD__,
			$queryInfo['options'],
			$queryInfo['join_conds']
		);

		// add newly found number of watchers to linkedPagesToKeep...
		while ( $row = $pageWatchStats->fetchObject() ) {
			$linkedPagesToKeep[ $row->page_id ][ 'num_watches' ] = $row->num_watches;
			$linkedPagesToKeep[ $row->page_id ][ 'num_views' ] = $row->num_views;
		}

		$watches = array();
		$links = array();
		$watchNeedArray = array();
		$sortableLinkedPages = array();
		foreach( $linkedPagesToKeep as $pageId => $pageData ) {
			if ( isset( $pageData[ 'num_watches' ] ) ) {
				$numWatches = intval( $pageData[ 'num_watches' ] );
				$numViews = intval( $pageData[ 'num_views' ] );
			}
			else {
				$numWatches = 0;
				$numViews = 0;
			}
			$numLinks = intval( $pageData[ 'num_links' ] );

			$watchNeed = $numLinks * pow( $numViews, 2 );

			$sortableLinkedPages[] = array(
				'page_id' => $pageId,
				'num_watches' => $numWatches,
				'num_links' => $numLinks,
				'num_views' => $numViews,
				'watch_need' => $watchNeed,
			);
			$watches[] = $numWatches;
			$links[] = $numLinks;
			$watchNeedArray[] = $watchNeed;
		}
		array_multisort( $watches, SORT_ASC, $watchNeedArray, SORT_DESC, $sortableLinkedPages );



		// $html .= "<h1>watchlist</h1><pre>" . print_r( $userWatchlistPageIds, true ) . "</pre>";
		// $html .= "<h1>linked pages</h1><pre>" . print_r( $sortableLinkedPages, true ) . "</pre>";

		global $wgUser;
		$userIsViewer = $wgUser->getId() == $this->mUser->getId();



		$count = 1;
		$html .= "<h2>This wiki needs your help watching pages</h2>"
			. "<p>Few (if any) people are watching the pages below. They are related to other pages in your watchlist, and it'd be really great if you could help by watching some of them."
			. "<ol>";
		foreach( $sortableLinkedPages as $pageId => $pageInfo ) {

			$suggestedTitle = Title::newFromID( $pageInfo[ 'page_id' ] );
			if ( ! $suggestedTitle // for some reason some pages in the pagelinks table don't exist in either table page or table archive...
				|| $suggestedTitle->getNamespace() !== 0 // skip pages not in the main namespace
				|| $suggestedTitle->isRedirect() ) { // don't need redirects
				continue;
			}

			// $cats = $suggestedTitle->getParentCategories();
			// if (  )

			if ( $userIsViewer ) {

				// action=watch&token=9d1186bca6dd20866e607538b92be6c8%2B%5C
				$watchLinkURL = $suggestedTitle->getLinkURL( array(
					'action' => 'watch',
					'token' => WatchAction::getWatchToken( $suggestedTitle, $wgUser ),
				) );

				$watchLink = ' (<strong>' . Xml::element(
					'a',
					array( 'href' => $watchLinkURL ),
					'watch' //FIXME: use a message
				) . '</strong>)';

			}
			else {
				$watchLink = '';
			}

			$html .= '<li><a href="' . $suggestedTitle->getLinkURL() . '">' 
				. $suggestedTitle->getFullText() . '</a>'
				. $watchLink
				// . ' - watches: ' . $pageInfo[ 'num_watches' ]
				// 	. ', links: ' . $pageInfo[ 'num_links' ]
				// 	. ', views: ' . $pageInfo[ 'num_views' ]
				// 	. ', watch need: ' . $pageInfo[ 'watch_need' ]
				. '</li>';
			
			$count++;
			if ( $count > 20 ) {
				break;
			}
		
		}
		$html .= '</ol>';

		return $html;

	}


	
	
}
