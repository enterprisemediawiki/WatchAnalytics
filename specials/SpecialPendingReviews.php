<?php

class SpecialPendingReviews extends SpecialPage {

	public $mMode;
	protected $header_links = array(
		'watchanalytics-pages-specialpage' => '',
		'watchanalytics-users-specialpage' => 'users',
		'watchanalytics-wikihistory-specialpage'  => 'wikihistory',
	);


	public function __construct() {
		parent::__construct( 
			"PendingReviews", // 
			"",  // rights required to view
			true // show in Special:SpecialPages
		);
	}
	
	function execute( $parser = null ) {
		global $wgRequest, $wgOut;

		$this->setHeaders();

		// load styles for watch analytics special pages
		$wgOut->addModuleStyles( 'ext.watchanalytics.specials' );

		
		$wgOut->addHTML( $this->getPageHeader() );
		
		// ...

		$dbr = wfGetDB( DB_SLAVE );

		$pendingReviews = '';
	}
	
	public function getPageHeader() {

	}

}

