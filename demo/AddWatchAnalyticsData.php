<?php

/**
 * This script adds pages and users to a wiki for the purpose of demonstrating
 * Extension:WatchAnalytics.
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

class AddWatchAnalyticsData extends Maintenance {
	
	public function __construct() {
		parent::__construct();
		
		$this->mDescription = "Add users and pages to a wiki to support WatchAnalytics demonstration.";
	}
	
	public function execute() {

		$numUsers = 26;
		$numPages = 200;
		$dbw = wfGetDB( DB_MASTER );

		$userNames = array();

		for ( $i = 0; $i < $numUsers; $i++ ) {
			$userNames[] = str_repeat( chr( $i + 97 ), 4 );
		}

		$users = array();
		foreach ( $userNames as $name ) {

			$newUser = User::createNew( $name );

			// // if username already exists
			// if ( $newUser === null ) {
			// 	$newUser = User::newFromName( $name );
			// }
			// // $newUser->loadFromDatabase();

			// $users[] = $newUser;

		}


		$res = $dbw->query('SELECT user_id FROM user;');

		while ( $u = $dbw->fetchRow( $res ) ) {
			$userIds[] = $u[ 'user_id' ];
		}
		foreach( $userIds as $uid ) {
			$users[] = User::newFromId( $uid );
		}



		for ( $i = 0; $i < $numPages; $i++ ) {

			$pageName = "Page $i";
			$this->output( "\n Working on $pageName" );
			$content = new TextContent( "This is page #$i" );
			$summary = "Scripted addition of watch analytics data...";

			$title = Title::makeTitle( 0, $pageName );
			$wikipage = new WikiPage( $title );
			$wikipage->doEditContent(
				$content,
				$summary
			);

			$numWatchers = rand( 1, 8 );

			$newPageWatchers = array();
			for ( $j = 0; $j < $numWatchers; $j++ ) {
			
				$newPageWatchers[] = rand( 0, $numUsers - 1 ); // push a user to the array

			}

			$newPageWatchers = array_unique( $newPageWatchers ); // remove duplicates

			foreach( $newPageWatchers as $arrInd => $userIndex ) {

				// if ( ! isset( $users[ $userIndex ] ) ) {
				// 	$debug = array();
				// 	foreach( $users as $u ) {
				// 		$uDebug[] = $u->getName();
				// 	}
				// 	$this->output( print_r( array( "index" => $userIndex, "users" => $uDebug ), true ) );
				// 	die();
				// }

				$this->output( "\n\t Adding user " . $users[ $userIndex ]->getName() );

				// $users[ $userIndex ]->addWatch( $title, WatchedItem::IGNORE_USER_RIGHTS );


				$main = array(
					'wl_user' => $users[ $userIndex ]->getId(),
					'wl_namespace' => MWNamespace::getSubject( $title->getNamespace() ),
					'wl_title' => $title->getDBkey(),
					'wl_notificationtimestamp' => null,
				);
				// Every single watched page needs now to be listed in watchlist;
				// namespace:page and namespace_talk:page need separate entries:
				$talk = array(
					'wl_user' => $users[ $userIndex ]->getId(),
					'wl_namespace' => MWNamespace::getTalk( $title->getNamespace() ),
					'wl_title' => $title->getDBkey(),
					'wl_notificationtimestamp' => null
				);

				// print_r( array( $talk, $users[ $userIndex ] ) );
				// die();
				$dbw->insert( 'watchlist', $main, __METHOD__, 'IGNORE' );
				$dbw->insert( 'watchlist', $talk, __METHOD__, 'IGNORE' );

				// $watchedItem = WatchedItem::fromUserTitle(
				// 	$users[ $userIndex ], 
				// 	$title
				// );
				// $watchSuccess = $watchedItem->addWatch();

				// if ( ! $watchSuccess ) {
				// 	$this->output( "\n\t failed watch" );
				// 	$this->output( "\n\t title is watchable?: " . print_r( $title->isWatchable(), true ) );
				// }
			}
		}

		$this->output( "\n Finished... \n" );

		/*
		global $wgTitle;
		
		$dbr = wfGetDB( DB_SLAVE );
		
		$pages = $dbr->select(
			'page',
			array(
				'page_id',
				'page_latest'
			)
		); 
		
		while ( $page = $pages->fetchObject() ) {
			$title = Title::newFromID( $page->page_id );
			// some extensions, like Semantic Forms, need $wgTitle
			// set as well
			$wgTitle = $title;
			if ( ApprovedRevs::pageIsApprovable( $title ) &&
				! ApprovedRevs::hasApprovedRevision( $title ) ) {
				ApprovedRevs::setApprovedRevID( $title, $page->page_latest, true );
				$this->output( wfTimestamp( TS_DB ) . ' Approved the last revision of page "' . $title->getFullText() . '".' );
			}
		}
		*/
		
	}
	
}

$maintClass = "AddWatchAnalyticsData";
require_once( DO_MAINTENANCE );
