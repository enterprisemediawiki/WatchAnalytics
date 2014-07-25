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

class UserWatchesQuery extends WatchesQuery {

	public $sqlUserName = 'u.user_name AS user_name';
	public $sqlNumWatches = 'COUNT(*) AS num_watches';
	public $sqlNumPending = 'SUM( IF(w.wl_notificationtimestamp IS NULL, 0, 1) ) AS num_pending';
	public $sqlPercentPending = 'SUM( IF(w.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending';

	protected $fieldNames = array(
		'user_name'               => 'watchanalytics-special-header-user',
		'num_watches'             => 'watchanalytics-special-header-watches',
		'num_pending'             => 'watchanalytics-special-header-pending-watches',
		'percent_pending'         => 'watchanalytics-special-header-pending-percent',
		'max_pending_minutes'     => 'watchanalytics-special-header-pending-maxtime',
		'avg_pending_minutes'     => 'watchanalytics-special-header-pending-averagetime',
	);

	public function getQueryInfo( $conds = null ) {

		$this->tables = array(
			'w' => 'watchlist',
			'u' => 'user',
			'p' => 'page',
		);

		$this->fields = array(
			$this->sqlUserName,
			$this->sqlNumWatches,
			$this->sqlNumPending,
			$this->sqlPercentPending,
			$this->sqlMaxPendingMins,
			$this->sqlAvgPendingMins,
		);
		
		$this->conds = $conds ? $conds : array();

		$this->join_conds = array(
			'u' => array(
				'LEFT JOIN', 'u.user_id=w.wl_user'
			),
			'p' => array(
				'INNER JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
			),
		);

		$this->options = array(
			'GROUP BY' => 'w.wl_user'
		);
		
		return parent::getQueryInfo();

	}

	public function getUserWatchStats ( User $user ) {
	
		$qInfo = $this->getQueryInfo();

		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			$qInfo['tables'],
			$qInfo['fields'],
			'w.wl_user=' . $user->getId(),
			__METHOD__,
			$qInfo['options'],
			$qInfo['join_conds']
		);
		
		return $dbr->fetchRow( $res );

	}

	public function getUserPendingWatches ( User $user ) {
		
		$tables = array(
			'w' => 'watchlist',
			'p' => 'page',
		);

		$fields = array(
			'p.page_id AS page_id',
			'w.wl_notificationtimestamp AS notificationtimestamp',
		);

		$conds = 'w.wl_user=' . $user->getId() . ' AND w.wl_notificationtimestamp IS NOT NULL';

		$options = array(
			'ORDER BY' => 'w.wl_notificationtimestamp ASC',
			// 'LIMIT' => $limit,
		);

		$join_conds = array(
			'p' => array(
				'INNER JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
			),
		);


		$dbr = wfGetDB( DB_SLAVE );

		$watchResult = $dbr->select(
			$tables,
			$fields,
			$conds,
			__METHOD__,
			$options,
			$join_conds
		);
		
		$pending = array();

		while ( $row = $dbr->fetchRow( $watchResult ) ) {
			$title = Title::newFromID( $row['page_id'] );
			if ( ! $title->exists() ) {
				$title = false;
			}

			$pageID = $row['page_id'];
			$notificationTimestamp = $row['notificationtimestamp'];

			$revResults = $dbr->select(
				array( 'r' => 'revision' ),
				array( '*' ),
				// array(
				// 	'r.rev_id AS rev_id',
				// 	'r.rev_comment AS rev_comment',
				// 	'r.rev_user AS rev_user_id',
				// 	'r.rev_user_text AS rev_user_name',
				// 	'r.rev_timestamp AS rev_timestamp',
				// 	'r.rev_len AS rev_len',
				// ),
				"r.rev_page=$pageID AND r.rev_timestamp>=$notificationTimestamp",
				__METHOD__,
				array( 'ORDER BY' => 'rev_timestamp ASC' ),
				null
			);
			$revsPending = array();
			while ( $rev = $revResults->fetchObject() ) {
				$revsPending[] = $rev;
			}

			$logResults = $dbr->select(
				array( 'l' => 'logging' ),
				array( '*' ),
				// array(
				// 	'l.log_id AS log_id',
				// 	'l.log_type AS log_type',
				// 	'l.log_action AS log_action',
				// 	'l.log_timestamp AS log_timestamp',
				// 	'l.log_user AS log_user_id',
				// 	'l.log_user_text AS log_user_name',
				// ),
				"l.log_page=$pageID AND l.log_timestamp>=$notificationTimestamp 
					AND l.log_type NOT IN ('interwiki','newusers','patrol','rights','upload')",
				__METHOD__,
				array( 'ORDER BY' => 'log_timestamp ASC' ),
				null
			);
			$logPending = array();
			while ( $log = $logResults->fetchObject() ) {
				$logPending[] = $log;
			}

			$pending[] = (object) array(
				'notificationTimestamp' => $notificationTimestamp,
				'title' => $title,
				'newRevisions' => $revsPending,
				'log' => $logPending,
			);
		}

		return $pending;
	}
}
