<?php
/**
 * Cliente HTTP para la API v3 de Sperant.
 *
 * @package CRM_Sperant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CRM_Sperant_Client {

	/** @var array Ajustes del plugin. */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Construye el header Authorization según el esquema configurado.
	 *
	 * @return string
	 */
	private function auth_header() {
		$token = isset( $this->settings['token'] ) ? trim( $this->settings['token'] ) : '';
		if ( 'bearer' === ( $this->settings['auth_scheme'] ?? 'raw' ) ) {
			return 'Bearer ' . $token;
		}
		return $token;
	}

	/**
	 * Crea un cliente/lead en Sperant.  POST /v3/clients
	 *
	 * @param array $payload Datos ya mapeados.
	 * @return array { success: bool, code: int, body: array|string, client_id: int|null }
	 */
	public function create_client( $payload ) {
		$base = untrailingslashit( $this->settings['api_base'] ?? 'https://api.sperant.com' );
		$url  = $base . '/v3/clients';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => $this->auth_header(),
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		return $this->parse_response( $response, 'client' );
	}

	/**
	 * Crea una proforma/cotización en Sperant.  POST /v3/budgets
	 *
	 * @param array $payload Datos de la proforma.
	 * @return array
	 */
	public function create_budget( $payload ) {
		$base = untrailingslashit( $this->settings['api_base'] ?? 'https://api.sperant.com' );
		$url  = $base . '/v3/budgets';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => $this->auth_header(),
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		return $this->parse_response( $response, 'budget' );
	}

	/**
	 * GET genérico a un recurso/catálogo de la API.  Ej: /v3/input_channels
	 *
	 * @param string $path Ruta relativa que empieza con "/".
	 * @return array { success: bool, code: int, items: array, body: mixed }
	 */
	public function get_resource( $path ) {
		$base = untrailingslashit( $this->settings['api_base'] ?? 'https://api.sperant.com' );
		$url  = $base . $path;

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Accept'        => 'application/json',
					'Authorization' => $this->auth_header(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'code'    => 0,
				'items'   => array(),
				'body'    => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$body = json_decode( $raw, true );

		$items = array();
		if ( is_array( $body ) && isset( $body['data'] ) && is_array( $body['data'] ) ) {
			foreach ( $body['data'] as $row ) {
				$attr    = isset( $row['attributes'] ) ? $row['attributes'] : $row;
				$items[] = array(
					'id'   => isset( $attr['id'] ) ? $attr['id'] : ( $row['id'] ?? '' ),
					'name' => isset( $attr['name'] ) ? $attr['name'] : ( $attr['code'] ?? '' ),
					'attr' => is_array( $attr ) ? $attr : array(),
				);
			}
		}

		return array(
			'success' => ( $code >= 200 && $code < 300 ),
			'code'    => $code,
			'items'   => $items,
			'body'    => ( null !== $body ) ? $body : $raw,
		);
	}

	/**
	 * Normaliza la respuesta de wp_remote_*.
	 *
	 * @param array|WP_Error $response Respuesta cruda.
	 * @param string         $context  client|budget.
	 * @return array
	 */
	private function parse_response( $response, $context ) {
		if ( is_wp_error( $response ) ) {
			return array(
				'success'   => false,
				'code'      => 0,
				'body'      => $response->get_error_message(),
				'client_id' => null,
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$body = json_decode( $raw, true );

		$client_id = null;
		if ( is_array( $body ) && isset( $body['data']['id'] ) ) {
			$client_id = (int) $body['data']['id'];
		}

		return array(
			'success'   => ( $code >= 200 && $code < 300 ),
			'code'      => $code,
			'body'      => ( null !== $body ) ? $body : $raw,
			'client_id' => $client_id,
		);
	}
}
