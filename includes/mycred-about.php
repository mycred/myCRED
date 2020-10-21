<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED About Page Header
 * @since 1.3.2
 * @version 1.3
 */
function mycred_about_header() {

	$name = mycred_label();

?>
<style type="text/css">
#mycred-badge { background: url('<?php echo plugins_url( 'assets/images/badge.png', myCRED_THIS ); ?>') no-repeat center center; background-size: 140px 160px; }.dashboard_page_mycred-about #wpwrap {background-color: white;}#wpwrap #mycred-about a {color: #666;background-color: #EBEBEB;padding: 10px;text-decoration: none;border-radius: 5px;}
</style>
<h1><?php printf( __( 'Welcome to %s %s', 'mycred' ), $name, myCRED_VERSION ); ?></h1>
<div class="about-text"><?php printf( 'An adaptive points management system for WordPress powered websites.', $name ); ?></div>
<p><?php printf( __( 'Thank you for using %s. If you have a moment, please leave a %s.', 'mycred' ), $name, sprintf( '<a href="https://wordpress.org/support/plugin/mycred/reviews/?rate=5#new-post" target="_blank">%s</a>', __( 'review', 'mycred' ) ) ); ?></p>
<div class="wp-badge" id="mycred-badge">Version <?php echo myCRED_VERSION; ?></div>
<h2 class="nav-tab-wrapper wp-clearfix">
	<a class="nav-tab nav-tab-active" href="#">What&#8217;s New</a>
	<a class="nav-tab" href="http://codex.mycred.me" target="_blank">Documentation</a>
	<a class="nav-tab" href="https://mycred.me/store/" target="_blank">Store</a>
</h2>
<?php

}

/**
 * myCRED About Page Footer
 * @since 1.3.2
 * @version 1.1
 */
function mycred_about_footer() {

?>
	<p style="text-align: center;">A big Thank You to everyone who helped support myCRED!</p>
	<p>&nbsp;</p>
	<div id="social-media">
		<a href="//plus.google.com/102981932999764129220?prsrc=3" rel="publisher" style="text-decoration:none;float: left; margin-right: 12px;">
<img src="//ssl.gstatic.com/images/icons/gplus-32.png" alt="Google+" style="border:0;width:24px;height:24px;"/></a><div class="fb-like" data-href="https://www.facebook.com/myCRED" data-height="32" data-colorscheme="light" data-layout="standard" data-action="like" data-show-faces="false" data-send="false" style="display:inline;"></div>
	</div>
	<div id="fb-root"></div>
<script>
(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "https://connect.facebook.net/en_US/all.js#xfbml=1&appId=283161791819752";
  fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));
</script>
<?php

}

/**
 * About myCRED Page
 * @since 1.3.2
 * @version 1.3
 */
function mycred_about_page() {

?>
<div class="wrap about-wrap" id="mycred-about-wrap">

	<?php mycred_about_header(); ?>

	<div id="mycred-about">
		<h2>Improved Management Tools</h2>
		<div class="feature-section two-col">
			<div class="col">
				<img src="<?php echo plugins_url( 'assets/images/mycred16-stats-addon.png', myCRED_THIS ); ?>" alt="" />			
			</div>
			<div class="col">
				<h3>Statistics 2.0</h3>
				<p>The Statistics add-on has received a complete re-write in order to add support for showing charts and statistical data on the front end of your website. The add-on comes with pre-set types of data that you can select to show either as a table or using charts (or both).</p>								
				<a href="https://mycred.me/guides/1-8-guide-statistics-add-on/">Documentation</a>
			</div>
			<div class="col">
				<h3>New BuyCred Checkout</h3>
				<p>One of the most requested features for buyCRED has been to making the checkout process easier to customize, so the checkout process has been completely re-written. You can now override the built-in template via your theme and style or customize the checkout page anyway you like.</p>
				<p><a href="https://mycred.me/guides/1-8-guide-buycred-add-on-updates/">Documentation</a></p>
			</div>
			<div class="col">	
				<img src="<?php echo plugins_url( 'assets/images/buycred-checkout-page.png', myCRED_THIS ); ?>" alt="" />							
			</div>
		</div>
		<hr />
		<h2>Add-on Improvements</h2>
		<div class="feature-section two-col">
			<div class="col">
				<h3>Ranks</h3>
				<p>As of version 1.8, ranks can be set to be assigned to users manually, just like badges. This means that you will need to manually change your users rank as myCRED will take no action. To do this, simply edit the user in question in the admin area and select the rank you want to assign them.</p>
			</div>
			<div class="col">
				<h3>Email Notifications</h3>
				<p>The email notifications add-on now supports setting up emails for specific instances based on reference.</p>
			</div>
		</div>
		<hr />
		<h2>New Shortcodes</h2>
		<div class="feature-section three-col">
			<div class="col">
				<h3><code>[mycred_chart_circulation]</code></h3>
				<p>This shortcode will render charts based on the amount of points that currently exists amongst your users for each point type.</p>
			</div>
			<div class="col">
				<h3><code>[mycred_chart_gain_loss]</code></h3>
				<p>This shortcode will render charts based on the amount of points that has been given to users vs. the total amount taken.</p>
			</div>
			<div class="col">
				<h3><code>[mycred_chart_top_balances]</code></h3>
				<p>This shortcode will render a list of balances ordered by size.</p>
			</div>
		</div>
		<div class="feature-section one-col">
			<p style="text-align: center;"><a href="https://mycred.me/support/changelog/" target="_blank">View All Changes</a></p>
		</div>
		<hr />
	</div>

	<?php mycred_about_footer(); ?>

</div>
<?php

}
