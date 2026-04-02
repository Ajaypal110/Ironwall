<?php

if(!defined('ABSPATH')){
exit;
}

/* Get custom slug */

function wps_login_slug(){

$slug=get_option('wsg_login_slug');

if(empty($slug)){
$slug='secure-login';
}

return trim($slug,'/');

}

/* Detect current path */

function wps_current_path(){

$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);

return trim($path,'/');

}

/* Block default login safely */

add_action('login_init','wps_block_default_login');

function wps_block_default_login(){

/* Allow POST requests (login processing) */

if($_SERVER['REQUEST_METHOD']=='POST'){
return;
}

/* Allow login processing parameters */

if(isset($_POST['log'])){
return;
}

/* Allow logout & password actions */

if(isset($_GET['action'])){

$allowed=array(
'logout',
'lostpassword',
'retrievepassword',
'resetpass',
'rp'
);

if(in_array($_GET['action'],$allowed)){
return;
}

}

/* Only block direct manual access */

if(strpos($_SERVER['REQUEST_URI'],'wp-login.php')!==false){

wp_safe_redirect(home_url());

exit;

}

}

/* Custom login loader */

add_action('init','wps_custom_login');

function wps_custom_login(){

$slug=wps_login_slug();

$current=wps_current_path();

if($current==$slug){

global $pagenow, $user_login, $error, $wp_error, $action, $user, $user_ID, $interim_login, $redirect_to;

$user_login='';
$error='';

$pagenow='wp-login.php';

define('WP_LOGIN_PAGE',true);

require_once ABSPATH.'wp-login.php';

exit;

}

}

/* Proper logout redirect */

add_filter('logout_redirect','wps_logout_redirect',10,3);

function wps_logout_redirect($redirect,$requested,$user){

return home_url('/'.wps_login_slug());

}