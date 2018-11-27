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
 * @license MIT License
 */

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/WatchAnalytics/WatchAnalytics.php" );
EOT;
	exit( 1 );
}

class PageWatchesQuery extends WatchesQuery {

	public $sqlNsAndTitle = 'CONCAT(p.page_namespace, ":", p.page_title) AS page_ns_and_title';
	public $sqlNumWatches = 'SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS num_watches';
	public $sqlNumReviewed = 'SUM( IF(w.wl_title IS NOT NULL AND w.wl_notificationtimestamp IS NULL, 1, 0) ) AS num_reviewed';
	public $sqlPercentPending = 'SUM( IF(w.wl_title IS NOT NULL AND w.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending';
	public $sqlWatchQuality = 'SUM( user_watch_scores.engagement_score ) AS watch_quality';

	protected $fieldNames = [
		'page_ns_and_title'       => 'watchanalytics-special-header-page-title',
		'num_watches'             => 'watchanalytics-special-header-watches',
		'num_reviewed'            => 'watchanalytics-special-header-reviewed-watches',
		'percent_pending'         => 'watchanalytics-special-header-pending-percent',
		'max_pending_minutes'     => 'watchanalytics-special-header-pending-maxtime',
		'avg_pending_minutes'     => 'watchanalytics-special-header-pending-averagetime',
		'watch_quality'           => 'watchanalytics-special-header-watch-quality',
	];

	public function getQueryInfo( $conds = null ) {
		$this->fields = [
			$this->sqlNsAndTitle,
			$this->sqlNumWatches,
			$this->sqlNumReviewed,
			$this->sqlPercentPending,
			$this->sqlMaxPendingMins,
			$this->sqlAvgPendingMins,
			$this->sqlWatchQuality,
		];

		$this->conds = $conds ? $conds : [ 'p.page_namespace IS NOT NULL' ];

		$this->tables = [ 'w' => 'watchlist' ];

		$this->join_conds = [];

		// optionally join the 'user_groups' table to filter by user group
		if ( $this->userGroupFilter ) {
			$this->tables['ug'] = 'user_groups';
			$this->join_conds['ug'] = [
				'RIGHT JOIN', "w.wl_user = ug.ug_user AND ug.ug_group = \"{$this->userGroupFilter}\""
			];
		}

		// JOIN 'page' table
		$this->tables['p'] = 'page';
		$this->join_conds['p'] = [
			'RIGHT JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
		];

		// optionally join the 'categorylinks' table to filter by page category
		if ( $this->categoryFilter ) {
			$this->setCategoryFilterQueryInfo();
		}

		// add user watch scores join
		$this->tables['user_watch_scores'] = '(
			SELECT
				w2.wl_user AS user_name,
				(
					ROUND( IFNULL(
						EXP(
							-0.01 * SUM(
								IF(w2.wl_notificationtimestamp IS NULL, 0, 1)
							)
						)
						*
						EXP(
							-0.01 * FLOOR(
								AVG(
									TIMESTAMPDIFF( DAY, w2.wl_notificationtimestamp, UTC_TIMESTAMP() )
								)
							)
						),
					1), 3)
				) AS engagement_score

			FROM watchlist AS w2
			GROUP BY w2.wl_user

		)';
		$this->join_conds['user_watch_scores'] = [
			'LEFT JOIN', 'user_watch_scores.user_name = w.wl_user'
		];

		$this->options = [
			// 'GROUP BY' => 'w.wl_title, w.wl_namespace'
			'GROUP BY' => 'p.page_title, p.page_namespace',
		];

		return parent::getQueryInfo();
	}

	public function getPageWatchesAndViews( $pages ) {
		$dbr = wfGetDB( DB_REPLICA );

		$pagesList = $dbr->makeList( $pages );

		$queryInfo = $this->getQueryInfo( 'p.page_id IN (' . $pagesList . ')' );
		$queryInfo['options'][ 'ORDER BY' ] = 'num_watches ASC';

		$cols = [
			'p.page_id AS page_id',
			$this->sqlNumWatches, // 'SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS num_watches'
		];

		global $egWatchAnalyticsPageCounter;
		if ( $egWatchAnalyticsPageCounter ) {
			$queryInfo['tables']['counter'] = $egWatchAnalyticsPageCounter['table'];
			$countCol = $egWatchAnalyticsPageCounter['column'];
			$countPageIdJoinCol = $egWatchAnalyticsPageCounter['join_column'];

			$cols[] = "counter.$countCol AS num_views";
			$queryInfo['join_conds']['counter'] = [
				'LEFT JOIN' , "p.page_id = counter.$countPageIdJoinCol"
			];
		}

		$pageWatchStats = $dbr->select(
			$queryInfo['tables'],
			$cols,
			$queryInfo['conds'],
			__METHOD__,
			$queryInfo['options'],
			$queryInfo['join_conds']
		);

		$return = [];
		while ( $row = $pageWatchStats->fetchObject() ) {
			if ( ! isset( $row->num_views ) ) {
				$row->num_views = 1;
			}
			$return[] = $row;
		}

		return $return;
	}

	public function getPageWatchers( $titleKey, $ns = NS_MAIN ) {
		$dbr = wfGetDB( DB_REPLICA );

		$pageWatchStats = $dbr->select(
			[ 'w' => 'watchlist' ],
			[ 'wl_user', 'wl_notificationtimestamp' ],
			[
				'w.wl_namespace' => $ns,
				'w.wl_title' => $titleKey,
			],
			__METHOD__,
			null, // no options
			null // no join conds (no other tables)
		);

		$return = [];
		while ( $row = $pageWatchStats->fetchObject() ) {
			$return[] = $row;
		}

		return $return;
	}

	public function getPageWatchQuality( Title $title ) {
		$dbr = wfGetDB( DB_REPLICA );

		$queryInfo = $this->getQueryInfo( [
			'p.page_namespace' => $title->getNamespace(),
			'p.page_title' => $title->getDBkey(),
		] );

		$pageData = $dbr->selectRow(
			$queryInfo['tables'],
			[
				$this->sqlWatchQuality
			],
			$queryInfo['conds'],
			__METHOD__,
			$queryInfo['options'],
			$queryInfo['join_conds']
		);

		// $row = $pageData->fetchObject();
		if ( $pageData ) {
			return $pageData->watch_quality;
		} else {
			return 0;
		}
	}

}
