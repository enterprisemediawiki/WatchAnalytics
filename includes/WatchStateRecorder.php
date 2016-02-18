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

class WatchStateRecorder {

	protected $dbr;
	protected $dbw;

	public function recordedWithinHours ( $withinHours = 1 ) {
		
		$withinHours = ( intval( $withinHours ) > 0 ) ? intval( $withinHours ) : 1;
		$withinDays = floor( $withinHours / 24 );
		if ( $withinDays > 0 ) {
			$withinHours = $withinHours % 24;
		}
		
		$now = new MWTimestamp();
		$diff = $now->diff( $this->getLatestAllWikiTimestamp() );

		if ( $diff->h >= $withinHours && $diff->days >= $withinDays ) {
			return false;
		}

		return true;
	}
	
	public function getLatestAllWikiTimestamp () {
	
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->selectRow(
			'watch_tracking_wiki',
			'tracking_timestamp',
			'', // conds
			__METHOD__,
			array(
				'LIMIT' => 1,
				'ORDER BY' => 'tracking_timestamp DESC',
			),
			null // join_conds
		);
		if ( $result && $result->tracking_timestamp ) {
			return new MWTimestamp( $result->tracking_timestamp );
		}
		else {
			return new MWTimestamp( '19700101000000' );
		}
		
	}

	public function recordAll() {
		
		$this->dbw = wfGetDB( DB_MASTER );
		
		// get user and page info
		$userWatchQuery = new UserWatchesQuery();
		$pageWatchQuery = new PageWatchesQuery();

		$userQueryInfo = $userWatchQuery->getQueryInfo();
		$pageQueryInfo = $pageWatchQuery->getQueryInfo();

		// override fields
		$userQueryInfo['fields'] = array(
			'u.user_id AS user_id',
			$userWatchQuery->sqlNumWatches,
			$userWatchQuery->sqlNumPending,
		);
		$pageQueryInfo['fields'] = array(
			'p.page_id AS page_id',
			'p.page_namespace AS page_namespace', // needed only for all-wiki info
			$pageWatchQuery->sqlNumWatches,
			$pageWatchQuery->sqlNumReviewed,
		);


		$users = $this->fetchAllFromQueryInfo( $userQueryInfo, array(
			'user_id', 'num_watches', 'num_pending'
		) );
		$pages = $this->fetchAllFromQueryInfo( $pageQueryInfo, array(
			'page_id', 'page_namespace', 'num_watches', 'num_reviewed'
		) );

		$now = new MWTimestamp();
		$now = $now->format('YmdHis');

		$unwatched = 0;
		$oneWatched = 0;
		$unreviewed = 0;
		$oneReviewed = 0;

		$nsMainUnwatched = 0;
		$nsMainOneWatched = 0;
		$nsMainUnreviewed = 0;
		$nsMainOneReviewed = 0;

		foreach( $users as $key => $user ) {
			$users[$key]['tracking_timestamp'] = $now;
		}

		foreach( $pages as $key => $page ) {
			$page['tracking_timestamp'] = $now;

			$numWatches = intval( $page['num_watches'] );
			$numReviewed = intval( $page['num_reviewed'] );
			$pageNS = intval( $page['page_namespace'] );

			if ( $numWatches === 0 ) {
				$unwatched++;
				if ( $pageNS === NS_MAIN ) {
					$nsMainUnwatched++;
				}
			}
			else if ( $numWatches === 1 ) {
				$oneWatched++;
				if ( $pageNS === NS_MAIN ) {
					$nsMainOneWatched++;
				}
			}

			if ( $numReviewed === 0 ) {
				$unreviewed++;
				if ( $pageNS === NS_MAIN ) {
					$nsMainUnreviewed++;
				}
			}
			else if ( $numReviewed === 1 ) {
				$oneReviewed++;
				if ( $pageNS === NS_MAIN ) {
					$nsMainOneReviewed++;
				}
			}

			unset( $page['page_namespace'] ); // can't be present for insert statement below

			$pages[$key] = $page;
			// unset( $pages[$key]['page_namespace'] );
		}

		$this->dbw->insert(
			'watch_tracking_user',
			$users,
			__METHOD__
		);

        foreach( array_chunk( $pages, 100 ) as $chunk ) {
            $this->dbw->insert(
                'watch_tracking_page',
                $chunk,
                __METHOD__
            );
        }

		// Get all wiki info
		$allWikiQueryInfo = $this->getWikiQueryInfo();
		$mainWikiQueryInfo = $this->getWikiQueryInfo( NS_MAIN, 'content_' );

		$allNamespaces = $this->fetchAllFromQueryInfo( $allWikiQueryInfo, array(
			'num_pages', 'num_watches', 'num_pending', 'max_pending_minutes', 'avg_pending_minutes'
		) );

		$allNamespaces[0][ 'max_pending_minutes' ] = 
			$allNamespaces[0][ 'max_pending_minutes' ]
			? $allNamespaces[0][ 'max_pending_minutes' ] : 0;
		
		$allNamespaces[0][ 'avg_pending_minutes' ] =
			$allNamespaces[0][ 'avg_pending_minutes' ]
			? $allNamespaces[0][ 'avg_pending_minutes' ] : 0;

		$contentOnly = $this->fetchAllFromQueryInfo( $mainWikiQueryInfo, array(
			'content_num_pages', 'content_num_watches', 'content_num_pending',
			'content_max_pending_minutes', 'content_avg_pending_minutes'
		) );

		$contentOnly[0][ 'content_max_pending_minutes' ] = 
			$contentOnly[0][ 'content_max_pending_minutes' ] 
			? $contentOnly[0][ 'content_max_pending_minutes' ] : 0;
		
		$contentOnly[0][ 'content_avg_pending_minutes' ] = 
			$contentOnly[0][ 'content_avg_pending_minutes' ]
			? $contentOnly[0][ 'content_avg_pending_minutes' ] : 0;


		$allWikiAnalytics = $allNamespaces[0] + $contentOnly[0] + array(
			'tracking_timestamp' => $now,

			'num_unwatched' => $unwatched,
			'num_one_watched' => $oneWatched,
			'num_unreviewed' => $unreviewed,
			'num_one_reviewed' => $oneReviewed,

			'content_num_unwatched' => $nsMainUnwatched,
			'content_num_one_watched' => $nsMainOneWatched,
			'content_num_unreviewed' => $nsMainUnreviewed,
			'content_num_one_reviewed' => $nsMainOneReviewed,
		);

		$this->dbw->insert(
			'watch_tracking_wiki',
			$allWikiAnalytics,
			__METHOD__
		);
		
		return true;
	}

