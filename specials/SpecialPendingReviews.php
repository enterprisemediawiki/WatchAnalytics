<?php

class SpecialPendingReviews extends SpecialPage {

	public $mMode;
	protected $header_links = array(
		'watchanalytics-pages-specialpage' => '',
		'watchanalytics-users-specialpage' => 'users',
		'watchanalytics-wikihistory-specialpage'  => 'wikihistory',
	);


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
	 */
	function execute( $parser = null ) {
		global $wgRequest, $wgOut, $wgUser;

		$this->setHeaders();

		// check if the request is to clear a notification timestamp
		$clearNotifyTitle = $wgRequest->getVal( 'clearNotificationTitle' );
		if ( $clearNotifyTitle ) {
			$clearNotifyNS = $wgRequest->getVal( 'clearNotificationNS' );
			if ( ! $clearNotifyNS ) {
				$clearNotifyNS = 0;
			}
			
			$title = Title::newFromText( $clearNotifyTitle, $clearNotifyNS );
			$watch = WatchedItem::fromUserTitle( $wgUser, $title );
			$watch->resetNotificationTimestamp();
			
			$wgOut->addHTML(
				wfMessage(
					'pendingreviews-clear-page-notification',
					$title->getFullText(),
					Xml::tags('a', 
						array(
							'href' => $this->getTitle()->getLocalUrl(),
							'style' => 'font-weight:bold;',
						), 
						$this->getTitle() 
					)
				)->text()
			);
			
			return true;
		}
		
		// Check if a user has been specified.
		$requestUser = $wgRequest->getVal( 'user' );		
		if ( $requestUser ) {
			$this->mUser = User::newFromName( $requestUser );
			if ( $this->mUser->getId() === $wgUser->getId() ) {
				$this->mUserIsViewer = true;
			}
			else {
				$this->mUserIsViewer = false;
			}
			$wgOut->setPageTitle( wfMessage( 'pendingreviews-user-page', $this->mUser->getName() )->text() );

		}
		else {
			$this->mUser = $wgUser;
		}

		// add pending reviews JS/CSS
		$wgOut->addModules( 'ext.watchanalytics.pendingreviews' );

		// load styles for watch analytics special pages
		$wgOut->addModuleStyles( array(
			'ext.watchanalytics.specials',
			'ext.watchanalytics.pendingreviews', // FIXME:redundant?
		) );

		// how many reviews to display
		if( $wgRequest->getVal( 'limit' ) ) {
			$this->reviewLimit = $wgRequest->getVal( 'limit' ); //FIXME: for consistency, shouldn't this be just "limit"
		}
		else {
			$this->reviewLimit = 20;		
		}
		
		//FIXME: is this using a limit?
		$this->pendingReviewList = PendingReview::getPendingReviewsList( $this->mUser );

		$html = $this->getPageHeader();
		
		$html .= '<table class="pendingreviews-list">';
		$rowCount = 0;
	
		// loop through pending reviews
		foreach ( $this->pendingReviewList as $item ) {
			if ( $rowCount >= $this->reviewLimit ) {
				break;
			}
			
			// logic for:
			//   * isRedirect
			//   * isDeleted
			//   * isNewPage
			//   * files, approvals ... other log actions?

			// if the title exists, then the page exists (and hence it has not
			// been deleted)
			if ( $item->title ) {
			
				$combinedList = $this->combineLogAndChanges( $item->log, $item->newRevisions, $item->title );
				$changes = $this->getPendingReviewChangesList( $combinedList );
				
				$reviewButton = $this->getReviewButton( $item );

				$historyButton = $this->getHistoryButton( $item );

				$displayTitle = '<strong>' . $item->title->getFullText() . '</strong>';
				

				// FIXME: wow this is ugly
				$rowClass = ( $rowCount % 2 === 0 ) ? 'pendingreviews-even-row' : 'pendingreviews-odd-row';
				
				$classAndAttr = "class='pendingreviews-row $rowClass pendingreviews-row-$rowCount' pendingreviews-row-count='$rowCount'";

				$html .= "<tr $classAndAttr><td class='pendingreviews-page-title pendingreviews-top-cell'>$displayTitle</td><td class='pendingreviews-review-links pendingreviews-bottom-cell pendingreviews-top-cell'>$reviewButton $historyButton</td></tr>";
				
				$html .= "<tr $classAndAttr><td colspan='2' class='pendingreviews-bottom-cell'>$changes</td></tr>";
		
			}
			// page has been deleted (or moved w/o a redirect)
			else {
			
				$changes = '';				
				$changes = $this->getPendingReviewChangesList( $item->deletionLog );

				$acceptDeletionButton = $this->getMarkDeleteReviewedButton( $item->deletedTitle, $item->deletedNS );

				$talkToDeleterButton = $this->getDeleterTalkButton( $item->deletionLog );

				$title = Title::makeTitle( $item->deletedNS, $item->deletedTitle );
				
				$displayTitle = '<strong>' 
					. wfMessage( 'pendingreviews-page-deleted', $title->getFullText() )->parse()
					. '</strong>';
				

				// FIXME: wow this is ugly
				$rowClass = ( $rowCount % 2 === 0 ) ? 'pendingreviews-even-row' : 'pendingreviews-odd-row';
				
				$classAndAttr = "class='pendingreviews-row $rowClass pendingreviews-row-$rowCount' pendingreviews-row-count='$rowCount'";

				$html .= "<tr $classAndAttr><td class='pendingreviews-page-title pendingreviews-top-cell'>$displayTitle</td><td class='pendingreviews-review-links pendingreviews-bottom-cell pendingreviews-top-cell'>$acceptDeletionButton $talkToDeleterButton</td></tr>";
				
				$html .= "<tr $classAndAttr><td colspan='2' class='pendingreviews-bottom-cell'>$changes</td></tr>";
		
			}
		
			$rowCount++;
		}

		// $html .= '</ul>';
		$html .= '</table>';

		/*
		Useful Title functions:
			* getAuthorsBetween
			* countAuthorsBetwee
			* countRevisionsBetween
			* exists
			* getEditNotices
			* getInternalURL - getLinkURL - getLocalURL
			* getFullURL
			* getFullText - getPrefixedText
			* getLatestRevID
			* getLength
			* getNextRevisionID
			* getNotificationTimestamp
			* isDeleted (returns num deleted revs)
			  * isDeletedQuick (returns bool)
			* isNewPage
			* isRedirect
		 */

		$wgOut->addHTML( $html );

	}
	
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

