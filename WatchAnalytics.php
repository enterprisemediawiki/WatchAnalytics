<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WatchAnalytics' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WatchAnalytics'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WatchAnalyticsAliases'] = __DIR__ . '/WatchAnalytics.alias.php';
	$wgExtensionMessagesFiles['WatchAnalyticsMagic'] = __DIR__ . '/WatchAnalytics.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for WatchAnalytics extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the WatchAnalytics extension requires MediaWiki 1.25+' );
}
