<?php
/**
 * Implements Special:PendingReviews, an alternative to Special:Watchlist.
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
 * @file
 * @ingroup SpecialPage
 */

/**
 * A special page that lists last changes made to the wiki that a user is
 * watching. Pages are listed in reverse-chronological order or by priority;
 * Priority is determined by how many people have already "reviewed" the
 * change.
 *
 * @ingroup SpecialPage
 */

class SpecialPendingReviews extends SpecialPage {

	public $mMode;
	protected $header_links = array(
		'watchanalytics-pages-specialpage' => '',
		'watchanalytics-users-specialpage' => 'users',
		'watchanalytics-wikihistory-specialpage'  => 'wikihistory',
	);


	/**
	 * Constructor for Special Page.
	 */
	public function __construct() {
		parent::__construct(
			"PendingReviews", //
			"",  // rights required to view
			true // show in Special:SpecialPages
		);
	}

	/**
	 * Main function for generating special page.
	 *
	 * First checks if this request is to clear a notification timestamp for a
	 * particular NS/title. If so, clear the notification then generate a
	 * simple response message and return
	 *
	 * Otherwise generates the special page.
	 *
	 * Generally this special page is only for the current user ($wgUser) to
	 * see their own pending reviews, but by setting the 'user' param in the
	 * query string it is possible to view others' Pending Reviews. FIXME: When
	 * this extension is "released" this function should be limited only to
	 * people with a special right.
	 *
	 * Useful Title functions:
	 * -----------------------
	 *   getAuthorsBetween
	 *   countAuthorsBetwee
	 *   countRevisionsBetween
	 *   exists
	 *   getEditNotices
	 *   getInternalURL - getLinkURL - getLocalURL
	 *   getFullURL
	 *   getFullText - getPrefixedText
	 *   getLatestRevID
	 *   getLength
	 *   getNextRevisionID
	 *   getNotificationTimestamp
	 *   isDeleted (returns num deleted revs) --AND-- isDeletedQuick (returns bool)
	 *   isNewPage
	 *   isRedirect
	 *
	 * @todo FIXME: break sections out into smaller functions - namely HTML writing (HTML templates?x)
	 * @todo FIXME: need logic for: isRedirect, isDeleted, isNewPage,
	 * and files, approvals ... other log actions?
	 * @todo FIXME: improve documentation above
	 * @param Parser|null $parser
	 * @return bool
	 */
	public function execute( $parser = null ) {
		global $wgOut, $wgUser;

		$this->setHeaders();

		// check if the request is to clear a notification timestamp
		$clearNotifyTitle = $this->getClearNotificationTitle();
		if ( $clearNotifyTitle ) {
			$this->handleClearNotification( $clearNotifyTitle );
			return true;
		}

		// sets user reviews to be displayed (if different from viewing user)
		$this->setPendingReviewsUser();

		// add pending reviews JS (and CSS, but need to explicitly call it below)
		$wgOut->addModules( 'ext.watchanalytics.pendingreviews' );

		// load styles for watch analytics special pages
		// Note: doing $out->addModules( ... ) instead of the two separate
		// functions causes the CSS to load later, which makes the page styles
		// apply late. This looks bad.
		$wgOut->addModuleStyles( array(
			'ext.watchanalytics.specials',
			'ext.watchanalytics.pendingreviews',
		) );

		// how many reviews to display
		$this->setReviewLimit();

		// FIXME: is this using a limit?
		$this->pendingReviewList = PendingReview::getPendingReviewsList( $this->mUser );

		$html = $this->getPageHeader();

		$html .= '<table class="pendingreviews-list">';
		$rowCount = 0;

		// loop through pending reviews
		foreach ( $this->pendingReviewList as $item ) {
			// if the title exists, then the page exists (and hence it has not
			// been deleted)
			if ( $item->title ) {
				$html .= $this->getStandardChangeRow( $item, $rowCount );
			}
			// page has been deleted (or moved w/o a redirect)
			else {
				$html .= $this->getDeletedPageRow( $item, $rowCount );
			}

			$rowCount++;
			if ( $rowCount >= $this->reviewLimit ) {
				break;
			}
		}// die();
		$html .= '</table>';

		global $egPendingReviewsShowWatchSuggestionsIfReviewsUnder; // FIXME: crazy long name...
		if ( $rowCount < $egPendingReviewsShowWatchSuggestionsIfReviewsUnder ) {
			$watchSuggest = new WatchSuggest( $this->mUser );
			$html .= $watchSuggest->getWatchSuggestionList();
		}

		$this->getOutput()->addHTML( $html );

		return true;
	}