	public function fetchAllFromQueryInfo ( $queryInfo, $columnsToKeep ) {
		$result = $this->dbw->select(
			$queryInfo['tables'],
			$queryInfo['fields'],
			$queryInfo['conds'],
			__METHOD__,
			$queryInfo['options'],
			$queryInfo['join_conds']
		);

		$output = array();

		while ( $row = $result->fetchRow() ) {
			$c = count( $output );
			foreach ($columnsToKeep as $col) {
				$output[$c][$col] = $row[$col];
			}
		}

		return $output;
	}

	public function getWikiQueryInfo ($namespace = false, $prefix = '') {

		$sqlNumPages = "COUNT( DISTINCT p.page_id ) AS {$prefix}num_pages";
		$sqlNumWatches = "SUM( IF( w.wl_title IS NOT NULL,             1, 0) ) AS {$prefix}num_watches";
		$sqlNumPending = "SUM( IF( w.wl_notificationtimestamp IS NULL, 0, 1) ) AS {$prefix}num_pending";
		$sqlMaxPendingMins = "MAX( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS {$prefix}max_pending_minutes";
		$sqlAvgPendingMins = "AVG( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS {$prefix}avg_pending_minutes";


		$tables = array(
			'w' => 'watchlist',
			'p' => 'page',
		);

		$fields = array(
			$sqlNumPages,
			$sqlNumWatches,
			$sqlNumPending,
			$sqlMaxPendingMins,
			$sqlAvgPendingMins,
		);

		$join_conds = array(
			'p' => array(
				'RIGHT JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
			),
		);

		$options = array(
			// unlike pages, group by NOTHING
			// 'GROUP BY' => 'p.page_title, p.page_namespace'
		);

		$conds = '';
		if ( $namespace !== false ) {
			$conds = 'p.page_namespace=' . $namespace;
		}

		$return = array(
			'tables' => $tables,
			'fields' => $fields,
			'join_conds' => $join_conds,
			'conds' => $conds,
			'options' => $options,
		);

		return $return;
	}

