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

class PageScore {

	/**
	 * @var int $limit: maximum number of database rows to return
	 * @todo FIXME: who/what sets this?
	 * @example 20
	 */
	// public $limit;

	public function __construct ( Title $title ) {
	
		$this->mTitle = $title;
		$this->cssColorClasses = array(
			'excellent',
			'good',
			'okay',
			'warning',
			'danger',
		);
	}

	/**
	 * Handles something.
	 * 
	 * @return string
	 */
	public function getWatchQuality () {
		$pwq = new PageWatchesQuery();
		return round( $pwq->getPageWatchQuality( $this->mTitle ), 1 );
	}

	public function getReviewStatus () {
		return $this->getNumReviews();
	}


	public function getNumReviews () {

		$dbr = wfGetDB( DB_SLAVE );


		$pageData = $dbr->selectRow(
			'watchlist',
			'COUNT(*) AS num_reviews',
			array(
				'wl_notificationtimestamp IS NULL',
				'wl_namespace' => $this->mTitle->getNamespace(),
				'wl_title' => $this->mTitle->getDBkey()
			),
			__METHOD__
		);

		return $pageData->num_reviews;

	}
	
	public function getScoreColor ( $score, $configVariable ) {

		$score = intval( $score );
		$cssIndex = 4;
		foreach( $GLOBALS[ $configVariable ] as $index => $upperBound ) {
			if ( $score > $upperBound ) {
				$cssIndex = $index;
				break;
			}
		}
		return $this->cssColorClasses[ $cssIndex ];

	}


	public function getPageScoreTemplate () {

		$watchQuality = $this->getWatchQuality();
		$watchQualityColorClass = $this->getScoreColor( $watchQuality, 'egWatchAnalyticsWatchQualityColors' );
		$reviewStatus = $this->getReviewStatus();
		$reviewStatusColorClass = $this->getScoreColor( $watchQuality, 'egWatchAnalyticsReviewStatusColors' );
		
		// when MW 1.25 is released (very soon) replace this with a mustache template
		$template = "<div class='ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-$reviewStatusColorClass'>
				<div class='ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-left'>
					Review Status
				</div>
				<div class='ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-right'>
					$reviewStatus
				</div>
			</div>
			<div class='ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-$watchQualityColorClass'>
				<div class='ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-left'>
					Watch Quality
				</div>
				<div class='ext-watchanalytics-pagescores-badge ext-watchanalytics-pagescores-right'>
					$watchQuality
				</div>
			</div>";

		return "<script type='text/template' id='ext-watchanalytics-pagescores'>$template</script>";

	}

}
