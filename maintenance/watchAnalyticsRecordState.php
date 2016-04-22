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

// Allow people to have different layouts.
if ( ! isset( $IP ) ) {
	$IP = __DIR__ . '/../../../';
	if ( getenv("MW_INSTALL_PATH") ) {
		$IP = getenv("MW_INSTALL_PATH");
	}
}

require_once( "$IP/maintenance/Maintenance.php" );

class WatchAnalyticsRecordState extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = "Record the current state of page-watching.";
	}

	public function execute() {
		$recorder = new WatchStateRecorder();
		$recorder->recordAll();
		$this->output( "\n Finished recording the state of wiki watching. \n" );
	}
}

$maintClass = "WatchAnalyticsRecordState";
require_once( DO_MAINTENANCE );