			$diffURL= $item->title->getLocalURL( array(
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
			$linkText = 'No content changes - view latest';
			
			$diffLink = Xml::element( 'a',
				array( 'href' => $diffURL, 'class' => 'pendingreviews-green-button' ),
				$linkText
			);

		}
		
		return $diffLink;
	}
	
	public function getHistoryButton ( $item ) {
		return Xml::element( 'a',
			array(
				'href' => $item->title->getLocalURL( array( 'action' => 'history' ) ),
				'class' => 'pendingreviews-dark-blue-button'
			),
			wfMessage( 'watchanalytics-pendingreviews-history-link' )->text()
		);
	}
	
	/*
	http://localhost/wiki/eva/api.php?
	
	action=setnotificationtimestamp
	
	&titles=ORU%20Temporary%20Stowage%20Device
	
	&format=jsonfm
	
	&token=ef93a5946cdd798274990bc31d804625%2B%5C
	*/
	public function getMarkDeleteReviewedButton ( $titleText, $namespace ) {
		global $wgTitle;
		
		
		return Xml::element( 'a',
			array(
				'href' => $this->getTitle()->getLocalURL( array( 
					'clearNotificationTitle' => $titleText,
					'clearNotificationNS' => $namespace,
				) ),
				'class' => 'pendingreviews-red-button pendingreviews-accept-deletion',
				'pending-namespace' => $namespace,
				'pending-title' => $titleText,
			),
			wfMessage( 'pendingreviews-accept-deletion' )->text()
		);
	}
	
