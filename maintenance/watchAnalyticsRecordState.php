<?php

/**
 * This script captures the current state of watchedness on the wiki and
 * records it in the appropriate tables.
 *
 * Usage:
 *  no parameters
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @author James Montalvo
 * @ingroup Maintenance
 */

require_once( __DIR__ . '/../../../maintenance/Maintenance.php' );

class WatchAnalyticsRecordState extends Maintenance {
	
	public function __construct() {
		parent::__construct();
		
		$this->mDescription = "Record the current state of page-watching.";
	}
	
	public function execute() {
		global $wgTitle;
		
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

		$now = date( 'YmdHis', time() );
		$totalWatches = 0;
		$totalPending = 0;

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

			if ( intval( $page['num_watches'] ) === 0 ) {
				$unwatched++;
				if ( $page['page_namespace'] === NS_MAIN ) {
					$nsMainUnwatched++;
				}
			}
			else if ( intval( $page['num_watches'] ) === 1 ) {
				$oneWatched++;
				if ( $page['page_namespace'] === NS_MAIN ) {
					$nsMainOneWatched++;
				}
			}

			if ( intval( $page['num_reviewed'] ) === 0 ) {
				$unreviewed++;
				if ( $page['page_namespace'] === NS_MAIN ) {
					$nsMainUnreviewed++;
				}
			}
			else if ( intval( $page['num_reviewed'] ) === 1 ) {
				$oneReviewed++;
				if ( $page['page_namespace'] === NS_MAIN ) {
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

		$this->dbw->insert(
			'watch_tracking_page',
			$pages,
			__METHOD__
		);

		// Get all wiki info
		$allWikiQueryInfo = $this->getWikiQueryInfo();
		$mainWikiQueryInfo = $this->getWikiQueryInfo( NS_MAIN, 'content_' );

		$allNamespaces = $this->fetchAllFromQueryInfo( $allWikiQueryInfo, array(
			'num_pages', 'num_watches', 'num_pending', 'max_pending_minutes', 'avg_pending_minutes'
		) );
		$contentOnly = $this->fetchAllFromQueryInfo( $mainWikiQueryInfo, array(
			'content_num_pages', 'content_num_watches', 'content_num_pending',
			'content_max_pending_minutes', 'content_avg_pending_minutes'
		) );

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
		
		$this->output( "\n Finished recording the state of wiki watching. \n" );
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

		$sqlNumPages = "COUNT(*) AS {$prefix}num_pages";
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
	
}

$maintClass = "WatchAnalyticsRecordState";
require_once( DO_MAINTENANCE );
