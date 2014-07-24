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

		$pending = $userWatchQuery->getUserPendingWatches( $this->mUser );

		// $html = '<pre>' . json_encode( $pending, JSON_PRETTY_PRINT ) . '</pre>';
		// $html = '<ul>';
		$html = '<table class="pendingreviews-list">';
		$rowCount = 0;
		foreach ( $pending as $item ) {
			// logic for:
			//   * isRedirect
			//   * isDeleted
			//   * isNewPage
			//   * files, approvals ... other log actions?

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

				$diffURL= $item->title->getLocalURL( array(
					'diff' => '', 
					'oldid' => $mostRecentReviewed->getId()
				) );

				$diffLink = Xml::element( 'a',
					array( 'href' => $diffURL ),
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
				array( 'href' => $item->title->getLocalURL( array( 'action' => 'history' ) ) ),
				wfMessage( 'watchanalytics-pendingreviews-history-link' )->text()
			);

			$displayTitle = '<strong>' . $item->title->getFullText() . '</strong> <small>' . $timeDiff . '</small>';



			// $revisions = count($item->newRevisions) . ' revisions';
			$logActions = count($item->log) . ' log actions';

			// $html .= "<li>$displayTime : $displayTitle - $diffLink  ($logActions)</li>";
			

			// FIXME: wow this is ugly
			$rowClass = ( $rowCount % 2 === 0 ) ? 'pendingreviews-even-row' : 'pendingreviews-odd-row';
			$classAndAttr = "class='pendingreviews-row $rowClass pendingreviews-row-$rowCount' pendingreviews-row-count='$rowCount'";

			$html .= "<tr $classAndAttr><td class='pendingreviews-page-title'>$displayTitle</td><td rowspan='2' class='pendingreviews-review-links pendingreviews-bottom-cell'>$diffLink $histLink</td></tr>";
			$html .= "<tr $classAndAttr><td class='pendingreviews-bottom-cell'>... list of stuff here</td></tr>";
		
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

}

