<?php

abstract class WatchAnalyticsTablePager extends TablePager {
	
	function __construct( $page, $conds ) {
		$this->page = $page;
		$this->limit = $page->limit;
		$this->offset = $page->offset;
		$this->conds = $conds;
		$this->mDefaultDirection = true;
		
		// $this->mIndexField = 'am_title';
		// $this->mPage = $page;
		// $this->mConds = $conds;
		// $this->mDefaultDirection = true; // always sort ascending
		// $this->mLimitsShown = array( 20, 50, 100, 250, 500, 5000 );

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
	

	/**
	 * Do a query with specified parameters, rather than using the object
	 * context
	 *
	 * @param string $offset index offset, inclusive
	 * @param $limit Integer: exact query limit
	 * @param $descending Boolean: query direction, false for ascending, true for descending
	 * @return ResultWrapper
	 */
	public function reallyDoQuery( $offset, $limit, $descending ) {
		$qInfo = $this->getQueryInfo( $offset, $limit, $descending );
		$tables = $qInfo['tables'];
		$fields = $qInfo['fields'];
		$conds  = $qInfo['conds'];
		$options = $qInfo['options'];
		$join_conds = $qInfo['join_conds'];
				
		// code below adapted from MW 1.22 core, Pager.php, 
		// IndexPager::buildQueryInfo()
		$sortColumns = array_merge( array( $this->mIndexField ), $this->mExtraSortFields );
		if ( $descending ) {
			$options['ORDER BY'] = $sortColumns;
		} else {
			$orderBy = array();
			foreach ( $sortColumns as $col ) {
				$orderBy[] = $col . ' DESC';
			}
			$options['ORDER BY'] = $orderBy;
		}
		if ( $offset != '' ) {
			if ( intval ( $offset ) < 0 ) {
				$offset = 0;
			}
			$options['OFFSET'] = $offset;
		}
		$options['LIMIT'] = intval( $limit );
		// end adapted code
		
		$dbr = wfGetDB( DB_SLAVE );
		return $dbr->select( $tables, $fields, $conds, __METHOD__, $options, $join_conds );
	}

	
	/**
	 * Override IndexPager in includes/Pager.php.
	 *
	 * @return Array
	 */
	function getPagingQueries() {
		$queries = parent::getPagingQueries();
				
		# Don't announce the limit everywhere if it's the default
		$this->limit = isset( $this->limit ) ? $this->limit : $this->mDefaultLimit;

		if ( isset( $this->offset ) ) {
			$offset = $this->offset;
		}
		else {
			$offset = 0;
		}
		
		
		if ( $offset <= 0 ) {
			$queries['prev'] = false;
			$queries['first'] = false;
		}
		else if ( isset( $queries['prev']['offset'] ) ) {
			$queries['prev']['offset'] = $offset - $this->limit;
		}
		
		if ( isset( $queries['next']['offset'] ) ) {
			$queries['next']['offset'] = $offset + $this->limit;
		}

		
		return $queries;
		
		// ----------------------------------------
		// Below from parent
		// ----------------------------------------
		
		// if ( !$this->mQueryDone ) {
			// $this->doQuery();
		// }

		// # Don't announce the limit everywhere if it's the default
		// $urlLimit = $this->mLimit == $this->mDefaultLimit ? null : $this->mLimit;

		// if ( $this->mIsFirst ) {
			// $prev = false;
			// $first = false;
		// } else {
			// $prev = array(
				// 'dir' => 'prev',
				// 'offset' => $this->mFirstShown,
				// 'limit' => $urlLimit
			// );
			// $first = array( 'limit' => $urlLimit );
		// }
		// if ( $this->mIsLast ) {
			// $next = false;
			// $last = false;
		// } else {
			// $next = array( 'offset' => $this->mLastShown, 'limit' => $urlLimit );
			// $last = array( 'dir' => 'prev', 'limit' => $urlLimit );
		// }
		// return array(
			// 'prev' => $prev,
			// 'next' => $next,
			// 'first' => $first,
			// 'last' => $last
		// );
	}
}
