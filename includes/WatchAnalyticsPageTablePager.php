<?php

class WatchAnalyticsPageTablePager extends WatchAnalyticsTablePager {

	protected $isSortable = array(
		'page_ns_and_title' => true,
		'num_watches' => true,
		'num_reviewed' => true,
		'percent_pending' => true,
		'max_pending_minutes' => true,
		'avg_pending_minutes' => true,
		'watch_quality' => true,
	);

	public function __construct( $page, $conds, $filters=array() ) {
		global $wgRequest;

		$this->watchQuery = new PageWatchesQuery();

		parent::__construct( $page, $conds, $filters );

		$sortField = $wgRequest->getVal( 'sort' );
		$this->mQueryNamespace = $wgRequest->getVal( 'ns' );

		if ( ! isset( $sortField ) ) {
			$this->mDefaultDirection = false;
		}
		
		$this->mExtraSortFields = array( 'num_watches', 'num_reviewed', 'page_ns_and_title' );
	}

	public function getQueryInfo() {
		$namespaces = MWNamespace::getCanonicalNamespaces();
		
		if ( $this->mQueryNamespace !== null 
			&& $this->mQueryNamespace >= 0 
			&& isset( $namespaces[ $this->mQueryNamespace ] ) ) {
			
			$conds = array(
				'p.page_namespace = ' . $this->mQueryNamespace
			);
		}
		else {
			$conds = array();
		}
		return $this->watchQuery->getQueryInfo( $conds );
	}

	public function formatValue ( $fieldName , $value ) {

		if ( $fieldName === 'page_ns_and_title' ) {
			$pageInfo = explode(':', $value, 2);
			$pageNsIndex = $pageInfo[0];
			$pageTitleText = $pageInfo[1];

			$title = Title::makeTitle( $pageNsIndex, $pageTitleText );

			$titleURL = $title->getLinkURL();
			$titleNsText = $title->getNsText();
			if ( $titleNsText === '' ) {
				$titleFullText = $title->getText();
			}
			else {
				$titleFullText = $titleNsText . ':' . $title->getText();
			}
			
			$pageLink = Xml::element(
				'a',
				array( 'href' => $titleURL ),
				$titleFullText
			);
			

			// FIXME: page stats not currently enabled. Uncomment when enabled
			$url = SpecialPage::getTitleFor( 'PageStatistics' )->getInternalURL( array(
				'page' => $title->getPrefixedText()
			) );
			$msg = wfMsg( 'watchanalytics-view-page-stats' );
			$pageStatsLink = Xml::element(
				'a',
				array( 'href' => $url ),
				$msg
			);

			
			$pageLink .= ' <small>(' . $pageStatsLink . ' | ' . WatchSuggest::getWatchLink( $title ) . ')</small>';


			return $pageLink;
		}
		else if ( $fieldName === 'max_pending_minutes' || $fieldName === 'avg_pending_minutes' ) {
			return ($value === NULL) ? NULL : $this->watchQuery->createTimeStringFromMinutes( $value );
		}
		else {
			return $value;
		}

	}

	public function getFieldNames() {
		return $this->watchQuery->getFieldNames();
	}

	public function getDefaultSort () {
		return 'num_reviewed';
	}

}