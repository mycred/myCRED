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
#mycred-badge { background: url('<?php echo plugins_url( 'assets/images/badge.png', myCRED_THIS ); ?>') no-repeat center center; background-size: 140px 160px; }
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
				<img src="<?php echo plugins_url( 'assets/images/mycred-about-balance.png', myCRED_THIS ); ?>" alt="" />
				<h3>New Balance & Log Editor</h3>
				<p>The new balance editor allows you to adjust your users balances and add a log entry with your adjustment. You can now also see a preview of the users latest log entries.</p>
			</div>
			<div class="col">
				<img src="<?php echo plugins_url( 'assets/images/mycred-about-hooks.png', myCRED_THIS ); ?>" alt="" />
				<h3>Manage Hooks like Widgets</h3>
				<p>The hook management page has been re-designed to work and look just like the widget editor in WordPress. Activating  or deactivating a hook is now a matter of drag and drop.</p>
			</div>
		</div>
		<hr />
		<h2>Add-on Improvements</h2>
		<div class="feature-section two-col">
			<div class="col">
				<h3>Sell Content 2.0</h3>
				<p>As of version 1.7, the sell content add-on supports sales using multiple point types! You can furthermore also set content for sale by default based on post type, category or tags.</p>
			</div>
			<div class="col">
				<h3>Badges 1.2</h3>
				<p>The badge editor has been completely re-written to make badge creations easier. Badges can now also have multiple requirements and you can reward users for gaining a badge.</p>
			</div>
		</div>
		<hr />
		<h2>New Shortcodes</h2>
		<div class="feature-section three-col">
			<div class="col">
				<h3><code>[mycred_show_if]</code></h3>
				<p>This shortcode can be used to wrap around content that you want to show only to those who have a certain balance and / or rank.</p>
			</div>
			<div class="col">
				<h3><code>[mycred_hide_if]</code></h3>
				<p>This is the polar opposite of the mycred_show_if shortcode.</p>
			</div>
			<div class="col">
				<h3><code>[mycred_total_since]</code></h3>
				<p>Show your users the total number of points they have gained or lost, since a given time period e.g. this month or today.</p>
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
