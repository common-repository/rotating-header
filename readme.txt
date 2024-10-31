=== Rotating Header ===
Contributors: taf2
Donate link: http://captico.com/
Tags: header, images, dynamic lead, rotating header
Requires at least: 3.0.0
Tested up to: 3.0.4
Stable tag: 0.6

Rotating Header plugin

== Description ==

Works similar to the Wordpress 3.x header image but instead allows you to place in your theme a set of header images that rotate using
either a sliding effect or a crossfade effect.


== Installation ==

To use this plugin you need to add a function in your theme and possibly add some additional CSS.
Here is an example of how we use it for many of our client websites.
`<?php if (function_exists('rotating_header_draw')) {
    rotating_header_draw();
} else { ?>
  <div id="splash"></div>
<?php } ?>`
Where #splash is a fallback to the built in Wordpress theme "custom header" feature
The block of code we would either put into a custom home page template or wrap in a call to is_front_page.

= Features =

* Easily configure home page graphics
* Change dynamic graphic order via drag and drop
* Change link text and add custom HTML
* Upload and crop graphics using a fixed aspect ratio

== Changelog ==

= 0.6 =
* Add admin interface for changing effect between slider and crossfade
* Change interface only rotating_header_draw should be called no more scripts

= 0.5 =
* IE 8 and below need to have the hidden panels use visibility: hidden

= 0.4 =
* Remove short tags

= 0.3 =
* Work on making this a releasable plugin

= 0.2 =
* Add support for cross fade - requires manual configuration

= 0.1 =
* Initial release, support sliding
