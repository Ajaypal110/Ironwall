<?php

if(!defined('ABSPATH')){
exit;
}

/* Successful login */

add_action('wp_login','wsg_success_login',10,2);

function wsg_success_login($user_login,$user){

wsg_insert_log('Successful login',$user_login);

}

/* Plugin activation */

add_action('activated_plugin','wsg_plugin_activated');

function wsg_plugin_activated(){

wsg_insert_log('Plugin activated');

}