<?php
/*
 * Plugin Name: Custom Referral Spam Blocker
 * Plugin URI: http://jacobbaron.net/
 * Description: This plugin blocks referral spam bots which are screwing up your Google Analytics data.
 * Version: 0.1
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


}


function crsb_spammers_list_render(  ) { 

	$spammers = get_option( 'crsb_spammers_list' );
	$spammers_file = crsb_return_file();
			
	if((strlen($spammers)==0) && ($spammers<>$spammers_file)){
		//import from flat file if blank
		$spammers = crsb_return_file_restore();
	}
	?>
	<textarea cols='40' rows='25' name='crsb_spammers_list'><?php echo $spammers; ?></textarea>
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

function crsb_return_file_restore(  ) {
	
	$spammers = file_get_contents(plugin_dir_path(__FILE__).'spammers-restore.txt');
	return $spammers;
	
}

function crsb_return_file_array(  ) {
	
	$spamfile = crsb_return_file();
	$spammers = explode("\n",$spamfile);
	return $spammers;
	
}

?>