<?php

class SpecialPageStatistics extends SpecialPage {

	public $mMode;
	// protected $header_links = array(
	// 	'watchanalytics-pages-specialpage' => '',
	// 	'watchanalytics-users-specialpage' => 'users',
	// 	'watchanalytics-wikihistory-specialpage'  => 'wikihistory',
	// 	'watchanalytics-watch-forcegraph-specialpage' => 'forcegraph',
	// );


	public function __construct() {
		parent::__construct( 
			"PageStatistics", // 
			"",  // rights required to view
			true // show in Special:SpecialPages
		);
	}
	
	function execute( $parser = null ) {
		global $wgRequest, $wgOut, $wgUser;

		$this->setHeaders();
		$wgOut->addModuleStyles( 'ext.watchanalytics.specials' ); // @todo FIXME: check if this is necessary

		$requestedPage = $wgRequest->getVal( 'page', '' );

		$this->mTitle = Title::newFromText( $requestedPage );

		// @todo: probably don't need filters, but may want to show stats just
		// from a certain group of users
		// $filters = array(
		// 	'groupfilter'    => $wgRequest->getVal( 'groupfilter', '' ),
		// 	'categoryfilter' => $wgRequest->getVal( 'categoryfilter', '' ),
		// );
		// foreach( $filters as &$filter ) {
		// 	if ( $filter === '' ) {
		// 		$filter = false;
		// 	}
		// }
		
		// @todo: delete if multiple views not needed (thus, not requiring header call here)
		if ( $this->mTitle && $this->mTitle->isKnown() && $this->mTitle->isWatchable() ) {

			$unReviewTimestamp = $wgRequest->getVal( 'unreview' );
			if ( $unReviewTimestamp ) {
				$rh = new ReviewHandler( $wgUser, $this->mTitle );
				$rh->resetNotificationTimestamp( $unReviewTimestamp );
				$wgOut->addModuleStyles( array( 'ext.watchanalytics.reviewhandler' ) );
				$wgOut->addHTML( $this->unReviewMessage() );
			}


			$wgOut->addHTML( $this->getPageHeader() );
			$this->renderPageStats();
		}
		else if ( $requestedPage ) {
			// @todo FIXME: internationalize
			$wgOut->addHTML( "<p>\"$requestedPage\" is either not a page or is not watchable</p>" );
		}
		else {
			$wgOut->addHTML( "<p>No page requested</p>" );			
		}

	}
	
	public function getPageHeader() {
		global $wgOut;
		$wgOut->addModuleStyles( array( 'ext.watchanalytics.pagescores' ) );


		$pageScore = new PageScore( $this->mTitle );
		// $out->addScript( $pageScore->getPageScoreTemplate() );

		$scrutinyBadge = 
			"<div id='ext-watchanalytics-pagescores' style='float:left; opacity:1.0; margin-right: 10px;'>"
				. $pageScore->getScrutinyBadge( true )
			. "</div>";

		$reviewsBadge = 
			"<div id='ext-watchanalytics-pagescores' style='float:left; opacity:1.0; margin-right: 10px;'>"
				. $pageScore->getReviewsBadge( true )
			. "</div>";

		$pageLink = Linker::link( $this->mTitle, $this->mTitle->getPrefixedText() );

		// @todo FIXME: This should have the single-input form to look up pages, maybe.
		// for now it's just an explanation of what should be here.
		// @todo FIXME: hard coded width of the badge column is lame
		return "<h2>Scores</h2>
			<p>The following are page scores and explanations for $pageLink</p>

			<table>
			<tr>
				<td style='width:120px;'>$scrutinyBadge</td>
				<td>This is a function of how many people are
				watching this page and how good those people are at reviewing pages in general. A higher number
				means that the page is likely to be reviewed quickly and by a greater number of
				people.</td>
			</tr>
			<tr>
				<td>$reviewsBadge</td>
				<td>The number of people who have reviewed this page.</td>
			</tr>
			</table>";

	}
	
