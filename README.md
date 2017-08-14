# WP DFP Ads

**Authors:**      [Mike Estrada](https://bleucellar.com), Alex Delgado
**Tags:**         ads, DFP, cmb2
**License:**      GPLv2 or later
**License URI:**  http://www.gnu.org/licenses/gpl-2.0.html

## Description

WP DFP Ads makes Ad management of DFP ads as simple as post management.
Note: This plugin currently depends on the [CMB2](https://github.com/CMB2/CMB2) WordPress plugin. There are plans to make this independent but for now, CMB2 must be installed.

## Features:

* CPT for Ads
* Admin area under WP Settings to manage options
* Filters for several features

## Installation

1. Place the plugin directory inside of your plugins directory (typically /wp-content/plugins).
2. Activate plugin through the Plugins Admin page
3. Add ad calls to theme via `<?php echo class_exists('Wp_Dfp_Ads') ? Wp_Dfp_Ads::display_ad( 'leaderboard-top' ) : ''; ?>`.

## Changelog
All notable changes to this project will be documented here.

## 1.3 - 02-13-2017

### Enhancements
* Added Lazy-load column to Ads CPT admin table
* Appended an underscore to Advert metadata so they do not appear in Custom Fields
* Added Refresh feature
	* Ads inside the viewport refresh every 30 seconds if browser and tab are active
	* Feature can be turned on/off in Advertisements settings
	* Manageable per ad (can be excluded in edit ad view)

## 1.2 - 01-30-2017

### Enhancements
* Made it so the ad div gets the class 'advert-loaded' when it has been loaded.
* Added the following filters:
	* 'wp_dfp_ads_breakpoints' to overwrite the breakpoints in the admin area with your own, through code.
	* 'wp_dfp_ads_filter' to remove or alter ads before they are printed into the head of the document.
	* 'wp_dfp_ads_keywords' to alter keywords printed to the ad target.
	* 'wp_dfp_ads_inarticle' to filter the inarticle ad. Includes it's markup as first parameter. (Might want to alter markup for AMP or FBIA)

## 1.1 - 01-10-2017

### Enhancements
* Add Lazy-load ads

### Bug fixes
* Removed call to undefined function
* Switched JS window load to .on('load', ... ) instead of .load(...) to avoid console warnings

## 1.0

* Initial creation
