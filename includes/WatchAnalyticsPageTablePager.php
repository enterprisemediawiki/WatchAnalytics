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
		global $wgRequest;

		$this->watchQuery = new PageWatchesQuery();

		if ( $wgRequest->getVal( 'groupfilter' ) ) {
			$this->watchQuery->setUserGroupFilter(
				trim( $wgRequest->getVal( 'groupfilter' ) )
			);
		}
		
		parent::__construct( $page , $conds );

		$sortField = $wgRequest->getVal( 'sort' );
		$this->mQueryNamespace = $wgRequest->getVal( 'ns' );

		if ( ! isset( $sortField ) ) {
			$this->mDefaultDirection = false;
		}
		
		$this->mExtraSortFields = array( 'num_watches', 'num_reviewed', 'page_ns_and_title' );
	}

	function getQueryInfo() {
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

	function formatValue ( $fieldName , $value ) {

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