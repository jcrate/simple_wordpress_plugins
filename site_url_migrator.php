<?php

/*
Plugin Name: Site URL Migrator
Plugin URI: https://github.com/jcrate/simple_wordpress_plugins
Description: Migrates site url stored in options and posts to new site url.
Author: Jim Crate
Version: 1.0
Author URI: https://github.com/jcrate/simple_wordpress_plugins
Network: true
*/

namespace SITE_URL_MIGRATOR;

defined('ABSPATH') or die("No script kiddies please!");

// error_log("swp::base, global: {$swpTestGlobal}");
// if (WP_DEBUG !== true) {
// 	error_log("Turn on WP_DEBUG for development.");
// }

return SiteURLMigratorPlugin::bootstrap(); 


class SiteURLMigratorPlugin
{
	static $instance;
    static public function bootstrap() 
	{
        if (NULL === self::$instance) {
            self::$instance = new SiteURLMigratorPlugin;
        }
        return self::$instance;
    }
	
	private $migrator_option_name = 'site_url_migrator_old_site_url';
	private $old_site_url;
	private $new_site_url;
	private $new_domain;
	private $old_domain;
	private $preg_quoted_old_domain;

    function __construct()
    {
		// fire late to let other plugin autoloaders load
		add_action('plugins_loaded', array($this, 'site_url_migrator'), 99999);
	}
	
	
	function site_url_migrator() {
		global $wpdb;
	
		$this->old_site_url = get_option($this->migrator_option_name);
		if ($this->old_site_url == false) {return; }
		
		$this->old_domain = preg_replace('/https?\:\/\//i', '', $this->old_site_url);
		$this->preg_quoted_old_domain = preg_quote($this->old_domain);
		$this->new_site_url = get_option('siteurl');
		$this->new_domain = preg_replace('/https?\:\/\//i', '', $this->new_site_url);
		
		$results = $wpdb->get_results("SELECT option_name FROM wp_options WHERE option_value LIKE '%{$this->old_domain}%'");
		foreach ($results as $row) {
			if ($row->option_name == $this->migrator_option_name) { continue; }
			
			error_log("site_url_migrator: migrating option {$row->option_name}");
			
			$option = get_option($row->option_name); // deserializes into array if necessary
			$option = $this->migrate_option($option);
			update_option($row->option_name, $option);
		}
		
		delete_option($this->migrator_option_name);
	}
	
	
	function migrate_option($option_value) {
		if (is_string($option_value)) {
			return $this->migrate_string($option_value);
		} else if (is_array($option_value)) {
			return $this->migrate_array($option_value);
		} else if (is_object($option_value)) {
			return $this->migrate_object($option_value);
		} else {
			return $option_value;
		}
	}

	function migrate_string($option_value) {
		$migrated_value = preg_replace("/{$this->preg_quoted_old_domain}/i", $this->new_domain, $option_value);
		// error_log("site_url_migrator: migrated string {$option_value} to {$migrated_value}");
		return $migrated_value;
	}

	function migrate_array($option_array) {
		foreach ($option_array as $key => $value) {
			$option_array[$key] = $this->migrate_option($value);
		}
		return $option_array;
	}
	
	function migrate_object($option_object) {
		$object_vars = get_object_vars($option_object);
		foreach ($object_vars as $name => $value) {
			$option_object->{$name} = $this->migrate_option($value);
		}
	}
	
}



// UPDATE wp_options SET option_value = REPLACE(option_value, 'www.sefa.com', 'www.sefa.local');
//
// UPDATE wp_posts SET post_content=REPLACE(post_content, 'www.sefa.com', 'www.sefa.local');
// UPDATE wp_postmeta SET meta_value=REPLACE(meta_value, 'www.sefa.com', 'www.sefa.local');




?>

