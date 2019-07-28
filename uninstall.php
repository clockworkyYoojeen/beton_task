<?php 

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// удаляем опции из базы данных
delete_option( 'rmc_menus' );