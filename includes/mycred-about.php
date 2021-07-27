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
.mycred_about_container {
    max-width: 1000px;
    margin: 24px auto;
    clear: both;
}
.mycred_about_header {
    padding-top: 32px;
    margin-bottom: 32px;
    background-color: #9852f1;
    color: #322d2b;
    color: #f1f1f1;
}

.mycred_about_header-title {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 20vh;
    max-height: 16em;
    padding: 0px 32px 32px 32px;
    margin-bottom: 2em;
    text-align: center;
}

.mycred_about_header-title p {
    margin: 0;
    padding: 0;
    font-size: 4em;
    line-height: 1;
    font-weight: 500;
}

.mycred_about_header-title p span {
    display: block;
    font-size: 2em;
}

#mycred-badge { 
	background: url('<?php echo plugins_url( 'assets/images/mycred-icon.png', myCRED_THIS ); ?>') no-repeat center center; 
	background-size: cover;
	display: block;
    margin: auto;
    box-shadow: none;
}
.dashboard_page_mycred-about #wpwrap {
	background-color: white;
}
#wpwrap #mycred-about a {
	color: #666;
	background-color: #EBEBEB;
	padding: 10px;
	text-decoration: none;
	border-radius: 5px;
}

.mycred_about_section.is-feature {
    font-size: 1.6em;
    font-weight: 600;
    text-align: center;
}

.mycred_about_section p {
    font-size: 15px !important;
    line-height: 1.5;
    margin: 1em 0;
    text-align: justify;
}

.mycred_about_section.is-feature {
    padding: 32px;
}

.mycred_about_container .has-background-color {
    background-color: #f1e6ff;
    color: #322d2b;
}

.mycred_about_container h1 {
    margin: 0 0 1em;
    padding: 0;
    font-weight: 600;
    color: inherit;
}

.mycred_about_container h1, .mycred_about_container h2 {
    margin-top: 0;
    font-size: 1.4em;
    line-height: 1.4;
}

.mycred_about_section p {
    font-size: inherit;
    line-height: inherit;
    font-size: 15px;
}

.mycred_about_section.has-1-column {
    margin-left: auto;
    margin-right: auto;
    max-width: 50em;
}

.mycred_about_section .column {
    padding: 32px;
}


.mycred_about_container ul {
    list-style: inside;
}

.mycred_about_container .has-accent-background-color {
    background-color: #9852f1;
    color: #fff;
}

.mycred_about_container .has-accent-background-color h2 {
    color: #fff;
}

.mycred_about_section.has-2-columns, .mycred_about_section.has-3-columns, .mycred_about_section.has-4-columns {
    display: -ms-grid;
    display: grid;
}
.mycred_about_section .has-accent-background-color .column {
    padding: 32px;
}
.mycred-txt-center {
	text-align: center !important;
}
.mycred-change-log {
	padding: 32px;
	margin-top: 32px;
}

</style>


<div class="mycred_about_header">
	<div class="wp-badge" id="mycred-badge"></div>
	<div class="mycred_about_header-title">
		<p><?php printf( '%s <span>%s</span>', $name, myCRED_VERSION ); ?></span></p>
	</div>
</div>
<?php

}

/**
 * myCRED About Page Footer
 * @since 1.3.2
 * @version 1.1
 */
function mycred_about_footer() {

?>
	<p style="text-align: center;">A big Thank You to everyone who helped support myCred!</p>
	
<?php

}

/**
 * About myCRED Page
 * @since 1.3.2
 * @version 1.3
 */
function mycred_about_page() {

?>
<div class="wrap mycred_about_container" id="mycred-about-wrap">

	<?php 

	$name = mycred_label();

	mycred_about_header(); 

	?>

	<div class="mycred_about_section is-feature has-background-color">
		<h1><?php printf( __( 'Welcome to %s %s', 'mycred' ), $name, myCRED_VERSION ); ?></h1>
		<p class="mycred-txt-center">Introducing cashCred - An intelligent way to convert myCred points into real money.</p>
	</div>

	<div class="mycred_about_section has-1-column">
		<div class="column">
			<h2>cashCred</h2>
			<p><strong>We are introducing cashCred to the core of myCred as functionality that allows users to redeem myCred points for money. Moreover, cashCred can also perform the following actions:</strong></p>
			<ul>
				<li>Users can redeem myCred points for money.</li>
				<li>Allow multiple custom point types.</li>
				<li>Define exchange rates for each point type.</li>
				<li>Users can send a request to the admin for cash withdrawal.</li>
				<li>Approve or deny user requests for cash withdrawal.</li>
				<li>Write additional notes for users which will be displayed on the payment form.</li>
				<li>Define the currency code (USD, GBP, AUD, etc.) for the payment form.</li>
				<li>Display the cashCred module on the website using a shortcode.</li>
				<li>Set minimum or maximum restriction limits on point conversion requests.</li>
			</ul>
			<p>Allow users to create something that they can sell on your website and charge people with myCred points. It can be anything from adoptables to tutorials. Later, they can convert their earned myCred points into real money using cashCred.</p>

			<p>Similarly, users who earn points with actions or specific hooks can also exchange them for money.</p>
			
			<p>cashCred works perfectly with a reward system that engages users to perform activities that require user interaction (watching a video, filling out a survey, etc.) but instead of giving them cash, you reward them with points that can be encashed at any point in time.</p>


		</div>
	</div>


	<div class="mycred_about_section has-2-columns has-accent-background-color is-wider-right">
		<div class="column">
			<h2>myCred Central Deposit</h2>
			<p>myCred’s Central Deposit feature enables the admin to nominate any user account to become a ‘Central Deposit’ account, which then allows the selected user account to manage all transactions related to myCred points.</p>

			<p><strong>How does the Central Deposit feature work?</strong></p>
			<p>The Admin can assign the Central Deposit account functionality to any existing user so that it can control all in-out transactions. In the case the account balance reaches ZERO, no points will be paid out.</p>

			<p>Instead of creating points out of thin air, all payouts are made from a Central Deposit account. Any point a user spends or loses is deposited back into the Central Deposit account.</p>
		</div>
	</div>

	<div class="mycred_about_section has-background-color mycred-change-log">
		<h2>Change Log</h2>
		<ul>
			<li><strong>NEW</strong> - myCred CashCred.</li>
			<li><strong>NEW</strong> - Added filter 'mycred_link_click_amount'.</li>
			<li><strong>NEW</strong> - Added exclude attribute in myCred leaderboard.</li>
			<li><strong>FIX</strong> - PHP notices in rank addon.</li>
			<li><strong>FIX</strong> - Rewards points option not visible for other product type in woocommerce.</li>
			<li><strong>FIX</strong> - Erros in mycred_total_balance shortcode.</li>
			<li><strong>FIX</strong> - myCred logs export issue.</li>
			<li><strong>FIX</strong> - Fixed mycred admin dashboard overview widget showing incorrect or same amount of points.</li>
			<li><strong>FIX</strong> - buyCred gift_to attribute not working.</li>
			<li><strong>TWEAK</strong> - myCred Central Deposit.</li>
			<li><strong>TWEAK</strong> - Removed mycred review dialog.</li>
		</ul>
	</div>


	<?php mycred_about_footer(); ?>

</div>
<?php

}
