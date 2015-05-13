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
		global $wgRequest, $wgOut;

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
		$wgOut->addHTML( $this->getPageHeader() );
		if ( $this->mTitle && $this->mTitle->isKnown() && $this->mTitle->isWatchable() ) {
			$this->renderPageStats();
		}
		else if ( $requestedPage ) {
			// @todo FIXME: internationalize
			$wgOut->addHTML( "<p>\"$requestedPage\" is either not a page or is not watchable</p>" );
		}

	}
	
	public function getPageHeader() {

		// @todo FIXME: This should have the single-input form to look up pages, maybe.
		// for now it's just an explanation of what should be here.
		return "<p>Welcome to page statistics. This page is under construction. In the future 
			it will show how well a page is being watched, reviewed, and how much traffic it
			is getting. For now, just some definitions to explain the colored \"badges\" on
			each page:</p>

			<ul>
				<li><strong>Scrutiny:</strong> This is a function of how many people are
				watching a page and how good those people are at reviewing pages.</li>
				<li><strong>Reviews:</strong> This is simply the number of people who have
				reviewed a page.</li>
			</ul>";

	}
	
	public function renderPageStats () {

		global $wgOut;

		// @todo FIXME: internationalization
		$wgOut->setPageTitle( 'Page Statistics: ' . $this->mTitle->getPrefixedText() );

		$dbr = wfGetDB( DB_SLAVE );

		// Load the module for the D3.js force directed graph
		//$wgOut->addModules( array( 'ext.watchanalytics.forcegraph' ) );
		// Load the styles for the D3.js force directed graph
		//$wgOut->addModuleStyles( 'ext.watchanalytics.forcegraph' );


		// $res = $dbr->select(
		// 	array(
		// 		'w' => 'watchlist',
		// 		'u' => 'user',
		// 		'p' => 'page',
		// 	),
		// 	array(
		// 		'w.wl_title AS title',
		// 		'w.wl_notificationtimestamp as notification',
		// 		'u.user_name as user_name',
		// 		'u.user_real_name AS real_name',
		// 	),
		// 	'w.wl_namespace = 0 AND p.page_is_redirect = 0',
		// 	__METHOD__,
		// 	array(
		// 		"LIMIT" => "100000",
		// 	),
		// 	array(
		// 		'u' => array(
		// 			'LEFT JOIN', 'u.user_id = w.wl_user'
		// 		),
		// 		'p' => array(
		// 			'RIGHT JOIN', 'w.wl_title = p.page_title AND w.wl_namespace = p.page_namespace'
		// 		),
		// 	)
		// );


		// $html = '<h3>' . wfMessage('watchanalytics-watch-forcegraph-header')->text() . '</h3>';
		// $html .= '<p>' . wfMessage('watchanalytics-watch-forcegraph-description')->text() . '</p>';
		// $html .= '<div id="mw-ext-watchAnalytics-forceGraph-container"></div>';
		// // $html .= "<pre>$json</pre>"; // easy testing
		// $html .= "<script type='text/template' id='mw-ext-watchAnalytics-forceGraph'>$json</script>";
		
		$pageTitle = $this->mTitle->getPrefixedText();
		$html = "<p>While the page \"$pageTitle\" does exist, this Special Page is not yet operational.</p>";
		$wgOut->addHTML( $html );

	}
}

