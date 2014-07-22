<?php

class WatchAnalyticsPageTablePager extends WatchAnalyticsTablePager {

	protected $isSortable = array(
		'page_ns_and_title' => true,
		'num_watches' => true,
		'num_reviewed' => true,
		'percent_pending' => true,
		'max_pending_minutes' => true,
		'avg_pending_minutes' => true,
	);

	function __construct( $page, $conds ) {
		$this->watchQuery = new PageWatchesQuery();

		parent::__construct( $page , $conds );

		global $wgRequest;

		$sortField = $wgRequest->getVal( 'sort' );
		if ( ! isset( $sortField ) ) {
			$this->mDefaultDirection = false;
		}
	}

	function getQueryInfo() {
		return $this->watchQuery->getQueryInfo();
	}

	function formatValue ( $fieldName , $value ) {

		if ( $fieldName === 'page_ns_and_title' ) {
			$pageInfo = explode(':', $value, 2);
			$pageNsIndex = $pageInfo[0];
			$pageTitleText = $pageInfo[1];

			$title = Title::makeTitle( $pageNsIndex, $pageTitleText );

			$pageLink = $this->getSkin()->makeLinkObj( $title, htmlspecialchars( $title->getText() ) );
			
			$url = Title::newFromText('Special:WatchAnalytics')->getLocalUrl(
				array( 'page' => $value )
			);
			$msg = wfMsg( 'watchanalytics-view-page-stats' );
			
			$pageLink .= ' (' . Xml::element(
				'a',
				array( 'href' => $url ),
				$msg
			) . ')';
			
			return $pageLink;
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
		return 'num_reviewed';
	}

}