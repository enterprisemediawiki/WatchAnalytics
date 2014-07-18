<?php

class SpecialWatchStrength extends SpecialPage {

	public $mMode;
	protected $header_links = array(
		'watchstrength-pages-specialpage' => '',
		'watchstrength-users-specialpage' => 'users',
		'watchstrength-wiki-specialpage'  => 'wiki',
	);


	public function __construct() {
		parent::__construct( 
			"Watchstrength", // 
			"",  // rights required to view
			true // show in Special:SpecialPages
		);
	}
	
	function execute( $parser = null ) {
		global $wgRequest, $wgOut;

		list( $limit, $offset ) = wfCheckLimits();

		// $userTarget = isset( $parser ) ? $parser : $wgRequest->getVal( 'username' );
		$this->mMode = $wgRequest->getVal( 'show' );
		//$fileactions = array('actions...?');

		$wgOut->addHTML( $this->getPageHeader() );
		
		if ($this->mMode == 'dailytotals')
			$this->totals();
		else
			$this->usersList();
			
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
				COUNT(*) AS watches,
				SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) AS num_pending,
				SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending
			FROM watchlist
			INNER JOIN page ON page.page_namespace = watchlist.wl_namespace AND page.page_title = watchlist.wl_title;
		');

		$allWikiData = $dbr->fetchRow( $res );

		list($watches, $pending, $percent) = array(
			$allWikiData['watches'],
			$allWikiData['num_pending'],
			$allWikiData['percent_pending']
		);
		
		$percent = round($percent, 1);
		return "<strong>The state of the Wiki: </strong>$watches watches of which $percent% ($pending) are pending";
		

		##
		#
		#	COMMENTED OUT WHILE ONLY USER PAGE EXISTS
		#
		##
		// $navLinks = '';
		// foreach($this->header_links as $msg => $query_param) {
		// 	$navLinks .= '<li>' . $this->createHeaderLink($msg, $query_param) . '</li>';
		// }

		// $header = wfMessage( 'watchstrength-view' )->text() . ' ';
		// $header .= Xml::tags( 'ul', null, $navLinks ) . "\n";

