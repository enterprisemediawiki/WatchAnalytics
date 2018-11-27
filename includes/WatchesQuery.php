<?php
/**
 * MediaWiki Extension: WatchAnalytics
 * http://www.mediawiki.org/wiki/Extension:WatchAnalytics
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * This program is distributed WITHOUT ANY WARRANTY.
 */

/**
 *
 * @file
 * @ingroup Extensions
 * @author James Montalvo
 * @license MIT License
 */

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/WatchAnalytics/WatchAnalytics.php" );
EOT;
	exit( 1 );
}

class WatchesQuery {

	public $sqlMaxPendingMins = 'MAX( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS max_pending_minutes';
	public $sqlAvgPendingMins = 'AVG( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS avg_pending_minutes';
	public $tables;
	public $fields;
	public $join_conds;
	public $conds;
	public $options;

	/**
	 * @var int $limit: maximum number of database rows to return
	 * @todo FIXME: who/what sets this?
	 * @example 20
	 */
	public $limit;

	/**
	 * @var int $offset: used with $limit for pagination
	 * @todo FIXME: who/what sets this?
	 * @example 100
	 */
	public $offset;

	/**
	 * @var array $fieldNames: property declared in child classes to bind a MW
	 * message with a SQL database column
	 * @todo FIXME: where is this used?
	 * @example array( 'dbkey' => 'message-name', 'page_ns_and_title' => 'watchanalytics-special-header-page-title' )
	 */
	protected $fieldNames;

	/**
	 * @var string|bool $userGroupFilter: defines which user group to be used to
	 * filter for page-watches
	 * @example 'sysop'
	 */
	protected $userGroupFilter = false;

	/**
	 * @var string|bool $categoryFilter: defines which category to be used to
	 * filter for page-watches
	 * @example 'Articles_with_unsourced_statements'
	 */
	protected $categoryFilter = false;

	public function __construct() {
	}

	public function createTimeStringFromMinutes( $totalMinutes ) {
		$remainder = $totalMinutes;

		$minutesInDay = 60 * 24;
		$minutesInHour = 60;

		$days = floor( $remainder / $minutesInDay );
		$remainder = $remainder % $minutesInDay;

		$hours = floor( $remainder / $minutesInHour );
		$remainder = $remainder % $minutesInHour;

		$minutes = $remainder;

		$time = [];
		if ( $days ) {
			$time[] = $days . ' day' . ( ( $days > 1 ) ? 's' : '' );
		}
		if ( $hours ) {
			$time[] = $hours . ' hour' . ( ( $hours > 1 ) ? 's' : '' );
		}
		if ( $minutes ) {
			$time[] = $minutes . ' minute' . ( ( $minutes > 1 ) ? 's' : '' );
		}

		// return implode(', ', $time);
		if ( count( $time ) > 0 ) {
			return $time[0];
		} else {
			return '';
		}
	}

	public function getFieldNames() {
		$output = [];

		foreach ( $this->fieldNames as $dbKey => $msg ) {
			$output[$dbKey] = wfMessage( $msg )->text();
		}

		return $output;
	}

	public function getQueryInfo() {
		$this->conds = $this->conds ? $this->conds : [];

		if ( isset( $this->limit ) ) {
			$this->options['LIMIT'] = $this->limit;
		}
		if ( isset( $this->offset ) ) {
			$this->options['OFFSET'] = $this->offset;
		}

		$return = [
			'tables' => $this->tables,
			'fields' => $this->fields,
			'join_conds' => $this->join_conds,
			'conds' => $this->conds,
			'options' => $this->options,
		];

		return $return;
	}

	public function setUserGroupFilter( $ugf ) {
		if ( $ugf ) {
			$this->userGroupFilter = $ugf;
		}
	}

	public function setCategoryFilter( $cf ) {
		if ( $cf ) {
			$this->categoryFilter = $cf;
		}
	}

	public function setCategoryFilterQueryInfo() {
		$this->tables['cat'] = 'categorylinks';
		$this->join_conds['cat'] = [
			'RIGHT JOIN', 'cat.cl_from = p.page_id AND cat.cl_to = "' . $this->categoryFilter . '"'
		];
	}

}
