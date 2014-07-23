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

class WikiWatchesQuery extends WatchesQuery {

	protected $fieldNames = array(
		'tracking_timestamp'          => 'watchanalytics-special-header-timestamp',
		
		'num_pages'                   => 'watchanalytics-special-header-num-pages',
		'num_watches'                 =>   'watchanalytics-special-header-watches',
		'num_pending'                 =>   'watchanalytics-special-header-pending-watches',
		'max_pending_minutes'         =>   'watchanalytics-special-header-pending-maxtime',
		'avg_pending_minutes'         =>   'watchanalytics-special-header-pending-averagetime',

		'num_unwatched'               => 'watchanalytics-special-header-num-unwatched',
		'num_one_watched'             => 'watchanalytics-special-header-num-one-watched',
		'num_unreviewed'              => 'watchanalytics-special-header-num-unreviewed',
		'num_one_reviewed'            => 'watchanalytics-special-header-num-one-reviewed',

		'content_num_pages'           => 'watchanalytics-special-header-main-num-pages',
		'content_num_watches'         => 'watchanalytics-special-header-main-watches',
		'content_num_pending'         => 'watchanalytics-special-header-main-pending-watches',
		'content_max_pending_minutes' => 'watchanalytics-special-header-main-pending-maxtime',
		'content_avg_pending_minutes' => 'watchanalytics-special-header-main-pending-averagetime',

		'content_num_unwatched'       => 'watchanalytics-special-header-main-num-unwatched',
		'content_num_one_watched'     => 'watchanalytics-special-header-main-num-one-watched',
		'content_num_unreviewed'      => 'watchanalytics-special-header-main-num-unreviewed',
		'content_num_one_reviewed'    => 'watchanalytics-special-header-main-num-one-reviewed',
	);

	function getQueryInfo( $conds = null ) {
	
		$this->tables = array(
			'w' => 'watch_tracking_wiki'
		);

		$this->fields = array(
			'tracking_timestamp',
			
			'num_pages',
			'num_watches',
			'num_pending',
			'max_pending_minutes',
			'avg_pending_minutes',

			'num_unwatched',
			'num_one_watched',
			'num_unreviewed',
			'num_one_reviewed',

			'content_num_pages',
			'content_num_watches',
			'content_num_pending',
			'content_max_pending_minutes',
			'content_avg_pending_minutes',

			'content_num_unwatched',
			'content_num_one_watched',
			'content_num_unreviewed',
			'content_num_one_reviewed',
		);

		$this->conds = $conds ? $conds : array();
		
		$this->join_conds = array();

		$this->options = array();
		
		return parent::getQueryInfo();

	}

}
