<?php
/**
 * MediaWiki Extension: WatchStrength
 * http://www.mediawiki.org/wiki/Extension:WatchStrength
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
require_once( "$IP/extensions/WatchStrength/WatchStrength.php" );
EOT;
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'WatchStrength',
	'url' => 'https://www.mediawiki.org/wiki/Extension:WatchStrength',
	'author' => array( '[https://www.mediawiki.org/wiki/User:Jamesmontalvo3 James Montalvo]' ),
	'descriptionmsg' => 'watchstrength-desc',
);

$wgMessagesDirs['WatchStrength'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['WatchStrength'] = __DIR__ . '/WatchStrength.i18n.php';
$wgExtensionMessagesFiles['WatchStrengthAliases'] = __DIR__ . '/WatchStrength.alias.php';

$wgAutoloadClasses['WatchStrengthHooks'] = __DIR__ . '/Hooks.php';
$wgAutoloadClasses['WatchStrengthUser'] = __DIR__ . '/WatchStrengthUser.php';

// special page
$wgAutoloadClasses['SpecialWatchStrength'] = __DIR__ . '/specials/SpecialWatchStrength.php';
$wgAutoloadClasses['WatchStrengthTablePager'] = __DIR__ . '/specials/WatchStrengthTablePager.php';
$wgAutoloadClasses['WatchStrengthUserTablePager'] = __DIR__ . '/specials/WatchStrengthUserTablePager.php';
$wgAutoloadClasses['WatchStrengthPageTablePager'] = __DIR__ . '/specials/WatchStrengthPageTablePager.php';



// $wgAutoloadClasses['WatchStrengthEvent'] = __DIR__ . '/model/Event.php';

// add watchlist notification system
$wgHooks['PersonalUrls'][] = 'WatchStrengthHooks::onPersonalUrls';


$wgSpecialPages['WatchStrength'] = 'SpecialWatchStrength'; // register special page





// Extension initialization
// $wgExtensionFunctions[] = 'WatchStrengthHooks::initWatchStrengthExtension';

// $WatchStrengthResourceTemplate = array(
// 	'localBasePath' => __DIR__ . '/modules',
// 	'remoteExtPath' => 'WatchStrength/modules',
// );


// $wgResourceModules += array(

// 	// ext.echo.base is used by mobile notifications as well, so be sure not to add any
// 	// dependencies that do not target mobile.
// 	'ext.echo.base' => $watchStrengthResourceTemplate + array(
// 		'styles' => 'base/ext.echo.base.css',
// 		'scripts' => 'base/ext.echo.base.js',
// 		'messages' => array(
// 			'echo-error-preference',
// 			'echo-error-token',
// 		),
// 		'targets' => array( 'desktop', 'mobile' ),
// 	),
// 	'ext.echo.desktop' => $watchStrengthResourceTemplate + array(
// 		'scripts' => 'desktop/ext.echo.desktop.js',
// 		'dependencies' => array(
// 			'ext.echo.base',
// 			'mediawiki.api',
// 			'mediawiki.Uri',
// 			'mediawiki.jqueryMsg',
// 			'mediawiki.user',
// 		),
// 	),
// 	'ext.echo.overlay' => $watchStrengthResourceTemplate + array(
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
	// ext.WatchStrength.base is used by mobile notifications as well, so be sure not to add any
	// dependencies that do not target mobile.
	'ext.WatchStrength.base' => $WatchStrengthResourceTemplate + array(
		'styles' => 'base/ext.WatchStrength.base.css',
		'scripts' => 'base/ext.WatchStrength.base.js',
		'messages' => array(
			'WatchStrength-error-preference',
			'WatchStrength-error-token',
		),
		'targets' => array( 'desktop', 'mobile' ),
	),
	'ext.WatchStrength.desktop' => $WatchStrengthResourceTemplate + array(
		'scripts' => 'desktop/ext.WatchStrength.desktop.js',
		'dependencies' => array(
			'ext.WatchStrength.base',
			'mediawiki.api',
			'mediawiki.Uri',
			'mediawiki.jqueryMsg',
			'mediawiki.user',
		),
	),
	'ext.WatchStrength.overlay' => $WatchStrengthResourceTemplate + array(
		'scripts' => array(
			'overlay/ext.WatchStrength.overlay.js',
		),
		'styles' => 'overlay/ext.WatchStrength.overlay.css',
		'skinStyles' => array(
			'modern' => 'overlay/ext.WatchStrength.overlay.modern.css',
			'monobook' => 'overlay/ext.WatchStrength.overlay.monobook.css',
		),
		'dependencies' => array(
			'ext.WatchStrength.desktop',
			'mediawiki.util',
			'mediawiki.language',
		),
		'messages' => array(
			'WatchStrength-overlay-title',
			'WatchStrength-overlay-title-overflow',
			'WatchStrength-overlay-link',
			'WatchStrength-none',
			'WatchStrength-mark-all-as-read',
			'WatchStrength-more-info',
			'WatchStrength-feedback',
		),
	),
	'ext.WatchStrength.special' => $WatchStrengthResourceTemplate + array(
		'scripts' => array(
			'special/ext.WatchStrength.special.js',
		),
		'styles' => 'special/ext.WatchStrength.special.css',
		'dependencies' => array(
			'ext.WatchStrength.desktop',
			'mediawiki.ui.button',
		),
		'messages' => array(
			'WatchStrength-load-more-error',
			'WatchStrength-more-info',
			'WatchStrength-feedback',
		),
		'position' => 'top',
	),
	'ext.WatchStrength.alert' => $WatchStrengthResourceTemplate + array(
		'styles' => 'alert/ext.WatchStrength.alert.css',
		'skinStyles' => array(
			'modern' => 'alert/ext.WatchStrength.alert.modern.css',
			'monobook' => 'alert/ext.WatchStrength.alert.monobook.css',
		),
	),
	'ext.WatchStrength.badge' => $WatchStrengthResourceTemplate + array(
		'styles' => 'badge/ext.WatchStrength.badge.css',
		'skinStyles' => array(
			'modern' => 'badge/ext.WatchStrength.badge.modern.css',
			'monobook' => 'badge/ext.WatchStrength.badge.monobook.css',
		),
	),
);



$WatchStrengthIconPath = "WatchStrength/modules/icons";

// Defines icons, which are 30x30 images. This is passed to BeforeCreateWatchStrengthEvent so
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
// $wgWatchStrengthNotificationIcons['site']['url']
$wgWatchStrengthNotificationIcons = array(
	'placeholder' => array(
		'path' => "$WatchStrengthIconPath/Generic.png",
	),
	'trash' => array(
		'path' => "$WatchStrengthIconPath/Deletion.png",
	),
	'chat' => array(
		'path' => "$WatchStrengthIconPath/Talk.png",
	),
	'linked' => array(
		'path' => "$WatchStrengthIconPath/CrossReferenced.png",
	),
	'featured' => array(
		'path' => "$WatchStrengthIconPath/Featured.png",
	),
	'reviewed' => array(
		'path' => "$WatchStrengthIconPath/Reviewed.png",
	),
	'tagged' => array(
		'path' => "$WatchStrengthIconPath/ReviewedWithTags.png",
	),
	'revert' => array(
		'path' => "$WatchStrengthIconPath/Revert.png",
	),
	'checkmark' => array(
		'path' => "$WatchStrengthIconPath/Reviewed.png",
	),
	'gratitude' => array(
		'path' => "$WatchStrengthIconPath/Gratitude.png",
	),
	'site' => array(
		'url' => false
	),
);
*/