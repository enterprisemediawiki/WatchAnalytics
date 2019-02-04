<?php

class SpecialPageStatistics extends SpecialPage {

	public $mMode;

	public function __construct() {
		parent::__construct(
			"PageStatistics", //
			"",  // rights required to view
			true // show in Special:SpecialPages
		);
	}

	public function execute( $parser = null ) {
		global $wgRequest, $wgOut, $wgUser;

		$this->setHeaders();
		$wgOut->addModuleStyles( 'ext.watchanalytics.specials' ); // @todo FIXME: check if this is necessary

		$requestedPage = $wgRequest->getVal( 'page', '' );

		$this->mTitle = Title::newFromText( $requestedPage );

		// @todo: probably don't need filters, but may want to show stats just
		// from a certain group of users
		// $filters = array(
		// 'groupfilter'    => $wgRequest->getVal( 'groupfilter', '' ),
		// 'categoryfilter' => $wgRequest->getVal( 'categoryfilter', '' ),
		// );
		// foreach( $filters as &$filter ) {
		// if ( $filter === '' ) {
		// $filter = false;
		// }
		// }

		// @todo: delete if multiple views not needed (thus, not requiring header call here)
		if ( $this->mTitle && $this->mTitle->isKnown() && $this->mTitle->isWatchable() ) {

			$unReviewTimestamp = $wgRequest->getVal( 'unreview' );
			if ( $unReviewTimestamp ) {
				$rh = new ReviewHandler( $wgUser, $this->mTitle );
				$rh->resetNotificationTimestamp( $unReviewTimestamp );
				$wgOut->addModuleStyles( [ 'ext.watchanalytics.reviewhandler.styles' ] );
				$wgOut->addHTML( $this->unReviewMessage() );
			}

			$wgOut->addHTML( $this->getPageHeader() );
			$this->renderPageStats();
		} elseif ( $requestedPage ) {
			// @todo FIXME: internationalize
			$wgOut->addHTML( "<p>\"$requestedPage\" is either not a page or is not watchable</p>" );
		} else {
			$wgOut->addHTML( "<p>No page requested</p>" );
		}
	}

	public function getPageHeader() {
		global $wgOut;
		$wgOut->addModuleStyles( [ 'ext.watchanalytics.pagescores.styles' ] );

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
			<p>The following are page scores and explanations for <strong>$pageLink</strong></p>

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

	public function renderPageStats() {
		global $wgOut;

		// @todo FIXME: internationalization
		$wgOut->setPageTitle( 'Page Statistics: ' . $this->mTitle->getPrefixedText() );

		$dbr = wfGetDB( DB_REPLICA );
		$html = '';
		// Load the module for the D3.js force directed graph
		// $wgOut->addModules( 'ext.watchanalytics.forcegraph.scripts' );
		// Load the styles for the D3.js force directed graph
		// $wgOut->addModuleStyles( 'ext.watchanalytics.forcegraph.styles' );

		// SELECT
		// rev.rev_user,
		// rev.rev_user_text,
		// COUNT( * ) AS num_revisions
		// FROM revision AS rev
		// LEFT JOIN page AS p ON p.page_id = rev.rev_page
		// WHERE p.page_title = "US_EVA_29_(US_EVA_IDA1_Cables)" AND p.page_namespace = 0
		// GROUP BY rev.rev_user
		// ORDER BY num_revisions DESC

		#
		# Page editors query
		#
		$res = $dbr->select(
			[
				'rev' => 'revision',
				'p' => 'page',
			],
			[
				'rev.rev_user',
				'rev.rev_user_text',
				'COUNT( * ) AS num_revisions',
			],
			[
				'p.page_title' => $this->mTitle->getDBkey(),
				'p.page_namespace' => $this->mTitle->getNamespace(),
			],
			__METHOD__,
			[
				'GROUP BY' => 'rev.rev_user',
				'ORDER BY' => 'num_revisions DESC',
			],
			[
				'p' => [
					'LEFT JOIN', 'p.page_id = rev.rev_page'
				],
			]
		);

		#
		# Page editors
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
		# Watchers query
		#
		$res = $dbr->select(
			[
				'w' => 'watchlist',
				'u' => 'user',
			],
			[
				'wl_user',
				'u.user_name',
				'wl_notificationtimestamp',
			],
			[
				'wl_title' => $this->mTitle->getDBkey(),
				'wl_namespace' => $this->mTitle->getNamespace(),
			],
			__METHOD__,
			null, // no limits, order by, etc
			[
				'u' => [
					'LEFT JOIN', 'u.user_id = w.wl_user'
				],
			]
		);

		#
		# Page watchers
		#
		$html .= Xml::element( 'h2', null, wfMessage( 'watchanalytics-pagestats-watchers-title' )->text() );
		$html .= Xml::openElement( "ul" );
		while ( $row = $res->fetchObject() ) {
			// $editor = User::newFromId( $row->rev_user )
			// $realName = $editor->getRealName();

			if ( is_null( $row->wl_notificationtimestamp ) ) {
				$watcherMsg = 'watchanalytics-pagestats-watchers-list-item-reviewed';
			} else {
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

	public function unReviewMessage() {
		// FIXME: Original self: this shouldn't use the same CSS ID.
		// Newer self: Why not?
		return "<div id='watch-analytics-review-handler'><p>" .
				wfMessage( 'watchanalytics-unreview-complete' )->parse() .
			"</p></div>";
	}

	public function pageChart() {
		global $wgOut;
		$wgOut->addModules( 'ext.watchanalytics.charts' );

		$html = '<h2>' . wfMessage( 'watchanalytics-pagestats-chart-header' )->text() . '</h2>';
		$html .= '<canvas id="page-reviews-chart" width="400" height="400"></canvas>';

		// $dateRangeStart = new MWTimestamp( date( 'YmdHis', strtotime( '2 weeks ago' ) ) );
		// $dateRangeStart = $dateRangeStart->format('YmdHis');

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'wtp' => 'watch_tracking_page' ],
			[
				"DATE_FORMAT( wtp.tracking_timestamp, '%Y-%m-%d %H:%i:%s' ) AS timestamp",
				"wtp.num_reviewed AS num_reviewed",
			],
			[
				'page_id' => $this->mTitle->getArticleID(),
				// 'tracking_timestamp > ' . $dateRangeStart
			],
			__METHOD__,
			[
				"ORDER BY" => "wtp.tracking_timestamp DESC",
				"LIMIT" => "200", // MOST RECENT 100 changes
			],
			null // join conditions
		);

		$data = [];
		while ( $row = $dbr->fetchObject( $res ) ) {
			$data[ $row->timestamp ] = $row->num_reviewed;
		}

		// data queried in reverse order in order to use LIMIT
		$data = array_reverse( $data );

		$html .= "<script type='text/template-json' id='ext-watchanalytics-page-stats-data'>" . json_encode( $data ) . "</script>";
		$wgOut->addHTML( $html );
	}
}
