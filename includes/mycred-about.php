<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED About Page Header
 * @since 1.3.2
 * @version 1.2
 */
function mycred_about_header() {

	$new = $credit = '';
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'mycred-credit' )
		$credit = ' nav-tab-active';
	else
		$new = ' nav-tab-active';

	$name         = mycred_label();
	$index_php    = admin_url( 'index.php' );
	$about_page   = esc_url( add_query_arg( array( 'page' => MYCRED_SLUG . '-about' ), $index_php ) );
	$credit_page  = esc_url( add_query_arg( array( 'page' => MYCRED_SLUG . '-credit' ), $index_php ) );

	$admin_php    = admin_url( 'admin.php' );
	$log_url      = esc_url( add_query_arg( array( 'page' => MYCRED_SLUG ), $admin_php ) );
	$hook_url     = esc_url( add_query_arg( array( 'page' => MYCRED_SLUG . '-hooks' ), $admin_php ) );
	$addons_url   = esc_url( add_query_arg( array( 'page' => MYCRED_SLUG . '-addons' ), $admin_php ) );
	$settings_url = esc_url( add_query_arg( array( 'page' => MYCRED_SLUG . '-settings' ), $admin_php ) );

?>
<style type="text/css">
#mycred-badge { background: url('<?php echo plugins_url( 'assets/images/badge.png', myCRED_THIS ); ?>') no-repeat center center; background-size: 140px 160px; }
</style>
<h1><?php printf( __( 'Welcome to %s %s', 'mycred' ), $name, myCRED_VERSION ); ?></h1>
<div class="about-text"><?php printf( 'An adaptive points management system for WordPress powered websites.', $name ); ?></div>
<p class="mycred-actions">
	<a href="<?php echo $log_url; ?>" class="button">Log</a>
	<a href="<?php echo $hook_url; ?>" class="button">Hooks</a>
	<a href="<?php echo $addons_url; ?>" class="button">Add-ons</a>
	<a href="<?php echo $settings_url; ?>" class="button button-primary">Settings</a>
</p>
<div class="wp-badge" id="mycred-badge">Version <?php echo myCRED_VERSION; ?></div>

<h2 class="nav-tab-wrapper wp-clearfix">
	<a class="nav-tab<?php echo $new; ?>" href="<?php echo $about_page; ?>">What&#8217;s New</a>
	<a class="nav-tab<?php echo $credit; ?>" href="<?php echo $credit_page; ?>">Credits</a>
	<a class="nav-tab" href="http://mycred.me/documentation/" target="_blank">Documentation</a>
	<a class="nav-tab" href="http://codex.mycred.me" target="_blank">Codex</a>
	<a class="nav-tab" href="http://mycred.me/support/forums/" target="_blank">Support Forum</a>
	<a class="nav-tab" href="http://mycred.me/store/" target="_blank">Store</a>
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
 * @version 1.2
 */
function mycred_about_page() {

	$mycred       = mycred();
	$settings_url = esc_url( add_query_arg( array( 'page' => '-settings' ), admin_url( 'admin.php' ) ) );

?>
<div class="wrap about-wrap" id="mycred-about-wrap">

	<?php mycred_about_header(); ?>

	<div id="mycred-about">
		<div class="feature-section two-col">
			<h2>Improved Management Tools</h2>
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
		<div class="feature-section two-col">
			<h2>Add-on Improvements</h2>
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
		<div class="feature-section three-col">
			<h2>New Shortcodes</h2>
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
			<p style="text-align: center;"><a href="http://mycred.me/support/changelog/" target="_blank">View All Changes</a></p>
		</div>
		<hr />
	</div>

	<?php mycred_about_footer(); ?>

</div>
<?php

}

/**
 * myCRED Credit Page
 * @since 1.3.2
 * @version 1.7
 */
function mycred_about_credit_page() {

?>
<div class="wrap about-wrap" id="mycred-credit-wrap">

	<?php mycred_about_header(); ?>

	<div id="mycred-about">
		<div class="feature-section two-col">
			<h2>Awesome People!</h2>
			<div class="col">
				<h3>Bug Finders</h3>
				<ul>
					<li><a href="http://mycred.me/community/innergy4every1/">innergy4every1</a></li>
					<li><a href="http://mycred.me/community/kristoff/">Kristoff</a></li>
					<li><a href="http://mycred.me/community/colson/">colson</a></li>
					<li><a href="http://mycred.me/community/Martin/">Martin</a></li>
					<li><a href="http://mycred.me/community/orousal/">Orousal</a></li>
					<li><a href="http://mycred.me/community/joseph/">Joseph</a></li>
					<li>Maria Campbell</li>
				</ul>
			</div>
			<div class="col">
				<h3>Translators</h3>
				<ul>
					<li><a href="http://bp-fr.net/">Dan</a> <em>( French )</em></li>
					<li>Mani Akhtar <em>( Persian )</em></li>
					<li><a href="http://robertrowshan.com/">Robert Rowshan</a> <em>( Spanish )</em></li>
					<li>Skladchik <em>( Russian )</em></li>
					<li><a href="http://coolwp.com">suifengtec</a> <em>( Chinese )</em></li>
					<li>Guilherme <em>( Portuguese - Brazil )</em></li>
					<li>Mochizuki Hiroshi <em>( Japanese )</em></li>
					<li><a href="http://www.merovingi.com/">Gabriel S Merovingi</a> <em>( Swedish )</em></li>
				</ul>
			</div>
		</div>
		<hr />
		<div class="feature-section two-col">
			<h2>Join the myCRED Community</h2>
			<div class="col">
				<h3>Earn Tokens</h3>
				<p>Helping translating myCRED, report bugs / solutions or helping out in the support forum will earn you myCRED Tokens which you can use in the store as payment. Signup for a <a href="http://mycred.me/community/access/#signup" target="_blank">free account</a> today!</p>
			</div>
			<div class="col">
				<h3>Premium Add-ons</h3>
				<p>Community members gain access to the <a href="http://mycred.me/store/" target="_blank">myCRED store</a> where you can purchase premium add-ons to further expand myCRED.</p>
			</div>
		</div>
	</div>

	<?php mycred_about_footer(); ?>

</div>
<?php

}

?>