<?php

class WatchAnalyticsHooks {

	/**
	 * Handler for PersonalUrls hook.
	 * Replace the "watchlist" item on the user toolbar ('personal URLs') with
	 * a link to Special:Watchlist which includes the number of pending watches
	 * the user has. Additionally, clicking the link in a javascript-enabled
	 * browser pops up the quick watchlist viewer.
	 * 
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
	 * @param &$personal_urls Array of URLs to append to.
	 * @param &$title Title of page being visited.
	 * @param SkinTemplate $sk (not available yet in earlier versions of MW)
	 * @return bool true in all cases
	 */
	static function onPersonalUrls( &$personal_urls, &$title /*,$sk*/ ) {
		
		global $wgUser, $wgOut;
		$user = $wgUser;
				
		if ( $user->isAnon() ) {
			return true;
		}

		$wgOut->addModuleStyles( 'ext.watchanalytics.base' );
		// NOTE: $wgOut->addModules() does not appear to work here, so 
		// the onBeforePageDisplay() method was created below.
		
		$watchStats = $user->watchStats; // set in onBeforePageDisplay() hook
		
		$numPending = $watchStats['num_pending'];
		
		// when $sk (third arg) available, replace wfMessage with $sk->msg()
		$text = wfMessage( 'watchanalytics-personal-url' )->params( $numPending )->text();		
		
		$personal_urls['watchlist']['text'] = $text;
		$maxPendingDays = $watchStats['max_pending_days'];
		
		if ( $numPending == 0 ) {
			$personal_urls['watchlist']['class'] = array( 'mw-watchanalytics-watchlist-badge' );
		} else {
			$personal_urls['watchlist']['class'] = array( 'mw-watchanalytics-watchlist-pending', 'mw-watchanalytics-watchlist-badge' );
			
			$personal_urls['watchlist']['href'] = SpecialPage::getTitleFor( 'Watchlist' )->getLocalURL( array( 'days' => $maxPendingDays ) );
		}

		global $egPendingReviewsEmphasizeDays;
		if ( $maxPendingDays > $egPendingReviewsEmphasizeDays ) {
			$personal_urls['watchlist']['class'][] = 'mw-watchanalytics-watchlist-pending-old';
		}
		$personal_urls['watchlist']['href'] = SpecialPage::getTitleFor( 'PendingReviews' )->getLocalURL();
		return true;
	}

	/**
	 * Handler for BeforePageDisplay hook.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * @param $out OutputPage object
	 * @param $skin Skin being used.
	 * @return bool true in all cases
	 */
	static function onBeforePageDisplay( $out, $skin ) {
		$user = $out->getUser();

		$userWatch = new UserWatchesQuery();

		$user->watchStats = $userWatch->getUserWatchStats( $user );
		
		$user->watchStats['max_pending_days'] = ceil(
			$user->watchStats['max_pending_minutes'] / ( 60 * 24 )
		);

		// if ( $user->isLoggedIn() && $user->getOption( 'echo-notify-show-link' ) ) {
			// // Load the module for the Notifications flyout
			// $out->addModules( array( 'ext.echo.overlay.init' ) );
			// // Load the styles for the Notifications badge
			// $out->addModuleStyles( 'ext.echo.badge' );
		// }
		
		global $egPendingReviewsEmphasizeDays;
		if ( $user->watchStats['max_pending_days'] > $egPendingReviewsEmphasizeDays ) {
			$out->addModules( array( 'ext.watchanalytics.shakependingreviews' ) );
		}
		return true;
	}

	/**
	 * Handler for TitleMoveComplete hook. This function makes it so page-moves
	 * are handled correctly in the `watchlist` table. Prior to a MW 1.25 alpha
	 * release when a page is moved, the new entries into the `watchlist` table
	 * are given an notification timestamp of NULL; they should be identical to
	 * the notification timestamps of the original title so users are notified 
	 * of changes prior to the move. 
	 * @todo document which commit fixes this issue specifically.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 * @param &$originalTitle Title
	 * @param &$newTitle Title
	 * @param &$moverUser User
	 * @param $oldid string|int
	 * @param $newId string|int
	 * @param $reason string
	 * @return bool true in all cases
	 */
	static function onTitleMoveComplete ( Title &$originalTitle, Title &$newTitle, User &$moverUser, $oldid, $newId, $reason ) {

		// query `watchlist` for all rows of $originalTitle (possibly multiple
		// users watching the page)

		$oldNS = $originalTitle->getNamespace();
		$newNS = $newTitle->getNamespace();
		$oldDBkey = $originalTitle->getDBkey();
		$newDBkey = $newTitle->getDBkey();

		$dbw = wfGetDB( DB_MASTER );
		$results = $dbw->select( 'watchlist',
			array( 'wl_user', 'wl_notificationtimestamp' ),
			array( 'wl_namespace' => $oldNS, 'wl_title' => $oldDBkey ),
			__METHOD__
		);
		# Construct array to replace into the watchlist
		$values = array();
		foreach ( $results as $oldRow ) {
			$values[] = array(
				'wl_user' => $oldRow->wl_user,
				'wl_namespace' => $newNS,
				'wl_title' => $newDBkey,
				'wl_notificationtimestamp' => $oldRow->wl_notificationtimestamp,
			);
		}

		if ( empty( $values ) ) {
			// Nothing to do
			return true;
		}

		# Perform replace
		# Note that multi-row replace is very efficient for MySQL but may be inefficient for
		# some other DBMSes, mostly due to poor simulation by us
		$dbw->replace(
			'watchlist',
			array( array( 'wl_user', 'wl_namespace', 'wl_title' ) ),
			$values,
			__METHOD__
		);

		return true;
	}
}
