jQuery(document).ready(function($) {

	// Make shift jQuery plugin to check if the element is in the viewport
	// Note: only checks vertically, not horizontally (is that really necessary for us?)
	$.fn.isInViewport = function( options ) {

		// lets set up some defaults
		var defaults = {
			percentInView: 1,
		};

		// replace the defaults with options selected
		var settings = $.extend( {}, defaults, options );

		var $window			= $(window),
			$this			= $(this),
			elementTop		= $this.offset().top,
			elementHeight	= $this.outerHeight(),
			elementBottom	= elementTop + elementHeight,
			viewportTop		= $window.scrollTop(),
			viewportHeight	= $window.height(),
			viewportBottom	= viewportTop + viewportHeight;

		if ( settings.percentInView < 1 && settings.percentInView > 0 ) {

			var visibleTop		= elementTop < viewportTop ? viewportTop : elementTop,
				visibleBottom	= elementBottom > viewportBottom ? viewportBottom : elementBottom,
				pixelsViewable	= visibleBottom - visibleTop;

			return ( pixelsViewable / elementHeight) >= settings.percentInView;

		} else {
			// FULL element has to be in viewport
			return elementBottom < viewportBottom && elementTop > viewportTop;
		}
	};

	/////////////////////////////////////////
	// Main visibility API function
	// Check if current tab is active or not
	var vis = ( function() {

		var stateKey,
			eventKey,
			keys = {
				hidden: 'visibilitychange',
				webkitHidden: 'webkitvisibilitychange',
				mozHidden: 'mozvisibilitychange',
				msHidden: 'msvisibilitychang'
			};

		for ( stateKey in keys ) {

			if ( stateKey in document ) {
				eventKey = keys[stateKey];
				break;
			}
		}

		return function(c) {
			if (c) document.addEventListener( eventKey, c );
			return !document[stateKey];
		}

	} )();

	var $window	= $(window);

	$window.on('load', function(){

		// Initiate our counter and variable for timer once page loads
		// note: refresh_time defined in class file for front end (class-wp-dfp-ads.php)
		var adsRefreshInterval	= setInterval( initRefresh, refresh_time ),
			timer				= true,
			eventsFlag			= false; // Flags for our event listeners (Chrome is crazy)

		function initRefresh() {

			for ( var slot in refresh_slots ) {

				if ( !refresh_slots.hasOwnProperty(slot) ) continue;

				var slot_ID			= refresh_slots[slot].getSlotElementId(),
					isInViewport	= $('#'+slot_ID).isInViewport({ percentInView: 0.5 });

				if ( isInViewport ) {

					// Load the ad!
					googletag.cmd.push( function() {
						googletag.pubads().refresh( [refresh_slots[slot]] );
					});

				}

			}

		}

		function startTimer() {

			if ( timer === false ) {
				adsRefreshInterval	= setInterval( initRefresh, refresh_time );
				timer	= true;
			};

		}

		function endTimer() {

			clearInterval( adsRefreshInterval );
			timer	= false;

		}

		vis( function() {

			// First we have to check if we are in an active tab in the browser
			var visibility	= vis();

			// if not active tab, remove the timer
			if ( !visibility ) {

				endTimer();

			} else if ( visibility ) {

				startTimer();

			}

		} );

		// Now we also have to check if the browser is active at all by checking blur/focus
		// Trying to do this across as many browsers as possible
		var notIE		= ( document.documentMode === undefined ),
			isChromium	= window.chrome;

		if ( notIE && !isChromium && eventsFlag === false ) {

			$(window)
				.on( 'focus', startTimer )
				.on( 'blur', endTimer );

		} else {

			if ( window.addEventListener && eventsFlag === false ) {

				// bind focus event
				window.addEventListener( 'focus', startTimer, false );

				// bind blur event
				window.addEventListener( 'blur', endTimer, false );

				eventsFlag	= true;

			} else if ( eventsFlag === false ) {

				// bind focus event
				window.attachEvent( 'focus', startTimer );

				// bind blur event
				window.attachEvent( 'blur', endTimer );

				eventsFlag	= true;
			}

		}

	});

});