	/**
	 * Handles case where user clicked a link to clear a pending review
	 * This will not display the pending reviews page.
	 *
	 * @return bool
	 */
	public function handleClearNotification ( $clearNotifyTitle ) {

		PendingReview::clearByUserAndTitle( $this->getUser(), $clearNotifyTitle );

		$this->getOutput()->addHTML(
			$this->msg(
				'pendingreviews-clear-page-notification',
				$clearNotifyTitle->getFullText(),
				Xml::tags( 'a',
					array(
						'href' => $this->getTitle()->getLocalUrl(),
						'style' => 'font-weight:bold;',
					),
					$this->getTitle()
				)
			)->text()
		);

	}

	/**
	 * Sending which user's reviews to display
	 *
	 * @return bool
	 */
	public function setPendingReviewsUser () {

		$viewingUser = $this->getUser();

		// Check if a user has been specified.
		$requestUser = $this->getRequest()->getVal( 'user' );
		if ( $requestUser ) {
			$this->mUser = User::newFromName( $requestUser );
			if ( $this->mUser->getId() === $viewingUser ) {
				$this->mUserIsViewer = true;
			}
			else {
				$this->mUserIsViewer = false;
			}
			$this->getOutput()->setPageTitle( wfMessage( 'pendingreviews-user-page', $this->mUser->getName() )->text() );

		}
		else {
			$this->mUser = $viewingUser;
		}

		return true;
	}

	/**
	 * Sets the number of reviews to return
	 *
	 * @return null
	 */
	public function setReviewLimit () {
		if ( $this->getRequest()->getVal( 'limit' ) ) {
			$this->reviewLimit = $this->getRequest()->getVal( 'limit' ); // FIXME: for consistency, shouldn't this be just "limit"
		}
		else {
			$this->reviewLimit = 20;
		}
	}

	/**
	 * Determines if user is attempting to clear a notification and returns
	 * the appropriate title.
	 *
	 * @return Title|false
	 */
	public function getClearNotificationTitle () {

		$clearNotifyTitle = $this->getRequest()->getVal( 'clearNotificationTitle' );

		if ( ! $clearNotifyTitle ) {
			return false;
		}

		$clearNotifyNS = $this->getRequest()->getVal( 'clearNotificationNS' );
		if ( ! $clearNotifyNS ) {
			$clearNotifyNS = 0;
		}

		$title = Title::newFromText( $clearNotifyTitle, $clearNotifyNS );
		return $title;
	}


	/**
	 * Generates row for a particular page in PendingReviews.
	 *
	 * @param PendingReview $item
	 * @param int $rowCount used to determine if the row is odd or even
	 * @return string HTML for row
	 */
	public function getStandardChangeRow ( PendingReview $item, $rowCount ) {

		$combinedList = $this->combineLogAndChanges( $item->log, $item->newRevisions, $item->title );
		$changes = $this->getPendingReviewChangesList( $combinedList );

		if ( $item->title->isRedirect() ) {
			$reviewButton = $this->getAcceptRedirectButton( $item );
		}
		else {
			$reviewButton = $this->getReviewButton( $item );
		}

		$historyButton = $this->getHistoryButton( $item );

		$displayTitle = '<strong>' . $item->title->getFullText() . '</strong>';

		return $this->getRowHTML( $item, $rowCount, $displayTitle, $reviewButton, $historyButton, $changes );

	}

