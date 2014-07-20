<?php

class WatchStrengthUserTablePager extends WatchStrengthTablePager {
	
	// function __construct( $page, $conds ) {}
	protected $isSortable = array(
		'user_name' => true,
		'watches' => true,
		'num_pending' => true,
		'percent_pending' => true,
		'max_pending_minutes' => true,
		'average_pending_minutes' => true,
	);


	/**
		SELECT
			user.user_name AS user_name,
			user.user_real_name AS real_name,
			COUNT(*) AS watches,
			SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) AS num_pending,
			SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending,
			SUM( TIMESTAMPDIFF(MINUTE, wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS max_pending_minutes,
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
			'MAX( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS max_pending_minutes',
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

		$options = array(
			'GROUP BY' => 'w.wl_user'
		);

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
	// max_pending_minutes
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
	// 	$totalMinutesPending = $row->max_pending_minutes;
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
		else if ( $fieldName === 'max_pending_minutes' || $fieldName === 'average_pending_minutes' ) {
			return ($value === NULL) ? NULL : $this->createTimeStringFromMinutes( $value );
		}
		else {
			return $value;
		}

	}

	function getFieldNames() {
		$headers = array(
			'user_name'               => 'watchstrength-special-header-user',
			'watches'                 => 'watchstrength-special-header-watches',
			'num_pending'             => 'watchstrength-special-header-pending-watches',
			'percent_pending'         => 'watchstrength-special-header-pending-percent',
			'max_pending_minutes'   => 'watchstrength-special-header-pending-maxtime',
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
