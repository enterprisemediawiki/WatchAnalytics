<?php

class WikiWatchesQuery extends WatchesQuery {

	protected $fieldNames = [
		'tracking_timestamp'          => 'watchanalytics-special-header-timestamp',

		'num_pages'                   => 'watchanalytics-special-header-num-pages',
		'num_watches'                 => 'watchanalytics-special-header-watches',
		'num_pending'                 => 'watchanalytics-special-header-pending-watches',
		'max_pending_minutes'         => 'watchanalytics-special-header-pending-maxtime',
		'avg_pending_minutes'         => 'watchanalytics-special-header-pending-averagetime',

		'num_unwatched'               => 'watchanalytics-special-header-num-unwatched',
		'num_one_watched'             => 'watchanalytics-special-header-num-one-watched',
		'num_unreviewed'              => 'watchanalytics-special-header-num-unreviewed',
		'num_one_reviewed'            => 'watchanalytics-special-header-num-one-reviewed',

		// 'content_num_pages'           => 'watchanalytics-special-header-main-num-pages',
		// 'content_num_watches'         => 'watchanalytics-special-header-main-watches',
		// 'content_num_pending'         => 'watchanalytics-special-header-main-pending-watches',
		// 'content_max_pending_minutes' => 'watchanalytics-special-header-main-pending-maxtime',
		// 'content_avg_pending_minutes' => 'watchanalytics-special-header-main-pending-averagetime',

		// 'content_num_unwatched'       => 'watchanalytics-special-header-main-num-unwatched',
		// 'content_num_one_watched'     => 'watchanalytics-special-header-main-num-one-watched',
		// 'content_num_unreviewed'      => 'watchanalytics-special-header-main-num-unreviewed',
		// 'content_num_one_reviewed'    => 'watchanalytics-special-header-main-num-one-reviewed',
	];

	public function getQueryInfo( $conds = null ) {
		$this->tables = [
			'w' => 'watch_tracking_wiki'
		];

		$this->fields = [
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

			// 'content_num_pages',
			// 'content_num_watches',
			// 'content_num_pending',
			// 'content_max_pending_minutes',
			// 'content_avg_pending_minutes',

			// 'content_num_unwatched',
			// 'content_num_one_watched',
			// 'content_num_unreviewed',
			// 'content_num_one_reviewed',
		];

		$this->conds = $conds ? $conds : [];

		$this->join_conds = [];

		$this->options = [];

		return parent::getQueryInfo();
	}

}
