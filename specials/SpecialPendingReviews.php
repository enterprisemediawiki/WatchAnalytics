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
		global $wgRequest, $wgOut;

		$this->setHeaders();

		// // Load the module for the Notifications flyout
		// $wgOut->addModules( array( 'ext.watchanalytics.forcegraph' ) );
		// // Load the styles for the Notifications badge
		// $wgOut->addModuleStyles( 'ext.watchanalytics.forcegraph' );


		// $userTarget = isset( $parser ) ? $parser : $wgRequest->getVal( 'username' );
		// $this->mMode = $wgRequest->getVal( 'show' );
		//$fileactions = array('actions...?');

		$w = new WatchStateRecorder();
		if ( ! $w->recordedWithinHours( 1 ) ) {
			$w->recordAll();
			$wgOut->addHTML( '<p>' . wfMessage('watchanalytics-all-wiki-stats-recorded')->text() . '</p>' );
		}
		
		$wgOut->addHTML( $this->getPageHeader() );
		if ($this->mMode == 'users') {
			$this->usersList();
		}
		else if ($this->mMode == 'wikihistory') {
			$this->wikiHistory();
		}
		else {
			$this->pagesList();
		}
	}
	
	public function getPageHeader() {

		// show the names of the four lists of pages, with the one
		// corresponding to the current "mode" not being linked		

		// SELECT
		// 	COUNT(*) AS watches,
		// 	SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) AS num_pending,
		// 	SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending
		// FROM watchlist
		// INNER JOIN page ON page.page_namespace = watchlist.wl_namespace AND page.page_title = watchlist.wl_title;		$dbr = wfGetDB( DB_SLAVE );

		$dbr = wfGetDB( DB_SLAVE );

		// $res = $dbr->select(
		// 	array(
		// 		'w' => 'watchlist',
		// 		'p' => 'page',
		// 	),
		// 	array(
		// 		"COUNT(*) AS watches", 
		// 		"SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) AS num_pending",
		// 		"SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending",
		// 	),
		// 	null, // conditions
		// 	__METHOD__,
		// 	array(), // options
		// 	array(
		// 		'page' => array(
		// 			'INNER JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
		// 		)
		// 	)
		// );

		$res = $dbr->query('
			SELECT
				COUNT(*) AS num_watches,
				SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) AS num_pending,
				SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending
			FROM watchlist
			INNER JOIN page ON page.page_namespace = watchlist.wl_namespace AND page.page_title = watchlist.wl_title;
		');

		$allWikiData = $dbr->fetchRow( $res );

		list($watches, $pending, $percent) = array(
			$allWikiData['num_watches'],
			$allWikiData['num_pending'],
			$allWikiData['percent_pending']
		);
		
		$percent = round($percent, 1);
		$stateOf = "<strong>The state of the Wiki: </strong>$watches watches of which $percent% ($pending) are pending";
		
		$navLinks = '';
		foreach($this->header_links as $msg => $query_param) {
			$navLinks .= '<li>' . $this->createHeaderLink($msg, $query_param) . '</li>';
		}

		$header = wfMessage( 'watchanalytics-view' )->text() . ' ';
		$header .= Xml::tags( 'ul', null, $navLinks ) . "\n";

		return $stateOf . Xml::tags('div', array('class'=>'special-watchanalytics-header'), $header);

	}

	function createHeaderLink($msg, $query_param) {
	
		$WatchAnalyticsTitle = SpecialPage::getTitleFor( $this->getName() );

		if ( $this->mMode == $query_param ) {
			return Xml::element( 'strong',
				null,
				wfMessage( $msg )->text()
			);
		} else {
			$show = ($query_param == '') ? array() : array( 'show' => $query_param );
			return Xml::element( 'a',
				array( 'href' => $WatchAnalyticsTitle->getLocalURL( $show ) ),
				wfMessage( $msg )->text()
			);
		}

	}
	
	public function pagesList () {
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle( wfMessage( 'watchanalytics-special-pages-pagetitle' )->text() );

		$pager = new WatchAnalyticsPageTablePager( $this, array() );
		
		// $form = $pager->getForm();
		$body = $pager->getBody();
		$html = '';
		// $html = $form;
		if ( $body ) {
			$html .= $pager->getNavigationBar();
			$html .= $body;
			$html .= $pager->getNavigationBar();
		} 
		else {
			$html .= '<p>' . wfMsgHTML('listusers-noresult') . '</p>';
		}
		$wgOut->addHTML( $html );
	}

	public function usersList () {
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle( wfMessage( 'watchanalytics-special-users-pagetitle' )->text() );

		$pager = new WatchAnalyticsUserTablePager( $this, array() );
		
		// $form = $pager->getForm();
		$body = $pager->getBody();
		$html = '';
		// $html = $form;
		if ( $body ) {
			$html .= $pager->getNavigationBar();
			$html .= $body;
			$html .= $pager->getNavigationBar();
		} 
		else {
			$html .= '<p>' . wfMsgHTML('listusers-noresult') . '</p>';
		}
		$wgOut->addHTML( $html );
	}
	
	public function wikiHistory () {
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle( wfMessage( 'watchanalytics-special-wikihistory-pagetitle' )->text() );

		$pager = new WatchAnalyticsWikiTablePager( $this, array() );
		
		// $form = $pager->getForm();
		$body = $pager->getBody();
		$html = '';
		// $html = $form;
		if ( $body ) {
			$html .= $pager->getNavigationBar();
			$html .= $body;
			$html .= $pager->getNavigationBar();
		} 
		else {
			$html .= '<p>' . wfMsgHTML('listusers-noresult') . '</p>';
		}
		$wgOut->addHTML( $html );

	}
	
	public function totals () {
		#THIS WAS FROM WIRETAP but WatchAnalytics may use something similar

		// global $wgOut;

		// $wgOut->setPageTitle( 'Wiretap: Daily Totals' );

		// $html = '<table class="wikitable"><tr><th>Date</th><th>Hits</th></tr>';
		// // $html = $form;
		// // if ( $body ) {
		
		// // } 
		// // else {
		// 	// $html .= '<p>' . wfMsgHTML('listusers-noresult') . '</p>';
		// // }
		// // SELECT wiretap.hit_year, wiretap.hit_month, wiretap.hit_day, count(*) AS num_hits
		// // FROM wiretap
		// // WHERE wiretap.hit_timestamp>20131001000000 
		// // GROUP BY wiretap.hit_year, wiretap.hit_month, wiretap.hit_day
		// // ORDER BY wiretap.hit_year DESC, wiretap.hit_month DESC, wiretap.hit_day DESC
		// // LIMIT 100000;
		// $dbr = wfGetDB( DB_SLAVE );

		// $res = $dbr->select(
		// 	array('w' => 'wiretap'),
		// 	array(
		// 		"w.hit_year AS year", 
		// 		"w.hit_month AS month",
		// 		"w.hit_day AS day",
		// 		"count(*) AS num_hits",
		// 	),
		// 	null, // CONDITIONS? 'wiretap.hit_timestamp>20131001000000',
		// 	__METHOD__,
		// 	array(
		// 		"DISTINCT",
		// 		"GROUP BY" => "w.hit_year, w.hit_month, w.hit_day",
		// 		"ORDER BY" => "w.hit_year DESC, w.hit_month DESC, w.hit_day DESC",
		// 		"LIMIT" => "100000",
		// 	),
		// 	null // join conditions
		// );
		// while( $row = $dbr->fetchRow( $res ) ) {
		
		// 	list($year, $month, $day, $hits) = array($row['year'], $row['month'], $row['day'], $row['num_hits']);
		// 	$html .= "<tr><td>$year-$month-$day</td><td>$hits</td></tr>";
		
		// }
		// $html .= "</table>";
		
		// $wgOut->addHTML( $html );

	}
}

