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
		$this->dbr = wfGetDB( DB_SLAVE );

	}

	/**
	 * Handles something.
	 * 
	 * @return bool
	 */
	public function getWatchSuggestionList () {

		$html = '';

		// gets id, NS and title of all pages in users watchlist in NS_MAIN
		$userWatchlist = $this->getUserWatchlist( $this->mUser, NS_MAIN );

		$linkedPages = $this->getPagesRelatedByLinks( $userWatchlist );

		$pageWatchQuery = new PageWatchesQuery;
		$pageWatchesAndViews = $pageWatchQuery->getPageWatchesAndViews( array_keys( $linkedPages ) );

		// add newly found number of watchers to linkedPages...
		foreach ( $pageWatchesAndViews as $row ) {
			$linkedPages[ $row->page_id ][ 'num_watches' ] = $row->num_watches;
			$linkedPages[ $row->page_id ][ 'num_views' ] = $row->num_views;
		}

		$sortedPages = $this->sortPagesByWatchImportance( $linkedPages );


		global $wgUser;
		$userIsViewer = $wgUser->getId() == $this->mUser->getId();



		$count = 1;
		$watchSuggestionsTitle = wfMessage( 'pendingreviews-watch-suggestion-title' )->text();
		$watchSuggestionsDescription = wfMessage( 'pendingreviews-watch-suggestion-description' )->text();

		global $egPendingReviewsNumberWatchSuggestions;

		$watchSuggestionsLIs = array();
		foreach( $sortedPages as $pageId => $pageInfo ) {

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

				$watchLink =
					'<strong>'
					. Xml::element(
						'a',
						array(
							'href' => $watchLinkURL,
							'class' => 'pendingreviews-watch-suggest-link',
							'suggest-title-prefixed-text' => $suggestedTitle->getPrefixedDBkey(),
							'thanks-msg' => wfMessage( 'pendingreviews-watch-suggestion-thanks' )->text()// FIXME: there's a better way
						),
						wfMessage( 'pendingreviews-watch-suggestion-watchlink' )->text()
					)
					. ':</strong> ';

			}
			else {
				$watchLink = '';
			}

			$pageLink = '<a href="' . $suggestedTitle->getLinkURL() . '">' . $suggestedTitle->getFullText() . '</a>';

			$watchSuggestionsLIs[] = '<li>' . $watchLink . $pageLink . '</li>';

			$count++;
			if ( $count > $egPendingReviewsNumberWatchSuggestions ) {
				break;
			}
		
		}

		$numTopWatchers = 20;

		$html .= "<br /><br />"
			. "<h3>$watchSuggestionsTitle</h3>"
			. "<p>$watchSuggestionsDescription</p>"
			. WatchAnalyticsHtmlHelper::formatListArray( $watchSuggestionsLIs, 2 )
			. '<br /><h3>Watch Leaders</h3>'
			. "<p>The following $numTopWatchers users are watching the most pages.</p>"
			. WatchAnalyticsHtmlHelper::formatListArray( $this->getMostWatchesListArray( $numTopWatchers ), 2 );

		return $html;

	}



	public function getUserWatchlist ( User $user, $namespaces = array() ) {

		if ( ! is_array( $namespaces ) ) {
			if ( intval( $namespaces ) < 0 ) {
				throw new MWException( __METHOD__ . ' argument $namespace requires integer or array' );
			}
			$namespaces = array( $namespaces );
		}
		
		if ( count( $namespaces ) > 1 ) {
			$namespaceCondition = 'AND p.page_namespace IN (' . $this->dbr->makeList( $namespaces ) . ')';
		}
		else if ( count( $namespaces ) === 1 ) {
			$namespaceCondition = 'AND p.page_namespace = ' . $namespaces[0];
		}
		else {
			$namespaceCondition = '';
		}

		$userId = $user->getId();

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
		$userWatchlist = $this->dbr->select(
			array(
				'p' => 'page',
				'w' => 'watchlist',
			),
			array(
				'p.page_id AS p_id',
				'w.wl_namespace AS p_namespace',
				'w.wl_title AS p_title',
 			),
			"w.wl_user=$userId " . $namespaceCondition,
			__METHOD__,
			array(), // options
			array(
				'w' => array(
					'LEFT JOIN', 
					'w.wl_namespace = p.page_namespace AND w.wl_title = p.page_title'
				)
			)
		);

		$return = array();
		while ( $row = $userWatchlist->fetchObject() ) {
			$return[] = $row;
		}


		return $userWatchlist;
	}
	
	
	public function getPagesRelatedByLinks ( $userWatchlist ) {


		$pagesIds = array();
		$pageTitles = array();
		foreach ( $userWatchlist as $row ) {
			$userWatchlistPageIds[] = $row->p_id;

			// FIXME: for now this will only work in NS_MAIN since the next query
			// these are used in assumes NS_MAIN
			$userWatchlistPageTitles[] = $row->p_title;
		}

		$ids = $this->dbr->makeList( $userWatchlistPageIds );
		$titles = $this->dbr->makeList( $userWatchlistPageTitles );

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


		$linkedPagesResult = $this->dbr->select(
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
		foreach ( $linkedPages as $pageId => $numLinks ) {
			if ( ! in_array( $pageId, $userWatchlistPageIds ) ) {
				$linkedPagesToKeep[ $pageId ] = array( 'num_links' => $numLinks );
			}
		}

		return $linkedPagesToKeep;

	}


	public function sortPagesByWatchImportance ( $pages ) {

		$watches = array();
		$links = array();
		$watchNeedArray = array();
		$sortedPages = array();
		foreach( $pages as $pageId => $pageData ) {
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

			$sortedPages[] = array(
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
		array_multisort( $watches, SORT_ASC, $watchNeedArray, SORT_DESC, $sortedPages );

		return $sortedPages;
	}

	// SELECT 
	// 	u.user_name AS uname,
	// 	u.user_real_name AS name,
	// 	COUNT( * ) AS user_watches
	// FROM
	// 	watchlist AS w
	// RIGHT JOIN page AS p ON
	// 	(w.wl_namespace = p.page_namespace AND w.wl_title = p.page_title)
	// LEFT JOIN user AS u ON
	// 	(w.wl_user = u.user_id)
	// WHERE
	// 	p.page_is_redirect = 0
	// GROUP BY w.wl_user
	// ORDER BY user_watches DESC
	public function getMostWatchesListArray ( $limit = 20 ) {

		$mostWatches = $this->dbr->select(
			array(
				'w' => 'watchlist',
				'p' => 'page',
				'u' => 'user',
			),
			array(
				'u.user_name AS user_name',
				'u.user_real_name AS real_name',
				'COUNT( * ) AS user_watches',
 			),
			'p.page_is_redirect = 0',
			__METHOD__,
			array(
				'GROUP BY' => 'w.wl_user',
				'ORDER BY' => 'user_watches DESC',
				'LIMIT' => $limit,
			),
			array(
				'p' => array(
					'RIGHT JOIN', 
					'w.wl_namespace = p.page_namespace AND w.wl_title = p.page_title'
				),
				'u' => array(
					'LEFT JOIN', 
					'w.wl_user = u.user_id'
				),
			)
		);

		$return = array();
		$count = 0;
		while ( $user = $mostWatches->fetchObject() ) {
			$count++;
			// CONSIDERING usering real name
			// if ( $user->real_name ) {
			// 	$displayName = $user->real_name;
			// }
			// else {
			// 	$displayName = $user->user_name;
			// }

			$userPage = User::newFromName( $user->user_name )->getUserPage();
			$userPageLink = Linker::link( $userPage, htmlspecialchars( $userPage->getFullText() ) );

			$watches = '<strong>' . $user->user_watches . '</strong> pages watched';

			$return[] = "<li>$userPageLink - $watches</li>";
		}

		return $return;

	}



}