	/**
	 * Record relevant info in watch_tracking_page and watch_tracking_user
	 * after a change to a page (e.g. an edit, a move, etc)
	 */
	static public function recordPageChange ( $article ) {

		$timestamp = date( "YmdHis", time() );
		$title = $article->getTitle();

		// page watch stats
		list( $numWatchers, $numReviewed, $userIdArray ) = self::getPageWatchInfo( $title );


		// query for each users' total watches/reviews
		$userWQ = new UserWatchesQuery();
		$userWatchStats = $userWQ->getMultiUserWatchStats( $userIdArray );
		$userInsertData = array();
		foreach ( $userWatchStats as $uData ) {
			$userInsertData[] = array(
				'tracking_timestamp' => $timestamp,
				'user_id' => $uData->wl_user,
				'num_watches' => $uData->num_watches,
				'num_pending' => $uData->num_pending,
			);
		}


		$dbw = wfGetDB( DB_MASTER );

		// insert into watch_tracking_page: $timestamp, $title->getId(), $numWatchers, $numReviewed
		$dbw->replace(
			'watch_tracking_page',
			array( array( 'tracking_timestamp', 'page_id' ) ),
			array(
				array(
					'tracking_timestamp' => $timestamp,
					'page_id' => $title->getArticleID(),
					'num_watches' => $numWatchers,
					'num_reviewed' => $numReviewed,
				),
			),
			__METHOD__
		);

		// insert into watch_tracking_user: timestamp, user ID, num watches, num reviews
		// @todo FIXME: this incorrectly records the editors watch state if they change from 
		// watched to unwatched or unwatched to watched during the edit...maybe.
		$dbw->replace(
			'watch_tracking_user',
			array( array( 'tracking_timestamp', 'user_id' ) ),
			$userInsertData,
			__METHOD__
		);

		// do a full wiki record?
		// take too long? excessive?
		// all buildable by page table, except avg/max time

		return true;

	}

	static public function recordReview ( User $user, Title $title ) {

		$timestamp = date( 'YmdHis', time() );

		$userWQ = new UserWatchesQuery();
		$userWatchStats = $userWQ->getMultiUserWatchStats( array( $user->getId() ) );

		$dbw = wfGetDB( DB_MASTER );

		// necessary to use replace here? probably, in case two requests
		// are made in rapid succession such that the second gets a read
		// request from the database saying that the notification timestamp
		// hasn't been cleared yet, suggesting it should be cleared again
		$dbw->replace(
			'watch_tracking_user',
			array( array( 'tracking_timestamp', 'user_id' ) ),
			array(
				'tracking_timestamp' => $timestamp,
				'user_id' => $userWatchStats[0]->wl_user,
				'num_watches' => $userWatchStats[0]->num_watches,
				'num_pending' => $userWatchStats[0]->num_pending,
			),
			__METHOD__
		);

		// page watch stats (disregarding third var, array of user IDs)
		list( $numWatchers, $numReviewed ) = self::getPageWatchInfo( $title );

		$dbw->replace(
			'watch_tracking_page',
			array( array( 'tracking_timestamp', 'page_id' ) ),
			array(
				'tracking_timestamp' => $timestamp,
				'page_id' => $title->getArticleID(),
				'num_watches' => $numWatchers,
				'num_reviewed' => $numReviewed,
			),
			__METHOD__
		);

		return true;

	}

	static public function getPageWatchInfo ( Title $title ) {

		// query for page watchers
		$pageWQ = new PageWatchesQuery();
		$watchers = $pageWQ->getPageWatchers( $title->getDBkey(), $title->getNamespace() );

		// PHP count num watchers and reviewers (watchers could change if user (un)checks "watch this page" box)
		$numWatchers = count( $watchers );
		$numReviewed = 0;
		$userIdArray = array();
		foreach( $watchers as $w ) {
			if ( $w->wl_notificationtimestamp === NULL ) {
				$numReviewed++;
			}
			$userIdArray[] = $w->wl_user;
		}

		return array( $numWatchers, $numReviewed, $userIdArray );
	}

}
