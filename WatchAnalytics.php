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
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'WatchAnalytics',
	'url' => 'https://www.mediawiki.org/wiki/Extension:WatchAnalytics',
	'author' => array( '[https://www.mediawiki.org/wiki/User:Jamesmontalvo3 James Montalvo]' ),
	'descriptionmsg' => 'watchanalytics-desc',
);

$wgMessagesDirs['WatchAnalytics'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['WatchAnalytics'] = __DIR__ . '/WatchAnalytics.i18n.php';
$wgExtensionMessagesFiles['WatchAnalyticsAliases'] = __DIR__ . '/WatchAnalytics.alias.php';
$wgExtensionMessagesFiles['WatchAnalyticsMagic'] = __DIR__ . '/WatchAnalytics.i18n.magic.php';

$wgAutoloadClasses['WatchAnalyticsHooks'] = __DIR__ . '/Hooks.php';
$wgAutoloadClasses['WatchAnalyticsUser'] = __DIR__ . '/WatchAnalyticsUser.php';

// schema updater
$wgAutoloadClasses['WatchAnalyticsUpdaterHooks'] = __DIR__ . '/schema/WatchAnalyticsUpdaterHooks.php';

// query classes
$wgAutoloadClasses['WatchesQuery'] = __DIR__ . '/includes/WatchesQuery.php';
$wgAutoloadClasses['PageWatchesQuery'] = __DIR__ . '/includes/PageWatchesQuery.php';
$wgAutoloadClasses['UserWatchesQuery'] = __DIR__ . '/includes/UserWatchesQuery.php';
$wgAutoloadClasses['WikiWatchesQuery'] = __DIR__ . '/includes/WikiWatchesQuery.php';

// table pages for special pages
$wgAutoloadClasses['WatchAnalyticsTablePager'] = __DIR__ . '/includes/WatchAnalyticsTablePager.php';
$wgAutoloadClasses['WatchAnalyticsUserTablePager'] = __DIR__ . '/includes/WatchAnalyticsUserTablePager.php';
$wgAutoloadClasses['WatchAnalyticsPageTablePager'] = __DIR__ . '/includes/WatchAnalyticsPageTablePager.php';
$wgAutoloadClasses['WatchAnalyticsWikiTablePager'] = __DIR__ . '/includes/WatchAnalyticsWikiTablePager.php';

// parser functions
$wgAutoloadClasses['WatchAnalyticsParserFunctions'] = __DIR__ . '/includes/WatchAnalyticsParserFunctions.php';

// state recorder
$wgAutoloadClasses['WatchStateRecorder'] = __DIR__ . '/includes/WatchStateRecorder.php';

// special page
$wgSpecialPages['WatchAnalytics'] = 'SpecialWatchAnalytics';
$wgAutoloadClasses['SpecialWatchAnalytics'] = __DIR__ . '/specials/SpecialWatchAnalytics.php';
$wgSpecialPages['PendingReviews'] = 'SpecialPendingReviews';
$wgAutoloadClasses['SpecialPendingReviews'] = __DIR__ . '/specials/SpecialPendingReviews.php';

// add watchlist notification system
$wgHooks['PersonalUrls'][] = 'WatchAnalyticsHooks::onPersonalUrls';
$wgHooks['BeforePageDisplay'][] = 'WatchAnalyticsHooks::onBeforePageDisplay';
$wgHooks['ParserFirstCallInit'][] = 'WatchAnalyticsParserFunctions::setup';

// update database
$wgHooks['LoadExtensionSchemaUpdates'][] = 'WatchAnalyticsUpdaterHooks::addSchemaUpdates';




$watchAnalyticsResourceTemplate = array(
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'WatchAnalytics/modules',
);

$wgResourceModules += array(

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

$egPendingReviewsEmphasizeDays = 7;

/*

*/

// Extension initialization
// $wgExtensionFunctions[] = 'WatchAnalyticsHooks::initWatchAnalyticsExtension';

// $WatchAnalyticsResourceTemplate = array(
// 	'localBasePath' => __DIR__ . '/modules',
// 	'remoteExtPath' => 'WatchAnalytics/modules',
// );


// $wgResourceModules += array(

// 	// ext.echo.base is used by mobile notifications as well, so be sure not to add any
// 	// dependencies that do not target mobile.
// 	'ext.echo.base' => $WatchAnalyticsResourceTemplate + array(
// 		'styles' => 'base/ext.echo.base.css',
// 		'scripts' => 'base/ext.echo.base.js',
// 		'messages' => array(
// 			'echo-error-preference',
// 			'echo-error-token',
// 		),
// 		'targets' => array( 'desktop', 'mobile' ),
// 	),
// 	'ext.echo.desktop' => $WatchAnalyticsResourceTemplate + array(
// 		'scripts' => 'desktop/ext.echo.desktop.js',
// 		'dependencies' => array(
// 			'ext.echo.base',
// 			'mediawiki.api',
// 			'mediawiki.Uri',
// 			'mediawiki.jqueryMsg',
// 			'mediawiki.user',
// 		),
// 	),
// 	'ext.echo.overlay' => $WatchAnalyticsResourceTemplate + array(
// 		'scripts' => array(
// 			'overlay/ext.echo.overlay.js',
// 		),
// 		'styles' => 'overlay/ext.echo.overlay.css',
// 		'skinStyles' => array(
// 			'modern' => 'overlay/ext.echo.overlay.modern.css',
// 			'monobook' => 'overlay/ext.echo.overlay.monobook.css',
// 		),
// 		'dependencies' => array(
// 			'ext.echo.desktop',
// 			'mediawiki.util',
// 			'mediawiki.language',
// 		),
// 		'messages' => array(
// 			'echo-overlay-title',
// 			'echo-overlay-title-overflow',
// 			'echo-overlay-link',
// 			'echo-none',
// 			'echo-mark-all-as-read',
// 			'echo-more-info',
// 			'echo-feedback',
// 		),
// 	),
// );
/*
	// ext.WatchAnalytics.base is used by mobile notifications as well, so be sure not to add any
	// dependencies that do not target mobile.
	'ext.WatchAnalytics.base' => $WatchAnalyticsResourceTemplate + array(
		'styles' => 'base/ext.WatchAnalytics.base.css',
		'scripts' => 'base/ext.WatchAnalytics.base.js',
		'messages' => array(
			'watchanalytics-error-preference',
			'watchanalytics-error-token',
		),
		'targets' => array( 'desktop', 'mobile' ),
	),
	'ext.WatchAnalytics.desktop' => $WatchAnalyticsResourceTemplate + array(
		'scripts' => 'desktop/ext.WatchAnalytics.desktop.js',
		'dependencies' => array(
			'ext.WatchAnalytics.base',
			'mediawiki.api',
			'mediawiki.Uri',
			'mediawiki.jqueryMsg',
			'mediawiki.user',
		),
	),
	'ext.WatchAnalytics.overlay' => $WatchAnalyticsResourceTemplate + array(
		'scripts' => array(
			'overlay/ext.WatchAnalytics.overlay.js',
		),
		'styles' => 'overlay/ext.WatchAnalytics.overlay.css',
		'skinStyles' => array(
			'modern' => 'overlay/ext.WatchAnalytics.overlay.modern.css',
			'monobook' => 'overlay/ext.WatchAnalytics.overlay.monobook.css',
		),
		'dependencies' => array(
			'ext.WatchAnalytics.desktop',
			'mediawiki.util',
			'mediawiki.language',
		),
		'messages' => array(
			'watchanalytics-overlay-title',
			'watchanalytics-overlay-title-overflow',
			'watchanalytics-overlay-link',
			'watchanalytics-none',
			'watchanalytics-mark-all-as-read',
			'watchanalytics-more-info',
			'watchanalytics-feedback',
		),
	),
	'ext.WatchAnalytics.special' => $WatchAnalyticsResourceTemplate + array(
		'scripts' => array(
			'special/ext.WatchAnalytics.special.js',
		),
		'styles' => 'special/ext.WatchAnalytics.special.css',
		'dependencies' => array(
			'ext.WatchAnalytics.desktop',
			'mediawiki.ui.button',
		),
		'messages' => array(
			'watchanalytics-load-more-error',
			'watchanalytics-more-info',
			'watchanalytics-feedback',
		),
		'position' => 'top',
	),
	'ext.WatchAnalytics.alert' => $WatchAnalyticsResourceTemplate + array(
		'styles' => 'alert/ext.WatchAnalytics.alert.css',
		'skinStyles' => array(
			'modern' => 'alert/ext.WatchAnalytics.alert.modern.css',
			'monobook' => 'alert/ext.WatchAnalytics.alert.monobook.css',
		),
	),
	'ext.WatchAnalytics.badge' => $WatchAnalyticsResourceTemplate + array(
		'styles' => 'badge/ext.WatchAnalytics.badge.css',
		'skinStyles' => array(
			'modern' => 'badge/ext.WatchAnalytics.badge.modern.css',
			'monobook' => 'badge/ext.WatchAnalytics.badge.monobook.css',
		),
	),
);



$WatchAnalyticsIconPath = "WatchAnalytics/modules/icons";

// Defines icons, which are 30x30 images. This is passed to BeforeCreateWatchAnalyticsEvent so
// extensions can define their own icons with the same structure.  It is recommended that
// extensions prefix their icon key. An example is myextension-name.  This will help
// avoid namespace conflicts.
//
// You can use either a path or a url, but not both.
// The value of 'path' is relative to $wgExtensionAssetsPath.
//
// The value of 'url' should be a URL.
//
// You should customize the site icon URL, which is:
// $wgWatchAnalyticsNotificationIcons['site']['url']
$wgWatchAnalyticsNotificationIcons = array(
	'placeholder' => array(
		'path' => "$WatchAnalyticsIconPath/Generic.png",
	),
	'trash' => array(
		'path' => "$WatchAnalyticsIconPath/Deletion.png",
	),
	'chat' => array(
		'path' => "$WatchAnalyticsIconPath/Talk.png",
	),
	'linked' => array(
		'path' => "$WatchAnalyticsIconPath/CrossReferenced.png",
	),
	'featured' => array(
		'path' => "$WatchAnalyticsIconPath/Featured.png",
	),
	'reviewed' => array(
		'path' => "$WatchAnalyticsIconPath/Reviewed.png",
	),
	'tagged' => array(
		'path' => "$WatchAnalyticsIconPath/ReviewedWithTags.png",
	),
	'revert' => array(
		'path' => "$WatchAnalyticsIconPath/Revert.png",
	),
	'checkmark' => array(
		'path' => "$WatchAnalyticsIconPath/Reviewed.png",
	),
	'gratitude' => array(
		'path' => "$WatchAnalyticsIconPath/Gratitude.png",
	),
	'site' => array(
		'url' => false
	),
);
*/