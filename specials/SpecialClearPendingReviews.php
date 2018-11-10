<?php
/**
* ClearPendingReviews SpecialPage
*
* @file
* @ingroup Extensions
*/

class SpecialClearPendingReviews extends FormSpecialPage {
	public function __construct() {
		parent::__construct( 'ClearPendingReviews', 'clearpendingreviews' );
	}

	protected function getFormFields() {
		return [
			'category' => [
				'label-message' => 'clearpendingreview-category',
				'type' => 'text',
			],
			'page' => [
				'label-message' => 'clearpendingreview-page-title',
				'type' => 'text',
			],
			'start' => [
				'label-message' => 'clearpendingreview-start-time',
				'type' => 'text',
				'required' => 'true',
				'validation-callback' => function ( $val ) {
					return $this->validateISO( $val );
				},
			],
			'end' => [
				'label-message' => 'clearpendingreview-end-time',
				'type' => 'text',
				'required' => 'true',
				'validation-callback' => function ( $val ) {
					return $this->validateISO( $val );
				},
			],
		];
	}

	public function validateISO( $val ) {
		if ( !is_string( $val ) ) {
			return false;
		}

		$dateTime = DateTime::createFromFormat( 'YmdHis', $val );
		if ( $dateTime ) {
				return $dateTime->format( 'YmdHis' ) === $val;
		}
		return false;
	}
	/**
	*TO DO
	*Add field validation to protect
	**No malicious inputs allowed
	**Verify category entered exists on wiki
	**Allow for wild card pagename entry in lieu of category
	*Allow for end time or just use current time
	*Display current time in ISO on the page for reference
	*Show number of pages/categories that will be cleared before clearpendingreview
	*only allow sysop to do this, add new userright
	*/

	/**
	* @param HTMLForm $form
	*/
	protected function alterForm( HTMLForm $form ) {
		$form->setSubmitTextMsg( 'clearpendingreview-preview' );
		$form->addButton( array('name' => 'continue', 'value' => $this->msg( 'clearpendingreview-clear' )->text(), 'type' => 'submit' ) );
		$form->addPreText('<b>Current time:</b> '.date('YmdHi'));
	}

	public static function doSearchQuery( array $data ) {
		$dbw = wfGetDB( DB_REPLICA );
		$category = preg_replace('/\s+/', '', $data['category']);
		$page = preg_replace('/\s+/', '_', $data['page']);
		$start = preg_replace('/\s+/', '', $data['start']);
		$end = preg_replace('/\s+/', '', $data['end']);
		$conditions = '';

		if ($category) {
			$conditions .= "c.cl_to='$category' AND ";
		}
		if ($page) {
			$conditions .= "w.wl_title LIKE '$page%' AND ";
		}

		$tables = array( 'w' => 'watchlist', 'p' => 'page', 'c' => 'categorylinks' );
		$vars = array( 'w.*' );
		$conditions .= "w.wl_notificationtimestamp IS NOT NULL AND w.wl_notificationtimestamp < $end AND w.wl_notificationtimestamp > $start";
		$join_conds = array(
			'p' => array(
				'LEFT JOIN', 'w.wl_title=p.page_title'
			),
			'c' => array(
				'LEFT JOIN', 'c.cl_from=p.page_id'
			)
		);

		return $dbw->select( $tables, $vars, $conditions, __METHOD__, 'DISTINCT', $join_conds );

	}

	public static function doClearQuery( array $data ) {
		$dbw = wfGetDB( DB_MASTER );
		$category = preg_replace('/\s+/', '', $data['category']);
		$page = preg_replace('/\s+/', '_', $data['page']);
		$start = preg_replace('/\s+/', '', $data['start']);
		$end = preg_replace('/\s+/', '', $data['end']);
		$conditions = '';

		if ($category) {
			$conditions .= "c.cl_to='$category' AND ";
		}
		if ($page) {
			$conditions .= "w.wl_title LIKE '$page%' AND ";
		}

		$tables = array( 'w' => 'watchlist', 'p' => 'page', 'c' => 'categorylinks' );
		$vars = array( 'wl_id' );
		$conditions .= "w.wl_notificationtimestamp IS NOT NULL AND w.wl_notificationtimestamp < $end AND w.wl_notificationtimestamp > $start";
		$join_conds = array(
			'p' => array(
				'LEFT JOIN', 'w.wl_title=p.page_title'
			),
			'c' => array(
				'LEFT JOIN', 'c.cl_from=p.page_id'
			)
		);

		$results = $dbw->select( $tables, $vars, $conditions, __METHOD__, 'DISTINCT', $join_conds );

		$pagesToClear = null;

		foreach ($results as $row) {
			$values = array('wl_notificationtimestamp' => null );
			$conds = array( 'wl_id' => $row->wl_id );
			$options = array();
			$dbw->update( 'watchlist', $values, $conds, __METHOD__, $options );
			$pagesToClear[] = $row->wl_id;
		}

		return $pagesToClear;

	}

	/**
	* @param array $data
	* @return Status
	*/

	public function onSubmit( array $data ) {

		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

		if (isset($_POST['continue'])) {
				$output->addHTML("<h3>Test</h3>");
				$res = $this->doClearQuery( $data );
				$output->addHTML("<table class='wikitable' style='text-align: center'>");
				foreach ($res as $value) {
					$output->addHTML("<tr>");
					$output->addHTML("<td>".$value."</td>");
					$output->addHTML("</tr>");
				}
				$output->addHTML("</table>");
				return Status::newGood();
		} else {
			$res = $this->doSearchQuery( $data );
			$output->addHTML("<table class='wikitable' style='width:100%'>");
			$output->addHTML("<tr>");
			$output->addHTML("<td>");
			$output->addHTML("<h3>The following pages will be cleared:</h3>");
			$output->addHTML("<ul>");
			foreach ($res as $value) {
				$page = Title::makeTitle( $value->wl_namespace, $value->wl_title );
				$pageLinkHtml = Linker::link( $page );
				$output->addHTML("<li>".$pageLinkHtml."</li>");
			}
			$output->addHTML("</ul>");
			$output->addHTML("</td>");
			$output->addHTML("<td>");

			$output->addHTML("<h3>The following people will be impacted:</h3>");
			$output->addHTML("<ul>");
			foreach ($res as $value) {
				$user = User::newFromId( $value->wl_user );
				$userTitleObj = $user->getUserPage();
				$userLinkHtml = Linker::link( $userTitleObj );
				// $output->addHTML("<td>".$value->wl_user."</td>");
				$output->addHTML("<li>".$userLinkHtml."</li>");
			}
			$output->addHTML("</ul>");
			$output->addHTML("</td>");
			$output->addHTML("</tr>");
			$output->addHTML("</table>");
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getDisplayFormat() {
		return 'ooui';
	}
}
