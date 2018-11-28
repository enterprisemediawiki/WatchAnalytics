<?php

class WatchAnalyticsUser {

	protected $user;
	protected $pendingWatches;

	public function __construct( User $user ) {
		$this->user = $user;
	}

	public function getPendingWatches() {
		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select(
			[ 'w' => 'watchlist' ],
			[
				'w.wl_namespace AS namespace_id',
				'w.wl_title AS title_text',
				'w.wl_notificationtimestamp AS notification_timestamp',
			],
			'w.wl_notificationtimestamp IS NOT NULL AND w.wl_user=' . $this->user->getId(),
			__METHOD__,
			[
				// "DISTINCT",
				// "GROUP BY" => "w.hit_year, w.hit_month, w.hit_day",
				// "ORDER BY" => "w.hit_year DESC, w.hit_month DESC, w.hit_day DESC",
				"LIMIT" => "100000",
			],
			null // array( 'u' => array( 'LEFT JOIN', 'u.user-id=w.wl_user' ) )
		);
		$this->pendingWatches = [];
		while ( $row = $dbr->fetchRow( $res ) ) {

			// $title = Title::newFromText( $row['title_text'], $row['notification_timestamp'] );
			$this->pendingWatches[] = $row;

		}

		return $this;
	}

}
