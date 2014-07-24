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

		// load styles for watch analytics special pages
		$wgOut->addModuleStyles( 'ext.watchanalytics.specials' );

		
		$wgOut->addHTML( $this->getPageHeader() );
		
		// ...

		$userWatchQuery = new UserWatchesQuery();

		$pending = $userWatchQuery->getUserPendingWatches( $wgUser );

		// $html = '<pre>' . json_encode( $pending, JSON_PRETTY_PRINT ) . '</pre>';
		$html = '<ul>';

		foreach ( $pending as $item ) {
			// logic for:
			//   * isRedirect
			//   * isDeleted
			//   * isNewPage
			//   * files, approvals ... other log actions?

			$ts = new MWTimestamp( $item->notificationTimestamp );
			$displayTime = '<small>' . $ts->getHumanTimestamp( new MWTimestamp() ) . '</small>';
			$displayTitle = '<strong>' . $item->title->getFullText() . '</strong>';
			$revisions = count($item->newRevisions) . ' revisions';
			$logActions = count($item->log) . ' log actions';

			$html .= "<li>$displayTime : $displayTitle ($revisions, $logActions)</li>";
		}

		$html .= '</ul>';

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

