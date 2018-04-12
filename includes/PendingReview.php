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

class PendingReview {

	/**
	 * @var string $notificationTimestamp: time of oldest change user hasn't seen
	 * @example 20141031072315
	 */
	public $notificationTimestamp;

	/**
	 * @var Title $title
	 */
	public $title;

	/**
	 * @var array|false $newRevisions
	 * @todo FIXME: document
	 */
	public $newRevisions;

	/**
	 * @var string $deletedTitle: text of deleted title
	 * @todo document is this "Main Page" or "Main_Page"
	 */
	public $deletedTitle;

	/**
	 * @var int $deletedNS
	 */
	public $deletedNS;

	/**
	 * @var array|false $deletionLog
	 * @todo FIXME: document
	 */
	public $deletionLog;

	/**
	 * @var int $numReviewers: number of people who have reviewed this page
	 */
	public $numReviewers;

	/**
	 * @var array|false $log
	 * @todo FIXME: document
	 */
	public $log;

	public function __construct ( $row ) {

		$pageID = $row['page_id'];
		$notificationTimestamp = $row['notificationtimestamp'];

		if ( $pageID ) {
			$title = Title::newFromID( $pageID );
		}
		else {
			$title = false;
		}

		if ( $pageID && $title->exists() ) {

			$dbr = wfGetDB( DB_SLAVE );


			$revResults = $dbr->select(
				array( 'r' => 'revision' ),
				Revision::selectFields(),
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

			$deletedNS = false;
			$deletedTitle = false;
			$deletionLog = false;

		}
		else {
			$deletedNS = $row['namespace'];
			$deletedTitle = $row['title'];
			$deletionLog = $this->getDeletionLog( $deletedTitle, $deletedNS, $notificationTimestamp );
			$logPending = false;
			$revsPending = false;
		}


		$this->notificationTimestamp = $notificationTimestamp;
		$this->title = $title;
		$this->newRevisions = $revsPending;
		$this->deletedTitle = $deletedTitle;
		$this->deletedNS = $deletedNS;
		$this->deletionLog = $deletionLog;
		$this->log = $logPending;
		$this->numReviewers = intval( $row['num_reviewed'] );

	}

	/*
		Make more like this:

		SELECT
			p.page_id AS id,
			w.wl_namespace AS namespace,
			w.wl_title AS title,
			w.wl_notificationtimestamp AS notificationtimestamp
		FROM `watchlist` `w`
		LEFT JOIN `page` `p` ON
			((p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title))
		LEFT JOIN `logging` `log` ON
			log.log_namespace = w.wl_namespace
			AND log.log_title = w.wl_title
			AND p.page_namespace IS NULL
			AND p.page_title IS NULL
			AND log.log_action = 'delete'
		WHERE
			w.wl_user=1
			AND w.wl_notificationtimestamp IS NOT NULL
		ORDER BY w.wl_notificationtimestamp ASC;

	*/
	static public function getPendingReviewsList ( User $user ) {

		$tables = array(
			'w' => 'watchlist',
			'p' => 'page',
			'log' => 'logging',
		);

		$fields = array(
			'p.page_id AS page_id',
			'log.log_action AS log_action',
			'w.wl_namespace AS namespace',
			'w.wl_title AS title',
			'w.wl_notificationtimestamp AS notificationtimestamp',
			'(SELECT COUNT(*) FROM watchlist AS subwatch
			  WHERE
				subwatch.wl_namespace = w.wl_namespace
				AND subwatch.wl_title = w.wl_title
				AND subwatch.wl_notificationtimestamp IS NULL
			) AS num_reviewed',
		);

		$conds = 'w.wl_user=' . $user->getId() . ' AND w.wl_notificationtimestamp IS NOT NULL';

		$options = array(
			'ORDER BY' => 'num_reviewed ASC, w.wl_notificationtimestamp ASC',
			// 'LIMIT' => $limit,
		);

		$join_conds = array(
			'p' => array(
				'LEFT JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
			),
			'log' => array(
				'LEFT JOIN',
				'log.log_namespace = w.wl_namespace '
				. ' AND log.log_title = w.wl_title'
				. ' AND p.page_namespace IS NULL'
				. ' AND p.page_title IS NULL'
				. ' AND log.log_action IN ("delete","move")'
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

			$pending[] = new self( $row );

		}

		return $pending;
	}

	public function getDeletionLog ( $title, $ns, $notificationTimestamp ) {

		$dbr = wfGetDB( DB_SLAVE );

		$title = $dbr->addQuotes( $title );

		// pages are deleted when (a) they are explicitly deleted or (b) they
		// are moved without leaving a redirect behind.
		$logResults = $dbr->select(
			array( 'l' => 'logging' ),
			Revision::selectFields(),
			"l.log_title=$title AND l.log_namespace=$ns AND l.log_timestamp>=$notificationTimestamp
				AND l.log_type IN ('delete','move')",
			__METHOD__,
			array( 'ORDER BY' => 'log_timestamp ASC' ),
			null
		);
		$logDeletes = array();
		while ( $log = $logResults->fetchObject() ) {
			$logDeletes[] = $log;
		}

		return $logDeletes;
	}


	/**
	 * FIXME: This was copied from LogEntry::getParameters() because
	 * I couldn't find a cleaner way to do it.
	 *
	 * var $logParams is the content of the column log_params in the logging table
	 */
	public static function getMoveTarget ( $logParams ) {

		wfSuppressWarnings();
		$unserializedParams = unserialize( $logParams );
		wfRestoreWarnings();
		if ( $unserializedParams !== false ) {
			$moveLogParams = $unserializedParams;
			// $this->legacy = false;

			// for some reason this serialized array is in the form:
			// Array( "4::target" => FULLPAGENAME, "5::noredir" => 1 )
			return $moveLogParams[ '4::target' ];

		} else {
			$moveLogParams = $logParams === '' ? array() : explode( "\n", $logParams );
			// $this->legacy = true;

			return $moveLogParams[0];
		}

	}

	/**
	 * Clears a pending reviews of a particular page for a particular user.
	 *
	 * @param User $user
	 * @param Title $title
	 * @return string HTML for row
	 */
	public function clearByUserAndTitle ( $user, $title ) {

		$watch = WatchedItem::fromUserTitle( $user, $title );
		$watch->resetNotificationTimestamp();

		// $wgOut->addHTML(
		// 	wfMessage(
		// 		'pendingreviews-clear-page-notification',
		// 		$title->getFullText(),
		// 		Xml::tags('a',
		// 			array(
		// 				'href' => $this->getTitle()->getLocalUrl(),
		// 				'style' => 'font-weight:bold;',
		// 			),
		// 			$this->getTitle()
		// 		)
		// 	)->text()
		// );

		return true;


	}
}
