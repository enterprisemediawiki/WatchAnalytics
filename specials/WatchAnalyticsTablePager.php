<?php

abstract class WatchAnalyticsTablePager extends TablePager {
	
	function __construct( $page, $conds ) {
		$this->page = $page;
		$this->conds = $conds;
		$this->mDefaultDirection = true;
		parent::__construct( $page->getContext() );
	}

	function getIndexField() {

		global $wgRequest;

		$sortField = $wgRequest->getVal( 'sort' );
		if ( isset( $sortField ) && $this->isFieldSortable( $sortField ) ) {
			return $sortField;
		}
		else {
			return $this->getDefaultSort();
		}

	}

	function isNavigationBarShown() {
		return true;
	}

	function isFieldSortable ( $field ) {
		if ( ! isset( $this->isSortable[$field] ) ) {
			return false;
		}
		else {
			return $this->isSortable[ $field ];
		}
	}

}
