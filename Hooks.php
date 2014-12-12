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
}
