/*global window:false */
( function ( $, mw ) {
	'use strict';

	// backwards compatibility <= MW 1.21
	var getUrl = mw.util.getUrl || mw.util.wikiGetlink;

	mw.watchstrength.overlay = {

		/**
		 * @param newCount formatted count
		 * @param rawCount unformatted count
		 */
		// updateCount: function ( newCount, rawCount ) {
			// var $badge = $( '.mw-echo-notifications-badge' );
			// $badge.text( newCount );

			// if ( rawCount !== '0' && rawCount !== 0 ) {
				// $badge.addClass( 'mw-echo-unread-notifications' );
			// } else {
				// $badge.removeClass( 'mw-echo-unread-notifications' );
			// }
		// },

		// configuration: mw.config.get( 'wgEchoOverlayConfiguration' ),

		removeOverlay: function () {
			$( '.mw-watchstrength-overlay, .mw-watchstrength-overlay-pokey' ).fadeOut( 'fast',
				function () { $( this ).remove(); }
			);
		},

		buildOverlay: function () {
		
			var $overlay = $( '<div>' ).addClass( 'mw-watchstrength-overlay' ),
				$header = this.buildOverlayHeader(),
				$content = this.buildOverlayContent(),
				$footer = this.buildOverlayFooter(),
				watchesLimit = this.getMaxWatchesShown();
			
		},
		
		buildOverlayHeader : function () {
			var $header = $( '<div>' ).addClass( 'mw-watchstrength-overlay-header' );

			$header.append(
				$( '<div>' )
					.attr( 'id', 'mw-watchstrength-overlay-title-text' )
					.html( mw.msg( 'watchstrength-overlay-title' ) )
			);
			
			return $header;
		
		},
		
		buildOverlayContent : function () {
			var $content = $( '<ul>' ).addClass( 'mw-watchstrength-overlay-content' );
			
			
		},
		
		buildOverlayFooter : function () {
		
			var $footer = $( '<div>' )
				.addClass( 'mw-watchstrength-overlay-footer' )
				.attr( 'id', 'mw-watchstrength-overlay-footer' );
			
			// add link to Special:Watchlist
			$footer.append(
				$( '<a>' )
					.attr( 'id', 'mw-watchstrength-overlay-link' )
					.addClass( 'mw-watchstrength-grey-link' )
					.attr( 'href', getUrl( 'Special:Watchlist' ) )
					.text( mw.msg( 'watchstrength-overlay-watchlist-link' ) )
					.hover(
						function() {
							$( this ).removeClass( 'mw-watchstrength-grey-link' );
						},
						function() {
							$( this ).addClass( 'mw-watchstrength-grey-link' );
						}
					)
			);

			return $footer; 
			
		},
		
		getMaxWatchesShown : function () {
		
			// Set the max number of watched pages to show based on height of the window		
			var watchesLimit = Math.floor( ( $( window ).height() - 134 ) / 90 );

			if ( watchesLimit < 1 ) {
				watchesLimit = 1;
			} else if ( watchesLimit > 8 ) {
				watchesLimit = 8;
			}
			
			return watchesLimit;
		},
		
		
		
		
		
		
		
		
		
		
		
		
		
		buildOverlay: function ( callback ) {
			var notificationLimit,
				$overlay = $( '<div>' ).addClass( 'mw-watchstrength-overlay' ),
				$prefLink = $( '#pt-watchlist a' ),
				count = 0;


			

			(function () { // this doesn't really need to be a an IIFE...
			
				var watches = ['?'];
				
				var $title = $( '<div>' ).addClass( 'mw-watchstrength-overlay-title' ),
					$ul = $( '<ul>' ).addClass( 'mw-watchstrength-notifications' ),
					titleText,
					overflow,
					$overlayFooter;


				
				$ul.css( 'max-height', watchesLimit * 95 + 'px' );
				$.each( notifications.index, function ( index, id ) {
					var $wrapper,
						data = notifications.list[id],
						$li = $( '<li>' )
							.addClass( 'mw-watchstrength-notification' );

					// $li.append( data['*'] )
						// .appendTo( $ul );

					// Grey links in the notification title and footer (except on hover)
					$li.find( '.mw-watchstrength-title a, .mw-watchstrength-notification-footer a' )
						.addClass( 'mw-watchstrength-grey-link' );
					$li.hover(
						function() {
							$( this ).find( '.mw-watchstrength-title a' ).removeClass( 'mw-watchstrength-grey-link' );
						},
						function() {
							$( this ).find( '.mw-watchstrength-title a' ).addClass( 'mw-watchstrength-grey-link' );
						}
					);
					
					// If there is a primary link, make the entire notification clickable.
					// Yes, it is possible to nest <a> tags via DOM manipulation,
					// and it works like one would expect.
					if ( $li.find( '.mw-echo-notification-primary-link' ).length ) {
						$wrapper = $( '<a>' )
							.addClass( 'mw-echo-notification-wrapper' )
							.attr( 'href', $li.find( '.mw-echo-notification-primary-link' ).attr( 'href' ) );
							// .click( function() {
								// if ( mw.echo.clickThroughEnabled ) {
									// // Log the clickthrough
									// mw.echo.logInteraction( 'notification-link-click', 'flyout', +data.id, data.type );
								// }
							// } );
					} else {
						$wrapper = $('<div>').addClass( 'mw-echo-notification-wrapper' );
					}

					$li.wrapInner( $wrapper );

					// mw.echo.setupNotificationLogging( $li, 'flyout' );

					// Set up each individual notification with a close box and dismiss
					// interface if it is dismissable.
					if ( $li.find( '.mw-echo-dismiss' ).length ) {
						mw.echo.setUpDismissability( $li );
					}
				} );

				if ( notifications.index.length > 0 ) {
					if ( unreadRawTotalCount > unread.length ) {
						titleText = mw.msg(
							'echo-overlay-title-overflow',
							mw.language.convertNumber( unread.length ),
							mw.language.convertNumber( unreadTotalCount )
						);
						overflow = true;
					} else {
						titleText = mw.msg( 'echo-overlay-title' );
						overflow = false;
					}
				} else {
					titleText = mw.msg( 'echo-none' );
				}

				
				if ( $ul.find( 'li' ).length ) {
					$ul.appendTo( $overlay );
				}

				callback( $overlay );

			} );
		}
	};

	$( function () {
		var $link = $( '#pt-notifications a' );
		if ( ! $link.length ) {
			return;
		}

		$link.click( function ( e ) {
			var $target;

			// log the badge click
			mw.echo.logInteraction( 'ui-badge-link-click' );

			e.preventDefault();

			$target = $( e.target );
			// If the user clicked on the overlay or any child, ignore the click
			if ( $target.hasClass( 'mw-echo-overlay' ) || $target.is( '.mw-echo-overlay *' ) ) {
				return;
			}

			if ( $( '.mw-echo-overlay' ).length ) {
				mw.echo.overlay.removeOverlay();
				return;
			}

			mw.echo.overlay.buildOverlay(
				function ( $overlay ) {
					$overlay
						.hide()
						.appendTo( $( '#pt-notifications' ) );

					// Create the pokey (aka chevron)
					$overlay.before( $( '<div>' ).addClass( 'mw-echo-overlay-pokey' ) );

					mw.hook( 'ext.echo.overlay.beforeShowingOverlay' ).fire( $overlay );

					// Show the notifications overlay
					$overlay.show();

					// Make sure the overlay is visible, even if the badge is near the edge of browser window.
					// 10 is an arbitrarily chosen "close enough" number.
					// We are careful not to slide out from below the pokey (which is 21px wide) (200-21/2+1 == 189)
					var
						offset = $overlay.offset(),
						width = $overlay.width(),
						windowWidth = $( window ).width();
					if ( offset.left < 10 ) {
						$overlay.css( 'left', '+=' + Math.min( 189, 10 - offset.left ) );
					} else if ( offset.left + width > windowWidth - 10 ) {
						$overlay.css( 'left', '-=' + Math.min( 189, ( offset.left + width ) - ( windowWidth - 10 ) ) );
					}
				}
			);
		} );

		$( 'body' ).click( function ( e ) {
			if ( ! $( e.target ).is( '.mw-echo-overlay, .mw-echo-overlay *, .mw-echo-overlay-pokey, #pt-notifications a' ) ) {
				mw.echo.overlay.removeOverlay();
			}
		} );

		// Closes the notifications overlay when ESC key pressed
		$( document ).on( 'keydown', function ( e ) {
			if ( e.which === 27 ) {
				mw.echo.overlay.removeOverlay();
			}
		} );

	} );
} )( jQuery, mediaWiki );