	public function renderPageStats () {

		global $wgOut;

		// @todo FIXME: internationalization
		$wgOut->setPageTitle( 'Page Statistics: ' . $this->mTitle->getPrefixedText() );

		$dbr = wfGetDB( DB_SLAVE );
		$html = '';
		// Load the module for the D3.js force directed graph
		//$wgOut->addModules( array( 'ext.watchanalytics.forcegraph' ) );
		// Load the styles for the D3.js force directed graph
		//$wgOut->addModuleStyles( 'ext.watchanalytics.forcegraph' );


		// SELECT
		// 	rev.rev_user,
		// 	rev.rev_user_text,
		// 	COUNT( * ) AS num_revisions
		// FROM revision AS rev
		// LEFT JOIN page AS p ON p.page_id = rev.rev_page
		// WHERE p.page_title = "US_EVA_29_(US_EVA_IDA1_Cables)" AND p.page_namespace = 0
		// GROUP BY rev.rev_user
		// ORDER BY num_revisions DESC

		#
		#	Page editors query
		#
		$res = $dbr->select(
			array(
				'rev' => 'revision',
				'p' => 'page',
			),
			array(
				'rev.rev_user',
				'rev.rev_user_text',
				'COUNT( * ) AS num_revisions',				
			),
			array(
				'p.page_title' => $this->mTitle->getDBkey(),
				'p.page_namespace' => $this->mTitle->getNamespace(),
			),
			__METHOD__,
			array(
				'GROUP BY' => 'rev.rev_user',
				'ORDER BY' => 'num_revisions DESC',
			),
			array(
				'p' => array(
					'LEFT JOIN', 'p.page_id = rev.rev_page'
				),
			)
		);



		#
		#	Page editors
		#
		$html .= Xml::element( 'h2', null, wfMessage( 'watchanalytics-pagestats-editors-list-title' )->text() );
		$html .= Xml::openElement( "ul" );
		while ( $row = $res->fetchObject() ) {
			// $editor = User::newFromId( $row->rev_user )
			// $realName = $editor->getRealName();

			$html .= 
				Xml::openElement( 'li' )
				. wfMessage(
					'watchanalytics-pagestats-editors-list-item',
					Linker::userLink( $row->rev_user, $row->rev_user_text ),
					$row->num_revisions
				)->text() 
				. Xml::closeElement( 'li' );

		}
		$html .= Xml::closeElement( "ul" );


		#
		#	Watchers query
		#
		$res = $dbr->select(
			array(
				'w' => 'watchlist',
				'u' => 'user',
			),
			array(
				'wl_user',
				'u.user_name',
				'wl_notificationtimestamp',
			),
			array(
				'wl_title' => $this->mTitle->getDBkey(),
				'wl_namespace' => $this->mTitle->getNamespace(),
			),
			__METHOD__,
			null, // no limits, order by, etc
			array(
				'u' => array(
					'LEFT JOIN', 'u.user_id = w.wl_user'
				),
			)
		);



		#
		#	Page watchers
		#
		$html .= Xml::element( 'h2', null, wfMessage( 'watchanalytics-pagestats-watchers-title' )->text() );
		$html .= Xml::openElement( "ul" );
		while ( $row = $res->fetchObject() ) {
			// $editor = User::newFromId( $row->rev_user )
			// $realName = $editor->getRealName();

			if ( is_null( $row->wl_notificationtimestamp ) ) {
				$watcherMsg = 'watchanalytics-pagestats-watchers-list-item-reviewed';
			}
			else {
				$watcherMsg = 'watchanalytics-pagestats-watchers-list-item-unreviewed';
			}

			$html .= 
				Xml::openElement( 'li' )
				. wfMessage(
					$watcherMsg,
					Linker::userLink( $row->wl_user, $row->user_name )
				)->text() 
				. Xml::closeElement( 'li' );

		}
		$html .= Xml::closeElement( "ul" );



		$wgOut->addHTML( $html );

		$this->pageChart();

	}

	public function unReviewMessage () {

		// FIXME: this shouldn't use the same CSS ID.
		return 
			"<div id='watch-analytics-review-handler'>
				<p>This page has been un-reviewed.</p>
			</div>";

	}



	public function pageChart () {

		global $wgOut;
		$wgOut->addModules( 'ext.watchanalytics.charts' );

		$html = '<h2>'. wfMessage( 'watchanalytics-pagestats-chart-header' )->text() .'</h2>';
		$html .= '<canvas id="page-reviews-chart" width="400" height="400"></canvas>';
		
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array('wtp' => 'watch_tracking_page'),
			array(
				"DATE_FORMAT( wtp.tracking_timestamp, '%Y-%m-%d %H:%i:%s' ) AS timestamp", 
				"wtp.num_reviewed AS num_reviewed",
			),
			array(
				'page_id' => $this->mTitle->getArticleID()
			),
			__METHOD__,
			array(
				"ORDER BY" => "wtp.tracking_timestamp DESC",
				"LIMIT" => "1000", // MOST RECENT 1000 changes
			),
			null // join conditions
		);

		$data = array();
		while( $row = $dbr->fetchObject( $res ) ) {
			$data[ $row->timestamp ] = $row->num_reviewed;
		}
		$data = array_reverse( $data );

		$html .= "<script type='text/template-json' id='ext-watchanalytics-page-stats-data'>" . json_encode( $data ) . "</script>";
		$wgOut->addHTML( $html );

	}
}

