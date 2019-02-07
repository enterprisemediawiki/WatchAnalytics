<?php

class WatchAnalyticsHooks {

	/**
	 * Handler for PersonalUrls hook. Replace the "watchlist" item on the user
	 * toolbar ('personal URLs') with a link to Special:PendingReviews.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
	 *
	 * @param array &$personal_urls Array of URLs to append to.
	 * @return bool
	 */
	public static function onPersonalUrls( array &$personal_urls ) {
		global $wgUser, $wgOut;
		$user = $wgUser;

		if ( !$user->isAllowed( 'pendingreviewslink' ) ) {
			return true;
		}

		$wgOut->addModuleStyles( 'ext.watchanalytics.base' );
		// NOTE: $wgOut->addModules() does not appear to work here, so
		// the onBeforePageDisplay() method was created below.

		// Get user's watch/review stats
		$watchStats = $user->watchStats; // set in onBeforePageDisplay() hook
		$numPending = $watchStats['num_pending'];
		$maxPendingDays = $watchStats['max_pending_days'];

		// Get user's pending approvals
		//Check that Approved Revs is installed
		$numPendingApprovals = 0;
		if ( class_exists( 'ApprovedRevs' ) ) {
			$numPendingApprovals =  count( PendingApproval::getUserPendingApprovals( $user ) );
		}

		// Determine CSS class of Watchlist/PendingReviews link
		$personal_urls['watchlist']['class'] = [ 'mw-watchanalytics-watchlist-badge' ];
		if ( $numPending != 0 ) {
			$personal_urls['watchlist']['class'] = [ 'mw-watchanalytics-watchlist-pending' ];
		}

		// Determine text of Watchlist/PendingReviews link
		global $egPendingReviewsEmphasizeDays;
		if ( $maxPendingDays > $egPendingReviewsEmphasizeDays ) {
			$personal_urls['watchlist']['class'][] = 'mw-watchanalytics-watchlist-pending-old';
			if ( $numPendingApprovals != 0 ) {
				$text = wfMessage( 'watchanalytics-personal-url-approvals-old' )->params( $numPending, $maxPendingDays, $numPendingApprovals )->text();
			} else{
				$text = wfMessage( 'watchanalytics-personal-url-old' )->params( $numPending, $maxPendingDays )->text();
			}
		} else {
			if ( $numPendingApprovals != 0 ) {
				$text = wfMessage( 'watchanalytics-personal-url-approvals' )->params( $numPending, $numPendingApprovals )->text();
			} else {
				// when $sk (third arg) available, replace wfMessage with $sk->msg()
				$text = wfMessage( 'watchanalytics-personal-url' )->params( $numPending )->text();
			}

		}
		$personal_urls['watchlist']['text'] = $text;

		// set "watchlist" link to Pending Reviews
		$personal_urls['watchlist']['href'] = SpecialPage::getTitleFor( 'PendingReviews' )->getLocalURL();
		return true;
	}

	/**
	 * Handler for BeforePageDisplay hook. This function supports several
	 * WatchAnalytics features:
	 *
	 * 1) Determine if user should see shaky pending reviews link
	 * 2) Insert page scores on applicable pages
	 * 3) REMOVED FOR MW 1.27: If a page review has occured on this page view, display an unreview
	 *    option and record that the review happened.
	 *
	 * Also supports parameter: Skin $skin.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 *
	 * @param OutputPage $out reference to OutputPage object
	 *
	 * @return bool true in all cases
	 */
	public static function onBeforePageDisplay( $out /*, $skin*/ ) {
		$user = $out->getUser();
		$title = $out->getTitle();

		#
		# 1) Is user's oldest pending review is old enough to require emphasis
		#
		$userWatch = new UserWatchesQuery();
		$user->watchStats = $userWatch->getUserWatchStats( $user );
		$user->watchStats['max_pending_days'] = ceil(
			$user->watchStats['max_pending_minutes'] / ( 60 * 24 )
		);

		global $egPendingReviewsEmphasizeDays;
		if ( $user->watchStats['max_pending_days'] > $egPendingReviewsEmphasizeDays ) {
			$out->addModules( [ 'ext.watchanalytics.shakependingreviews' ] );
		}

		#
		# 2) Insert page scores
		#
		if ( in_array( $title->getNamespace(), $GLOBALS['egWatchAnalyticsPageScoreNamespaces'] )
			&& $user->isAllowed( 'viewpagescore' )
			&& PageScore::pageScoreIsEnabled() ) {

			$pageScore = new PageScore( $title );
			$out->addScript( $pageScore->getPageScoreTemplate() );
			$out->addModules( 'ext.watchanalytics.pagescores.scripts' );
			$out->addModuleStyles( 'ext.watchanalytics.pagescores.styles' );
		}

		// REMOVED FOR MW 1.27
		// #
		// # 3) If user has reviewed page on this page load show "unreview" option
		// #
		// $reviewHandler = ReviewHandler::pageHasBeenReviewed();
		// if ( $reviewHandler ) {

		// // display "unreview" button
		// $out->addScript( $reviewHandler->getTemplate() );
		// $wgOut->addModules( [
		// 'ext.watchanalytics.reviewhandler.scripts',
		// 'ext.watchanalytics.reviewhandler.styles'
		// ] );

		// // record change in user/page stats
		// WatchStateRecorder::recordReview( $user, $title );

		// }

		return true;
	}