	public function getDeleterTalkButton ( $deletionLog ) {

		// echo "<pre>" . print_r($deletionLog, true) . "</pre>"; return '';
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
	
	public function getPageHeader() {
		// message like "You have X pending reviews"
		$html = '<p>' . wfMessage( 'pendingreviews-num-reviews', count( $this->pendingReviewList ) )->text();
		
		// message like "showing the oldest Y reviews"
		if ( count( $this->pendingReviewList ) > $this->reviewLimit ) {
			$html .= ' ' . wfMessage( 'pendingreviews-num-shown', $this->reviewLimit )->text();
		}
		
		// close out header
		$html .= '</p>';
		
		return $html;
	}
	
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

	protected function getLogChangeMessage ( $logEntry ) {

		// add pendingreviews-edited-by?
		$messages = array(
			'approval' => array( 
				'approve' => 'pendingreviews-log-approved',
				'unapprove' => 'pendingreviews-log-unapproved'
			),
			'delete' => array(
				'delete' => 'pendingreviews-log-delete',
				'restore' => 'pendingreviews-log-restore',
			),
			'import' => array(
				'upload' => 'pendingreviews-log-import-upload',
			),
			'move' => array(
				'move' => 'pendingreviews-log-move',
				'move_redir' => 'pendingreviews-log-move-redir',
			),
			'protect' => array(
				'protect' => 'pendingreviews-log-protect',
				'unprotect' => 'pendingreviews-log-unprotect',
				'modify' => 'pendingreviews-log-modify-protect',
			),
			'upload' => array(
				'upload' => 'pendingreviews-log-upload-new',
				'overwrite' => 'pendingreviews-log-upload-overwrite',
			),
		);

		$userPage = Title::makeTitle( NS_USER , $logEntry->log_user_text )->getFullText();

		if ( isset( $messages[ $logEntry->log_type ][ $logEntry->log_action ] ) ) {
			$messageParams = array( $userPage );
			if ( $logEntry->log_action == 'move' || $logEntry->log_action == 'move_redir' ) {
				$messageParams[] = PendingReview::getMoveTarget( $logEntry->log_params );
			}
			return wfMessage( $messages[ $logEntry->log_type ][ $logEntry->log_action ], $messageParams );
		}
		else {
			return wfMessage( 'pendingreviews-log-unknown-change', $userPage );
		}

	}
	
	public function getReviewTimeDiff ( $item ) {	
			
		$ts = new MWTimestamp( $item->notificationTimestamp );
		$displayTime = '<small>' . $ts->getHumanTimestamp( new MWTimestamp() ) . '</small>';

		$timeDiff = $ts->diff( new MWTimestamp() );
		if ( $timeDiff->days > 0 ) {
			$timeDiff = wfMessage( 'pendingreviews-timediff-days', $timeDiff->format( '%a' ) );
		}
		else if ( $timeDiff->h > 0 ) {
			$timeDiff = wfMessage( 'pendingreviews-timediff-hours', $timeDiff->format( '%h' ) );
		}
		else if ( $timeDiff->i > 0 ) {
			$timeDiff = wfMessage( 'pendingreviews-timediff-minutes', $timeDiff->format( '%i' ) );
		}
		else {
			$timeDiff = wfMessage( 'pendingreviews-timediff-just-now' )->text();
		}
		
		return $timeDiff;
	}

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
					$comment = '<nowiki>' . htmlspecialchars($comment) . "</nowiki>";
					$changeText = ' ' . wfMessage( 'pendingreviews-with-comment', array( $userPage, $comment ) )->parse();
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

