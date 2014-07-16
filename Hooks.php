<?php

class WatchStrengthHooks {

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
	 * @param SkinTemplate $sk
	 * @return bool true in all cases
	 */
	static function onPersonalUrls( &$personal_urls, &$title, $sk ) {
		
		$user = $sk->getUser();
		
		if ( $user->isAnon() ) {
			return true;
		}

		$watcher = new WatchStrengthUser( $user );
		$watcher->getPendingWatches();
		
		$text = $sk->msg( 'watchstrength-personal-url' )->text() . $watcher->countPendingChanges() . ')';
		
		$url = SpecialPage::getTitleFor( 'Watchlist' )->getLocalURL();
		
		
		if ( $watcher->countPendingChanges() == 0 ) {
			$linkClasses = array( 'mw-watchstrength-watchlist-badge' );
		} else {
			$linkClasses = array( 'mw-watchstrength-watchlist-pending', 'mw-watchstrength-watchlist-badge' );
		}
		$newWatchlistLink = array(
			'href' => $url,
			'text' => $text,
			'active' => ( $url == $title->getLocalUrl() ),
			'class' => $linkClasses,
		);


		$personal_urls['watchlist'] = $newWatchlistLink;
		/*	
		// If the user has new messages, display a talk page alert
		if ( $wgEchoNewMsgAlert && $user->getOption( 'echo-show-alert' ) && $user->getNewtalk() ) {
			$personal_urls['mytalk']['text'] = $sk->msg( 'echo-new-messages' )->text();
			$personal_urls['mytalk']['class'] = array( 'mw-echo-alert' );
			$sk->getOutput()->addModuleStyles( 'ext.echo.alert' );
		}
		*/

		return true;
	}
	
}
