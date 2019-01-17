=== myCRED ===
Contributors: designbymerovingi
Tags: point, points, tokens, credit, management, reward, charge, community, contest, buddypress, jetpack, bbpress, simple press, woocommerce, wp e-commerce, contact-form-7
Requires at least: 4.0
Tested up to: 4.7.3
Stable tag: 1.7.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An adaptive and powerful points management system for WordPress powered websites.

== Description ==

> #### Plugin Support
> Free support is offered via the [myCRED website](https://mycred.me/support/) Monday to Friday 9AM - 5PM (GMT+1). You can consult the [online community](https://mycred.me/support/forums/) for customisations or open a [free support ticket](https://mycred.me/support/) if you can not get myCRED to work as described in the documentation. No support is provided here on the wordpress.org support forum or on any social media account.


myCRED is an adaptive points management system that lets you build a broad range of point related applications for your WordPress powered website.
Store reward systems, community leaderboards, online banking or monetizing your websites content, these are some of the ways you can use myCRED.


= Points =

Each user on your WordPress websites gets their own point balance which you can manually [adjust](https://mycred.me/about/features/#points-management) at any time. As of version 1.4, myCRED also supports [multiple point types](https://mycred.me/about/features/#multiple-point-types) for those who need more then one type of points on their website.


= Log =

Each time myCRED adds or deducts points from a user, the adjustment is [logged](https://mycred.me/about/features/#account-history) in a dedicated log, allowing your users to browse their history. The log is also used to provide you with statistics, accountability, badges, ranks and to enforce limits you might set.


= Awarding or Deducting Points Automatically =

myCRED supports a vast set of ways you can automatically give / take points from a user. Everything from new comments to store purchases. These automatic adjustments are managed by so called [Hooks](https://mycred.me/about/features/#automatic-points) which you can setup in your admin area.


= Third-party plugin Support =

myCRED supports some of the most [popular plugins](https://mycred.me/about/supported-plugins/) for WordPress like BuddyPress, WooCommerce, Jetpack, Contact Form 7 etc. To prevent to much cluttering in the admin area with settings, myCRED will only show features/settings for third-party plugins that are installed and enabled.


= Add-ons =

There is so much more to myCRED then just adjusting balances. The plugin comes with several [built-in add-ons](https://mycred.me/add-ons/) which enabled more complex features such as allowing point transfers, buying points for real money, allow payments in stores etc.


= Documentation =

You can find extensive [documentation](http://codex.mycred.me/) on everything myCRED related in the myCRED Codex. You can also find a list of [frequently asked](https://mycred.me/about/faq/) questions on the myCRED website.


= Customizations =

myCRED was not built to "do-it-all". Instead a lot of effort has been made to make the plugin as developer friendly as possible. If you need a custom feature built, you can submit a [request for a quote](https://mycred.me/customize/request-quote/) via the myCRED website.


= Code Snippets =

The most commonly asked customizations for myCRED are available as code snippets on the [myCRED website](https://mycred.me/code-snippets/), free to use by anyone.


= Support =

I provide [free support](https://mycred.me/support/) if you can not get myCRED to work as described in the documentation, and pay myCRED Store Tokens as a reward for reporting bugs and/or bug fixes. There is also a [community forum](https://mycred.me/support/forums/) where you can post your questions or [contact me directly](https://mycred.me/contact/).


== Installation ==

= myCRED Guides =

[Chapter I - Introduction](http://codex.mycred.me/chapter-i/)

[Chapter II - Getting Started](http://codex.mycred.me/chapter-ii/)

[Chapter III - Add-ons](http://codex.mycred.me/chapter-iii/)

[Chapter IV - Premium Add-ons](http://codex.mycred.me/chapter-iv/)

[Chapter V - For Developers](http://codex.mycred.me/chapter-v/)

[Chapter VI - Reference Guides](http://codex.mycred.me/chapter-vi/)


== Frequently Asked Questions ==

You can find a list of [frequently asked questions](https://mycred.me/about/faq/) on the myCRED website.


== Screenshots ==

1. **Add-ons** - Add-ons are managed just like themes in WordPress.
2. **Edit Balances** - Administrators can edit any users balance at any time via the Users page in the admin area.
3. **Hooks** - Hooks are managed just like widgets in WordPress.
4. **Edit Log Entries** - Administrators can edit any log entry at any time via the admin area.


== Upgrade Notice ==

= 1.7.7 =
Important bug fixes.


== Other Notes ==

= Requirements =
* WordPress 4.0 or greater
* PHP version 5.3 or greater
* PHP mcrypt library enabled
* MySQL version 5.0 or greater

= Language Contributors =
* Swedish - Gabriel S Merovingi
* French - Chouf1 [Dan - BuddyPress France](http://bp-fr.net/)
* Persian - Mani Akhtar
* Spanish - Robert Rowshan [Website](http://robertrowshan.com)
* Russian - Skladchik
* Chinese - suifengtec [Website](http://coolwp.com)
* Portuguese (Brazil) - Guilherme
* Japanese - Mochizuki Hiroshi


== Changelog ==

= 1.7.7 =
FIX - When deleting a log entry it is no longer animated and visually removed.
FIX - Displaying users badges that no longer exists cause fatal error.
FIX - Video hook ajax url fails to load.
FIX - mycred_sell_this shortcode causes the entire content to be hidden.
FIX - Fixed array to string conversion error in Statistics add-on.
FIX - Currency symbol missing in WooCommerce when points is selected as the store currency.
FIX - When the Transfer add-on is set to only allow transfers of 1 custom point type and not the default point type, the transfer shortcode will show a "over limit" error.
FIX - Sell Content add-on can in certain situations produce a PHP notice until the add-ons settings are saved after an update.
TWEAK - Mixed up the member and visitor default template values.
TWEAK - The mycred_types_select_from_checkboxes() function is not rendering checkboxes and labels correctly. Also removed break line element.
NEW - Added new mycred_get_addon_settings() function to make add-on settings retrieval easier.


= Previous Versions =
https://mycred.me/support/changelog/