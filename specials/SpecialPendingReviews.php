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
	
	function execute( $parser = null ) {
		global $wgRequest, $wgOut, $wgUser;

		$this->setHeaders();

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


		$wgOut->addModules( 'ext.watchanalytics.pendingreviews' );

		// load styles for watch analytics special pages
		$wgOut->addModuleStyles( array(
			'ext.watchanalytics.specials',
			'ext.watchanalytics.pendingreviews',
		) );

		
		$wgOut->addHTML( $this->getPageHeader() );
		
		// ...

		$userWatchQuery = new UserWatchesQuery();

		$limit = 20;
		$pending = $userWatchQuery->getUserPendingWatches( $this->mUser );

		// $html = '<pre>' . json_encode( $pending, JSON_PRETTY_PRINT ) . '</pre>';
		// $html = '<ul>';
		$html = '<p>' . wfMessage( 'pendingreviews-num-reviews', count( $pending ) )->text();
		if ( count( $pending ) > $limit ) {
			$html .= ' ' . wfMessage( 'pendingreviews-num-shown', $limit )->text();
		}
		$html .= '</p>';
		
		$html .= '<table class="pendingreviews-list">';
		$rowCount = 0;
		
		foreach ( $pending as $item ) {
			if ( $rowCount >= $limit ) {
				break;
			}
			
			// logic for:
			//   * isRedirect
			//   * isDeleted
			//   * isNewPage
			//   * files, approvals ... other log actions?

			$combinedList = $this->combineLogAndChanges( $item->log, $item->newRevisions, $item->title );
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

					$changeText = wfMessage( 'pendingreviews-edited-by', $userPage )->parse();
					$comment = $rev->getComment();
					if ( $comment ) {
						$changeText .= ' ' . wfMessage( 'pendingreviews-with-comment', $comment)->text();
					}
				}

				$changeTs = Xml::element( 'span',
					array( 'class' => 'pendingreviews-changes-list-time' ),
					( new MWTimestamp( $changeTs ) )->getHumanTimestamp()
				) . ' ';

				$changes[] = $changeTs . $changeText;
			}
			
			$changes = '<ul><li>' . implode( '</li><li>', $changes ) . '</li></ul>';
			
			
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


			if ( count( $item->newRevisions ) ) {
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
					array( 'href' => $diffURL, 'class' => 'pendingreviews-diff-button' ),
					wfMessage(
						'watchanalytics-pendingreviews-diff-revisions',
						count( $item->newRevisions )
					)->text()
				);
			}
			else {
				$diffLink = wfMessage( 'pendingreviews-no-revisions' )->text();
			}

			$histLink = Xml::element( 'a',
				array(
					'href' => $item->title->getLocalURL( array( 'action' => 'history' ) ),
					'class' => 'pendingreviews-hist-button'
				),
				wfMessage( 'watchanalytics-pendingreviews-history-link' )->text()
			);

			$displayTitle = '<strong>' . $item->title->getFullText() . '</strong>';
			//$displayTitle .= "<p class='pendingreviews-timediff'>$timeDiff</p>";



			// $revisions = count($item->newRevisions) . ' revisions';
			$logActions = count($item->log) . ' log actions';

			// $html .= "<li>$displayTime : $displayTitle - $diffLink  ($logActions)</li>";
			

			// FIXME: wow this is ugly
			$rowClass = ( $rowCount % 2 === 0 ) ? 'pendingreviews-even-row' : 'pendingreviews-odd-row';
			$classAndAttr = "class='pendingreviews-row $rowClass pendingreviews-row-$rowCount' pendingreviews-row-count='$rowCount'";

			$html .= "<tr $classAndAttr><td class='pendingreviews-page-title pendingreviews-top-cell'>$displayTitle</td><td class='pendingreviews-review-links pendingreviews-bottom-cell pendingreviews-top-cell'>$diffLink $histLink</td></tr>";
			$html .= "<tr $classAndAttr><td colspan='2' class='pendingreviews-bottom-cell'>$changes</td></tr>";
		
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
	
	public function getPageHeader() {

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
			return wfMessage( $messages[ $logEntry->log_type ][ $logEntry->log_action ], $userPage );
		}
		else {
			return wfMessage( 'pendingreviews-log-unknown-change', $userPage );
		}

	}

}