		// return Xml::tags('div', array('class'=>'special-watchstrength-header'), $header);

	}

	function createHeaderLink($msg, $query_param) {
	
		$watchStrengthTitle = SpecialPage::getTitleFor( $this->getName() );

		if ( $this->mMode == $query_param ) {
			return Xml::element( 'strong',
				null,
				wfMessage( $msg )->text()
			);
		} else {
			$show = ($query_param == '') ? array() : array( 'show' => $query_param );
			return Xml::element( 'a',
				array( 'href' => $watchStrengthTitle->getLocalURL( $show ) ),
				wfMessage( $msg )->text()
			);
		}

	}
	
	public function usersList () {
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle( wfMessage( 'watchstrength-special-users-pagetitle' )->text() );

		$pager = new WatchStrengthPager($this, array());
		// $pager->filterUser = $wgRequest->getVal( 'filterUser' );
		// $pager->filterPage = $wgRequest->getVal( 'filterPage' );
		
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
		#THIS WAS FROM WIRETAP but watchstrength may use something similar

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

class WatchStrengthPager extends TablePager {
	protected $rowCount = 0;
	public $filterUser;
	public $filterPage;
	
	function __construct( $page, $conds ) {
		$this->page = $page;
		$this->conds = $conds;
		$this->mDefaultDirection = true;
		parent::__construct( $page->getContext() );
	}

	// function __construct() {
	// 	parent::__construct();
	// 	// global $wgRequest;
	// 	// $this->filterUsers = $wgRequest->getVal( 'filterusers' );
	// 	// $this->filterUserList = explode("|", $this->filterUsers);
	// 	// $this->ignoreUsers = $wgRequest->getVal( 'ignoreusers' );
	// 	// $this->ignoreUserList = explode("|", $this->ignoreUsers);
	// }

	function getIndexField() {

		global $wgRequest;

		$sortField = $wgRequest->getVal( 'sort' );
		if ( isset( $sortField ) && $this->isFieldSortable( $sortField ) ) {
			return $sortField;
		}
		else {
			return $this->getDefaultSort();
		}

	}
	
	// function getExtraSortFields() {
	// 	return array();
	// }

	function isNavigationBarShown() {
		return true;
	}
	

	/**
		SELECT
			user.user_name AS user_name,
			user.user_real_name AS real_name,
			COUNT(*) AS watches,
			SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) AS num_pending,
			SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending,
			SUM( TIMESTAMPDIFF(MINUTE, wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS total_pending_minutes,
			AVG( TIMESTAMPDIFF(MINUTE, wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS average_pending_minutes
		FROM watchlist
		LEFT JOIN user ON user.user_id = watchlist.wl_user
		INNER JOIN page ON page.page_namespace = watchlist.wl_namespace AND page.page_title = watchlist.wl_title
		GROUP BY watchlist.wl_user
		ORDER BY average_pending_minutes DESC
	**/
	function getQueryInfo() {
		$tables = array(
			'w' => 'watchlist',
			'u' => 'user',
			'p' => 'page',
		);

		$fields = array(
			'u.user_name AS user_name',
			'COUNT(*) AS watches',
			'SUM( IF(w.wl_notificationtimestamp IS NULL, 0, 1) ) AS num_pending',
			'SUM( IF(w.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending',
			'SUM( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS total_pending_minutes',
			'AVG( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS average_pending_minutes',
		);

		$join_conds = array(
			'u' => array(
				'LEFT JOIN', 'u.user_id=w.wl_user'
			),
			'p' => array(
				'INNER JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
			),
		);


		$order = 'average_pending_minutes';
		$direction = 'DESC';

		$options = array(
			'GROUP BY' => 'w.wl_user'
		);


		// if ( isset($order) ) {
		// 	$options['ORDER BY'] = $order;
		// 	if ( isset($direction) ) {
		// 		$options['ORDER BY'] .= ' ' . $direction;
		// 	}
		// }

		$conds = array();

		$return = array(
			'tables' => $tables,
			'fields' => $fields,
			'join_conds' => $join_conds,
			'conds' => $conds,
			'options' => $options,
		);
		
		return $return;

	}


	// user_name
	// watches
	// num_pending
	// percent_pending
	// total_pending_minutes
	// average_pending_minutes
	// function formatRow( $row ) {
	// 	$userPage = Title::makeTitle( NS_USER, $row->user_name );
	// 	$name = $this->getSkin()->makeLinkObj( $userPage, htmlspecialchars( $userPage->getText() ) );
		

	// 	$url = Title::newFromText('Special:WatchStrength')->getLocalUrl(
	// 		array( 'user' => $row->user_name )
	// 	);
	// 	$msg = wfMsg( 'watchstrength-view-user-stats' );
		
	// 	$name .= ' (' . Xml::element(
	// 		'a',
	// 		array( 'href' => $url ),
	// 		$msg
	// 	) . ')';

	// 	$watches = $row->watches;
	// 	$pending = $row->num_pending;
	// 	$percentPending = $row->percent_pending;
	// 	$totalMinutesPending = $row->total_pending_minutes;
	// 	$averageMinutesPending = $row->average_pending_minutes;

	// 	return "<tr>
	// 			<td>$name</td>
	// 			<td>$watches</td>
	// 			<td>$pending</td>
	// 			<td>$percentPending</td>
	// 			<td>$totalMinutesPending</td>
	// 			<td>$averageMinutesPending</td>
	// 		</tr>\n";
	// }

	// function getForm() {
	// 	$out = '<form name="filteruser" id="filteruser" method="post">';
	// 	$out .='Usernames: <input type="text" name="filterusers" value="' . $this->filterUsers . '">';
	// 	$out .='<input type="submit" value="Filter">';
	// 	$out .='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	// 	$out .='Usernames: <input type="text" name="ignoreusers" value="' . $this->ignoreUsers . '">';
	// 	$out .='<input type="submit" value="Exclude">';
	// 	$out .='</form><br /><hr /><br />';
	// 	return $out;
	// }

	/**
	 * Preserve filter offset parameters when paging
	 * @return array
	 */
	// function getDefaultQuery() {
	// 	$query = parent::getDefaultQuery();
	// 	// if( $this->filterUsers != '' )
	// 		// $query['filterusers'] = $this->filterUsers;
	// 	// if( $this->ignoreUsers != '' )
	// 		// $query['ignoreusers'] = $this->ignoreUsers;
	// 	return $query;
	// }

	function isFieldSortable ( $field ) {
		$isSortable = array(
			'user_name' => true,
			'watches' => true,
			'num_pending' => true,
			'percent_pending' => true,
			'total_pending_minutes' => true,
			'average_pending_minutes' => true,
		);

		return $isSortable[ $field ];
	}

	function formatValue ( $fieldName , $value ) {

		if ( $fieldName === 'user_name' ) {

			$user_name = $value;

			$userPage = Title::makeTitle( NS_USER, $user_name );
			$name = $this->getSkin()->makeLinkObj( $userPage, htmlspecialchars( $userPage->getText() ) );
			

			$url = Title::newFromText('Special:WatchStrength')->getLocalUrl(
				array( 'user' => $user_name )
			);
			$msg = wfMsg( 'watchstrength-view-user-stats' );
			
			$name .= ' (' . Xml::element(
				'a',
				array( 'href' => $url ),
				$msg
			) . ')';
			
			return $name;
		}
		else if ( $fieldName === 'total_pending_minutes' || $fieldName === 'average_pending_minutes' ) {
			return ($value === NULL) ? NULL : $this->createTimeStringFromMinutes( $value );
		}
		else {
			return $value;
		}

	}

	protected function createTimeStringFromMinutes ( $totalMinutes ) {
		
		$remainder = $totalMinutes;

		$minutesInDay = 60 * 24;
		$minutesInHour = 60;

		$days = floor( $remainder / $minutesInDay );
		$remainder = $remainder % $minutesInDay;

		$hours = floor( $remainder / $minutesInHour );
		$remainder = $remainder % $minutesInHour;

		$minutes = $remainder;



		$time = array();
		if ( $days ) {
			$time[] = $days . ' day' . (($days > 1) ? 's' : ''); 
		}
		if ( $hours ) {
			$time[] = $hours . ' hour' . (($hours > 1) ? 's' : ''); 
		}
		if ( $minutes ) {
			$time[] = $minutes . ' minute' . (($minutes > 1) ? 's' : ''); 
		}


		return implode(', ', $time);
	}

	function getFieldNames() {
		$headers = array(
			'user_name'               => 'watchstrength-special-header-user',
			'watches'                 => 'watchstrength-special-header-watches',
			'num_pending'             => 'watchstrength-special-header-pending-watches',
			'percent_pending'         => 'watchstrength-special-header-pending-percent',
			'total_pending_minutes'   => 'watchstrength-special-header-pending-totaltime',
			'average_pending_minutes' => 'watchstrength-special-header-pending-averagetime',
		);

		foreach ( $headers as $key => $val ) {
			$headers[$key] = $this->msg( $val )->text();
		}

		return $headers;
	}

	function getDefaultSort () {
		return 'num_pending';
	}

}
