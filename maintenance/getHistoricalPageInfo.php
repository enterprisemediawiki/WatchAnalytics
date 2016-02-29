<?php

/**
 * This script is very BETA. It looks at the watch_tracking_page table
 * and calculates the watching and reviewing history of your wiki. The
 * output is to a text file in this directory (__DIR__) for now...
 *
 * Usage:
 *  no parameters
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @author James Montalvo
 * @ingroup Maintenance
 */

require_once( __DIR__ . '/../../../maintenance/Maintenance.php' );

class WatchAnalyticsGetHistoricalPageInfo extends Maintenance {

	// page_id => array( num_watches, num_reviewed )
	protected $pages = array();

	protected $curId; // debug only?

	protected $totalWatches = 0;
	protected $totalReviews = 0;
	protected $watchBins = array();
	protected $reviewBins = array();


	/**
	 * Add description
	 *
	 * @param string $varName add description
	 * @return null
	 */
	public function __construct() {
		parent::__construct();

		$this->mDescription = "Get all the datas about page watches and reviews.";

	}

	/**
	 * Add description
	 *
	 * @param string $varName add description
	 * @return null
	 */
	public function execute() {

		$history = $this->getPageHistory();

		$textOutput = $this->formatOutput( $history );

		$outputFile = __DIR__ . "/output.txt";
		$this->output( "Writing output file.\n" );
		file_put_contents( $outputFile, $textOutput );

		$this->output( "\nComplete! See output file at:\n" );
		$this->output( "$outputFile\n" );

	}

	protected function getPageHistory () {

		$pageTracking = $this->getPageWatchTracking();
		$validPages = $this->getValidPages();
		/**
		 *  "Y-m-d H:i:s" => array(
		 *      total_watches,
		 *      total_reviewed,
		 *      watch_bins => array( 0 => 2342, 1 => 12352, 2 ... ) )
		 *      review_bins => array( 0 => 2342, 1 => 12352, 2 ... ) )
		 **/
		$return = array();

		// for counting how many complete,
		$rowComplete = 0;
		$rowTotal = $pageTracking->numRows();

		while ( $row = $pageTracking->fetchRow() ) {

			$ts = $row['tracking_timestamp'];
			$pageId = $row['page_id'];
			$this->curId = $pageId; // debug only?

			// Makes sure the data is setup for $this->pages[$pageId], which
			// is the tracking data on this page ID at its most recent timestamp.
			// This data may not be setup, if this is the first instance of
			// this page ID.
			$this->initPageInfo( $pageId );

			// skip this line if the page is not in the specified namespaces
			// (should this be handled with a join?)
			if ( ! in_array( $pageId, $validPages ) ) {
				continue;
			}

			// if first instance of this timestamp, init array. Otherwise will
			// update the values of the array.
			if ( ! isset( $return[$ts] ) ) {
				$return[$ts] = array();
			}

			$newPage = isset( $this->pages[$pageId]['new_page'] ) ? $this->pages[$pageId]['new_page'] : false;
			$wChange = $row['num_watches'] - $this->pages[$pageId]['num_watches'];
			$rChange = $row['num_reviewed'] - $this->pages[$pageId]['num_reviewed'];
			// if ( ! $newPage ) {
			// 	$this->output( "changes: $wChange, $rChange\n" );
			// }

			// update current state
			$this->totalWatches += $wChange;
			$this->totalReviews += $rChange;
			$this->binChange(
				'watch',
				$newPage,
				$this->pages[$pageId]['num_watches'],
				$row['num_watches']
			);
			$this->binChange(
				'review',
				$newPage,
				$this->pages[$pageId]['num_reviewed'],
				$row['num_reviewed']
			);
			$this->pages[$pageId] = array(
				'num_watches' => $row['num_watches'],
				'num_reviewed' => $row['num_reviewed'],
			);


			// write this row of data for this timestamp
			$return[$ts] = array(
				'total_watches'  => $this->totalWatches,
				'total_reviewed' => $this->totalReviews,
				'watch_bins'     => $this->watchBins,
				'review_bins'    => $this->reviewBins,
			);


			// output current state to terminal
			$rowComplete++;
			if ( $rowComplete % 100000 === 0 ) {
				$percent = round( ($rowComplete / $rowTotal) * 100, 1 );
				$this->output( "$rowComplete rows of $rowTotal complete ($percent%)...\n" );
			}
		}

		// This script uses a lot of memory, which may or may not be causing memory
		// leaks. Explicitly releasing the variables here in hopes of saving memory
		unset( $pageTracking );
		unset( $validPages );

		return $return;

	}

