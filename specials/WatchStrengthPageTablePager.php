<?php

class WatchStrengthPageTablePager extends WatchStrengthTablePager {

	protected $isSortable = array(
		'page_ns_and_title' => true,
		'watches' => true,
		'num_reviewed' => true,
		'percent_pending' => true,
		'max_pending_minutes' => true,
		'average_pending_minutes' => true,
	);

	function __construct( $page, $conds ) {
		parent::__construct( $page , $conds );

		global $wgRequest;

		$sortField = $wgRequest->getVal( 'sort' );
		if ( ! isset( $sortField ) ) {
			$this->mDefaultDirection = false;
		}
	}

	/**
		SELECT
			watchlist.wl_namespace AS namespace,
			watchlist.wl_title AS title,
			COUNT(*) AS watches,
			SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) AS num_pending,
			SUM( IF(watchlist.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending,
			SUM( TIMESTAMPDIFF(MINUTE, wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS max_pending_minutes,
			AVG( TIMESTAMPDIFF(MINUTE, wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS average_pending_minutes
		FROM watchlist
		INNER JOIN page ON page.page_namespace = watchlist.wl_namespace AND page.page_title = watchlist.wl_title
		GROUP BY watchlist.wl_user
		ORDER BY average_pending_minutes DESC
	**/
	function getQueryInfo() {
		$tables = array(
			'w' => 'watchlist',
			'p' => 'page',
		);

		$fields = array(
			'CONCAT(p.page_namespace, ":", p.page_title) AS page_ns_and_title',
			'SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS watches',
			'SUM( IF(w.wl_title IS NOT NULL AND w.wl_notificationtimestamp IS NULL, 1, 0) ) AS num_reviewed',
			'SUM( IF(w.wl_title IS NOT NULL AND w.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending',
			'MAX( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS max_pending_minutes',
			'AVG( TIMESTAMPDIFF(MINUTE, w.wl_notificationtimestamp, UTC_TIMESTAMP()) ) AS average_pending_minutes',
		);

		$join_conds = array(
			'p' => array(
				'RIGHT JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
			),
		);

		$options = array(
			// 'GROUP BY' => 'w.wl_title, w.wl_namespace'
			'GROUP BY' => 'p.page_title, p.page_namespace'
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

	function formatValue ( $fieldName , $value ) {

		if ( $fieldName === 'page_ns_and_title' ) {
			$pageInfo = explode(':', $value, 2);
			$pageNsIndex = $pageInfo[0];
			$pageTitleText = $pageInfo[1];

			$title = Title::makeTitle( $pageNsIndex, $pageTitleText );

			$pageLink = $this->getSkin()->makeLinkObj( $title, htmlspecialchars( $title->getText() ) );
			
			$url = Title::newFromText('Special:WatchStrength')->getLocalUrl(
				array( 'page' => $value )
			);
			$msg = wfMsg( 'watchstrength-view-page-stats' );
			
			$pageLink .= ' (' . Xml::element(
				'a',
				array( 'href' => $url ),
				$msg
			) . ')';
			
			return $pageLink;
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
			'page_ns_and_title'       => 'watchstrength-special-header-page-title',
			'watches'                 => 'watchstrength-special-header-watches',
			'num_reviewed'            => 'watchstrength-special-header-reviewed-watches',
			'percent_pending'         => 'watchstrength-special-header-pending-percent',
			'max_pending_minutes'     => 'watchstrength-special-header-pending-maxtime',
			'average_pending_minutes' => 'watchstrength-special-header-pending-averagetime',
		);

		foreach ( $headers as $key => $val ) {
			$headers[$key] = $this->msg( $val )->text();
		}

		return $headers;
	}

	function getDefaultSort () {
		return 'num_reviewed';
	}

}