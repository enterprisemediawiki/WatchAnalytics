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

class WikiWatchesQuery extends WatchesQuery {

	public $sqlNumPages = 'COUNT(*)';

	public $sqlNumWatches = 'SUM( IF( w.wl_title IS NOT NULL,             1, 0) )';
	public $sqlNumPending = 'SUM( IF( w.wl_notificationtimestamp IS NULL, 0, 1) )';
	// inherit $sqlMaxPendingMins 
	// inherit $sqlAvgPendingMins

	// public $sqlContentNumPages = 'SUM( IF(w.wl_namespace = ' . NS_MAIN . ', 1, 0) )';

	// public $sqlContentNumWatches = 'SUM( IF(w.wl_title IS NOT NULL AND w.wl_namespace = ' . NS_MAIN . ', 1, 0) )';
	// public $sqlContentNumPending = 'SUM( IF(w.wl_notificationtimestamp IS NULL AND w.wl_namespace = ' . NS_MAIN . ', 0, 1) )';
	// public $sqlContentMaxPendingMins = 
	// 	'MAX (
	// 		IF ( w.wl_namespace = ' . NS_MAIN . ', TIMESTAMPDIFF( MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP() ), NULL ) 
	// 	) AS max_pending_minutes';
	// public $sqlContentAvgPendingMins = '';


	// public $sqlMaxPendingMins = 'MAX( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS max_pending_minutes';
	// public $sqlAvgPendingMins = 'AVG( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS avg_pending_minutes';


	protected $fieldNames = array(

		// all namespaces
		'num_pages' => '',

		'num_unwatched' => '',
		'num_one_watched' => '',

		'num_watches' => 'watchanalytics-special-header-watches',
		'num_pending' => 'watchanalytics-special-header-pending-watches',
		'max_pending_minutes' => 'watchanalytics-special-header-pending-maxtime',
		'avg_pending_minutes' => 'watchanalytics-special-header-pending-averagetime',


		// NS_MAIN info only
		'content_num_pages' => '',

		'content_num_unwatched' => '',
		'content_num_one_watched' => '',
		
		'content_num_watches' => '',
		'content_num_pending' => '',
		'content_max_pending_minutes' => '',
		'content_avg_pending_minutes' => '',
		
		// What event recorded this data?
		'event_notes' => '',
	);

	function getQueryInfo() {

		$tables = array(
			'w' => 'watchlist',
			'u' => 'user',
			'p' => 'page',
		);

		$fields = array(
			$this->sqlUserName,
			$this->sqlNumWatches,
			$this->sqlNumPending,
			$this->sqlPercentPending,
			$this->sqlMaxPendingMins,
			$this->sqlAvgPendingMins,
		);

		$join_conds = array(
			'u' => array(
				'LEFT JOIN', 'u.user_id=w.wl_user'
			),
			'p' => array(
				'INNER JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
			),
		);

		$options = array(
			'GROUP BY' => 'w.wl_user'
		);

		$conds = array();

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
