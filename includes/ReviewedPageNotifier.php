<?php
/**
 * MediaWiki Extension: WatchAnalytics
 * http://www.mediawiki.org/wiki/Extension:WatchAnalytics
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * This program is distributed WITHOUT ANY WARRANTY.
 */

/**
 *
 * @file
 * @ingroup Extensions
 * @author James Montalvo
 * @licence MIT License
 */

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/WatchAnalytics/WatchAnalytics.php" );
EOT;
	exit( 1 );
}

class ReviewedPageNotifier {

	protected $user;
	protected $title;
	protected $initialTimestamp;
	protected $finalTimestamp;

	public function __construct ( User $user ) {
		$this->user = $user;
	}
	public function notifyIfReviewed( OutputPage $out ) {

		if ( ! $this->title ) {
			return;
		}

		$this->finalTimestamp = WatchedItem::fromUserTitle(
			$this->user, $this->title )->getNotificationTimestamp();


		if ( $this->initialTimestamp !== $this->finalTimestamp ) {

			// page was pending review (initial timestamp not null), and was
			// viewed, thus making the final timestamp null (note: they can't
			// both be null per the wrapping if-statement)			
			if ( $this->finalTimestamp === null ) {

			}

			// If initially was not pending, but now is???
			// could this even happen?
			// @todo: are there cases when initialTimestamp is null but
			// finalTimestamp is not? Like when you right-click open in
			// new window on the "watch" button? What does initialTimestamp
			// equal if at the start it is not watched (null? false?) and 
			// then becomes watched during load? It's final value should be
			// null since it couldn't (likely) have been edited in the time
			// between initiating watching and final check of timestamp. It
			// is possible that there could be a false !== null problem, though.
			elseif ( $initialTimestamp === null ) {
				// for now do nothing since this is an edge case
			}

			// neither timestamp is null (so the page was pending review) 
			// but an old rev was viewed and thus the final timestamp is
			// not null
			else {

			}

			$html = Linker::linkKnown(
				SpecialPage::getTitleFor( 'Pendingreviews' ),
				'Mark unreviewed',
				array(
					'class' => 'pendingreviews-orange-button pendingreviews-button',
					'style' => 'float:right;',
				),
				array(
					'setNotificationTitle' => $this->title->getText(),
					'setNotificationNS' => $this->title->getNamespace(),
					'setNotificationTS' => $this->initialTimestamp,
				)
			);


			// $html = Xml::element( 'a',
			// 	array(
			// 		'href' => ,
			// 		'style' => 'float:right;',
			// 		'class' => "pendingreviews-orange-button pendingreviews-button",
			// 	),
			// 	'Mark unreviewed'
			// 	// wfMessage(
			// 	// 	'watchanalytics-pendingreviews-diff-revisions',
			// 	// 	count( $item->newRevisions )
			// 	// )->text()
			// );

			$html .= '<strong>Stuff been reviewed...</strong><p>This page was just been marked reviewed. If you didn\'t want to mark it reviewed you can click undo on the right.</p>';

			$out->addHTML( "<script type='text/template' id='reviewed-page-notifier-template'>$html</script>" );
			$out->addModules( array( 'ext.watchanalytics.reviewedpagenotifier', 'ext.watchanalytics.buttons' ) );

		}

	}
	public function setTitle ( Title $title ) {
		if ( $title->isWatchable() ) {
			$this->title = $title;
			$this->initialTimestamp = WatchedItem::fromUserTitle(
				$this->user, $this->title )->getNotificationTimestamp();
		}
	}



}
