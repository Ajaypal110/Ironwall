<?php

if(!defined('ABSPATH')){
exit;
}

remove_action('wp_head','wp_generator');

add_filter('login_errors',function(){

return 'Login failed';

});

if(get_option('wsg_security_headers')){

add_action('send_headers',function(){

header('X-Frame-Options: SAMEORIGIN');

header('X-XSS-Protection: 1; mode=block');

header('X-Content-Type-Options: nosniff');

});

}