<?php

class nss_plugin_updater_learndash_propanel 
{
    /**
     * The plugin current version
     * @var string
     */
    public $current_version;

    /**
     * The plugin remote update path
     * @var string
     */
    public $update_path;

    /**
     * Plugin Slug (plugin_directory/plugin_file.php)
     * @var string
     */
    public $plugin_slug;

    /**
     * Plugin name (plugin_file)
     * @var string
     */
    public $slug;

	public $code;
    /**
     * Initialize a new instance of the WordPress Auto-Update class
     * @param string $current_version
     * @param string $update_path
     * @param string $plugin_slug
     */
    function __construct($update_path, $plugin_slug)
    {

		// Set the class public variables
        //$this->update_path = $update_path;
		$this->plugin_slug = $plugin_slug;
        $this->current_version = $this->get_plugin_data()->Version;
        
		list ($t1, $t2) = explode('/', $plugin_slug);
        $this->slug = str_replace('.php', '', $t2);
		$code = $this->code = $this->slug;
		
		$license = get_option('nss_plugin_license_'.$code);
		$licenseemail = get_option('nss_plugin_license_email_'.$code);
		$this->update_path = $update_path.'?pluginupdate='.$code.'&licensekey='.urlencode($license).'&licenseemail='.urlencode($licenseemail).'&nsspu_wpurl='.urlencode(get_bloginfo('wpurl')).'&nsspu_admin='.urlencode(get_bloginfo('admin_email')).'&current_version='.$this->current_version;
		
        
		//Add Menu
		add_action('admin_menu', array(&$this, 'nss_plugin_license_menu'), 1);
			
        // define the alternative API for updating checking
        add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_update'));

        // Define the alternative response for information checking
        add_filter('plugins_api', array(&$this, 'check_info'), 10, 3);
    }

	function admin_notice() {
		echo "<style>
				#nss_plugin_updater_admin_notice {
					width: 90%;
					margin: auto;
					margin-top: 30px;
					border: 2px solid black;
					padding: 5px 10px;
					background-color: yellow;
				}
				</style>";
		$licensepage = get_admin_url(null,'admin.php?page=nss_plugin_license-'.$this->code.'-settings');
		echo "<p id='nss_plugin_updater_admin_notice'>License of your plugin <b>".$this->get_plugin_data()->Name."</b> is invalid or incomplete. Please click <a href='".$licensepage."'>here</a> and update your license.</p>";			
	}
	
	function invalid_current_license() {
		add_action( 'admin_notices', array(&$this, 'admin_notice'));
		deactivate_plugins( $this->plugin_slug );
	}
	function get_plugin_data() {
		if(!function_exists('get_plugin_data'))
		include_once( ABSPATH.'wp-admin'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'plugin.php');

		return (object) get_plugin_data(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.$this->plugin_slug);
	}
    /**
     * Add our self-hosted autoupdate plugin to the filter transient
     *
     * @param $transient
     * @return object $ transient
     */
    public function check_update($transient)
    {
        if (empty($transient->checked)) {
           // return $transient;
        }
	//print_r($transient);

        // Get the remote version
        $remote_version = $this->getRemote_version();
		$license = $this->getRemote_license();
		
		if(empty($license))
		$this->getRemote_current_license();
        // If a newer version is available, add the update
        if (version_compare($this->current_version, $remote_version, '<')) {
            $obj = new stdClass();
            $obj->slug = $this->slug;
            $obj->new_version = $remote_version;
            $obj->url = $this->update_path;
            $obj->package = $this->update_path;
            $transient->response[$this->plugin_slug] = $obj;
        }
        ///var_dump($transient);
        return $transient;
    }

    /**
     * Add our self-hosted description to the filter
     *
     * @param boolean $false
     * @param array $action
     * @param object $arg
     * @return bool|object
     */
    public function check_info($false, $action, $arg)
    {
		if(empty($arg) || empty($arg->slug) || empty($this->slug))
		return false;
		
        if ($arg->slug === $this->slug) {
            $information = $this->getRemote_information();
            return $information;
        }
        return false;
    }

    /**
     * Return the remote version
     * @return string $remote_version
     */
    public function getRemote_version()
    {
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'version')));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return $request['body'];
        }
        return false;
    }

    /**
     * Get information about the remote version
     * @return bool|object
     */
    public function getRemote_information()
    {
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'info')));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return unserialize($request['body']);
        }
        return false;
    }

    /**
     * Return the status of the plugin licensing
     * @return boolean $remote_license
     */
    public function getRemote_license()
    {
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'license')));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
			if($request['body'] == "false" || $request['body'] == "not_found" || empty($request['body']))
				add_action( 'admin_notices', array(&$this, 'admin_notice'));
            return $request['body'];
     	 }
		//add_action( 'admin_notices', array(&$this, 'admin_notice'));
        return true;
    }
	
    public function getRemote_current_license()
    {
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'current_license')));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
			if($request['body'] == "not_found")
				$this->invalid_current_license();
            return $request['body'];
     	 }
		//$this->invalid_current_license();
        return true;
    }	
	
	function nss_plugin_license_menu() {
		add_submenu_page("learndash-lms-non-existant", $this->get_plugin_data()->Name." License", $this->get_plugin_data()->Name." License",'manage_options','nss_plugin_license-'.$this->code.'-settings', array(&$this, 'nss_plugin_license_menupage'));

	}

	function nss_plugin_license_menupage()
	{
		$code = $this->code;
	   //must check that the user has the required capability 
		if (!current_user_can('manage_options'))
		{
		  wp_die( __('You do not have sufficient permissions to access this page.') );
		}

		// Read in existing option value from database
		$license = get_option('nss_plugin_license_'.$code);
		$email = get_option('nss_plugin_license_email_'.$code);

		// See if the user has posted us some information
		// If they did, this hidden field will be set to 'Y'
		if( isset($_POST[ "update_nss_plugin_license_".$code ]) ) {
			// Read their posted value
			$license = $_POST['nss_plugin_license_'.$code];
			$email = $_POST['nss_plugin_license_email_'.$code];
		
			// Save the posted value in the database
			update_option( 'nss_plugin_license_'.$code, $license);
			update_option( 'nss_plugin_license_email_'.$code, $email);
			
			

			// Put an settings updated message on the screen

	?>
	<div class="updated"><p><strong><?php _e('settings saved.', 'learndash_propanel' ); ?></strong></p></div>
	<?php

		}
	?>
	<style>
	.grayblock {
		border: solid 1px #ccc;
		background: #eee;
		padding: 1px 8px;
		width: 30%;
	}
	</style>
	<div class=wrap>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<h2><?php __("License Settings", "learndash_propanel"); ?></h2>
	<br>
	<h3><?php _e("Email:", "learndash_propanel"); ?></h3>
	<input name="nss_plugin_license_email_<?php echo $code; ?>" style="min-width:30%" value="<?php echo   _e(apply_filters('format_to_edit',$email), 'learndash_propanel') ?>" />
	<h3><?php _e("License Key:", "learndash_propanel"); ?></h3>
	<input name="nss_plugin_license_<?php echo $code; ?>" style="min-width:30%" value="<?php echo   _e(apply_filters('format_to_edit',$license), 'learndash_propanel') ?>" />

	<div class="submit">
	<input type="submit" name="update_nss_plugin_license_<?php echo $code; ?>" value="<?php _e('Update License', 'learndash_propanel') ?>" /></div>
	</form>

	<br><br><br><br>
	<div id="nss_license_footer">
	<?php do_action($code."-nss_license_footer"); ?>
	</div>
	</div>
	<?php
	}

}