	/**
	 * Generates row for a deleted page in PendingReviews. Pages could have
	 * been explicitly deleted, or they could have been moved without leaving
	 * a redirect behind.
	 *
	 * @param PendingReview $item
	 * @param int $rowCount used to determine if the row is odd or even
	 * @return string HTML for row
	 */
	public function getDeletedPageRow ( PendingReview $item, $rowCount ) {

		$pageWasMoved = false;
		$deletionLogLength = count( $item->deletionLog );
		for ( $i = $deletionLogLength - 1; $i >= 0; $i-- ) {
			if ( $item->deletionLog[$i]->log_type == 'move' ) {
				$pageWasMoved = true;
				break;
			}
			else if ( $item->deletionLog[$i]->log_type == 'delete' ) {
				$pageWasMoved = false;
				break;
			}
		}

		$changes = $this->getPendingReviewChangesList( $item->deletionLog );

		if ( $pageWasMoved ) {
			$acceptDeletionButton = $this->getAcceptMoveWithoutRedirectButton( $item->deletedTitle, $item->deletedNS );
			$displayMessage = 'pendingreviews-page-moved-no-redirect';
		}
		else {
			$acceptDeletionButton = $this->getMarkDeleteReviewedButton( $item->deletedTitle, $item->deletedNS );
			$displayMessage = 'pendingreviews-page-deleted';
		}

		$talkToDeleterButton = $this->getDeleterTalkButton( $item->deletionLog );

		$title = Title::makeTitle( $item->deletedNS, $item->deletedTitle );
		$displayTitle = '<strong>'
			. wfMessage( $displayMessage, $title->getFullText() )->parse()
			. '</strong>';

		return $this->getRowHTML( $item, $rowCount, $displayTitle, $acceptDeletionButton, $talkToDeleterButton, $changes );
	}

	/**
	 * Creates a button bringing user to the diff page.
	 *
	 * @param PendingReview $item
	 * @param int $rowCount
	 * @param string $displayTitle
	 * @param string $buttonOne
	 * @param string $buttonTwo
	 * @param string $changes
	 * @return string HTML for pending review of a given page
	 */
	public function getRowHTML ( PendingReview $item, $rowCount, $displayTitle, $buttonOne, $buttonTwo, $changes ) {

		// FIXME: wow this is ugly
		$rowClass = ( $rowCount % 2 === 0 ) ? 'pendingreviews-even-row' : 'pendingreviews-odd-row';

		if ( $item->numReviewers > $GLOBALS['egPendingReviewsOrangePagesThreshold'] ) {
			$reviewCriticality = 'green'; // page is "green" because it has lots of reviewers
		}
		else if ( $item->numReviewers > $GLOBALS['egPendingReviewsRedPagesThreshold'] ) {
			$reviewCriticality = 'orange';
		}
		else {
			$reviewCriticality = 'red'; // page is red because it has very few reviewers
		}
		$reviewCriticalityClass = 'pendingreviews-criticality-' . $reviewCriticality;

		$classAndAttr = "class='pendingreviews-row $rowClass $reviewCriticalityClass pendingreviews-row-$rowCount' pendingreviews-row-count='$rowCount'";

		$html = "<tr $classAndAttr><td class='pendingreviews-page-title pendingreviews-top-cell'>$displayTitle</td><td class='pendingreviews-review-links pendingreviews-bottom-cell pendingreviews-top-cell'>$buttonOne $buttonTwo</td></tr>";

		$html .= "<tr $classAndAttr><td colspan='2' class='pendingreviews-bottom-cell'>$changes</td></tr>";

		return $html;
	}

