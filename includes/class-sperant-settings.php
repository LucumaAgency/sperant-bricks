<?php
/**
 * Página de ajustes del plugin en el admin de WordPress.
 *
 * @package CRM_Sperant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CRM_Sperant_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_crm_sperant_catalogs', array( $this, 'ajax_catalogs' ) );
	}

	/**
	 * AJAX: consulta los catálogos de Sperant y devuelve sus id → nombre.
	 * Usa el token escrito en el formulario (aunque no esté guardado todavía).
	 */
	public function ajax_catalogs() {
		check_ajax_referer( 'crm_sperant_catalogs', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Sin permisos.' ) );
		}

		$settings = array(
			'api_base'    => isset( $_POST['api_base'] ) ? esc_url_raw( wp_unslash( $_POST['api_base'] ) ) : 'https://api.sperant.com',
			'token'       => isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '',
			'auth_scheme' => isset( $_POST['auth_scheme'] ) ? sanitize_text_field( wp_unslash( $_POST['auth_scheme'] ) ) : 'raw',
		);
		$project_id = isset( $_POST['project_id'] ) ? (int) $_POST['project_id'] : 0;

		if ( empty( $settings['token'] ) ) {
			wp_send_json_error( array( 'message' => 'Escribe primero el token de Sperant.' ) );
		}

		$client = new CRM_Sperant_Client( $settings );

		// Catálogos globales.
		$resources = array(
			'input_channel_id' => array( 'Canales de entrada', '/v3/input_channels' ),
			'source_id'        => array( 'Medios de captación', '/v3/captation_ways' ),
			'interest_type_id' => array( 'Niveles de interés', '/v3/interest_types' ),
			'document_type_id' => array( 'Tipos de documento', '/v3/document_types' ),
		);

		// Recursos por proyecto (unidades y tipologías) si hay project_id.
		if ( $project_id > 0 ) {
			$resources['units'] = array( 'Unidades del proyecto ' . $project_id, '/v3/projects/' . $project_id . '/units' );
			$resources['types'] = array( 'Tipologías del proyecto ' . $project_id, '/v3/projects/' . $project_id . '/types' );
		}

		$out = array();
		foreach ( $resources as $key => $info ) {
			$res         = $client->get_resource( $info[1] );
			$out[ $key ] = array(
				'label'   => $info[0],
				'success' => $res['success'],
				'code'    => $res['code'],
				'items'   => $res['items'],
				'error'   => $res['success'] ? '' : ( is_string( $res['body'] ) ? $res['body'] : wp_json_encode( $res['body'] ) ),
			);
		}

		// Campos ESTÁNDAR del lead (fijos en la API; no vienen de ningún endpoint).
		$out['standard_fields'] = array(
			'label' => 'Campos estándar del lead (POST /v3/clients)',
			'items' => $this->standard_fields(),
		);

		// Campos PERSONALIZADOS (best-effort: la API v3 no tiene endpoint oficial para listarlos).
		$out['custom_fields'] = $this->discover_custom_fields( $client, $project_id );

		$out['_meta'] = array( 'project_id' => $project_id );
		wp_send_json_success( $out );
	}

	/**
	 * Lista fija de los campos estándar que acepta POST /v3/clients.
	 *
	 * @return array
	 */
	private function standard_fields() {
		return array(
			array( 'key' => 'fname', 'required' => true, 'mapped' => 'map_fname', 'desc' => 'Nombre' ),
			array( 'key' => 'lname', 'required' => false, 'mapped' => 'map_lname', 'desc' => 'Apellido' ),
			array( 'key' => 'email', 'required' => false, 'mapped' => 'map_email', 'desc' => 'Correo' ),
			array( 'key' => 'phone', 'required' => false, 'mapped' => 'map_phone', 'desc' => 'Teléfono' ),
			array( 'key' => 'document', 'required' => false, 'mapped' => 'map_document', 'desc' => 'N° de documento' ),
			array( 'key' => 'document_type_id', 'required' => false, 'mapped' => 'document_type_id', 'desc' => 'Tipo de documento (catálogo)' ),
			array( 'key' => 'input_channel_id', 'required' => true, 'mapped' => 'input_channel_id', 'desc' => 'Canal de entrada (catálogo)' ),
			array( 'key' => 'source_id', 'required' => true, 'mapped' => 'source_id', 'desc' => 'Medio de captación (catálogo)' ),
			array( 'key' => 'interest_type_id', 'required' => true, 'mapped' => 'interest_type_id', 'desc' => 'Nivel de interés (catálogo)' ),
			array( 'key' => 'project_id', 'required' => false, 'mapped' => 'project_id', 'desc' => 'Proyecto' ),
			array( 'key' => 'observation', 'required' => false, 'mapped' => 'map_observation', 'desc' => 'Mensaje / observación' ),
			array( 'key' => 'utm_source', 'required' => false, 'mapped' => 'form-field-utm_source', 'desc' => 'UTM (campo del form)' ),
			array( 'key' => 'utm_medium', 'required' => false, 'mapped' => 'form-field-utm_medium', 'desc' => 'UTM (campo del form)' ),
			array( 'key' => 'utm_campaign', 'required' => false, 'mapped' => 'form-field-utm_campaign', 'desc' => 'UTM (campo del form)' ),
			array( 'key' => 'utm_term', 'required' => false, 'mapped' => 'form-field-utm_term', 'desc' => 'UTM (campo del form)' ),
			array( 'key' => 'utm_content', 'required' => false, 'mapped' => 'form-field-utm_content', 'desc' => 'UTM (campo del form)' ),
			array( 'key' => 'extra_fields', 'required' => false, 'mapped' => 'map_tipologia', 'desc' => 'Contenedor de campos personalizados' ),
		);
	}

	/**
	 * Intenta descubrir los campos personalizados (extra_fields).
	 *
	 * La API v3 NO expone un endpoint oficial para listarlos, así que se hace en dos frentes:
	 *  1) Se sondean endpoints candidatos (no documentados).
	 *  2) Se leen leads recientes y se extraen las claves de extra_fields realmente usadas.
	 *
	 * @param CRM_Sperant_Client $client     Cliente API.
	 * @param int                $project_id Proyecto (opcional).
	 * @return array { items: array, tried: array }
	 */
	private function discover_custom_fields( $client, $project_id ) {
		$found = array(); // clave => array( key, label, sample, source ).
		$tried = array();

		// 1) Endpoints candidatos (no oficiales).
		$candidates = array( '/v3/custom_fields', '/v3/extra_fields', '/v3/client_custom_fields' );
		if ( $project_id > 0 ) {
			$candidates[] = '/v3/projects/' . $project_id . '/custom_fields';
			$candidates[] = '/v3/projects/' . $project_id . '/extra_fields';
		}
		foreach ( $candidates as $path ) {
			$res     = $client->get_json( $path );
			$tried[] = $path . ' → HTTP ' . $res['code'];
			if ( $res['success'] && is_array( $res['body'] ) && ! empty( $res['body']['data'] ) && is_array( $res['body']['data'] ) ) {
				foreach ( $res['body']['data'] as $row ) {
					$attr = isset( $row['attributes'] ) && is_array( $row['attributes'] ) ? $row['attributes'] : $row;
					$key  = $attr['key'] ?? ( $attr['slug'] ?? ( $attr['name'] ?? ( $row['id'] ?? '' ) ) );
					if ( '' !== $key ) {
						$found[ $key ] = array(
							'key'    => $key,
							'label'  => $attr['name'] ?? ( $attr['label'] ?? $key ),
							'sample' => '',
							'source' => 'endpoint ' . $path,
						);
					}
				}
			}
		}

		// 2) Muestra de leads recientes: claves presentes en extra_fields.
		$sample  = $client->get_json( '/v3/clients' );
		$tried[] = '/v3/clients (muestra) → HTTP ' . $sample['code'];
		if ( $sample['success'] && is_array( $sample['body'] ) && ! empty( $sample['body']['data'] ) && is_array( $sample['body']['data'] ) ) {
			foreach ( $sample['body']['data'] as $row ) {
				$attr = isset( $row['attributes'] ) && is_array( $row['attributes'] ) ? $row['attributes'] : $row;
				$ef   = isset( $attr['extra_fields'] ) && is_array( $attr['extra_fields'] ) ? $attr['extra_fields'] : array();
				foreach ( $ef as $k => $v ) {
					$sample_val = is_scalar( $v ) ? (string) $v : wp_json_encode( $v );
					if ( ! isset( $found[ $k ] ) ) {
						$found[ $k ] = array(
							'key'    => $k,
							'label'  => $k,
							'sample' => $sample_val,
							'source' => 'visto en leads',
						);
					} elseif ( '' === $found[ $k ]['sample'] ) {
						$found[ $k ]['sample'] = $sample_val;
					}
				}
			}
		}

		return array(
			'items' => array_values( $found ),
			'tried' => $tried,
		);
	}

	public function add_menu() {
		add_options_page(
			__( 'CRM Sperant', 'crm-sperant' ),
			__( 'CRM Sperant', 'crm-sperant' ),
			'manage_options',
			'crm-sperant',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'crm_sperant_group',
			CRM_SPERANT_OPTION,
			array( $this, 'sanitize' )
		);
	}

	/**
	 * Saneo de todos los campos.
	 */
	public function sanitize( $input ) {
		$out = array();
		$text_keys = array(
			'api_base', 'token', 'auth_scheme', 'target_form_id',
			'map_fname', 'map_lname', 'map_document', 'map_email',
			'map_phone', 'map_observation', 'map_tipologia',
			'tipologia_mode', 'extra_key', 'debug',
		);
		foreach ( $text_keys as $k ) {
			$out[ $k ] = isset( $input[ $k ] ) ? sanitize_text_field( $input[ $k ] ) : '';
		}
		$int_keys = array( 'project_id', 'input_channel_id', 'source_id', 'interest_type_id', 'document_type_id' );
		foreach ( $int_keys as $k ) {
			$out[ $k ] = ( isset( $input[ $k ] ) && '' !== $input[ $k ] ) ? (string) (int) $input[ $k ] : '';
		}
		if ( empty( $out['api_base'] ) ) {
			$out['api_base'] = 'https://api.eterniasoft.com'; // PRUEBA por defecto.
		}
		return $out;
	}

	private function field( $key, $default = '' ) {
		$o = get_option( CRM_SPERANT_OPTION, array() );
		return isset( $o[ $key ] ) ? $o[ $key ] : $default;
	}

	private function text_input( $key, $placeholder = '' ) {
		printf(
			'<input type="text" name="%s[%s]" value="%s" placeholder="%s" class="regular-text" />',
			esc_attr( CRM_SPERANT_OPTION ),
			esc_attr( $key ),
			esc_attr( $this->field( $key ) ),
			esc_attr( $placeholder )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1>CRM Sperant — Conector Bricks</h1>
			<p>Conecta los formularios de Bricks Builder con el CRM Sperant (API v3 — <code>/v3/clients</code>).
			En el formulario de Bricks, agrega la acción <strong>Custom</strong> en
			<em>Actions after submit</em>; este plugin se encarga del resto.</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'crm_sperant_group' ); ?>

				<h2>1. Conexión con Sperant</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">API Base URL</th>
						<td><?php $this->text_input( 'api_base', 'https://api.eterniasoft.com' ); ?>
						<p class="description"><strong>Prueba:</strong> <code>https://api.eterniasoft.com</code> &nbsp;·&nbsp; <strong>Producción:</strong> <code>https://api.sperant.com</code></p></td>
					</tr>
					<tr>
						<th scope="row">Token (Authorization)</th>
						<td><?php $this->text_input( 'token', 'Token entregado por Sperant' ); ?></td>
					</tr>
					<tr>
						<th scope="row">Esquema de Authorization</th>
						<td>
							<select name="<?php echo esc_attr( CRM_SPERANT_OPTION ); ?>[auth_scheme]">
								<option value="raw" <?php selected( $this->field( 'auth_scheme', 'raw' ), 'raw' ); ?>>Token a secas</option>
								<option value="bearer" <?php selected( $this->field( 'auth_scheme' ), 'bearer' ); ?>>Bearer &lt;token&gt;</option>
							</select>
							<p class="description">Si no estás seguro, prueba primero "Token a secas". Confírmalo con Sperant.</p>
						</td>
					</tr>
				</table>

				<p>
					<button type="button" class="button button-secondary" id="crm-sperant-load-catalogs">
						Cargar catálogos y campos desde Sperant
					</button>
					<span id="crm-sperant-catalogs-status" style="margin-left:8px;"></span>
				</p>
				<div id="crm-sperant-catalogs-result"></div>

				<h2>2. IDs del proyecto (te los da Sperant)</h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row">project_id</th><td><?php $this->text_input( 'project_id', 'Ej. 19' ); ?></td></tr>
					<tr><th scope="row">input_channel_id <span style="color:#b32d2e">*</span></th><td><?php $this->text_input( 'input_channel_id', 'Ej. 8 (Web)' ); ?></td></tr>
					<tr><th scope="row">source_id <span style="color:#b32d2e">*</span></th><td><?php $this->text_input( 'source_id', 'Ej. 2' ); ?></td></tr>
					<tr><th scope="row">interest_type_id <span style="color:#b32d2e">*</span></th><td><?php $this->text_input( 'interest_type_id', 'Ej. 4' ); ?></td></tr>
					<tr><th scope="row">document_type_id (DNI)</th><td><?php $this->text_input( 'document_type_id', 'Ej. 1' ); ?></td></tr>
				</table>
				<p class="description"><span style="color:#b32d2e">*</span> Obligatorios en la API de Sperant para crear el cliente.</p>

				<h2>3. Mapeo de campos del formulario</h2>
				<p>Escribe el <strong>ID del campo de Bricks</strong> (suele tener el formato <code>form-field-xxxxx</code>;
				lo ves en el panel del campo en Bricks).</p>
				<table class="form-table" role="presentation">
					<tr><th scope="row">Nombre → fname</th><td><?php $this->text_input( 'map_fname', 'form-field-...' ); ?></td></tr>
					<tr><th scope="row">Apellido → lname</th><td><?php $this->text_input( 'map_lname', 'form-field-...' ); ?></td></tr>
					<tr><th scope="row">DNI → document</th><td><?php $this->text_input( 'map_document', 'form-field-...' ); ?></td></tr>
					<tr><th scope="row">Email → email</th><td><?php $this->text_input( 'map_email', 'form-field-...' ); ?></td></tr>
					<tr><th scope="row">Teléfono → phone</th><td><?php $this->text_input( 'map_phone', 'form-field-...' ); ?></td></tr>
					<tr><th scope="row">Mensaje → observation</th><td><?php $this->text_input( 'map_observation', 'form-field-...' ); ?></td></tr>
					<tr><th scope="row">Tipología → campo</th><td><?php $this->text_input( 'map_tipologia', 'form-field-...' ); ?></td></tr>
				</table>

				<h2>4. Manejo de la tipología</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Modo</th>
						<td>
							<select name="<?php echo esc_attr( CRM_SPERANT_OPTION ); ?>[tipologia_mode]">
								<option value="extra" <?php selected( $this->field( 'tipologia_mode', 'extra' ), 'extra' ); ?>>Campo personalizado (texto)</option>
								<option value="unit" <?php selected( $this->field( 'tipologia_mode' ), 'unit' ); ?>>unit_id real de Sperant</option>
							</select>
							<p class="description">
								<strong>Campo personalizado:</strong> guarda el texto (ej. "601 - 1 Ambiente") en <code>extra_fields</code>.<br>
								<strong>unit_id real:</strong> el <code>value</code> de cada opción del select debe ser el ID interno de la unidad en Sperant.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Clave del campo extra</th>
						<td><?php $this->text_input( 'extra_key', 'tipologia' ); ?>
						<p class="description">Debe coincidir con la clave del campo personalizado creado en Sperant.</p></td>
					</tr>
				</table>

				<h2>5. Opciones</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Form ID objetivo</th>
						<td><?php $this->text_input( 'target_form_id', 'Vacío = todos los forms con acción Custom' ); ?>
						<p class="description">Limita el envío a un solo formulario. Déjalo vacío para aplicar a todos.</p></td>
					</tr>
					<tr>
						<th scope="row">Modo debug</th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( CRM_SPERANT_OPTION ); ?>[debug]" value="1" <?php checked( $this->field( 'debug' ), '1' ); ?> />
								Escribir errores en <code>wp-content/debug.log</code>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Guardar ajustes' ); ?>
			</form>
		</div>

		<script>
		( function () {
			var btn    = document.getElementById( 'crm-sperant-load-catalogs' );
			var status = document.getElementById( 'crm-sperant-catalogs-status' );
			var result = document.getElementById( 'crm-sperant-catalogs-result' );
			if ( ! btn ) { return; }

			var opt = <?php echo wp_json_encode( CRM_SPERANT_OPTION ); ?>;
			var nonce = <?php echo wp_json_encode( wp_create_nonce( 'crm_sperant_catalogs' ) ); ?>;

			function val( name ) {
				var el = document.querySelector( '[name="' + opt + '[' + name + ']"]' );
				return el ? el.value : '';
			}

			function esc( v ) {
				return ( v === null || v === undefined ) ? '' : String( v )
					.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
			}

			function tableFor( cat ) {
				var html = '<h4 style="margin-bottom:4px;">' + esc( cat.label ) + '</h4>';
				if ( ! cat.success ) {
					return html + '<p style="color:#b32d2e;">Error (HTTP ' + cat.code + '): ' +
						esc( cat.error || 'sin respuesta' ) + '</p>';
				}
				if ( ! cat.items.length ) {
					return html + '<p><em>Sin elementos.</em></p>';
				}
				html += '<table class="widefat striped" style="max-width:520px;margin-bottom:16px;">' +
					'<thead><tr><th style="width:90px;">ID</th><th>Nombre</th></tr></thead><tbody>';
				cat.items.forEach( function ( it ) {
					html += '<tr><td><strong>' + esc( it.id ) + '</strong></td><td>' + esc( it.name ) + '</td></tr>';
				} );
				return html + '</tbody></table>';
			}

			function tableForUnits( cat ) {
				var html = '<h4 style="margin-bottom:4px;">' + esc( cat.label ) + '</h4>';
				if ( ! cat.success ) {
					return html + '<p style="color:#b32d2e;">Error (HTTP ' + cat.code + '): ' +
						esc( cat.error || 'sin respuesta' ) + '</p>';
				}
				if ( ! cat.items.length ) {
					return html + '<p><em>Sin unidades.</em></p>';
				}
				html += '<p class="description">El <strong>unit_id</strong> es la columna ID. El número de depto (601, 701…) está en Código/Nombre.</p>';
				html += '<table class="widefat striped" style="max-width:760px;margin-bottom:16px;">' +
					'<thead><tr><th style="width:90px;">unit_id</th><th>Código</th><th>Nombre</th><th>Tipo</th><th>Estado</th><th>Precio</th></tr></thead><tbody>';
				cat.items.forEach( function ( it ) {
					var a = it.attr || {};
					html += '<tr>' +
						'<td><strong>' + esc( it.id ) + '</strong></td>' +
						'<td>' + esc( a.code ) + '</td>' +
						'<td>' + esc( a.name ) + '</td>' +
						'<td>' + esc( a.property_type || a.type || '' ) + '</td>' +
						'<td>' + esc( a.commercial_status || '' ) + '</td>' +
						'<td>' + esc( ( a.price !== undefined ? a.price : '' ) + ' ' + ( a.currency || '' ) ) + '</td>' +
						'</tr>';
				} );
				return html + '</tbody></table>';
			}

			function tableForStandard( cat ) {
				var html = '<h4 style="margin-bottom:4px;">' + esc( cat.label ) + '</h4>';
				html += '<p class="description">Campos fijos del CRM. La columna "Mapeo" indica el ajuste del plugin que los rellena.</p>';
				html += '<table class="widefat striped" style="max-width:760px;margin-bottom:16px;">' +
					'<thead><tr><th style="width:160px;">Campo</th><th style="width:70px;">Oblig.</th>' +
					'<th>Descripción</th><th>Mapeo en el plugin</th></tr></thead><tbody>';
				cat.items.forEach( function ( it ) {
					html += '<tr>' +
						'<td><code>' + esc( it.key ) + '</code></td>' +
						'<td>' + ( it.required ? '<span style="color:#b32d2e;">Sí</span>' : '—' ) + '</td>' +
						'<td>' + esc( it.desc ) + '</td>' +
						'<td><code>' + esc( it.mapped ) + '</code></td>' +
						'</tr>';
				} );
				return html + '</tbody></table>';
			}

			function tableForCustom( cat ) {
				var html = '<h4 style="margin-bottom:4px;">Campos personalizados (extra_fields)</h4>';
				html += '<p class="description">La API de Sperant no los lista oficialmente. Esto es una detección automática ' +
					'(endpoints candidatos + claves vistas en leads recientes). Si tu campo no aparece, créalo en el panel de Sperant ' +
					'y usa esa misma clave en "Clave del campo extra".</p>';
				if ( cat.items && cat.items.length ) {
					html += '<table class="widefat striped" style="max-width:760px;margin-bottom:8px;">' +
						'<thead><tr><th style="width:200px;">Clave</th><th>Etiqueta</th>' +
						'<th>Ejemplo de valor</th><th>Origen</th></tr></thead><tbody>';
					cat.items.forEach( function ( it ) {
						html += '<tr>' +
							'<td><code>' + esc( it.key ) + '</code></td>' +
							'<td>' + esc( it.label ) + '</td>' +
							'<td>' + esc( it.sample ) + '</td>' +
							'<td>' + esc( it.source ) + '</td>' +
							'</tr>';
					} );
					html += '</tbody></table>';
				} else {
					html += '<p><em>No se detectaron campos personalizados automáticamente.</em></p>';
				}
				if ( cat.tried && cat.tried.length ) {
					html += '<details style="margin-bottom:16px;"><summary style="cursor:pointer;">Ver endpoints sondeados</summary>' +
						'<ul style="margin:6px 0 0 18px;">';
					cat.tried.forEach( function ( t ) { html += '<li><code>' + esc( t ) + '</code></li>'; } );
					html += '</ul></details>';
				}
				return html;
			}

			btn.addEventListener( 'click', function () {
				status.textContent = 'Consultando Sperant…';
				result.innerHTML   = '';

				var data = new URLSearchParams();
				data.append( 'action', 'crm_sperant_catalogs' );
				data.append( 'nonce', nonce );
				data.append( 'token', val( 'token' ) );
				data.append( 'api_base', val( 'api_base' ) );
				data.append( 'auth_scheme', val( 'auth_scheme' ) );
				data.append( 'project_id', val( 'project_id' ) );

				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						if ( ! res.success ) {
							status.textContent = '';
							result.innerHTML = '<p style="color:#b32d2e;">' +
								( res.data && res.data.message ? res.data.message : 'Error.' ) + '</p>';
							return;
						}
						status.textContent = '✓ Listo. Copia el ID que corresponda a los campos de arriba.';
						var html = '';
						[ 'input_channel_id', 'source_id', 'interest_type_id', 'document_type_id' ].forEach(
							function ( k ) { if ( res.data[ k ] ) { html += tableFor( res.data[ k ] ); } }
						);
						if ( res.data.units ) { html += tableForUnits( res.data.units ); }
						if ( res.data.types ) { html += tableFor( res.data.types ); }
						if ( ! val( 'project_id' ) ) {
							html += '<p class="description">Pon el <strong>project_id</strong> (sección 2) y vuelve a cargar para ver unidades y tipologías.</p>';
						}
						html += '<hr style="margin:20px 0;">';
						if ( res.data.standard_fields ) { html += tableForStandard( res.data.standard_fields ); }
						if ( res.data.custom_fields ) { html += tableForCustom( res.data.custom_fields ); }
						result.innerHTML = html;
					} )
					.catch( function () {
						status.textContent = '';
						result.innerHTML = '<p style="color:#b32d2e;">No se pudo conectar.</p>';
					} );
			} );
		} )();
		</script>
		<?php
	}
}
