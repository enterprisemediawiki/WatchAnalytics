<?php

/**
 * This script adds all pages in a category to a user's watchlist. This
 * occurs just once, and does not cause pages added to the category later
 * to be added to the user's watchlist.
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
	if ( getenv( "MW_INSTALL_PATH" ) ) {
		$IP = getenv( "MW_INSTALL_PATH" );
	}
}

require_once "$IP/maintenance/Maintenance.php";

class WatchAnalyticsAddCategoryToWatchlist extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = "Add all pages in a category to a user's watchlist.";

		// addOption ($name, $description, $required=false, $withArg=false, $shortName=false)
		$this->addOption(
			'usernames',
			'Apply watch actions to comma separated list of usernames',
			true, true );

		$this->addOption(
			'categories',
			'Apply watch actions to comma separated list of categories',
			true, true );

		// $this->addOption(
		// 'dry-run',
		// "List whether a page will be added to a user's watchlist, but do not perform action",
		// false, true );
	}

	public function execute() {
		$dbw = wfGetDB( DB_MASTER );

		$usernames = $this->getOption( 'usernames' );
		if ( $usernames ) {
			$namesArray = explode( ',', $usernames );
			foreach ( $namesArray as $i => $u ) {
				$namesArray[$i] = trim( $u );
			}
		} else {
			die( 'You must supply at least one username' );
		}

		$users = [];
		foreach ( $namesArray as $username ) {
			$users[] = User::newFromName( $username );
		}

		$categories = $this->getOption( 'categories' );
		if ( $categories ) {
			$catsArray = explode( ',', $categories );
			foreach ( $catsArray as $i => $c ) {
				$catsArray[$i] = trim( $c );
			}
		} else {
			die( 'You must supply at least one category' );
		}

		foreach ( $catsArray as $categoryName ) {
			$this->output( "Start processing Category:$categoryName\n" );

			$category = Category::newFromName( $categoryName );
			$titleArray = $category->getMembers();

			while ( $titleArray->valid() ) {
				$this->output( "\nAdding watchers to [[" . $titleArray->current->getFullText() . "]]...\n" );

				foreach ( $users as $user ) {
					$this->output( "    ...checking " . $user->getName() . "... " );

					$watchedItem = WatchedItem::fromUserTitle(
						$user,
						$titleArray->current,
						WatchedItem::IGNORE_USER_RIGHTS
					);

					if ( $watchedItem->isWatched() ) {
						$this->output( "already watching\n" );
					} else {
						$watchedItem->addWatch();
						$this->output( "added to watchlist\n" );
					}
				}

				// jump to next page in category
				$titleArray->next();
			}

		}

		$this->output( "\nComplete adding pages to watchlists!\n" );
	}
}

$maintClass = "WatchAnalyticsAddCategoryToWatchlist";
require_once RUN_MAINTENANCE_IF_MAIN;
