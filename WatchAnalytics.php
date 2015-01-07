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

// Extension credits that will show up on Special:Version
$GLOBALS['wgExtensionCredits']['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'WatchAnalytics',
	'url' => 'https://www.mediawiki.org/wiki/Extension:WatchAnalytics',
	'author' => array( '[https://www.mediawiki.org/wiki/User:Jamesmontalvo3 James Montalvo]' ),
	'descriptionmsg' => 'watchanalytics-desc',
);

$GLOBALS['wgMessagesDirs']['WatchAnalytics'] = __DIR__ . '/i18n';
$GLOBALS['wgExtensionMessagesFiles']['WatchAnalytics'] = __DIR__ . '/WatchAnalytics.i18n.php';
$GLOBALS['wgExtensionMessagesFiles']['WatchAnalyticsAliases'] = __DIR__ . '/WatchAnalytics.alias.php';
$GLOBALS['wgExtensionMessagesFiles']['WatchAnalyticsMagic'] = __DIR__ . '/WatchAnalytics.i18n.magic.php';

$GLOBALS['wgAutoloadClasses']['WatchAnalyticsHooks'] = __DIR__ . '/Hooks.php';
$GLOBALS['wgAutoloadClasses']['WatchAnalyticsUser'] = __DIR__ . '/WatchAnalyticsUser.php';

// schema updater
$GLOBALS['wgAutoloadClasses']['WatchAnalyticsUpdaterHooks'] = __DIR__ . '/schema/WatchAnalyticsUpdaterHooks.php';

// pending reviews
$GLOBALS['wgAutoloadClasses']['PendingReview'] = __DIR__ . '/includes/PendingReview.php';

// query classes
$GLOBALS['wgAutoloadClasses']['WatchesQuery'] = __DIR__ . '/includes/WatchesQuery.php';
$GLOBALS['wgAutoloadClasses']['PageWatchesQuery'] = __DIR__ . '/includes/PageWatchesQuery.php';
$GLOBALS['wgAutoloadClasses']['UserWatchesQuery'] = __DIR__ . '/includes/UserWatchesQuery.php';
$GLOBALS['wgAutoloadClasses']['WikiWatchesQuery'] = __DIR__ . '/includes/WikiWatchesQuery.php';

// table pages for special pages
$GLOBALS['wgAutoloadClasses']['WatchAnalyticsTablePager'] = __DIR__ . '/includes/WatchAnalyticsTablePager.php';
$GLOBALS['wgAutoloadClasses']['WatchAnalyticsUserTablePager'] = __DIR__ . '/includes/WatchAnalyticsUserTablePager.php';
$GLOBALS['wgAutoloadClasses']['WatchAnalyticsPageTablePager'] = __DIR__ . '/includes/WatchAnalyticsPageTablePager.php';
$GLOBALS['wgAutoloadClasses']['WatchAnalyticsWikiTablePager'] = __DIR__ . '/includes/WatchAnalyticsWikiTablePager.php';

// parser functions
$GLOBALS['wgAutoloadClasses']['WatchAnalyticsParserFunctions'] = __DIR__ . '/includes/WatchAnalyticsParserFunctions.php';

// state recorder
$GLOBALS['wgAutoloadClasses']['WatchStateRecorder'] = __DIR__ . '/includes/WatchStateRecorder.php';

// special page
$GLOBALS['wgSpecialPages']['WatchAnalytics'] = 'SpecialWatchAnalytics';
$GLOBALS['wgAutoloadClasses']['SpecialWatchAnalytics'] = __DIR__ . '/specials/SpecialWatchAnalytics.php';
$GLOBALS['wgSpecialPages']['PendingReviews'] = 'SpecialPendingReviews';
$GLOBALS['wgAutoloadClasses']['SpecialPendingReviews'] = __DIR__ . '/specials/SpecialPendingReviews.php';

// add watchlist notification system
$GLOBALS['wgHooks']['PersonalUrls'][] = 'WatchAnalyticsHooks::onPersonalUrls';
$GLOBALS['wgHooks']['BeforePageDisplay'][] = 'WatchAnalyticsHooks::onBeforePageDisplay';
$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'WatchAnalyticsParserFunctions::setup';
$GLOBALS['wgHooks']['TitleMoveComplete'][] = 'WatchAnalyticsHooks::onTitleMoveComplete';

// update database
$GLOBALS['wgHooks']['LoadExtensionSchemaUpdates'][] = 'WatchAnalyticsUpdaterHooks::addSchemaUpdates';




$watchAnalyticsResourceTemplate = array(
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'WatchAnalytics/modules',
);

$GLOBALS['wgResourceModules'] += array(

	'ext.watchanalytics.base' => $watchAnalyticsResourceTemplate + array(
		'styles' => 'base/ext.watchanalytics.base.css',
	),

	'ext.watchanalytics.forcegraph' => $watchAnalyticsResourceTemplate + array(
		'styles' => 'forcegraph/ext.watchanalytics.forcegraph.css',
		'scripts' => array(
			'forcegraph/ext.watchanalytics.circlesort.js',
			'forcegraph/ext.watchanalytics.forcegraph.js',
		),
		'messages' => array(
			'watchanalytics-pause-visualization',
			'watchanalytics-unpause-visualization',
		),
		'dependencies' => array(
			'underscore.js',
			'd3.js',
		),

	),

	'ext.watchanalytics.specials' => $watchAnalyticsResourceTemplate + array(
		'styles' => 'specials/ext.watchanalytics.specials.css',
	),

	'ext.watchanalytics.pendingreviews' => $watchAnalyticsResourceTemplate + array(
		'styles' => 'pendingreviews/ext.watchanalytics.pendingreviews.css',
		'scripts' => 'pendingreviews/ext.watchanalytics.pendingreviews.js',
		'dependencies' => array(
			'mediawiki.Title',
		),

	),

	'underscore.js' => $watchAnalyticsResourceTemplate + array(
		'scripts' => array(
			'underscore.js/underscore-min.js',
		),
	),

	'd3.js' => $watchAnalyticsResourceTemplate + array(
		'scripts' => array(
			'd3.js/d3.js',
		),
	),

	'ext.watchanalytics.shakependingreviews' => $watchAnalyticsResourceTemplate + array(
		'scripts' => array(
			'shakependingreviews/shake.js',
		),
		'dependencies' => array(
			'jquery.effects.shake',
		),
	),
);

$GLOBALS['egPendingReviewsEmphasizeDays'] = 7;
$GLOBALS['egPendingReviewsRedPagesThreshold'] = 2; // 0 or 1 reviewers BESIDES the person who made the change
$GLOBALS['egPendingReviewsOrangePagesThreshold'] = 4; // 2 or 3 reviewers BESIDES the person who made the change
