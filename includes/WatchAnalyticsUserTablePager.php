<?php

class WatchAnalyticsUserTablePager extends WatchAnalyticsTablePager {

	protected $isSortable = [
		'user_name' => true,
		'num_watches' => true,
		'num_pending' => true,
		'percent_pending' => true,
		'max_pending_minutes' => true,
		'avg_pending_minutes' => true,
		'engagement_score' => true,
	];

	public function __construct( $page, $conds, $filters = [] ) {
		$this->watchQuery = new UserWatchesQuery();
		parent::__construct( $page, $conds, $filters );
	}

	public function getQueryInfo() {
		return $this->watchQuery->getQueryInfo();
	}

	public function formatValue( $fieldName, $value ) {
		if ( $fieldName === 'user_name' ) {

			$user_name = $value;

			$userPage = Title::makeTitle( NS_USER, $user_name );
			$name = Linker::link( $userPage, htmlspecialchars( $userPage->getText() ) );

			/*
			Maybe do a stats page at some point...for now just show that user's pending reviews...
			$url = Title::newFromText('Special:WatchAnalytics')->getLocalUrl(
				array( 'user' => $user_name )
			);
			$msg = wfMessage( 'watchanalytics-view-user-stats' );
			*/

			$url = Title::newFromText( 'Special:PendingReviews' )->getLocalUrl(
				[ 'user' => $user_name ]
			);
			$msg = wfMessage( 'watchanalytics-view-user-pendingreviews' );

			$name .= ' (' . Xml::element(
				'a',
				[ 'href' => $url ],
				$msg
			) . ')';

			return $name;
		} elseif ( $fieldName === 'max_pending_minutes' || $fieldName === 'avg_pending_minutes' ) {
			return ( $value === null ) ? null : $this->watchQuery->createTimeStringFromMinutes( $value );
		} else {
			return $value;
		}
	}

	public function getFieldNames() {
		return $this->watchQuery->getFieldNames();
	}

	public function getDefaultSort() {
		return 'num_pending';
	}

}
