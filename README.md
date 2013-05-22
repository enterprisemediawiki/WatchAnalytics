# MediaWiki-WatchStrength

Extension:WatchStrength

Used to determine how well pages are watched on a wiki
 
## Special Pages

### SpecialPage:WeakPagesWatchStrength
Lists pages which are not being watched strongly. Compares to average watch strength and highest watch strength.
 
### SpecialPage:UserWatchStrength
Determines how strong a watcher a user is. If no user is specified, lists users by their watch strength. Also list number of pages watched
 
#### A user's watch strength determined by:
* Number of pages watching? Probably not...
* How long between recieving edit-notification and checking article
* Number of pending articles user should verify
* Use of "view history" to examine changes? This could be hard. Requires new database table?
* Has edits after other peoples' edits?

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
