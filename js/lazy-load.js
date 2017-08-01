jQuery(document).ready(function($) {

	var $window	= $(window);

	$window.load(function(){

		var adsLoaded	= [],
			adsToLoad	= [],
			throttle		= function(a,b){
				var c,d;
				return function(){
					var e =this,
						f =arguments,
						g =+new Date;

						if ( c && g < c + a ) {
							clearTimeout(d),
							d = setTimeout(
								function(){
									c = g, b.apply(e,f)
								}, a )
						} else {
							c = g, b.apply(e,f)
						}
				}
			};

		$window.on( 'scroll resize', throttle( 500, initAds ) );

		adsToLoad	= refresh_slots;

		initAds();

		function initAds() {

			adsToLoadArray	= Object.keys(adsToLoad);

			// if we've loaded all ads, break out
			if ( !adsToLoadArray.length ) return;

			var winScroll	= $window.scrollTop(),
				winHeight	= $window.height();

			for ( var name in adsToLoad ) {
				if (adsToLoad.hasOwnProperty(name) ) {
					var id				= adsToLoad[name].getSlotElementId(),
						$this			= $('#'+id);

					// For safety reasons,
					// Check that the element exists, if not continue
					// Someone might not define the proper ad logic in admin area and this could cause the JS to choke
					if ( !$this.length )
						continue;

					var offset_top		= $this.offset().top,
						outer_height	= $this.outerHeight();

					if ( offset_top - winScroll > winHeight || winScroll - offset_top - outer_height - winHeight > 0 )
						continue;

					// Load the ad!
					googletag.cmd.push( function() {
						googletag.pubads().refresh([adsToLoad[name]]);
					});

					// Add ad to adsLoaded object and remove from the adsToLoad object
					adsLoaded[name]	= adsToLoad[name];
					delete adsToLoad[name];

				}
			}

		}

	});

});
