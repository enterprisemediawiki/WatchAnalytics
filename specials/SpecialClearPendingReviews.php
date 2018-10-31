
<?php
/**
* ClearPendingReviews SpecialPage
*
* @file
* @ingroup Extensions
*/

class SpecialClearPendingReviews extends FormSpecialPage {
	public function __construct() {
		parent::__construct( 'ClearPendingReviews' );
	}

	protected function getFormFields() {
		return [
			'category' => [
				'label-message' => 'clearpendingreview-category',
				'type' => 'text',
				'required' => 'false',
			],
			'start' => [
				'label-message' => 'clearpendingreview-start-time',
				'type' => 'text',
				'required' => 'true',
			],
		];
	}

	/**
	*TO DO
	*Add field validation to protect
	**No malicious inputs allowed
	**Validate entered time is real time
	**Verify category entered exists on wiki
	**Allow for wild card pagename entry in lieu of category
	*Get rid of warning "Cannot modify header information"
	*Allow for end time or just use current time
	*Display current time in ISO on the page for reference
	*Show number of pages/categories that will be cleared before clearpendingreview
	*only allow sysop to do this, add new userright
	*/

	/**
	 * @param HTMLForm $form
	 */
	protected function alterForm( HTMLForm $form ) {
		$form->setSubmitTextMsg( 'clearpendingreview-submit' );
	}

	/**
	*TO DO
	*/

	/**
 * @param array $data
 * @return Status
 */
	public function onSubmit( array $data ) {
		$currentTime = date("YmdHis");
		$dbw = wfGetDB( DB_MASTER );
		$category = preg_replace('/\s+/', '', $data['category']);
		$start = preg_replace('/\s+/', '', $data['start']);

		$res = $dbw->select(
				array(
					'w' => 'watchlist',
					'p' => 'page',
					'c' => 'categorylinks',
				),
				array(
					'w.*'
				),
				"c.cl_to='$category' AND w.wl_notificationtimestamp IS NOT NULL AND w.wl_notificationtimestamp < $currentTime AND w.wl_notificationtimestamp > $start",
				array(
					'p' => array(
						'LEFT JOIN', 'w.wl_title=p.page_title'
					),
					'c' => array(
						'LEFT JOIN', 'c.cl_from=p.page_id'
					)
				)
			);
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
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

		$output->addHTML("<b>Start time:</b>".$start."<br>");
		$output->addHTML("<b>End time:</b>".$currentTime);
		return Status::newGood();
	}
}
