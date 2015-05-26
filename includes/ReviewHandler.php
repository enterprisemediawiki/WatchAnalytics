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

	public function __construct ( User $user, Title $title ) {
		$this->user = $user;
		$this->title = $title;
	}

	public static function setup ( User $user, Title $title ) {
		if ( ! $title->isWatchable() ) {
			self::$isReviewable = false;
			return false;
		} 
		self::$pageLoadHandler = new self ( $user, $title );
		self::$pageLoadHandler->initial = self::$pageLoadHandler->getReviewStatus();
	}

	/**
	 * Get the "watch status" of a user for a page, e.g. whether they're watching
	 * and, if they're watching, whether they have reviewed the latest revision.
	 *
	 */
	public function getReviewStatus () {

		$dbr = wfGetDB( DB_SLAVE );

		// FIXME: probably should use User->getNotificationTimestmap() or something
		// but I'm on a plane and I don't know what is available to me without docs
		$row = $dbr->selectRow(
			'watchlist',
			array( 'wl_notificationtimestamp' ),
			array(
				'wl_user' => $this->user->getId(),
				'wl_namespace' => $this->title->getNamespace(),
				'wl_title' => $this->title->getDBkey(),
			),
			__METHOD__,
			array()
		);

		// user is not watching the page
		if ( $row === false ) {
			return -1;
		}
		else if ( $row->wl_notificationtimestamp === NULL ) {
			return 0;
		}
		else {
			return $row->wl_notificationtimestamp;
		}

	}

	public static function pageHasBeenReviewed () {

		// never setup
		if ( ! self::$isReviewable || self::$pageLoadHandler === null ) {
			return false;
		}

		// no initial notification timestamp ($initial = 0) or not watching ($initial = -1)
		if ( self::$pageLoadHandler->initial < 1 ) {
			return false;
		}

		$newStatus = self::$pageLoadHandler->getReviewStatus();

		// either $newStatus is 0 or -1 meaning they don't have a pending review
		// or $newStatus is a timestamp greater than the original timestamp, meaning
		// they have reviewed a more recent version of the page than they had originally
		if ( $newStatus < 1 || $newStatus > self::$pageLoadHandler->initial ) {
			self::$pageLoadHandler->final = $newStatus;
			return self::$pageLoadHandler;
		}
		else {
			return false;
		}

	}

	public function getTemplate () {

		// $msg = wfMessage( 'watch-analytics-page-score-tooltip' )->text();

		$unReviewLink = SpecialPage::getTitleFor( 'PageStatistics' )->getInternalURL( array(
			'page' => $this->title->getPrefixedText(),
			'unreview' => $this->initial
		) );


		// when MW 1.25 is released (very soon) replace this with a mustache template
		$template = 
			"<div id='watch-analytics-review-handler'>
				<a id='watch-analytics-unreview' href='$unReviewLink'>Un-review</a>
				<p>By navigating to this page you have marked it reviewed. If you did not mean to review the page you may un-review it.</p>
			</div>";

		return "<script type='text/template' id='ext-watchanalytics-review-handler-template'>$template</script>";

	}

	public function resetNotificationTimestamp ( $ts ) {

		$dbw = wfGetDB( DB_MASTER );

		return $dbw->update(
			'watchlist',
			array(
				'wl_notificationtimestamp' => $ts,
			),
			array(
				'wl_user' => $this->user->getId(),
				'wl_namespace' => $this->title->getNamespace(),
				'wl_title' => $this->title->getDBkey(),
			),
			__METHOD__,
			null
		);

	}

}
