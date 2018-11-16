<?php
/**
* ClearPendingReviews SpecialPage
*
* @file
* @ingroup Extensions
*/

class SpecialClearPendingReviews extends SpecialPage {
	public function __construct() {
		parent::__construct( 'ClearPendingReviews', 'clearreviews' );
	}

	public function execute( $par ) {
		global $wgOut;

		if ( !$this->getUser()->isAllowed( 'clearreviews' ) ) {
			throw new PermissionsError( 'clearreviews' );
		}

		$this->setHeaders();
		$wgOut->addModules( 'ext.watchanalytics.clearpendingreviews.scripts' );
		$output = $this->getOutput();

		//Defines input form
		$formDescriptor = [
				'start' => [
					'section' => 'section1',
					'label-message' => 'clearpendingreview-start-time',
					'type' => 'text',
					'required' => 'true',
					'validation-callback' => [ $this, 'validateTime' ],
				],
				'end' => [
					'section' => 'section1',
					'label-message' => 'clearpendingreview-end-time',
					'type' => 'text',
					'required' => 'true',
					'validation-callback' => [ $this, 'validateTime' ],
					'help' => '<b>Current time:</b> '.date('YmdHi').'00',
				],
				'category' => [
					'section' => 'section2',
					'label-message' => 'clearpendingreview-category',
					'type' => 'text',
					'validation-callback' => [ $this, 'validateCategory' ],
				],
				'page' => [
					'section' => 'section2',
					'label-message' => 'clearpendingreview-page-title',
					'type' => 'text',
				],
			];

			$form = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext(), 'clearform' );

			$form->setSubmitText( 'Preview' );
			$form->setSubmitName( 'preview' );
			$form->setSubmitCallback( [ $this, 'trySubmit' ] );
			$form->show();

	}

	public function validateTime( $dateField, $allData ) {
		if ( !is_string( $dateField ) ) {
			return wfMessage( 'clearpendingreviews-date-invalid' )->inContentLanguage();
		}

		//Validates start time is before end time
		if ( $allData['start'] > $allData['end'] ) {
			return wfMessage( 'clearpendingreviews-date-order-invalid' )->inContentLanguage();
		}

		//Verifys input format is ISO
		$dateTime = DateTime::createFromFormat( 'YmdHis', $dateField );
		if ( $dateTime ) {
				return $dateTime->format( 'YmdHis' ) === $dateField;
		}

		return wfMessage( 'clearpendingreviews-date-invalid' )->inContentLanguage();
	}

	public function validateCategory( $categoryField, $allData ) {
		$bad_cat_name = false;

		//Validates either Category or Title field is used
		if ( empty( $categoryField ) && empty ( $allData['page'] ) ) {
			return wfMessage( 'clearpendingreviews-missing-date-category' )->inContentLanguage();
		}
		if ( empty ( $categoryField ) ) {
			return true;
		} else {
			//Verifys category exists in wiki
			$category_title = Title::makeTitleSafe( NS_CATEGORY, $categoryField );
			if ( !$category_title->exists() ) {
				return wfMessage( 'clearpendingreviews-category-invalid' )->inContentLanguage();
			}
		}
		return true;
	}

	/**
	* @param array $data
	* @param bool $clearPages
	* @return $results
	*/

	public static function doSearchQuery( $data, $clearPages ) {
		$dbw = wfGetDB( DB_REPLICA );
		$category = preg_replace('/\s+/', '_', $data['category']);
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

		$results = $dbw->select( $tables, $vars, $conditions, __METHOD__, 'DISTINCT', $join_conds );

		if ( $clearPages == True ) {
			$dbw = wfGetDB( DB_MASTER );

			foreach ($results as $result ) {
				$values = array('wl_notificationtimestamp' => null );
				$conds = array( 'wl_id' => $result->wl_id );
				$options = array();
				$dbw->update( 'watchlist', $values, $conds, __METHOD__, $options );
			}
		}

		return $results;
	}

	/**
	* @param array $data
	* @param object $form
	* @return Status
	*/

	public function trySubmit( $data, $form ) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

		if (isset($_POST['clearpages'])) {
			//Clears pending reviews
			$results = $this->doSearchQuery( $data, True );

			//Count how many pages were cleared
			$pageCount = 0;
			foreach ( $results as $result ) {
				$pageCount = $pageCount + 1;
			}

			//Log when pages are cleared in Special:Log
			$logEntry = new ManualLogEntry( 'pendingreviews', 'clearreivews' );
			$logEntry->setPerformer( $this->getUser() );
			$logEntry->setTarget( $this->getPageTitle() );
			$logEntry->setParameters( [
				'4::paramname' => '('.$pageCount.')',
				'5::paramname' => '('.$data['category'].')',
				'6::paramname' => '('.$data['page'].')',
				] );
			$logid = $logEntry->insert();
			$logEntry->publish( $logid );
			Hooks::run( 'PendingReviewsCleared', [&$data, &$results, &$pageCount] );

			//Create link back to Special:ClearPendingReviews
			$pageLinkHtml = Linker::link( $this->getPageTitle() );
			$output->addHTML( "<b>" );
			$output->addHTML( wfMessage( 'clearpendingreviews-success' )->numParams( $pageCount )->plain() );
			$output->addHTML( "</b>" );
			$output->addHTML( "<br>" );
			$output->addHTML( wfMessage( 'clearpendingreviews-success-return' ) );
			$output->addHTML( $pageLinkHtml );

			//Don't reload the form after clearing pages.
			return true;

		} else {
			$results = $this->doSearchQuery( $data, False );
			$table = '';
			$table .= "<table class='wikitable' style='width:100%'>";
			$table .= "<tr>";
			$table .= "<td style='vertical-align:top;'>";
			$table .= "<h3>".wfMessage( 'clearpendingreviews-pages-cleared' )."</h3>";
			$table .= "<ul>";
			$impactedPages = array();
			foreach ($results as $result) {
				$page = Title::makeTitle( $result->wl_namespace, $result->wl_title );
				$pageLinkHtml = Linker::link( $page );
				$impactedPages[] = $pageLinkHtml;
			}

			$impactedPages = array_unique( $impactedPages );
			foreach ($impactedPages as $impactedPage ) {
				$table .= "<li>".$impactedPage."</li>";
			}

			$table .= "</ul>";
			$table .= "</td>";
			$table .= "<td style='vertical-align:top;'>";
			$table .= "<h3>".wfMessage( 'clearpendingreviews-people-impacted' )."</h3>";
			$table .= "<ul>";
			$impactedUsers = array();
			foreach ($results as $result ) {
				$user = User::newFromId( $result->wl_user );
				$userTitleObj = $user->getUserPage();
				$userLinkHtml = Linker::link( $userTitleObj );
				$impactedUsers[] = $userLinkHtml;
			}

			$impactedUsers = array_unique( $impactedUsers );
			foreach ($impactedUsers as $impactedUser ) {
				$table .= "<li>".$impactedUser."</li>";
			}

			$table .= "</ul>";
			$table .= "</td>";
			$table .= "</tr>";
			$table .= "</table>";

			$form->setSubmitText( 'Clear pages' );
			$form->setSubmitName( 'clearpages' );
			$form->setSubmitDestructive();
			$form->setCancelTarget( $this->getPageTitle());
			$form->showCancel();
			Hooks::run( 'PendingReviewsPreview', [&$data, &$results] );
			//Display preview of pages to be cleared
			$form->setPostText( $table );

			return false;
		}
	}
}
