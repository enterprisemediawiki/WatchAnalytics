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
	 * @param SkinTemplate $sk (not available yet in earlier versions of MW)
	 * @return bool true in all cases
	 */
	static function onPersonalUrls( &$personal_urls, &$title /*,$sk*/ ) {
		
		global $wgUser;
		$user = $wgUser;
				
		if ( $user->isAnon() ) {
			return true;
		}

		$watcher = new WatchStrengthUser( $user );
		$watcher->getPendingWatches();
		
		// when $sk (third arg) available, replace wfMessage with $sk->msg()
		$text = wfMessage( 'watchstrength-personal-url' )->params( $watcher->countPendingChanges() )->text();		
		
		$personal_urls['watchlist']['text'] = $text;
		if ( $watcher->countPendingChanges() == 0 ) {
			$personal_urls['watchlist']['class'] = array( 'mw-watchstrength-watchlist-badge' );
		} else {
			$personal_urls['watchlist']['class'] = array( 'mw-watchstrength-watchlist-pending', 'mw-watchstrength-watchlist-badge' );
		}

		return true;
	}
	
}