	/**
	 * Creates a button bringing user to the diff page.
	 *
	 * @param PendingReview $item
	 * @return string HTML for button
	 */
	public function getReviewButton ( $item ) {

		if ( count( $item->newRevisions ) > 0 ) {

			// returns essentially the negative-oneth revision...the one before
			// the wl_notificationtimestamp revision...or null/false if none exists?
			$mostRecentReviewed = Revision::newFromRow( $item->newRevisions[0] )->getPrevious();
		}
		else {
			$mostRecentReviewed = false; // no previous revision, the user has not reviewed the first!
		}

		if ( $mostRecentReviewed ) {

			$diffURL = $item->title->getLocalURL( array(
				'diff' => '',
				'oldid' => $mostRecentReviewed->getId()
			) );

			$diffLink = Xml::element( 'a',
				array( 'href' => $diffURL, 'class' => 'pendingreviews-green-button' ),
				wfMessage(
					'watchanalytics-pendingreviews-diff-revisions',
					count( $item->newRevisions )
				)->text()
			);
		}
		else {

			$latest = Revision::newFromTitle( $item->title );
			$diffURL = $item->title->getLocalURL( array( 'oldid' => $latest->getId() ) );

			$diffLink = Xml::element( 'a',
				array( 'href' => $diffURL, 'class' => 'pendingreviews-green-button' ),
				$this->msg( 'watchanalytics-pendingreviews-users-first-view' )->text()
			);

		}

		return $diffLink;
	}

	/**
	 * Creates a button bringing user to the history page.
	 *
	 * @param PendingReview $item
	 * @return string HTML for button
	 */
	public function getHistoryButton ( $item ) {
		return Xml::element( 'a',
			array(
				'href' => $item->title->getLocalURL( array( 'action' => 'history' ) ),
				'class' => 'pendingreviews-dark-blue-button'
			),
			wfMessage( 'watchanalytics-pendingreviews-history-link' )->text()
		);
	}


	/**
	 * Creates a button which marks a deleted or redirected page as "reviewed"
	 * (e.g. nullifies notification timestamp in watchlist). This function is
	 * used by several other functions to specify particular buttons.
	 *
	 * Reference example for API:
	 * http://example.com/wiki/api.php
	 *     ?action=setnotificationtimestamp
	 *     &titles=Some%20Page
	 *     &format=jsonfm
	 *     &token=ef93a5946cdd798274990bc31d804625%2B%5C
	 *
	 * @param string $titleText
	 * @param string|int $namespace
	 * @param string $buttonMsg i18n message key for the button's display text
	 * @param string $buttonClass class or space-separate list of classes to apply to the button
	 *
	 * @return string HTML for button
	 */
 	public function getClearNotificationButton ( $titleText, $namespace, $buttonMsg, $buttonClass ) {
		return Xml::element( 'a',
			array(
				'href' => $this->getTitle()->getLocalURL( array(
					'clearNotificationTitle' => $titleText,
					'clearNotificationNS' => $namespace,
				) ),
				'class' => $buttonClass,
				'pending-namespace' => $namespace,
				'pending-title' => $titleText,
			),
			wfMessage( $buttonMsg )->text()
		);
	}

	/**
	 * Creates a button which marks a deleted page as "reviewed" (e.g. nullifies
	 * notification timestamp in watchlist).
	 *
	 * @param string $titleText
	 * @param string|int $namespace
	 *
	 * @return string HTML for button
	 */
	public function getMarkDeleteReviewedButton ( $titleText, $namespace ) {
		return $this->getClearNotificationButton(
			$titleText, $namespace, 'pendingreviews-accept-deletion',
			'pendingreviews-red-button pendingreviews-accept-deletion'
		);
	}

	/**
	 * Creates a button which marks the "deleted" page that is "created" when
	 * a page is moved without leaving a redirect behind. Button allows the
	 * deleted page to be marked as "reviewed" (e.g. nullifies notification
	 * timestamp in watchlist).
	 *
	 * @param string $titleText
	 * @param string|int $namespace
	 *
	 * @return string HTML for button
	 */
	public function getAcceptMoveWithoutRedirectButton ( $titleText, $namespace ) {
		return $this->getClearNotificationButton(
			$titleText, $namespace, 'pendingreviews-accept-move-without-redirect',
			'pendingreviews-orange-button pendingreviews-accept-deletion'
		);
	}

	/**
	 * If a page is a redirect it should have a simple "accept" button
	 *
	 * @param PendingReview $item
	 *
	 * @return string HTML for button
	 */
	public function getAcceptRedirectButton ( $item ) {
		$titleText = $item->title->getDBkey();
		$namespace = $item->title->getNamespace();

		return $this->getClearNotificationButton(
			$titleText, $namespace, 'pendingreviews-accept-redirect',
			'pendingreviews-orange-button pendingreviews-accept-deletion'
		);
	}

