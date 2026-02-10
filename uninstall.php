<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options
delete_option('vt_google_maps_api_key');
delete_option('vt_avatek_api_url');
delete_option('vt_avatek_api_key');
delete_option('vt_avatek_account_id');
delete_option('vt_tcp_server_port');
delete_option('vt_tcp_server_ip');
delete_option('vt_update_interval');
delete_option('vt_default_latitude');
delete_option('vt_default_longitude');
delete_option('vt_default_zoom');
delete_option('vt_speed_limit');
delete_option('vt_idle_timeout');
delete_option('vt_email_alerts');
delete_option('vt_db_version');

// Drop custom tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vt_vehicles");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vt_positions");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vt_geofences");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vt_alerts");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vt_drivers");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vt_commands");

// Clear any scheduled hooks
wp_clear_scheduled_hook('vt_sync_avatek_positions');
wp_clear_scheduled_hook('vt_cleanup_old_positions');
wp_clear_scheduled_hook('vt_check_alerts');
