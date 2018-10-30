<?php
/**
 * MezaAdmin SpecialPage
 *
 * @file
 * @ingroup Extensions
 */

class SpecialClearPendingReviews extends SpecialPage {
	public function __construct() {
		parent::__construct( 'ClearPendingReviews' );
	}

	public function execute( $par ) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

		$output->addHTML('<h2>Clear Pending Reviews</h2>');
		$output->addHTML("<p>Use these drop downs to select the page category and time frame to clear pending reviews from.</p>");
		$output->addHTML("<select>");
		$output->addHTML('<option value="Test">Test</option>');
  	$output->addHTML('<option value="Test2">Test2</option>');
		$output->addHTML("</select>");
		$output->addHTML("<select>");
		$output->addHTML('<option value="0.5">30 minutes ago</option>');
		$output->addHTML('<option value="1">1 hour ago</option>');
  	$output->addHTML('<option value="2">2 hours ago</option>');
		$output->addHTML('<option value="2">5 hours ago</option>');
		$output->addHTML("</select>");
		$output->addHTML('<button type="button" action="$action" method="post">Clear Pending Reviews</button>');

		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
      array(
        'tables' => array(
          'w' => 'watchlist',
          'p' => 'page',
          'c' => 'categorylinks',
        ),
        'fields' => array(
          'w.*',
        ),
        'join_conds' => array(
          'p' => array(
            'LEFT JOIN', 'w.wl_title=p.page_title'
          ),
          'c' => array(
            'LEFT JOIN', 'c.cl_from = p.page_id'
          ),
        ),
        'conds' => "c.cl_to=Test"
      )
    );

    foreach ($res as $value) {
      $output->addHTML($value);
    }
  }
  // static public function removePendingReviewsByCategory($start, $category) {
  //   return array(
  //     'tables' => array(
  //       'w' => 'watchlist',
  //       'p' => 'page',
  //       'c' => 'categorylinks',
  //     ),
  //     'fields' => array(
  //       'w.*',
  //     ),
  //     'join_conds' => array(
  //       'p' => array(
  //         'JOIN', 'w.wl_title=p.page_title'
  //       ),
  //       'c' => array(
  //         'LEFT OUTER JOIN', 'c.cl_from = p.page_id'
  //       ),
  //     ),
  //     'conds' => "c.cl_to => $category AND w.wl_notificationtimestamp < $now AND w.wl_notificationtimestamp > $start",
  //   );
  // }

}
