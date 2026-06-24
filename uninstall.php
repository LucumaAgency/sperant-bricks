<?php
/**
 * Se ejecuta al desinstalar el plugin: limpia las opciones.
 *
 * @package CRM_Sperant
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'crm_sperant_settings' );
