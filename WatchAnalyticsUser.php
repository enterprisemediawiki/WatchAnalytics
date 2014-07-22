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

class WatchAnalyticsUser {

	protected $user;
	protected $pendingWatches;

	public function __construct ( User $user ) {
		$this->user = $user;
	}
	
	
	/*
	SELECT watchlist.wl_title, user.user_name
	FROM watchlist
	LEFT JOIN user ON
		user.user_id = watchlist.wl_user
	WHERE
		wl_notificationtimestamp IS NOT NULL
		AND user.user_name = 'Cmavridi';
	*/
	// SELECT
		// watchlist.wl_title AS title,
		// watchlist.wl_namespace AS namespace,
		// watchlist.wl_notificationtimestamp AS notification,
		// user.user_name AS user_name,
		// user.user_real_name AS real_name
	// FROM watchlist
	// LEFT JOIN user ON user.user_id = watchlist.wl_user
	public function getPendingWatches () {
	
		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			array('w' => 'watchlist'),
			array(
				'w.wl_namespace AS namespace_id', 
				'w.wl_title AS title_text', 
				'w.wl_notificationtimestamp AS notification_timestamp', 
			),
			'w.wl_notificationtimestamp IS NOT NULL AND w.wl_user=' . $this->user->getId(),
			__METHOD__,
			array(
				// "DISTINCT",
				// "GROUP BY" => "w.hit_year, w.hit_month, w.hit_day",
				// "ORDER BY" => "w.hit_year DESC, w.hit_month DESC, w.hit_day DESC",
				"LIMIT" => "100000",
			),
			null // array( 'u' => array( 'LEFT JOIN', 'u.user-id=w.wl_user' ) )
		);
		$this->pendingWatches = array();
		while( $row = $dbr->fetchRow( $res ) ) {

			// $title = Title::newFromText( $row['title_text'], $row['notification_timestamp'] );
			$this->pendingWatches[] = $row;
			
		
		}

		return $this;
	}

}
