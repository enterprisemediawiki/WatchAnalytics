<?php

/**
 * This script captures the current state of watchedness on the wiki and
 * records it in the appropriate tables.
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

class WatchAnalyticsForgivePendingReviews extends Maintenance {
	
	protected $forgiveBefore;
	
	public function __construct() {
		parent::__construct();
				
		$this->forgiveBefore = date( 'YmdHis', time() - 365*24*60*60 );
		$this->reviewedBy = 2;
		
		$this->mDescription = "Record the current state of page-watching.";
		
		// addOption ($name, $description, $required=false, $withArg=false, $shortName=false)
		$this->addOption(
			'usernames',
			'Limit forgiveness to comma separated list of usernames', 
			false, true );
		
		$this->addOption(
			'forgivebefore',
			'Limit forgiveness to pending reviews older than specified date (default 1 year ago, ' . $this->forgiveBefore . ')', 
			false, true );
		
		$this->addOption(
			'reviewedby',
			'Limit forgiveness to pages which have been reviewed by at least the specified number of people (default ' . $this->forgiveBefore . ')', 
			false, true );

	}
	
	// $query = 
		// "SELECT
			// u.user_name AS user_name,
			// p.page_title AS title,
			// w.wl_notificationtimestamp AS pending_since
		// FROM watchlist AS w
		// LEFT JOIN page AS p ON
			// w.wl_title = p.page_title 
			// AND w.wl_namespace = p.page_namespace
		// LEFT JOIN user AS u ON
			// u.user_id = w.wl_user
		// WHERE
			// w.wl_notificationtimestamp IS NOT NULL 
			// AND w.wl_notificationtimestamp < $forgiveBefore
			// AND (
				// SELECT COUNT(*)
				// FROM watchlist AS w2
				// WHERE
					// w.wl_namespace = w2.wl_namespace
					// AND w.wl_title = w2.wl_title
					// AND w2.wl_notificationtimestamp IS NULL
			// ) >= $reviewedBy
			// $usernames
		// ORDER BY w.wl_notificationtimestamp DESC;";
	public function execute() {
		$dbw = wfGetDB( DB_MASTER );

		$usernames = $this->getOption( 'usernames' );
		if ( $usernames ) {
			$namesArray = explode( ',', $usernames );
			foreach( $namesArray as $i => $u ) {
				$namesArray[$i] = trim( $u );
			}
		
			$namesForDB = $dbw->makeList( $namesArray );
			$usernames = "AND u.user_name IN ($namesForDB)";
		}
		else {
			$usernames = '';
		}
		

		$forgiveBefore = $this->getOption( 'forgivebefore', $this->forgiveBefore );
		$reviewedBy = $this->getOption( 'reviewedby', $this->reviewedBy );

		$query = 
			"UPDATE watchlist AS w
			LEFT JOIN page AS p ON
				w.wl_title = p.page_title 
				AND w.wl_namespace = p.page_namespace
			LEFT JOIN user AS u ON
				u.user_id = w.wl_user
			SET wl_notificationtimestamp = NULL
			WHERE
				w.wl_notificationtimestamp IS NOT NULL 
				AND w.wl_notificationtimestamp < $forgiveBefore
				$usernames
				AND (
					SELECT COUNT(*)
					FROM (SELECT * FROM watchlist) AS w2
					WHERE
						w.wl_namespace = w2.wl_namespace
						AND w.wl_title = w2.wl_title
						AND w2.wl_notificationtimestamp IS NULL
				) >= $reviewedBy;";

		$result = $dbw->query( $query );
		$success = print_r( $result, true );
		// $count = 0;
		// while ( $row = $dbw->fetchObject( $result ) ) {
			// $count++;
			// $this->output( "\n{$row->pending_since}: {$row->user_name}: {$row->title}" );
		// }
		$this->output( "\n $success \n" );
	}
}

$maintClass = "WatchAnalyticsForgivePendingReviews";
require_once( DO_MAINTENANCE );