	/**
	 * Creates a button bringing user to the talk page of the user who deleted
	 * the page, allowing them to ask questions about why the page was deleted.
	 *
	 * @param $deletionLog
	 * @return string HTML for button
	 */
	public function getDeleterTalkButton ( $deletionLog ) {

		if ( count( $deletionLog ) == 0 ) {
			return '';
		}

		$userId = $deletionLog[ count( $deletionLog ) - 1 ]->log_user;
		$user = User::newFromId( $userId );

		$userTalk = $user->getTalkPage();

		if ( $userTalk->exists() ) {
			$talkQueryString = array();
		}
		else {
			$talkQueryString = array( 'action' => 'edit' );
		}

		return Xml::element( 'a',
			array(
				'href' => $userTalk->getLocalURL( $talkQueryString ),
				'class' => 'pendingreviews-dark-blue-button' // pendingreviews-delete-talk-button
			),
			wfMessage( 'pendingreviews-page-deleted-talk', $user->getUserPage()->getFullText() )->text()
		);
	}

	/**
	 * Creates simple header stating how many pending reviews the user has.
	 *
	 * @return string HTML for header
	 */
	public function getPageHeader() {
		$numPendingReviews = count( $this->pendingReviewList );
		$html = '';

		if ( $numPendingReviews > 0 ) {
			$html .= $this->getPendingReviewsLegend();
		}

		// message like "You have X pending reviews"
		$html .= '<p>' . wfMessage( 'pendingreviews-num-reviews', $numPendingReviews )->text();

		// message like "showing the most important Y reviews"
		if ( $numPendingReviews > $this->reviewLimit ) {
			$html .= ' ' . wfMessage( 'pendingreviews-num-shown', $this->reviewLimit )->text();
		}

		// close out header
		$html .= '</p>';

		return $html;
	}


	/**
	 * Creates a legend for PendingReviews showing what colors mean regarding priority of pages
	 *
	 * @return string HTML for legend (table)
	 */
	public function getPendingReviewsLegend () {

		$redMaxReviewers = $GLOBALS['egPendingReviewsRedPagesThreshold'] - 1;
		$orangeMaxReviewers =  $GLOBALS['egPendingReviewsOrangePagesThreshold'] - 1;

		$redReviewersMsg = $this->msg(
			'pendingreviews-reviewer-criticality-red',
			$redMaxReviewers
		)->text();

		$orangeReviewersMsg = $this->msg(
			'pendingreviews-reviewer-criticality-orange',
			$orangeMaxReviewers
		)->text();

		$greenReviewersMsg = $this->msg(
			'pendingreviews-reviewer-criticality-green',
			$orangeMaxReviewers
		)->text();

		return "<table class='pendingreviews-legend'>
			<tr class='pendingreviews-criticality-red'><td>$redReviewersMsg</td></tr>
			<tr class='pendingreviews-criticality-orange'><td>$orangeReviewersMsg</td></tr>
			<tr class='pendingreviews-criticality-green'><td>$greenReviewersMsg</td></tr>
		</table>";

	}

	/**
	 * Merges arrays.
	 *
	 * @todo FIXME: documentation...why does this do what it does?
	 * @todo FIXME: cleanup temporary code
	 *
	 * @param $log
	 * @param $revisions
	 * @param $title
	 * @return array
	 */
	protected function combineLogAndChanges( $log, $revisions, $title ) {

		// if ( $title->getNamespace() === NS_FILE ) {

		// }


		// $log = array_reverse( $log );
		// $revisions = array_reverse( $revisions );
		$logI = 0;
		$revI = 0;

		$combinedArray = array();

		while ( count( $log ) > 0 && count( $revisions ) > 0 ) {

			$revTs = $revisions[ $revI ]->rev_timestamp;
			$logTs = $log[ $logI ]->log_timestamp;

			if ( $revTs > $logTs ) {
				$combinedArray[] = array_shift( $log );
			}
			else {
				$combinedArray[] = array_shift( $revisions );
			}

		}

		// $combinedArray += $revisions;
		// $combinedArray += $log;
		// print_r( array(count($combinedArray), count($log), count($revisions)) );
		$combinedArray = array_merge( $combinedArray, $revisions, $log );

		return $combinedArray;

	}

