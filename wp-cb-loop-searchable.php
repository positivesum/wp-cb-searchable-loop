<?php
/*
Plugin Name: WPCB Searchable Loop
Plugin URI: http://positivesum.org/
Description: Searchable Loop Module for CB
Version: 0.1
Author: Valera Satsura
Author URI: http://www.odesk.com/users/~~41ba9055d0f90cee
*/

// Include main module class
function wp_cb_searchable_loop() {
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'loop-searchable.php');
}

add_action('cfct-modules-loaded', 'wp_cb_searchable_loop');

 
