<?php

class WatchAnalyticsUpdaterHooks {

	public static function addSchemaUpdates( $updater = null ) {

		// NOTE: this SQL file adds tables watch_tracking_user,
		// watch_tracking_page and watch_tracking_wiki. Since no changes have
		// been made to the database schema over the life of this extension so
		// far, there's no reason to check for all the tables. Checking for the
		// existence of one is sufficient to determine if the tables need to be
		// created.

		// DB updates
		// For now, there's just a single SQL file for all DB types.
		//if ( $updater->getDB()->getType() == 'mysql' ) {
			$updater->addExtensionUpdate( array( 'addTable', 'watch_tracking_user', __DIR__ . '/WatchAnalytics.sql', true ) );
		//}

		return true;
	}
	
}
