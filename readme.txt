=== Dukagate ===
Contributors: rixed
Donate link: http://dukagate.info/
Tags: shopping cart, web shop, cart, shop,Paypal,paypal,Pesapal, e-commerce, ecommerce

Requires at least: 3.0
Tested up to: 3.5.2
Stable tag: 3.4.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Dukagate is an open source e-commerce solution built for Wordpress.

== Description ==

Dukagate is open source software that can be used to build online shops quickly and easily. Dukagate is built on top of Wordpress, a world class content management system. Dukagate is built to be both simple and elegant yet powerful and scalable.

Main Features:

* Everything is customizable
* You can sell tangible regular products;
* You can sell tangible products with selectable options (size, colour, etc);
* You can sell digital products;
* You can set widget products
* You can set up 'affiliate' products which redirect to other products you want to promte (affiliate marketing)
* Choose between a normal shop mode and a catalogue mode;
* Numerous payment processing options including Paypal and more on the way
* Ability to work with multiple currencies
* One-page checkout;
* A myriad of shipping processing options;
* Custom GUI (Graphical User Interface) for product management;
* Easy to translate into your own language

We noticed printable invoices arent there :( Working to get this up in the upcoming versions

There are still many more features we are working on that are yet to come

== Installation ==
Please bear with us as we do better documentation. As for now, this is what we have for version 1. Thank you

1. Upload the Dukagate folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Once ethe plugin is activated there will be an admin section where you can configure your cart and also a section to set up your products. 
4. Displaying products
	* Normal List. Use the shortcode [dg_display_products] to display your products on a page. Parameters are total (number to display), top (number to appear on the top), order (Product order), image_width (Product image width), image_height (product image height)
	* Grouped Products. Use the shortcode [dg_group_display] to display grouped products on a page as a list and [dg_group_grid] to display them as a grid. Parameters are : parent (parent ids to include), child (child ids to include)
5. Cart checkout page shortcode is [dg_display_cart_checkout]
6. Checkout link shortcode [dg_display_checkout_link] to just show a link to the checkout page
7. Thank you page shortcode is [dg_display_thankyou]

== Frequently Asked Questions ==

= Why is the make payment button not working? =

Nine out of ten times, this is because there is a javascript error somewhere on your site.  The first place to look is your theme - try and run Dukagate using the default WordPress theme to confirm if it is your theme that is failing you.

Another reason is usually the pdf folder inside of Dukagate. Please try make it writable

= Why doesn't Dukagate work for me?  It seems to work for everyone else =

No.  Nothing is wrong with you. :)

We test Dukagate on a large number of different server set-ups and envrionments and we are satisfied that it does work in these environments.  However, the number of different environments 'out there' is infinite and we cannot possibly test on every single environment.  If everything that you try fails to work, perharps you should move your site to one of the more common web hosts? 


== Screenshots ==
1. Order Log
2. Payment plugins settings
3. Settings Page
4. Mail Settings (Mail content)
5. Advanced Settings
6. Check Out settings
7. Product Management
8. Sample product with a widget product

== Changelog ==

= 1.0 =
Dukagate is brand new.  As such you won't be upgrading but joining our handsomely awesome family. We will be upgrading and fixes bugs as we improve the plugin

= 1.1 =
Some documenation on the shortcodes and set up. Still working on it

= 1.2 =
Fixed Pesapal bugs, need to work on the shopping cart ajax and finalise some payment options

= 2.0 =
Added shipping and removed language folder

= 2.1 =
Added new Icon and link to site

= 2.2 =
Fixed plugin loading of gateways

= 3.0 =
Adding new version

= 3.1 =
Fixing versioning

= 3.2 =
Fixed issue with adding to cart

= 3.3 =
Fixed issues on cart functions. At first it was all submitting shipping :(

= 3.4 =
Added Authorize.net payment gateway

= 3.4.1=
* Fixing checkout page settings. Added ability to show/hide images for products.
* Pesapal upgrade

= 3.4.2 =
Making Pesapal work

=3.4.3=
Pesapal Sandbox mode for tests and APN return url

=3.4.4=
Added option to modify text on products

= 3.4.5 =
Fixed ajax for non auth