<?php

class WatchAnalyticsUserTablePager extends WatchAnalyticsTablePager {
	
	protected $isSortable = array(
		'user_name' => true,
		'num_watches' => true,
		'num_pending' => true,
		'percent_pending' => true,
		'max_pending_minutes' => true,
		'avg_pending_minutes' => true,
	);

	function __construct( $page, $conds ) {
		$this->watchQuery = new UserWatchesQuery();
		parent::__construct( $page, $conds );
	}

	function getQueryInfo() {
		return $this->watchQuery->getQueryInfo();
	}

	function formatValue ( $fieldName , $value ) {

		if ( $fieldName === 'user_name' ) {

			$user_name = $value;

			$userPage = Title::makeTitle( NS_USER, $user_name );
			$name = $this->getSkin()->makeLinkObj( $userPage, htmlspecialchars( $userPage->getText() ) );
			
			/*
			Maybe do a stats page at some point...for now just show that user's pending reviews...
			$url = Title::newFromText('Special:WatchAnalytics')->getLocalUrl(
				array( 'user' => $user_name )
			);
			$msg = wfMsg( 'watchanalytics-view-user-stats' );
			*/

			$url = Title::newFromText('Special:PendingReviews')->getLocalUrl(
				array( 'user' => $user_name )
			);
			$msg = wfMsg( 'watchanalytics-view-user-pendingreviews' );

			$name .= ' (' . Xml::element(
				'a',
				array( 'href' => $url ),
				$msg
			) . ')';
			
			return $name;
		}
		else if ( $fieldName === 'max_pending_minutes' || $fieldName === 'avg_pending_minutes' ) {
			return ($value === NULL) ? NULL : $this->watchQuery->createTimeStringFromMinutes( $value );
		}
		else {
			return $value;
		}

	}

	function getFieldNames() {
		return $this->watchQuery->getFieldNames();
	}

	function getDefaultSort () {
		return 'num_pending';
	}

}