	/**
	 * Handler for TitleMoveComplete hook. This function makes it so page-moves
	 * are handled correctly in the `watchlist` table. Prior to a MW 1.25 alpha
	 * release when a page is moved, the new entries into the `watchlist` table
	 * are given an notification timestamp of NULL; they should be identical to
	 * the notification timestamps of the original title so users are notified
	 * of changes prior to the move. Code taken from MediaWiki core head branch
	 * WatchedItem::doDuplicateEntries() method.
	 *
	 * @todo FIXME: make this work for <1.25 and 1.25+
	 * @todo document which commit fixes this issue specifically.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 *
	 * @param Title &$originalTitle
	 * @param Title &$newTitle
	 * @param User &$user
	 * @param int $oldid
	 * @param int $newid
	 * @param FIXME string|null $reason
	 *
	 * @return bool true in all cases
	 */
	public static function onTitleMoveComplete( Title &$originalTitle, Title &$newTitle,
			User &$user, $oldid, $newid, $reason = null ) {
		#
		# Record move in watch stats
		#
		WatchStateRecorder::recordPageChange( Article::newFromID( $oldid ) );

		// if a redirect was created, record data for the "new" page (the redirect)
		if ( $newid > 0 ) {
			WatchStateRecorder::recordPageChange( Article::newFromID( $newid ) );
		}

		#
		# BELOW IS THE pre-MW 1.25 FIX.
		#
		$oldNS = $originalTitle->getNamespace();
		$newNS = $newTitle->getNamespace();
		$oldDBkey = $originalTitle->getDBkey();
		$newDBkey = $newTitle->getDBkey();

		$dbw = wfGetDB( DB_MASTER );
		$results = $dbw->select( 'watchlist',
			[ 'wl_user', 'wl_notificationtimestamp' ],
			[ 'wl_namespace' => $oldNS, 'wl_title' => $oldDBkey ],
			__METHOD__
		);
		# Construct array to replace into the watchlist
		$values = [];
		foreach ( $results as $oldRow ) {
			$values[] = [
				'wl_user' => $oldRow->wl_user,
				'wl_namespace' => $newNS,
				'wl_title' => $newDBkey,
				'wl_notificationtimestamp' => $oldRow->wl_notificationtimestamp,
			];
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
			[ [ 'wl_user', 'wl_namespace', 'wl_title' ] ],
			$values,
			__METHOD__
		);

		return true;
	}

	/**
	 * Register magic-word variable ID to hide page score from select pages.
	 *
	 * @see FIXME (include link to hook documentation)
	 *
	 * @param Array &$magicWordVariableIDs array of names of magic words
	 *
	 * @return bool
	 */
	public static function addMagicWordVariableIDs( &$magicWordVariableIDs ) {
		$magicWordVariableIDs[] = 'MAG_NOPAGESCORE';
		return true;
	}

	/**
	 * Set values in the page_props table based on the presence of the
	 * 'NOPAGESCORE' magic word in a page
	 *
	 * @see FIXME (include link to hook documentation)
	 *
	 * @param Parser &$parser reference to MediaWiki parser.
	 * @param string &$text FIXME html/wikitext? of output page before complete
	 *
	 * @return bool
	 */
	public static function handleMagicWords( &$parser, &$text ) {
		$magicWord = MagicWord::get( 'MAG_NOPAGESCORE' );
		if ( $magicWord->matchAndRemove( $text ) ) {
			// $parser->mOutput->setProperty( 'approvedrevs', 'y' );
			PageScore::noPageScore();
		}
		return true;
	}

	/**
	 * Prior to clearing notification timestamp determines if user is watching page,
	 * and if so determines what their review status is. Records review and adds
	 * "defer" banner if required.
	 *
	 * @see FIXME (include link to hook documentation)
	 *
	 * @param WikiPage $wikiPage
	 * @param User $user
	 *
	 * @return bool
	 */
	public static function onPageViewUpdates( WikiPage $wikiPage, User $user ) {
		$title = $wikiPage->getTitle();
		$reviewHandler = ReviewHandler::setup( $user, $title );

		if ( $reviewHandler::pageIsBeingReviewed() ) {

			global $wgOut;

			// display "unreview" button
			$wgOut->addScript( $reviewHandler->getTemplate() );
			$wgOut->addModules( [
				'ext.watchanalytics.reviewhandler.scripts',
				'ext.watchanalytics.reviewhandler.styles'
			] );

			// record change in user/page stats
			WatchStateRecorder::recordReview( $user, $title );

		}
		return true;
	}

	/**
	 * Occurs after the save page request has been processed, and causes the
	 * new state of "watches" and "reviews" to be recorded for the page and all
	 * of its watchers.
	 *
	 * Additional parameters available include: User $user, Content $content,
	 * string $summary, boolean $isMinor, boolean $isWatch, $section Deprecated,
	 * integer $flags, {Revision|null} $revision, Status $status, integer $baseRevId
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 *
	 * @param WikiPage $wikipage
	 *
	 * @return bool
	 */
	public static function onPageContentSaveComplete( WikiPage $wikipage ) {
		WatchStateRecorder::recordPageChange( $wikipage );
		return true;
	}

	public static function onLanguageGetMagic( &$magicWords, $langCode ) {
		switch ( $langCode ) {
		default:
			$magicWords['underwatched_categories']    = [ 0, 'underwatched_categories' ];
			$magicWords['watchers_needed'] = [ 0, 'watchers_needed' ];
			$magicWords['MAG_NOPAGESCORE']   = [ 0, '__NOPAGESCORE__' ];
		}
		return true;
	}

}
