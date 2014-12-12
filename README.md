# MediaWiki-WatchAnalytics

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jamesmontalvo3/WatchAnalytics/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jamesmontalvo3/WatchAnalytics/?branch=master)

Extension:WatchAnalytics is used to determine how well pages are watched on a wiki
 
## Special Pages

### SpecialPage:WeakPagesWatchAnalytics
Lists pages which are not being watched strongly. Compares to average watch strength and highest watch strength.
 
### SpecialPage:UserWatchAnalytics
Determines how strong a watcher a user is. If no user is specified, lists users by their watch strength. Also list number of pages watched
 
#### A user's watch strength determined by:
* Number of pages watching? Probably not...
* How long between recieving edit-notification and checking article
* Number of pending articles user should verify
* Use of "view history" to examine changes? This could be hard. Requires new database table?
* Has edits after other peoples' edits?
* Seniority of the person
 * All other things being equal, a senior employee watching is better than a new hire, is better than an intern
* Also could break down by organization, so you could see how well Quality Assurance is watching versus how R&D is watching. Or how well management is watching versus accounting.

## Coding Considerations

### Extensions to research
* Extension:Watchers
* Extension:WhoIsWatching
* Extension:WhoIsWatchingTabbed
* Extension:WatchLog
* Extension:WatchEmailOptional
* Extension:CollaborativeWatchlist
* Extension:WatchRight
* Extension:WhosOnline
* Extension:Contribution Scores
* Extension:Contributions
* Extension:Contributors
* Extension:Editcount
* Category:User activity extensions
