<?php
/**
 * Conecta la acción "Custom" de los formularios de Bricks con Sperant.
 *
 * @package CRM_Sperant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CRM_Sperant_Form_Handler {

	/** @var array */
	private $settings;

	public function __construct() {
		$this->settings = get_option( CRM_SPERANT_OPTION, array() );
		add_action( 'bricks/form/custom_action', array( $this, 'handle' ), 10, 1 );
	}

	/**
	 * Maneja el envío del formulario de Bricks.
	 *
	 * @param Bricks\Integrations\Form\Init $form Instancia del formulario.
	 */
	public function handle( $form ) {
		$fields   = $form->get_fields();
		$settings = $this->settings;

		// Diagnóstico: con debug activo, registra clave => valor de los campos del form
		// (solo los form-field-xxxxx) para poder mapearlos sin adivinar.
		if ( '1' === ( $settings['debug'] ?? '0' ) ) {
			$dump = array();
			foreach ( $fields as $k => $v ) {
				if ( 0 === strpos( (string) $k, 'form-field-' ) ) {
					$dump[ $k ] = is_scalar( $v ) ? (string) $v : wp_json_encode( $v );
				}
			}
			$this->log( 'Campos recibidos (clave => valor): ' . wp_json_encode( $dump, JSON_UNESCAPED_UNICODE ) );
		}

		// 1) Si se configuró un formulario objetivo, ignorar el resto.
		$target = trim( $settings['target_form_id'] ?? '' );
		if ( '' !== $target ) {
			$form_id = isset( $fields['formId'] ) ? (string) $fields['formId'] : '';
			if ( $form_id !== $target ) {
				return;
			}
		}

		// 2) Validar configuración mínima.
		if ( empty( $settings['token'] ) ) {
			$this->log( 'Falta el token de Sperant. Lead NO enviado.' );
			return;
		}

		// 3) Construir el payload del cliente.
		$payload = $this->build_client_payload( $fields, $settings );

		// 4) Validar obligatorios de Sperant.
		if ( empty( $payload['fname'] ) ) {
			$this->log( 'Falta el nombre (fname). Lead NO enviado.' );
			$this->set_result( $form, false, __( 'No se pudo registrar el lead: falta el nombre.', 'crm-sperant' ) );
			return;
		}

		// 5) Enviar a Sperant.
		$client = new CRM_Sperant_Client( $settings );
		$result = $client->create_client( $payload );

		if ( $result['success'] ) {
			$this->log( 'Lead creado en Sperant. client_id=' . ( $result['client_id'] ?? 'n/d' ) );
		} else {
			$this->log(
				sprintf(
					'Error al crear lead. HTTP %d. Respuesta: %s',
					$result['code'],
					is_string( $result['body'] ) ? $result['body'] : wp_json_encode( $result['body'] )
				)
			);
			// No bloqueamos el formulario: el lead igual se guarda como submission/email en Bricks.
			$this->set_result( $form, false, __( 'Lead recibido (pendiente de sincronizar con el CRM).', 'crm-sperant' ) );
		}
	}

	/**
	 * Arma el payload de /v3/clients a partir del mapeo configurado.
	 *
	 * @param array $fields   Campos del formulario.
	 * @param array $settings Ajustes.
	 * @return array
	 */
	private function build_client_payload( $fields, $settings ) {
		$payload = array();

		$map = array(
			'fname'       => $settings['map_fname'] ?? '',
			'lname'       => $settings['map_lname'] ?? '',
			'document'    => $settings['map_document'] ?? '',
			'email'       => $settings['map_email'] ?? '',
			'phone'       => $settings['map_phone'] ?? '',
			'observation' => $settings['map_observation'] ?? '',
		);

		foreach ( $map as $sperant_key => $field_id ) {
			if ( $field_id && isset( $fields[ $field_id ] ) && '' !== $fields[ $field_id ] ) {
				$payload[ $sperant_key ] = sanitize_text_field( $fields[ $field_id ] );
			}
		}

		// IDs fijos / de configuración (solo si tienen valor).
		foreach ( array( 'project_id', 'input_channel_id', 'source_id', 'interest_type_id', 'document_type_id' ) as $key ) {
			if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
				$payload[ $key ] = (int) $settings[ $key ];
			}
		}

		// Parámetros UTM si llegan como campos del formulario.
		foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' ) as $utm ) {
			$field_id = 'form-field-' . $utm;
			if ( isset( $fields[ $field_id ] ) && '' !== $fields[ $field_id ] ) {
				$payload[ $utm ] = sanitize_text_field( $fields[ $field_id ] );
			}
		}

		// Tipología / unidad de interés.
		$tip_field = $settings['map_tipologia'] ?? '';
		if ( $tip_field && isset( $fields[ $tip_field ] ) && '' !== $fields[ $tip_field ] ) {
			$value = sanitize_text_field( $fields[ $tip_field ] );
			if ( 'unit' === ( $settings['tipologia_mode'] ?? 'extra' ) && is_numeric( $value ) ) {
				// Modo unidad: el value del <option> es el unit_id real de Sperant.
				// Se guarda como campo extra de referencia; la unidad real se usa en la proforma.
				$payload['extra_fields'] = array( 'unit_id_interes' => (int) $value );
			} else {
				$key                     = $settings['extra_key'] ? $settings['extra_key'] : 'tipologia';
				$payload['extra_fields'] = array( $key => $value );
			}
		}

		/**
		 * Permite ajustar el payload antes de enviarlo a Sperant.
		 *
		 * @param array $payload
		 * @param array $fields
		 */
		return apply_filters( 'crm_sperant_client_payload', $payload, $fields );
	}

	/**
	 * Devuelve un resultado al formulario de Bricks (mensaje al usuario).
	 */
	private function set_result( $form, $success, $message ) {
		if ( method_exists( $form, 'set_result' ) ) {
			$form->set_result(
				array(
					'action'  => 'crm_sperant',
					'type'    => $success ? 'success' : 'info',
					'message' => $message,
				)
			);
		}
	}

	/**
	 * Log a debug.log solo si el modo debug está activo.
	 */
	private function log( $message ) {
		if ( '1' === ( $this->settings['debug'] ?? '0' ) ) {
			error_log( '[CRM Sperant] ' . $message );
		}
	}
}
