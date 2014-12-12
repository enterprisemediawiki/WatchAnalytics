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
 * @licence MIT License
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
	protected $userGroupFilter = false;

	
	public function __construct () {
	}
	
	public function createTimeStringFromMinutes ( $totalMinutes ) {
		
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

		// return implode(', ', $time);
		return $time[0];
	}

	function getFieldNames() {
		$output = array();

		foreach ( $this->fieldNames as $dbKey => $msg ) {
			$output[$dbKey] = wfMessage( $msg )->text();
		}

		return $output;
	}

	public function getQueryInfo() {
	
		$this->conds = $this->conds ? $this->conds : array();

		if ( isset ( $this->limit ) ) {
			$this->options['LIMIT'] = $this->limit;
		}
		if ( isset ( $this->offset ) ) {
			$this->options['OFFSET'] = $this->offset;
		}

		$return = array(
			'tables' => $this->tables,
			'fields' => $this->fields,
			'join_conds' => $this->join_conds,
			'conds' => $this->conds,
			'options' => $this->options,
		);
		
		return $return;

	}

	public function setUserGroupFilter ( $ugf ) {
		if ( $ugf ) {
			$this->userGroupFilter = $ugf;
		}
	}

	public function setCategoryFilter ( $cf ) {
		if ( $cf ) {
			$this->categoryFilter = $cf;
		}
	}
	
}
