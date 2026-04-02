<?php

if(!defined('ABSPATH')){
exit;
}

if(get_option('wsg_xmlrpc_disable')){

add_filter('xmlrpc_enabled','__return_false');

}

add_action('init',function(){

if(isset($_REQUEST['author'])){

wp_redirect(home_url());

exit;

}

});

if(!defined('DISALLOW_FILE_EDIT')){

define('DISALLOW_FILE_EDIT',true);

}