# Changelog
All notable changes to this project will be documented in this file.

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

## 1.0

* Initial creation