	/**
	 * Creates and returns a Message object appropriate for the type of log entry.
	 *
	 * @todo FIXME: what type is $logEntry
	 *
	 * @param object $logEntry
	 * @return Message HTML for button
	 */
	protected function getLogChangeMessage ( $logEntry ) {

		// add pendingreviews-edited-by?
		$messages = array(
			'approval' => array(
				'approve'    => 'pendingreviews-log-approved',
				'unapprove'  => 'pendingreviews-log-unapproved'
			),
			'delete' => array(
				'delete'     => 'pendingreviews-log-delete',
				'restore'    => 'pendingreviews-log-restore',
			),
			'import' => array(
				'upload'     => 'pendingreviews-log-import-upload',
			),
			'move' => array(
				'move'       => 'pendingreviews-log-move',
				'move_redir' => 'pendingreviews-log-move-redir',
			),
			'protect' => array(
				'protect'    => 'pendingreviews-log-protect',
				'unprotect'  => 'pendingreviews-log-unprotect',
				'modify'     => 'pendingreviews-log-modify-protect',
			),
			'upload' => array(
				'upload'     => 'pendingreviews-log-upload-new',
				'overwrite'  => 'pendingreviews-log-upload-overwrite',
			),
		);

		// get user page of user who created the log entry
		$userPage = Title::makeTitle( NS_USER , $logEntry->log_user_text )->getFullText();

		// if a message exists for the particular log type, handle it as follows
		if ( isset( $messages[ $logEntry->log_type ][ $logEntry->log_action ] ) ) {

			// all messages will use the executing users user-page
			$messageParams = array( $userPage );

			// if the log action is move or move_redir, the move target is in the message
			if ( $logEntry->log_action == 'move' || $logEntry->log_action == 'move_redir' ) {
				$messageParams[] = PendingReview::getMoveTarget( $logEntry->log_params );
			}

			if ( $logEntry->log_action == 'delete' ) { // $logEntry->log_comment ) {
				$messageParams[] = $logEntry->log_comment;
			}

			return wfMessage( $messages[ $logEntry->log_type ][ $logEntry->log_action ], $messageParams );

		}

		// if no message exists for the log type and action, handling with "unknown change"
		else {
			return wfMessage( 'pendingreviews-log-unknown-change', $userPage );
		}

	}

	/**
	 * Creates list of changes for a given page.
	 *
	 * @param array $combinedList
	 * @return string HTML
	 */
	public function getPendingReviewChangesList ( $combinedList ) {
		$changes = array();
		foreach ( $combinedList as $change ) {
			if ( isset( $change->log_timestamp ) ) {
				$changeTs = $change->log_timestamp;
				$changeText = $this->getLogChangeMessage( $change );
			}
			else {
				$rev = Revision::newFromRow( $change );
				$changeTs = $change->rev_timestamp;
				$userPage = Title::makeTitle( NS_USER , $change->rev_user_text )->getFullText();

				$comment = $rev->getComment();
				if ( $comment ) {
					$comment = '<span class="comment">' . Linker::formatComment( $comment ) . '</span>';
					$changeText = ' ' . wfMessage( 'pendingreviews-with-comment', array( $userPage ) )->parse() . ' ' . $comment;
				}
				else {
					$changeText = ' ' . wfMessage( 'pendingreviews-edited-by', $userPage )->parse();
				}
			}

			$changeTs = Xml::element( 'span',
				array( 'class' => 'pendingreviews-changes-list-time' ),
				( new MWTimestamp( $changeTs ) )->getHumanTimestamp()
			) . ' ';

			$changes[] = $changeTs . $changeText;
		}

		$changes = '<ul><li>' . implode( '</li><li>', $changes ) . '</li></ul>';

		return $changes;
	}

}

