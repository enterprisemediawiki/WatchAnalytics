<?php

abstract class WatchStrengthTablePager extends TablePager {
	
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

	protected function createTimeStringFromMinutes ( $totalMinutes ) {
		
		$remainder = $totalMinutes;

		$minutesInDay = 60 * 24;
		$minutesInHour = 60;

		$days = floor( $remainder / $minutesInDay );
		$remainder = $remainder % $minutesInDay;

		$hours = floor( $remainder / $minutesInHour );
		$remainder = $remainder % $minutesInHour;

		$minutes = $remainder;

		$time = array();
		if ( $days ) {
			$time[] = $days . ' day' . (($days > 1) ? 's' : ''); 
		}
		if ( $hours ) {
			$time[] = $hours . ' hour' . (($hours > 1) ? 's' : ''); 
		}
		if ( $minutes ) {
			$time[] = $minutes . ' minute' . (($minutes > 1) ? 's' : ''); 
		}


		return implode(', ', $time);
	}

}
