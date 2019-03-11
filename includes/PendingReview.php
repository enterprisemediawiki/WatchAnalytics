<?php

use MediaWiki\MediaWikiServices;

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

	public function __construct( $row, Title $title = null ) {
		$notificationTimestamp = $row['notificationtimestamp'];

		$this->notificationTimestamp = $notificationTimestamp;
		$this->numReviewers = intval( $row['num_reviewed'] );

		if ( $title ) {
			$pageID = $title->getArticleID();
			$namespace = $title->getNamespace();
			$titleDBkey = $title->getDBkey();
		} else {
			$pageID = $row['page_id'];
			$namespace = $row['namespace'];
			$titleDBkey = $row['title'];

			if ( $pageID ) {
				$title = Title::newFromID( $pageID );
			} else {
				$title = false;
			}
		}

		if ( $pageID && $title->exists() ) {

			$dbr = wfGetDB( DB_REPLICA );

			$revisionStore = MediaWikiServices::getInstance()->getRevisionStore();

			$revQueryInfo = $revisionStore->getQueryInfo();

			$revResults = $dbr->select(
				$revQueryInfo['tables'],
				$revQueryInfo['fields'],
				"rev_page=$pageID AND rev_timestamp>=$notificationTimestamp",
				__METHOD__,
				[ 'ORDER BY' => 'rev_timestamp ASC' ],
				$revQueryInfo['joins']
			);
			$revsPending = [];
			while ( $rev = $revResults->fetchObject() ) {
				$revsPending[] = $rev;
			}

			$logResults = $dbr->select(
				[ 'l' => 'logging' ],
				[ '*' ],
				"l.log_page=$pageID AND l.log_timestamp>=$notificationTimestamp
					AND l.log_type NOT IN ('interwiki','newusers','patrol','rights','upload')",
				__METHOD__,
				[ 'ORDER BY' => 'log_timestamp ASC' ],
				null
			);
			$logPending = [];
			while ( $log = $logResults->fetchObject() ) {
				$logPending[] = $log;
			}

			$deletedNS = false;
			$deletedTitle = false;
			$deletionLog = false;

		} else {
			$deletedNS = $namespace;
			$deletedTitle = $titleDBkey;
			$deletionLog = $this->getDeletionLog( $deletedTitle, $deletedNS, $notificationTimestamp );
			$logPending = false;
			$revsPending = false;
		}

		$this->title = $title;
		$this->newRevisions = $revsPending;
		$this->deletedTitle = $deletedTitle;
		$this->deletedNS = $deletedNS;
		$this->deletionLog = $deletionLog;
		$this->log = $logPending;
	}

	public static function getPendingReviewsList( User $user, $limit, $offset ) {
		$tables = [
			'w' => 'watchlist',
			'p' => 'page',
			'log' => 'logging',
		];

		$fields = [
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
		];

		$conds = 'w.wl_user=' . $user->getId() . ' AND w.wl_notificationtimestamp IS NOT NULL';

		$options = [
			'ORDER BY' => 'num_reviewed ASC, w.wl_notificationtimestamp ASC',
			'OFFSET' => $offset,
			'LIMIT' => $limit,
		];

		$join_conds = [
			'p' => [
				'LEFT JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
			],
			'log' => [
				'LEFT JOIN',
				'log.log_namespace = w.wl_namespace '
				. ' AND log.log_title = w.wl_title'
				. ' AND p.page_namespace IS NULL'
				. ' AND p.page_title IS NULL'
				. ' AND log.log_action IN ("delete","move")'
			],
		];

		$dbr = wfGetDB( DB_REPLICA );

		$watchResult = $dbr->select(
			$tables,
			$fields,
			$conds,
			__METHOD__,
			$options,
			$join_conds
		);

		$pending = [];

		while ( $row = $dbr->fetchRow( $watchResult ) ) {

			$pending[] = new self( $row );

		}

		// If ApprovedRevs is installed, append any pages in need of approvals
		// to the front of the Pending Reviews list
		if ( class_exists( 'ApprovedRevs' ) ) {
			$pending = array_merge( PendingApproval::getUserPendingApprovals( $user ), $pending );
		}

		return $pending;
	}

	public static function getPendingReview( User $user, Title $title ) {
		$tables = [
			'w' => 'watchlist',
			'p' => 'page',
			'log' => 'logging',
		];

		$fields = [
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
		];

		$conds = [ 'w.wl_user' => $user->getId() , 'p.page_id' => $title->getArticleID() , 'w.wl_notificationtimestamp IS NOT NULL' ];

		$options = [];

		$join_conds = [
			'p' => [
				'LEFT JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
			],
			'log' => [
				'LEFT JOIN',
				'log.log_namespace = w.wl_namespace '
				. ' AND log.log_title = w.wl_title'
				. ' AND p.page_namespace IS NULL'
				. ' AND p.page_title IS NULL'
				. ' AND log.log_action IN ("delete","move")'
			],
		];

		$dbr = wfGetDB( DB_REPLICA );

		$watchResult = $dbr->select(
			$tables,
			$fields,
			$conds,
			__METHOD__,
			$options,
			$join_conds
		);

		$pending = [];

		while ( $row = $dbr->fetchRow( $watchResult ) ) {

			$pending[] = new self( $row );

		}
		return $pending;
	}

	public function getDeletionLog( $title, $ns, $notificationTimestamp ) {
		$dbr = wfGetDB( DB_REPLICA );
		$title = $dbr->addQuotes( $title );

		// pages are deleted when (a) they are explicitly deleted or (b) they
		// are moved without leaving a redirect behind.
		$logResults = $dbr->select(
			[ 'l' => 'logging', 'c' => 'comment' ],
			[
				'l.log_id',
				'l.log_type',
				'l.log_action',
				'l.log_timestamp',
				'l.log_user',
				'l.log_user_text',
				'l.log_namespace',
				'l.log_title',
				'l.log_page',
				'l.log_comment_id',
				'l.log_params',
				'l.log_deleted',
				'c.comment_id',
				'c.comment_text AS log_comment'
			],
			"l.log_title=$title AND l.log_namespace=$ns AND l.log_timestamp>=$notificationTimestamp
				AND l.log_type IN ('delete','move')",
			__METHOD__,
			[ 'ORDER BY' => 'l.log_timestamp ASC' ],
			[ 'c' => [ 'INNER JOIN', [ 'l.log_comment_id=c.comment_id' ] ] ]
		);
		$logDeletes = [];
		while ( $log = $logResults->fetchObject() ) {
			$logDeletes[] = $log;
		}

		return $logDeletes;
	}

	public static function getMoveTarget( $logParams ) {
		// FIXME: This was copied from LogEntry::getParameters() because
		// I couldn't find a cleaner way to do it.
		// $logParams the content of the column log_params in the logging table

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
			$moveLogParams = $logParams === '' ? [] : explode( "\n", $logParams );
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
	public function clearByUserAndTitle( $user, $title ) {
		$watch = WatchedItem::fromUserTitle( $user, $title );
		$watch->resetNotificationTimestamp();

		// $wgOut->addHTML(
		// wfMessage(
		// 'pendingreviews-clear-page-notification',
		// $title->getFullText(),
		// Xml::tags('a',
		// array(
		// 'href' => $this->getTitle()->getLocalUrl(),
		// 'style' => 'font-weight:bold;',
		// ),
		// $this->getTitle()
		// )
		// )->text()
		// );

		return true;
	}
}