	protected function formatOutput ( $history ) {

		$this->output( "Building output file.\n" );

		$highestWatchBin = max( array_keys( $this->watchBins ) );
		$highestReviewBin = max( array_keys( $this->reviewBins ) );

		// make text header
		$textOutput = "timestamp\ttotal_watches\ttotal_reviewed";
		for( $i = 0; $i <= $highestWatchBin; $i++ ) {
			$textOutput .= "\t$i watchers";
		}

		for( $i = 0; $i <= $highestReviewBin; $i++ ) {
			$textOutput .= "\t$i reviewers";
		}

		$textOutput .= "\n";

		// adding data...
		foreach( $history as $ts => $row ) {
			$textOutput .= "$ts\t"
				. $row['total_watches'] . "\t"
				. $row['total_reviewed'];

			for( $i = 0; $i <= $highestWatchBin; $i++ ) {
				$binSize = isset( $row['watch_bins'][$i] ) ? $row['watch_bins'][$i] : 0;
				$textOutput .= "\t" . $binSize;
			}

			for( $i = 0; $i <= $highestReviewBin; $i++ ) {
				$binSize = isset( $row['review_bins'][$i] ) ? $row['review_bins'][$i] : 0;
				$textOutput .= "\t" . $binSize;
			}

			$textOutput .= "\n";
		}

		return $textOutput;
	}


	protected function getPageWatchTracking () {

		$dbr = wfGetDB( DB_SLAVE );

		// index => array( tracking_timestamp, page_id, num_watches, num_reviewed )
		$this->output( "Starting query for watch_tracking_page data.\n" );
		$return = $dbr->select(
			'watch_tracking_page',
			array(
				'DATE_FORMAT( tracking_timestamp, "%Y-%m-%d %H:%i:%s") as tracking_timestamp',
				'page_id',
				'num_watches',
				'num_reviewed'
			),
			null,
			// array(
			// 	'tracking_timestamp > 20140000000000',
			// 	'tracking_timestamp < 20150000000000',
			// ),
			__METHOD__,
			array( "ORDER BY" => "tracking_timestamp ASC" )
		);
		// join method
		// $return = $dbr->select(
		// 	array(
		// 		'wtp' => 'watch_tracking_page',
		// 		'p' => 'page',
		// 	),
		// 	array(
		// 		'DATE_FORMAT( tracking_timestamp, "%Y-%m-%d %H:%i:%s") as tracking_timestamp',
		// 		'p.page_id',
		// 		'num_watches',
		// 		'num_reviewed'
		// 	),
		// 	array(
		// 		'page_namespace' => 0
		// 	),
		// 	__METHOD__,
		// 	array( "ORDER BY" => "tracking_timestamp ASC" )
		// 	,array( 'page' => array( 'RIGHT JOIN', 'p.page_id=wtp.page_id' ) )
		// );
		$this->output( "Query for watch_tracking_page data complete.\n" );

		return $return;

	}

	protected function getValidPages () {

		$dbr = wfGetDB( DB_SLAVE );

		$pageNamespacesResult = $dbr->select(
			'page',
			array( 'page_id' ),
			array(
				'page_namespace' => 0, // in the main namespace only for now
			),
			__METHOD__
		);

		$validPages = array();
		while( $row = $pageNamespacesResult->fetchRow() ) {
			$validPages[] = $row['page_id'];
		}

		unset( $pageNamespacesResult );

		return $validPages;

	}

	protected function initPageInfo ( $pageId ) {
		if ( isset( $this->pages[ $pageId ] ) ) {
			return; // do nothing if page already is tracked
		}
		else {
			$this->pages[ $pageId ] = array(
				'num_watches' => 0,
				'num_reviewed' => 0,
				'new_page' => true,
			);
		}
	}

	protected function getBin ( $binType ) {
		if ( $binType === 'watch' ) {
			return 'watchBins';
		}
		else if ( $binType === 'review' ) {
			return 'reviewBins';
		}
		else {
			die( 'binType needs to be "watch" or "review"' ); // fixme: should be exception.
		}
	}

	protected function binChange ( $binType, $newPage, $oldBinNumber, $newBinNumber ) {

		$this->incrementBin( $binType, $newBinNumber );

		// decrease old bin if it's not a new page
		if ( ! $newPage ) {
			$this->decrementBin( $binType, $oldBinNumber );
		}
	}

	protected function incrementBin ( $binType, $binNumber ) {

		$bin = $this->getBin( $binType );

		if ( isset( $this->{$bin}[$binNumber] ) ) {
			$this->{$bin}[$binNumber]++;
		}
		else {
			$this->{$bin}[$binNumber] = 1;
		}

		// $this->output( print_r( $this->{$bin}, true ) . "\n" );
		// $this->output( print_r( $this->watchBins, true ) . "\n" );

	}

	protected function decrementBin ( $binType, $binNumber ) {

		$bin = $this->getBin( $binType );

		if ( ! isset( $this->{$bin}[$binNumber] ) ) {
			die( "Somehow $binType bin #$binNumber is being decremented when it doesn't exist yet. Page ID = " . $this->curId );
		}
		else if ( $this->{$bin}[$binNumber] === 0 ) {
			die( "Somehow $binType bin #$binNumber is trying to be decremented below zero Page ID = " . $this->curId );
		}
		else {
			$this->{$bin}[$binNumber]--;
		}

	}



	protected function getPreviousPageNumWatches ( $pageId ) {
		return isset( $this->pages[ $pageId ] ) ? $this->pages[ $pageId ]['num_watches'] : 0;
	}

	protected function getPreviousPageNumReviewed ( $pageId ) {
		return isset( $this->pages[ $pageId ] ) ? $this->pages[ $pageId ]['num_reviewed'] : 0;
	}

}

$maintClass = "WatchAnalyticsGetHistoricalPageInfo";
require_once( DO_MAINTENANCE );








