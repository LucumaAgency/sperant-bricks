<?php
/**
 * Plugin Name:       CRM Sperant — Bricks Connector
 * Plugin URI:        https://pruebalucuma.site
 * Description:        Conecta los formularios de Bricks Builder con el CRM inmobiliario Sperant (API v3). Crea automáticamente el lead/cliente en Sperant en cada envío de formulario.
 * Version:           1.1.0
 * Author:            Lucuma Agency
 * Author URI:        https://pruebalucuma.site
 * License:           GPL-2.0+
 * Text Domain:       crm-sperant
 *
 * @package CRM_Sperant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Acceso directo no permitido.
}

define( 'CRM_SPERANT_VERSION', '1.1.0' );
define( 'CRM_SPERANT_PATH', plugin_dir_path( __FILE__ ) );
define( 'CRM_SPERANT_URL', plugin_dir_url( __FILE__ ) );
define( 'CRM_SPERANT_OPTION', 'crm_sperant_settings' );

require_once CRM_SPERANT_PATH . 'includes/class-sperant-client.php';
require_once CRM_SPERANT_PATH . 'includes/class-sperant-settings.php';
require_once CRM_SPERANT_PATH . 'includes/class-sperant-form-handler.php';

/**
 * Arranque del plugin.
 */
function crm_sperant_init() {
	if ( is_admin() ) {
		new CRM_Sperant_Settings();
	}
	new CRM_Sperant_Form_Handler();
}
add_action( 'plugins_loaded', 'crm_sperant_init' );

/**
 * Valores por defecto al activar.
 */
function crm_sperant_activate() {
	if ( false === get_option( CRM_SPERANT_OPTION ) ) {
		add_option(
			CRM_SPERANT_OPTION,
			array(
				'api_base'         => 'https://api.eterniasoft.com', // PRUEBA. Producción: https://api.sperant.com
				'token'            => '',
				'auth_scheme'      => 'raw', // raw | bearer
				'project_id'       => '',
				'input_channel_id' => '',
				'source_id'        => '',
				'interest_type_id' => '',
				'document_type_id' => '',
				'target_form_id'   => '', // vacío = todos los formularios con acción Custom
				// IDs de campos de Bricks (form-field-xxxxx).
				'map_fname'        => '',
				'map_lname'        => '',
				'map_document'     => '',
				'map_email'        => '',
				'map_phone'        => '',
				'map_observation'  => '',
				'map_tipologia'    => '',
				'tipologia_mode'   => 'extra', // extra | unit
				'extra_key'        => 'tipologia',
				'debug'            => '0',
			)
		);
	}
}
register_activation_hook( __FILE__, 'crm_sperant_activate' );
