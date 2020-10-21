=== myCRED ===
Contributors: mycred,wpexpertsio
Tags: point, credit, loyalty program, engagement, reward
Requires at least: 4.8
Tested up to: 5.1
Stable tag: 1.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An adaptive and powerful points management system for WordPress powered websites.

== Description ==

myCRED is an adaptive points management system that lets you build a broad range of point related applications for your WordPress powered website.
Store reward systems, community leaderboards, online banking or monetizing your websites content, are a few examples of the ways myCRED is used.

= Points =

Each user on your WordPress websites gets their own point balance which you can manually [adjust](https://mycred.me/about/features/#points-management) at any time. You can use just one point balance or setup multiple types of balances. How these balances are accessed, used or shows is entirely up to you.


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

Support is offered on our [myCRED website](https://mycred.me/support/)  from Monday to Friday 9AM - 5PM (GMT+5). Submit [customization request](https://mycred.me/customize/request-quote/) or open a [support ticket](https://mycred.me/support/) If you have trouble with myCRED which is not described in documentation also you can consult the [online community](https://mycred.me/support/forums/) for your question. We pay myCRED Store Tokens as a reward on reporting bugs and their fixes as well. Support is not entertained here on the wordpress.org support forum or on any social media account. 


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


= 1.7.9.8 =
Bug fixes.


== Other Notes ==

= Requirements =
* WordPress 4.5 or greater
* PHP version 5.6 or greater
* PHP mcrypt library enabled
* MySQL version 5.0 or greater

= Language Contributors =
* Swedish - Gabriel S Merovingi
* French - Chouf1 [Dan - BuddyPress France](http://bp-fr.net/)
* Persian - Mani Akhtar
* Spanish - Jose Maria Bescos [Website](http://www.ibidem-translations.com/spanish.php)
* Russian - Skladchik
* Chinese - suifengtec [Website](http://coolwp.com)
* Portuguese (Brazil) - Guilherme
* Japanese - Mochizuki Hiroshi



== Changelog ==

= 1.8 =
NEW - Added new mycred_over_hook_limit filter for adjusting hook limit checks.
NEW - Added new MYCRED_RANK_KEY constant which can be used to whitelabel ranks.
NEW - Added new MYCRED_COUPON_KEY constant which can be used to whitelabel coupons.
NEW - Added new MYCRED_BADGE_KEY constant which can be used to whitelabel badges.
NEW - Added new MYCRED_EMAIL_KEY constant with can be used to whitelabel email notifications.
NEW - Added new MYCRED_BUY_KEY constant with can be used to whitelabel pending buyCRED payments.
NEW - Added new MYCRED_ENABLE_SHORTCODES constant in cases where myCRED shortcodes needs to be disabled.
NEW - Updated the Email Notifications add-on to version 1.4 with support for custom instances, multiple point types / notice and introduced the new myCRED_Email object.
NEW - Updated the buyCRED add-on which now has improved checkout process. 
NEW - Added the option to set a custom gateway logo for all built-in payment gateways.
NEW - Updated the mycred_load_coupon shortcode to show an error message when an invalid coupon is used.
NEW - Added new Anniversary hook allowing you to reward users for each year they are a member on your website.
NEW - Added new MYCRED_ENABLE_HOOKS constant to disable hooks completely.
NEW - Added support for Multi Network setups.
NEW - Added new mycred_add_post_meta(), mycred_get_post_meta(), mycred_update_post_meta() and mycred_delete_post_meta() functions in order to add support for the Master Template feature on multisites.
NEW - Added support for multiple point types in leaderboards.
NEW - The leaderboard shortcode can now be setup to render results based on multiple point types.
NEW - Added caching of log and leaderboard queries.
NEW - Added new filter to allow adjustments to the reference used for publishing and deleting content hooks.
NEW - Added new mycred_give_run filter to control if the mycred_give shortcode should run or not.
TWEAK - Moved hooks to /includes/hooks/ and third-party hooks to /includes/hooks/external/.
TWEAK - Implemented the use of $mycred_log_table global throughout the plugin.
TWEAK - Improved Multisite support.
TWEAK - When a user jumps more than one badge level in a single instance, we want to make sure he gets rewarded for each level (if rewards is set).
TWEAK - Corrected codex urls for functions and shortcodes throughout the plugin.
TWEAK - Added support to whitelabel shortcodes.
TWEAK - Added new MYCRED_SHOW_PREMIUM_ADDONS constant to hide all mentions of premium add-ons in myCRED.
TWEAK - BuddyPress fixed issue related to points ignoring limit on adding to favorites
TWEAK - Optimized search the search for log entries
TWEAK - issue related to email not getting send on transfer in and out triggers in transfer addon
TWEAK - Rank excerpt fix


= Previous Versions =
https://mycred.me/support/changelog/