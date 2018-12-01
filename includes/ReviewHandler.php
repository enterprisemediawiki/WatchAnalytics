<?php

class ReviewHandler {

	// used to track change of state through page load.
	public static $pageLoadHandler = null;
	public static $isReviewable = true;

	/**
	 * @var User $user: reference to the current user
	 */
	public $user;

	/**
	 * @var Title $title: reference to current title
	 */
	public $title;

	/**
	 * @var int $initial: state of the user watching the page initially (at the
	 * beginning of the page load). Possible values: -1 for not watching the
	 * page, 0 for watching and has seen the latest version, and a large int
	 * like 20150102030405 (timestamp) for the user not having seen the latest.
	 */
	public $initial = null;

	/**
	 * @var int $final: same purpose as $initial, but determined late in the
	 * page load to see if the watch/review-state has changed.
	 */
	public $final = null;

	public function __construct( User $user, Title $title ) {
		$this->user = $user;
		$this->title = $title;
	}

	public static function setup( User $user, Title $title ) {
		if ( ! $title->isWatchable() ) {
			self::$isReviewable = false;
			return false;
		}
		self::$pageLoadHandler = new self ( $user, $title );
		self::$pageLoadHandler->initial = self::$pageLoadHandler->getReviewStatus();
		return self::$pageLoadHandler;
	}

	/**
	 * Get the "watch status" of a user for a page, e.g. whether they're watching
	 * and, if they're watching, whether they have reviewed the latest revision.
	 *
	 * @return int
	 */
	public function getReviewStatus() {
		$dbr = wfGetDB( DB_REPLICA );

		// FIXME: probably should use User->getNotificationTimestmap() or something
		// but I'm on a plane and I don't know what is available to me without docs
		$row = $dbr->selectRow(
			'watchlist',
			[ 'wl_notificationtimestamp' ],
			[
				'wl_user' => $this->user->getId(),
				'wl_namespace' => $this->title->getNamespace(),
				'wl_title' => $this->title->getDBkey(),
			],
			__METHOD__,
			[]
		);

		// user is not watching the page
		if ( $row === false ) {
			return -1;
		} elseif ( $row->wl_notificationtimestamp === null ) {
			return 0;
		} else {
			return $row->wl_notificationtimestamp;
		}
	}

	public static function pageIsBeingReviewed() {
		// never setup
		if ( ! self::$isReviewable || self::$pageLoadHandler === null ) {
			return false;
		}

		// no initial notification timestamp ($initial = 0) or not watching ($initial = -1)
		if ( self::$pageLoadHandler->initial < 1 ) {
			return false;
		}

		// After MW 1.25 (either 1.26 or 1.27), clearing wl_notificationtimestamp
		// was done via the job queue. This broke the ability to do a second
		// check of the review status and then compare the two statuses.
		// Instead just assume if the page is being viewed and it has a
		// positive wl_notificationtimestamp, then it is being reviewed.
		// $newStatus = self::$pageLoadHandler->getReviewStatus();

		// OLD VERSIONS OF WatchAnalytics DID THIS, BUT THE JOB QUEUE CHANGE
		// MADE THIS DIFFICULT. THIS MAY BE ADDED BACK LATER, BUT TO GET THE
		// EXTENSION WORKING AGAIN, INSTEAD WE'LL BE LESS EXACT FOR NOW.
		// either $newStatus is 0 or -1 meaning they don't have a pending review
		// or $newStatus is a timestamp greater than the original timestamp, meaning
		// they have reviewed a more recent version of the page than they had originally
		// if ( $newStatus < 1 || $newStatus > self::$pageLoadHandler->initial ) {
		// self::$pageLoadHandler->final = $newStatus;
		// return self::$pageLoadHandler;
		// }
		// else {
		// return false;
		// }

		return true;
	}

	public function getTemplate() {
		// $msg = wfMessage( 'watch-analytics-page-score-tooltip' )->text();

		$unReviewLink = SpecialPage::getTitleFor( 'PageStatistics' )->getInternalURL( [
			'page' => $this->title->getPrefixedText(),
			'unreview' => $this->initial
		] );

		$linkText = wfMessage( 'watchanalytics-unreview-button' )->text();
		$bannerText = wfMessage( 'watchanalytics-unreview-banner-text' )->parse();

		// when MW 1.25 is released (very soon) replace this with a mustache template
		$template =
			"<div id='watch-analytics-review-handler'>
				<a id='watch-analytics-unreview' href='$unReviewLink'>$linkText</a>
				<p>$bannerText</p>
			</div>";

		return "<script type='text/template' id='ext-watchanalytics-review-handler-template'>$template</script>";
	}

	public function resetNotificationTimestamp( $ts ) {
		$dbw = wfGetDB( DB_MASTER );

		return $dbw->update(
			'watchlist',
			[
				'wl_notificationtimestamp' => $ts,
			],
			[
				'wl_user' => $this->user->getId(),
				'wl_namespace' => $this->title->getNamespace(),
				'wl_title' => $this->title->getDBkey(),
			],
			__METHOD__,
			null
		);
	}

}
