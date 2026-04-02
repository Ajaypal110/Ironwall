<?php

if(!defined('ABSPATH')){
exit;
}

add_action('admin_menu','wsg_admin_menu');

function wsg_admin_menu(){

    add_menu_page(
        'WP Sentinel',
        'WP Sentinel',
        'manage_options',
        'wsg-dashboard',
        'wsg_dashboard',
        'dashicons-shield',
        25
    );

    add_submenu_page(
        'wsg-dashboard',
        'Live Traffic',
        'Live Traffic',
        'manage_options',
        'wsg-live-traffic',
        'wsg_live_traffic_page'
    );

    add_submenu_page(
        'wsg-dashboard',
        'Malware Scanner',
        'Scanner',
        'manage_options',
        'wsg-scanner',
        'wsg_scanner_page'
    );

    add_submenu_page(
        'wsg-dashboard',
        'Settings',
        'Settings',
        'manage_options',
        'wsg-settings',
        'wsg_settings_page'
    );

    add_submenu_page(
        'wsg-dashboard',
        'Logs',
        'Logs',
        'manage_options',
        'wsg-logs',
        'wsg_logs_page'
    );

}