<?php

class SpecialWatchAnalytics extends SpecialPage {

	public $mMode;
	protected $header_links = array(
		'watchanalytics-pages-specialpage' => '',
		'watchanalytics-users-specialpage' => 'users',
		'watchanalytics-wikihistory-specialpage'  => 'wikihistory',
		'watchanalytics-watch-forcegraph-specialpage' => 'forcegraph',
	);


	public function __construct() {
		parent::__construct( 
			"WatchAnalytics", // 
			"",  // rights required to view
			true // show in Special:SpecialPages
		);
	}
	
	function execute( $parser = null ) {
		global $wgRequest, $wgOut;

		$this->setHeaders();
		$wgOut->addModuleStyles( 'ext.watchanalytics.specials' );

		list( $this->limit, $this->offset ) = wfCheckLimits();

		// $userTarget = isset( $parser ) ? $parser : $wgRequest->getVal( 'username' );
		$this->mMode = $wgRequest->getVal( 'show' );
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
		else if ( $this->mMode == 'wikihistory' ) {
			$this->wikiHistory();
		}
		else if ( $this->mMode == 'forcegraph' ) {
			$this->forceGraph();
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
	
	public function forceGraph () {

		global $wgOut;

		$wgOut->setPageTitle( 'Watch Analytics: User/Page Watch Relationships' );

		$dbr = wfGetDB( DB_SLAVE );

		// Load the module for the D3.js force directed graph
		$wgOut->addModules( array( 'ext.watchanalytics.forcegraph' ) );
		// Load the styles for the D3.js force directed graph
		$wgOut->addModuleStyles( 'ext.watchanalytics.forcegraph' );

		// SELECT
		// 	watchlist.wl_title AS title,
		// 	watchlist.wl_notificationtimestamp AS notification,
		// 	user.user_name AS user_name,
		// 	user.user_real_name AS real_name
		// FROM watchlist
		// LEFT JOIN user ON user.user_id = watchlist.wl_user
		// WHERE
		// 	wl_namespace = 0
		// 	AND user.user_name IN (\'Lwelsh\',\'Swray\',\'Balpert\',\'Ejmontal\',\'Cmavridi\', \'Sgeffert\', \'Smulhern\', \'Kgjohns1\', \'Bscheib\', \'Ssjohns5\')
		// LIMIT 20000

		$res = $dbr->select(
			array(
				'w' => 'watchlist',
				'u' => 'user',
			),
			array(
				'w.wl_title AS title',
				'w.wl_notificationtimestamp as notification',
				'u.user_name as user_name',
				'u.user_real_name AS real_name',
			),
			'wl_namespace = 0',
			__METHOD__,
			array(
				"LIMIT" => "100000",
			),
			array(
				'u' => array(
					'LEFT JOIN', 'u.user_id = w.wl_user'
				),
			)
		);


		$nodes = array();
		$pages = array();
		$users = array();
		$links = array();
		while ( $row = $res->fetchRow() ) {

			// if the page isn't in $pages, then it's also not in $nodes
			// add to both
			if ( ! isset( $pages[ $row['title'] ] ) ) {
				$nextNode = count($nodes);

				$pages[ $row['title'] ] = $nextNode;

				// $nodes[ $nextNode ] = $row['title'];
				$nodes[ $nextNode ] = array(
					"name" => $row['title'],
					"label" => $row['title'],
					"group" => 1
				);
			}

			// same for users...add to $users and $nodes accordingly
			if ( ! isset( $users[ $row['user_name'] ] ) ) {
				$nextNode = count($nodes);

				$users[ $row['user_name'] ] = $nextNode;

				$nodes[ $nextNode ] = $row['user_name'];
				if ( $row['real_name'] !== NULL && trim($row['real_name']) !== '' ) {
					$displayName = $row['real_name'];
				}
				else {
					$displayName = $row['user_name'];
				}

				$nodes[ $nextNode ] = array(
					"name" => $displayName,
					"label" => $displayName,
					"group" => 2,
					"weight" => 1
				);

			}
			else {
				$userNodeIndex = $users[ $row['user_name'] ];
				$nodes[ $userNodeIndex ]['weight']++;
			}

			if ( $row['notification'] == NULL ) {
				$linkClass = "link";
			}
			else {
				$linkClass = "unreviewed";
			}

			// if ( $linkClass !== "unreviewed" ) {
				$links[] = array(
					"source" => $users[ $row['user_name'] ],
					"target" => $pages[ $row['title']     ],
					"value"  => 1,
					"linkclass" => $linkClass
				);
			// }
		}

		$json = array( "nodes" => $nodes, "links" => $links );
		$json = json_encode( $json , JSON_PRETTY_PRINT );

		$html = '<h3>' . wfMessage('watchanalytics-watch-forcegraph-header')->text() . '</h3>';
		$html .= '<p>' . wfMessage('watchanalytics-watch-forcegraph-description')->text() . '</p>';
		$html .= '<div id="mw-ext-watchAnalytics-forceGraph-container"></div>';
		// $html .= "<pre>$json</pre>"; // easy testing
		$html .= "<script type='text/template' id='mw-ext-watchAnalytics-forceGraph'>$json</script>";
		$wgOut->addHTML( $html );

	}
}

