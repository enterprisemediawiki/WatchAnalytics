<?php

class WatchAnalyticsUpdaterHooks {

	public static function addSchemaUpdates( $updater = null ) {

		// DB updates
		// For now, there's just a single SQL file for all DB types.
		//if ( $updater->getDB()->getType() == 'mysql' ) {
			$updater->addExtensionUpdate( array( 'addTable', 'watchanalytics', __DIR__ . '/WatchAnalytics.sql', true ) );
		//}

		return true;
	}
	
}
