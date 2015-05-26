<?php

abstract class WatchAnalyticsTablePager extends TablePager {
	
	public function __construct( $page, $conds, $filters=array() ) {
		$this->page = $page;
		$this->limit = $page->limit;
		$this->offset = $page->offset;
		$this->conds = $conds;
		$this->filters = $filters;
		$this->mDefaultDirection = true;
				
		if ( isset( $filters['groupfilter'] ) ) {
			$this->watchQuery->setUserGroupFilter(
				trim( $filters['groupfilter'] )
			);
		}

		if ( isset( $filters['categoryfilter'] ) ) {
			$this->watchQuery->setCategoryFilter(
				trim( $filters['categoryfilter'] )
			);
		}
		
		// $this->mIndexField = 'am_title';
		// $this->mPage = $page;
		// $this->mConds = $conds;
		// $this->mDefaultDirection = true; // always sort ascending
		// $this->mLimitsShown = array( 20, 50, 100, 250, 500, 5000 );

		parent::__construct( $page->getContext() );
	}

	public function getIndexField() {

		global $wgRequest;

		$sortField = $wgRequest->getVal( 'sort' );
		if ( isset( $sortField ) && $this->isFieldSortable( $sortField ) ) {
			return $sortField;
		}
		else {
			return $this->getDefaultSort();
		}

	}

	public function isNavigationBarShown() {
		return true;
	}

	public function isFieldSortable ( $field ) {
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
	public function getPagingQueries() {
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
		
	}
	

	/**
	 * Creates form to filter Watch Analytics results (e.g. by user group or
	 * page category).
	 * 
	 * FIXME: this is the ugly method taken from SpecialAllmessages...I'm
	 * hoping MW has a better way (templating engine?) to do this now than it
	 * did when SpecialAllmessages was created...
	 */
	public function buildForm() {
		global $wgScript;

		// user group filter
		$groups = array( $this->msg( 'watchanalytics-user-group-no-filter' )->text() => '');
		$rawGroups = User::getAllGroups();
		foreach( $rawGroups as $group ) {
			$labelMsg = $this->msg( 'group-' . $group );
			if ( $labelMsg->exists() ) {
				$label = $labelMsg->text();
			}
			else {
				$label = $group;
			}
			$groups[ $label ] = $group;
		}
		$groupFilter = new XmlSelect( 'groupfilter', false, $this->filters['groupfilter'] );
		$groupFilter->addOptions( $groups );

		// category filter
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select(
			'categorylinks',
			'cl_to',
			'',
			__METHOD__,
			array( 'DISTINCT' )
		);
		$categories = array( $this->msg( 'watchanalytics-category-no-filter' )->text() => '');
		while ( $row = $result->fetchRow() ) {
			$category = Category::newFromName( $row['cl_to'] );
			$label = $category->getTitle()->getText();
			
			$categories[ $label ] = $row['cl_to'];
		}
		$categoryFilter = new XmlSelect( 'categoryfilter', false, $this->filters['categoryfilter'] );
		$categoryFilter->addOptions( $categories );
		
		$out = 
			// create the form element
			Xml::openElement( 'form', array( 'method' => 'get', 'action' => $wgScript, 'id' => 'ext-watchanalytics-form' ) ) .
			
			// create fieldset
			Xml::fieldset( $this->msg( 'allmessages-filter-legend' )->text() ) .
			
			// create hidden <input> showing page name (Special:WatchAnalytics)
			Html::hidden( 'title', $this->getTitle()->getPrefixedText() ) .
			
			// create table for form elements
			Xml::openElement( 'table', array( 'class' => 'ext-watchanalytics-stats-filter-table' ) ) . "\n" .
			
			
			// filter results by user group
			'<tr>
				<td class="mw-label">' .
			Xml::label(
				$this->msg( 'watchanalytics-user-group-filter-label' )->text(),
				'ext-watchanalytics-user-group-filter'
			) .
			'</td>
			<td class="mw-input">' .
			$groupFilter->getHTML() .
			'</td>
			</tr>' .

			// filter results by page category
			'<tr>
				<td class="mw-label">' .
			Xml::label(
				$this->msg( 'watchanalytics-category-filter-label' )->text(),
				'ext-watchanalytics-category-filter'
			) .
			'</td>
			<td class="mw-input">' .
			$categoryFilter->getHTML() .
			'</td>
			</tr>' .
			
			// limit results returned
			'<tr>
				<td class="mw-label">' .
			Xml::label( $this->msg( 'table_pager_limit_label' )->text(), 'mw-table_pager_limit_label' ) .
			'</td>
			<td class="mw-input">' .
			$this->getLimitSelect() .
			'</td>
			</tr>' .
			
			// submit button
			'<tr>
				<td></td>
				<td>' .
			Xml::submitButton( $this->msg( 'allmessages-filter-submit' )->text() ) .
			"</td>\n
			</tr>" .

			// close out table element
			Xml::closeElement( 'table' ) .
			
			// FIXME: are all of these needed? are additional need to support
			// WatchAnalytics fields?
			$this->getHiddenFields( array( 'title', 'prefix', 'filter', 'lang', 'limit', 'groupfilter', 'categoryfilter' ) ) .
			
			// close fieldset and form elements
			Xml::closeElement( 'fieldset' ) .
			Xml::closeElement( 'form' );
			
		return $out;
	}
}
