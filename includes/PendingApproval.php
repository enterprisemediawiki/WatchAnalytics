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

class PendingApproval extends PendingReview {
	public function __construct( $row, Title $title ) {

		$this->title = $title;

		$this->notificationTimestamp = $row['notificationtimestamp'];
		$this->numReviewers = intval( $row['num_reviewed'] );

		// Keep these just to be consistent with PendingReview class
		$this->deletedTitle = false;
		$this->deletedNS = false;
		$this->deletionLog = false;

		// FIXME
		// no log for now, maybe link to approval log
		// no list of revisions for now
		$this->log = [];
		$this->newRevisions = [];
	}

	/**
	 * Get an array of pages user can approve that require approvals
	 * @param User $user
	 * @return Array
	 */
	public static function getUserPendingApprovals( User $user ) {
		$dbr = wfGetDB( DB_REPLICA );

		$queryInfo = ApprovedRevs::getQueryInfoPageApprovals( 'notlatest' );
		$latestNotApproved = $dbr->select(
			$queryInfo['tables'],
			$queryInfo['fields'],
			$queryInfo['conds'],
			__METHOD__,
			$queryInfo['options'],
			$queryInfo['join_conds']
		);
		$pagesUserCanApprove = [];

		while ( $page = $latestNotApproved->fetchRow() ) {

			// $page with keys id, rev_id, latest_id
			$title = Title::newFromID( $page['id'] );

			if ( ApprovedRevs::userCanApprove( $user, $title ) ) {

				// FIXME: May want to get these in there so PendingReviews can
				// show the list of revs in the approval.
				// 'approved_rev_id' => $page['rev_id']
				// 'latest_rev_id' => $page['latest_id']
				$pagesUserCanApprove[] = new self(
					[
						'notificationtimestamp' => null,
						'num_reviewed' => 0, // if page has pending approval, zero people have approved
					],
					$title
				);

			}

		}

		return $pagesUserCanApprove;
	}

}
