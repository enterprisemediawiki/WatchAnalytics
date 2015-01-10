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

class PageWatchesQuery extends WatchesQuery {

	public $sqlNsAndTitle = 'CONCAT(p.page_namespace, ":", p.page_title) AS page_ns_and_title';
	public $sqlNumWatches = 'SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS num_watches';
	public $sqlNumReviewed = 'SUM( IF(w.wl_title IS NOT NULL AND w.wl_notificationtimestamp IS NULL, 1, 0) ) AS num_reviewed';
	public $sqlPercentPending = 'SUM( IF(w.wl_title IS NOT NULL AND w.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending';
	
	protected $fieldNames = array(
		'page_ns_and_title'       => 'watchanalytics-special-header-page-title',
		'num_watches'             => 'watchanalytics-special-header-watches',
		'num_reviewed'            => 'watchanalytics-special-header-reviewed-watches',
		'percent_pending'         => 'watchanalytics-special-header-pending-percent',
		'max_pending_minutes'     => 'watchanalytics-special-header-pending-maxtime',
		'avg_pending_minutes'     => 'watchanalytics-special-header-pending-averagetime',
	);

	function getQueryInfo( $conds = null ) {
	
		$this->fields = array(
			$this->sqlNsAndTitle,
			$this->sqlNumWatches,
			$this->sqlNumReviewed,
			$this->sqlPercentPending,
			$this->sqlMaxPendingMins,
			$this->sqlAvgPendingMins,
		);

		$this->conds = $conds ? $conds : array( 'p.page_namespace IS NOT NULL' );
	
		$this->tables = array( 'w' => 'watchlist' );
	
		$this->join_conds = array();
		
		// optionally join the 'user_groups' table to filter by user group
		if ( $this->userGroupFilter ) {
			$this->tables['ug'] = 'user_groups';
			$this->join_conds['ug'] = array(
				'RIGHT JOIN', "w.wl_user = ug.ug_user AND ug.ug_group = \"{$this->userGroupFilter}\""
			);
		}

		// JOIN 'page' table
		$this->tables['p'] = 'page';
		$this->join_conds['p'] = array(
			'RIGHT JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
		);

		// optionally join the 'categorylinks' table to filter by page category
		if ( $this->categoryFilter ) {
			$this->tables['cat'] = 'categorylinks';
			$this->join_conds['cat'] = array(
				'RIGHT JOIN', "cat.cl_from = p.page_id AND cat.cl_to = \"{$this->categoryFilter}\""
			);
		}
		
		$this->options = array(
			// 'GROUP BY' => 'w.wl_title, w.wl_namespace'
			'GROUP BY' => 'p.page_title, p.page_namespace',
		);
		
		return parent::getQueryInfo();

	}

	public function getPageWatchesAndViews ( $pages ) {
		
		$dbr = wfGetDB( DB_SLAVE );

		$pagesList = $dbr->makeList( $pages );

		$queryInfo = $this->getQueryInfo( 'p.page_id IN (' . $pagesList . ')' );
		$queryInfo['options'][ 'ORDER BY' ] = 'num_watches ASC';

		$pageWatchStats = $dbr->select(
			$queryInfo['tables'],
			array(
				'p.page_id AS page_id',
				'p.page_counter AS num_views',
				$this->sqlNumWatches, // 'SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS num_watches'
			),
			$queryInfo['conds'],
			__METHOD__,
			$queryInfo['options'],
			$queryInfo['join_conds']
		);

		$return = array();
		while ( $row = $pageWatchStats->fetchObject() ) {
			$return[] = $row;
		}

		return $return;

	}
	
}
