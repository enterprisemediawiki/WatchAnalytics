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

    $currentTime = date("YmdHis");
    $startTime = $currentTime-3000;
    $category = 'Test';
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
        array(
          'w' => 'watchlist',
          'p' => 'page',
          'c' => 'categorylinks',
        ),
        array(
          'w.*'
        ),
        "c.cl_to='$category' AND w.wl_notificationtimestamp IS NOT NULL AND w.wl_notificationtimestamp < $currentTime AND w.wl_notificationtimestamp > $startTime",
        array(
          'p' => array(
            'LEFT JOIN', 'w.wl_title=p.page_title'
          ),
          'c' => array(
            'LEFT JOIN', 'c.cl_from=p.page_id'
          )
        )
      );

    $output->addHTML("<table>");
    $output->addHTML("<tr><th>ID</th><th>User</th><th>Namespace</th><th>Title</th><th>Notification TimeStamp</th></tr>");
    foreach ($res as $value) {
      $output->addHTML("<tr>");
      $output->addHTML("<td>".$value->wl_id."</td>");
      $output->addHTML("<td>".$value->wl_user."</td>");
      $output->addHTML("<td>".$value->wl_namespace."</td>");
      $output->addHTML("<td>".$value->wl_title."</td>");
      $output->addHTML("<td>".$value->wl_notificationtimestamp."</td>");
      $output->addHTML("</tr>");
    }
    $output->addHTML("</table>");

    $output->addHTML("<b>Start time:</b>".$startTime);
    $output->addHTML("<b>Start time:</b>".$currentTime);
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
