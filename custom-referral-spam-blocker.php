<?php
/*
 * Plugin Name: Custom Referral Spam Blocker
 * Plugin URI: http://jacobbaron.net/
 * Description: This plugin blocks referral spam bots which are screwing up your Google Analytics data.
 * Version: 0.3
 * Author: csmicfool
 * Author URI: http://jacobbaron.net
 * License: GPLv2+
 * Text Domain: custom-referral-spam-block
 * Min WP Version: 2.5.0
 * Max WP Version: 4.2.4
 */

function custom_referral_spam_block()
{
	
	if(isset($_SERVER['HTTP_REFERER'])) {
		$referrer = $_SERVER["HTTP_REFERER"];   
		
		$spammers = explode("\n",get_option( 'crsb_spammers_list' ));
		if(strlen($spammers[0]==0)||count($spammers)<1){
			$spammers = crsb_return_file_array();
		}
		
	}
	else
	{
		return true;
	}

	foreach ($spammers as $spammer) { 
		$pattern = "/$spammer/i"; 
		if (preg_match ($pattern, $referrer)) { 
			header("Location: $referrer"); exit(); 
		} 
	}
}

add_action('init','custom_referral_spam_block');




//Admin sections

add_action( 'admin_menu', 'crsb_add_admin_menu' );
add_action( 'admin_init', 'crsb_settings_init' );


function crsb_add_admin_menu(  ) { 

	add_options_page( 'Custom Referral Spam Blocker', 'Custom Referral Spam Blocker', 'manage_options', 'custom_referral_spam_blocker', 'crsb_options_page' );

}


function crsb_settings_init(  ) { 

	register_setting( 'pluginPage', 'crsb_spammers_list' );
	register_setting( 'pluginPage', 'crsb_share_data' );
	register_setting( 'pluginPage', 'crsb_share_data_last' );

	add_settings_section(
		'crsb_pluginPage_section', 
		__( 'Spam Referrer List', 'custom-referral-spam-block' ), 
		'crsb_settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'crsb_spammers_list', 
		__( 'Referrer List<br><br/><small><em>Add spam referrer domains<br/>one on each line.</em></small><br><br/><small><em>Set your custom list blank<br/>to restore default list.</em></small>', 'custom-referral-spam-block' ), 
		'crsb_spammers_list_render', 
		'pluginPage', 
		'crsb_pluginPage_section' 
	);
	
	add_settings_field(
		'crsb_share_data',
		__( '<span title="Enabling this feature will send anonymous data back to the plugin developer to help maintain rerferrer list. This feature is opt-in only and no personal data is collected nor stored.">Allow data sharing</span>','custom-referral-spam-block' ),
		'crsb_share_data_render',
		'pluginPage',
		'crsb_pluginPage_section'
	);


}


function crsb_spammers_list_render(  ) { 

	$spammers = get_option('crsb_spammers_list');
	$spammers_file = crsb_return_file();
	$last_post = get_option('crsb_share_data_last');
	$share_data = get_option('crsb_share_data');
	$send_post = false;
	
	if($share_data&&(time()-$last_post>(24*60*60))){
		$send_post=true;
	}
	if($send_post){
		$response = wp_remote_post('http://jacobbaron.net/test/referrers.php', array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => false,
			'headers' => array(),
			'body' => array( 'refurl' => $spammers ),
			'cookies' => array()
			)
		);

		if ( is_wp_error( $response ) ) {
		   $error_message = $response->get_error_message();
		   echo "Something went wrong: $error_message";
		} 
		else{
			update_option('crsb_share_data_last',time());
		}
	}
	
	if((strlen($spammers)==0) && ($spammers<>$spammers_file)){
		//import from flat file if blank
		$spammers = crsb_return_file();
	}
	?>
	<textarea cols='40' rows='25' name='crsb_spammers_list'><?php echo $spammers; ?></textarea>
	<?php

}


function crsb_share_data_render(  ) { 

	$share = get_option( 'crsb_share_data' );
			
	?>
	<input type="checkbox" name="crsb_share_data" value="true" <?php if($share) echo "checked"; ?>/>
	<?php

}


function crsb_settings_section_callback(  ) { 

	echo __( 'Add referrers to the list to block them from being recorded in Google Analytics.', 'custom-referral-spam-block' );

}


function crsb_options_page(  ) { 

	?>
	<form action='options.php' method='post'>
		
		<h2>Custom Referral Spam Blocker</h2>
		
		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>
		
	</form>
	<?php

}

function crsb_return_file(  ) {
	
	$spammers = file_get_contents(plugin_dir_path(__FILE__).'spammers.txt');
	return $spammers;
	
}

function crsb_return_file_array(  ) {
	
	$spamfile = crsb_return_file();
	$spammers = explode("\n",$spamfile);
	return $spammers;
	
}

?>