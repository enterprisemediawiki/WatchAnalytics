<?php

class WatchAnalyticsWikiTablePager extends WatchAnalyticsTablePager {

	protected $isSortable = array(		
		'tracking_timestamp' => true,
		
		'num_pages' => true,
		'num_watches' => true,
		'num_pending' => true,
		'max_pending_minutes' => true,
		'avg_pending_minutes' => true,

		'num_unwatched' => true,
		'num_one_watched' => true,
		'num_unreviewed' => true,
		'num_one_reviewed' => true,

		'content_num_pages' => true,
		'content_num_watches' => true,
		'content_num_pending' => true,
		'content_max_pending_minutes' => true,
		'content_avg_pending_minutes' => true,

		'content_num_unwatched' => true,
		'content_num_one_watched' => true,
		'content_num_unreviewed' => true,
		'content_num_one_reviewed' => true,
	);

	public function __construct( $page, $conds ) {
		$this->watchQuery = new WikiWatchesQuery();

		parent::__construct( $page , $conds );

		global $wgRequest;

		$sortField = $wgRequest->getVal( 'sort' );
		if ( ! isset( $sortField ) ) {
			$this->mDefaultDirection = false;
		}
		
		$this->mExtraSortFields = array();
	}

	public function getQueryInfo() {
		return $this->watchQuery->getQueryInfo();
	}

	public function formatValue ( $fieldName , $value ) {

		$timeDiffFields = array(
			'max_pending_minutes',
			'avg_pending_minutes',
			'content_max_pending_minutes',
			'content_avg_pending_minutes',
		);
	
		if ( in_array( $fieldName, $timeDiffFields ) ) {
			return ($value === NULL) ? NULL : $this->watchQuery->createTimeStringFromMinutes( $value );
		}
		else if ( $fieldName === 'tracking_timestamp' ) {
			$ts = new MWTimestamp( $value );
			return $ts->format('Y-m-d') . '<br />' . $ts->format('H:i:s');
		}
		else {
			return $value;
		}

	}

	public function getFieldNames() {
		return $this->watchQuery->getFieldNames();
	}

	public function getDefaultSort () {
		return 'tracking_timestamp';
	}

}