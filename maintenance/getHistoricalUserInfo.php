<?php

/**
 * This script is very BETA. It looks at the watch_tracking_user table
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

class WatchAnalyticsGetHistoricalUserInfo extends Maintenance {

	// page_id => array( num_watches, num_pending )
	protected $users = array();

	protected $curId; // debug only?

	protected $totalWatches = 0;
	protected $totalPending = 0; // was totalPending
	protected $watchBins = array();
	protected $pendingBins = array();


	/**
	 * Add description
	 *
	 * @param string $varName add description
	 * @return null
	 */
	public function __construct() {
		parent::__construct();

		$this->mDescription = "Get all the datas about user watches and pending reviews.";

	}

	/**
	 * Add description
	 *
	 * @param string $varName add description
	 * @return null
	 */
	public function execute() {

		$history = $this->getUserHistory();

		$textOutput = $this->formatOutput( $history );

		$outputFile = __DIR__ . "/output.txt";
		$this->output( "Writing output file.\n" );
		file_put_contents( $outputFile, $textOutput );

		$this->output( "\nComplete! See output file at:\n" );
		$this->output( "$outputFile\n" );

	}

	protected function getUserHistory () {

		$userTracking = $this->getUserWatchTracking();
		$validUsers = $this->getValidUsers();
		/**
		 *  "Y-m-d H:i:s" => array(
		 *      total_watches,
		 *      total_pending,
		 *      watch_bins => array( 0 => 2342, 1 => 12352, 2 ... ) )
		 *      pending_bins => array( 0 => 2342, 1 => 12352, 2 ... ) )
		 **/
		$return = array();

		// for counting how many complete,
		$rowStarted = 0;
		$rowTotal = $userTracking->numRows();

		while ( $row = $userTracking->fetchRow() ) {

			// output current state to terminal
			$rowStarted++;
			if ( $rowStarted % 1000 === 0 ) {
				$percent = round( ($rowStarted / $rowTotal) * 100, 1 );
				$this->output( "Starting row $rowStarted of $rowTotal ($percent%)...\n" );
			}

			$ts = $row['tracking_timestamp'];
			$userId = $row['user_id'];
			$this->curId = $userId; // debug only?

			// Makes sure the data is setup for $this->users[$userId], which
			// is the tracking data on this page ID at its most recent timestamp.
			// This data may not be setup, if this is the first instance of
			// this page ID.
			$this->initUserInfo( $userId );

			// skip this line if the page is not in the specified namespaces
			// (should this be handled with a join?)
			if ( ! in_array( $userId, $validUsers ) ) {
				continue;
			}

			// if first instance of this timestamp, init array. Otherwise will
			// update the values of the array.
			if ( ! isset( $return[$ts] ) ) {
				$return[$ts] = array();
			}

			$newUser = isset( $this->users[$userId]['new_user'] ) ? $this->users[$userId]['new_user'] : false;
			$wChange = $row['num_watches'] - $this->users[$userId]['num_watches'];
			$rChange = $row['num_pending'] - $this->users[$userId]['num_pending'];
			// if ( ! $newUser ) {
			// 	$this->output( "changes: $wChange, $rChange\n" );
			// }


			$oldWatchBin = $this->allocateToBin( $this->users[$userId]['num_watches'] );
			$newWatchBin = $this->allocateToBin( $row['num_watches'] );
			$oldPendBin  = $this->allocateToBin( $this->users[$userId]['num_pending'] );
			$newPendBin  = $this->allocateToBin( $row['num_pending'] );


			// update current state
			$this->totalWatches += $wChange;
			$this->totalPending += $rChange;
			$this->binChange(
				'watch',
				$newUser,
				$oldWatchBin,
				$newWatchBin
			);
			$this->binChange(
				'pending',
				$newUser,
				$oldPendBin,
				$newPendBin
			);
			$this->users[$userId] = array(
				'num_watches' => $row['num_watches'],
				'num_pending' => $row['num_pending'],
			);


			// write this row of data for this timestamp
			$return[$ts] = array(
				'total_watches'  => $this->totalWatches,
				'total_pending'  => $this->totalPending,
				'watch_bins'     => $this->watchBins,
				'pending_bins'   => $this->pendingBins,
			);

		}

		// This script uses a lot of memory, which may or may not be causing memory
		// leaks. Explicitly releasing the variables here in hopes of saving memory
		unset( $userTracking );
		unset( $validUsers );

		return $return;

	}

	protected function formatOutput ( $history ) {

		$this->output( "Building output file.\n" );

		$highestWatchBin = max( array_keys( $this->watchBins ) );
		$highestPendingBin = max( array_keys( $this->pendingBins ) );

		// make text header
		$textOutput = "timestamp\ttotal_watches\ttotal_pending";
		for( $i = 0; $i <= $highestWatchBin; $i++ ) {
			$textOutput .= "\t$i watches";
		}

		for( $i = 0; $i <= $highestPendingBin; $i++ ) {
			$textOutput .= "\t$i pending";
		}

		$textOutput .= "\n";

		// adding data...
		foreach( $history as $ts => $row ) {
			$textOutput .= "$ts\t"
				. $row['total_watches'] . "\t"
				. $row['total_pending'];

			for( $i = 0; $i <= $highestWatchBin; $i++ ) {
				$binSize = isset( $row['watch_bins'][$i] ) ? $row['watch_bins'][$i] : 0;
				$textOutput .= "\t" . $binSize;
			}

			for( $i = 0; $i <= $highestPendingBin; $i++ ) {
				$binSize = isset( $row['pending_bins'][$i] ) ? $row['pending_bins'][$i] : 0;
				$textOutput .= "\t" . $binSize;
			}

			$textOutput .= "\n";
		}

		return $textOutput;
	}


	protected function getUserWatchTracking () {

		$dbr = wfGetDB( DB_SLAVE );

		// index => array( tracking_timestamp, page_id, num_watches, num_pending )
		$this->output( "Starting query for watch_tracking_user data.\n" );
		$return = $dbr->select(
			'watch_tracking_user',
			array(
				'DATE_FORMAT( tracking_timestamp, "%Y-%m-%d %H:%i:%s") as tracking_timestamp',
				'user_id',
				'num_watches',
				'num_pending'
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
		// 		'wtp' => 'watch_tracking_user',
		// 		'p' => 'page',
		// 	),
		// 	array(
		// 		'DATE_FORMAT( tracking_timestamp, "%Y-%m-%d %H:%i:%s") as tracking_timestamp',
		// 		'p.page_id',
		// 		'num_watches',
		// 		'num_pending'
		// 	),
		// 	array(
		// 		'page_namespace' => 0
		// 	),
		// 	__METHOD__,
		// 	array( "ORDER BY" => "tracking_timestamp ASC" )
		// 	,array( 'page' => array( 'RIGHT JOIN', 'p.page_id=wtp.page_id' ) )
		// );
		$this->output( "Query for watch_tracking_user data complete.\n" );

		return $return;

	}

	protected function getValidUsers () {

		$this->output( "Starting query for valid users.\n" );
		$dbr = wfGetDB( DB_SLAVE );

		$usersResult = $dbr->select(
			array(
				'u' => 'user',
				'ug' => 'user_groups',
			),
			array( 'user_id' ),
			array(
				'ug.ug_group' => "CX3", // in CX3 only for now
			),
			__METHOD__,
			null,
			array( 'ug_groups' =>  array( 'LEFT JOIN', 'u.user_id = ug.ug_user' ) )
		);

		$validUsers = array();
		while( $row = $usersResult->fetchRow() ) {
			$validUsers[] = $row['user_id'];
		}

		unset( $usersResult );
		$this->output( "Complete with query for valid users.\n" );

		return $validUsers;

	}

	protected function initUserInfo ( $userId ) {
		if ( isset( $this->users[ $userId ] ) ) {
			return; // do nothing if user already is tracked
		}
		else {
			$this->users[ $userId ] = array(
				'num_watches' => 0,
				'num_pending' => 0,
				'new_user' => true,
			);
		}
	}

	protected function getBin ( $binType ) {
		if ( $binType === 'watch' ) {
			return 'watchBins';
		}
		else if ( $binType === 'pending' ) {
			return 'pendingBins';
		}
		else {
			die( 'binType needs to be "watch" or "pending"' ); // fixme: should be exception.
		}
	}

	protected function binChange ( $binType, $newUser, $oldBinNumber, $newBinNumber ) {

		$this->incrementBin( $binType, $newBinNumber );

		// decrease old bin if it's not a new user
		if ( ! $newUser ) {
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
			die( "Somehow $binType bin #$binNumber is being decremented when it doesn't exist yet. User ID = " . $this->curId );
		}
		else if ( $this->{$bin}[$binNumber] === 0 ) {
			die( "Somehow $binType bin #$binNumber is trying to be decremented below zero User ID = " . $this->curId );
		}
		else {
			$this->{$bin}[$binNumber]--;
		}

	}

	protected function allocateToBin ( $value ) {

		if ( $value === 0 ) {
			return '0';
		}
		else if ( $value < 11 ) {
			return "1 - 10";
		}
		else if ( $value < 21) {
			return "11 - 20";
		}
		else if ( $value < 41 ) {
			return "21 - 40";
		}
		else if ( $value < 61 ) {
			return "41 - 60";
		}
		else if ( $value < 81 ) {
			return "61 - 80";
		}
		else if ( $value < 101 ) {
			return "81 - 100";
		}
		else if ( $value < 201 ) {
			return "101 - 200";
		}
		else if ( $value < 501 ) {
			return "201 - 500";
		}
		else if ( $value < 1001 ) {
			return "501 - 1000";
		}
		else if ( $value < 2001 ) {
			return "1001 - 2000";
		}
		else {
			return ">2000";
		}

	}


}

$maintClass = "WatchAnalyticsGetHistoricalUserInfo";
require_once( DO_MAINTENANCE );








