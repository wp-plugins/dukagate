=== Dukagate ===
Contributors: rixeo
Donate link: http://dukagate.info/
Tags: shopping cart, web shop, cart, shop,Paypal,paypal,Pesapal, e-commerce, ecommerce, worldpay, mpesa, bank, kopokopo, KopoKopo

Requires at least: 3.0
Tested up to: 4.0
Stable tag: 3.7.4.1
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
* Printable, customizable PDF invoices


There are still many more features we are working on that are yet to come

== Installation ==

1. Upload the Dukagate folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Once the plugin is activated there will be an admin section where you can configure your cart and also a section to set up your products. 
4. To set up your shop, Please visit http://dukagate.info/documentation

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

= 3.7.4.1 =
* There was an issue with product templates. Reverted for now
* Added shortcode to show total items in cart

= 3.7.4 =
* Added tax. You can now set global product taxes
* Javascript internalization.
* CSV Order Export with date range picker

= 3.7.3 =
Fixes to Authorize.net gateway

= 3.7.2.9 =
Product content templates. No need to use a shortcode to display product details. Yo can also define your own product theme template.

= 3.7.2.8 =
Pesapal Call Back fix

= 3.7.2.7 =
Bug Fix - Order log saving user info. Invoice data and email data fix. 
Added functionality to delete orders

= 3.7.2.6 =
Bug Fix - Setting options for registering users and currency position

= 3.7.2.5 =
Currency converter cache. We have added a WordPress cache for the currency conversion incase the currency conversion API does not work.
We have also fixed some issues on user registration


= 3.7.2.4 =
Currency converter fix

= 3.7.2.3 =
Checkout fixes for paid items

= 3.7.2.2 =
Fix checkout page issue not loading custom fields

= 3.7.2.1 =
Fix to mobile payments

= 3.7.2 =
We have now made it easier to enable and disable payment gateways and also save all settings at once. This update however will require you to re-enter your payment gateway information.

= 3.7.1 =
* New payment gateways
* Some code fixes on pesapal IPN

= 3.7 =
* External directories. We now store files, invoices and uploads in the wp-content directory and not in the plugin directory.
* User registration. You can now register users and allow them to see their own order logs
* Bank and mobile money payment options
* Text domain loading
* Minor security changes


= 3.6.5 =
* Single menu for Dukagate - The plugin now uses a single menu with other sections tabed under the settings menu
* Security Fixes - We are working on some security fixes and we will release new versions as they are fixed


= 3.6.4 =
There was some html added that was still on test

= 3.6.3 =
* Digital File upload
* Digital file sending in mail on successful purchase

Working on text transaltion and code cleanup. Also expect variations in next version and a free Dukagate Theme

= 3.6.2 =
Fixed a bug where order logs were not being saved well

= 3.6.2 =
Fixed a bug where order logs were not being saved well

= 3.6 =
* Discount and coupon managment added. You can now create discount codes and use them as coupons
* Import and export of products. You can now import and export products to different Dukagate Settups.
* Shortcode cleanups

To come : Working on language translations and a Dukagate theme

= 3.5.2.1 =
Ajax javascript for product was missing

= 3.5.2 =
* Accidentally removed price. Added it back
* Added shortcode for product page [dg_display_product]

= 3.5.1 =
Fixed issue of product images not loading sometimes

= 3.5 =
* Fixed issue where shipping price was not being carried to the payment gateway
* Fixed CSS issues For WordPress 3.8
* Working on Product Variations to be released in next version

= 3.4.9.5 =
Author information

= 3.4.9.4 =
Shortcode for carts: dg_display_cart

= 3.4.9.3 =
Fixed url issue on mini cart widget

= 3.4.9.2 =
* Fixed issue with Area shipping plugin.
* Added Ajax update on all cart widgets. 
* Modified Pesapal to have custom checkout name. 


= 3.4.9.1 =
Afew bug fixes and versioning fix

= 3.4.9 =
Added a mini cart widget. Working on product variations
Added cash on delivery. Still working on more functions

= 3.4.8 =
Forgot some javascript :)

= 3.4.7 =
Added transaction graph over view on Wordpress dashboard

= 3.4.6 =
PDF invoices and invoice customizations. Read more here http://dukagate.info/documentation/invoices/

= 3.4.5 =
Fixed ajax for non auth

= 3.4.4 =
Added option to modify text on products

= 3.4.3 =
Pesapal Sandbox mode for tests and APN return url


= 3.4.2 =
Making Pesapal work

= 3.4.1 =
* Fixing checkout page settings. Added ability to show/hide images for products.
* Pesapal upgrade

= 3.4 =
Added Authorize.net payment gateway

= 3.3 =
Fixed issues on cart functions. At first it was all submitting shipping :(

= 3.2 =
Fixed issue with adding to cart

= 3.1 =
Fixing versioning

= 3.0 =
Adding new version

= 2.2 =
Fixed plugin loading of gateways

= 2.1 =
Added new Icon and link to site

= 2.0 =
Added shipping and removed language folder

= 1.2 =
Fixed Pesapal bugs, need to work on the shopping cart ajax and finalise some payment options

= 1.1 =
Some documenation on the shortcodes and set up. Still working on it

= 1.0 =
Dukagate is brand new.  As such you won't be upgrading but joining our handsomely awesome family. We will be upgrading and fixes bugs as we improve the plugin