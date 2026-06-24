# CRM Sperant — Conector para Bricks Builder

Plugin de WordPress que conecta los formularios de **Bricks Builder** con el CRM inmobiliario **Sperant** (API v3). En cada envío del formulario, crea automáticamente el **lead/cliente** en Sperant (`POST /v3/clients`).

- **Versión:** 1.0.0
- **Autor:** Lucuma Agency
- **Proyecto:** Bastión

---

## 1. Cómo funciona (arquitectura)

El formulario **vive en tu web (Bricks)**, no dentro de Sperant. Sperant solo **recibe** los datos por su API.

```
Visitante → Form en la web (Bricks) → [este plugin] → API Sperant /v3/clients → Lead en el CRM
```

No existe un paso de "crear el formulario dentro de Sperant" para una web propia. Eso del manual de Sperant aplica solo a los formularios de **Facebook Lead Ads**.

---

## 2. Instalación

1. Copia la carpeta `crm-sperant/` a `wp-content/plugins/`.
2. En WordPress → **Plugins**, activa **CRM Sperant — Bricks Connector**.
3. Ve a **Ajustes → CRM Sperant** y completa la configuración (ver más abajo).
4. En tu formulario de Bricks, en **Actions after submit**, agrega la acción **Custom**.
   (Puedes dejar también "Email" y "Save submission" como respaldo.)

> El plugin se engancha al hook `bricks/form/custom_action`, así que basta con que el formulario tenga la acción **Custom** activada.

---

## 3. Datos que debes pedir a Sperant

Antes de configurar necesitas, del panel o soporte de Sperant:

| Dato | Para qué | Dónde se obtiene |
|------|----------|------------------|
| **Token** | Autenticación de la API | Soporte Sperant |
| Formato del header `Authorization` | "Token a secas" o `Bearer <token>` | Soporte Sperant |
| `project_id` | Proyecto al que entra el lead | Proyectos → Ver Proyecto |
| `input_channel_id` *(obligatorio)* | Canal de entrada (ej. "Web") | Soporte Sperant |
| `source_id` *(obligatorio)* | Medio de captación | Soporte Sperant |
| `interest_type_id` *(obligatorio)* | Nivel de interés | Soporte Sperant |
| `document_type_id` | Tipo de documento (DNI) | Soporte Sperant |
| `unit_id` de cada unidad | Solo si enlazas la tipología real | Proyectos → Unidades |

---

## 4. Configuración del plugin (Ajustes → CRM Sperant)

### Sección 1 — Conexión
- **API Base URL:** `https://api.sperant.com` (por defecto).
- **Token:** el token entregado por Sperant.
- **Esquema de Authorization:** prueba primero *Token a secas*; si la API rechaza, cambia a *Bearer*.

### Sección 2 — IDs del proyecto
Rellena `project_id` (Bastión = **473**), `input_channel_id`, `source_id`, `interest_type_id` y `document_type_id`.

> **Botón "Cargar catálogos desde Sperant":** después de escribir el token (sección 1),
> haz clic en este botón y el plugin consultará Sperant y mostrará en pantalla la tabla
> `ID → nombre` de canales, medios, niveles de interés y tipos de documento. Copia el ID
> que corresponda a cada campo. (Internamente llama a `/v3/input_channels`,
> `/v3/captation_ways`, `/v3/interest_types` y `/v3/document_types`.)
>
> Si además tienes el **project_id** escrito (Bastión = 473), el mismo botón trae las
> **Unidades** (con su `unit_id`, código, estado y precio) y las **Tipologías** (`type_id`)
> del proyecto, llamando a `/v3/projects/{id}/units` y `/v3/projects/{id}/types`. Así
> mapeas cada depto "601, 701…" a su `unit_id` real sin usar terminal.

### Sección 3 — Mapeo de campos
Por cada campo de tu formulario escribe el **ID del campo en Bricks** (formato `form-field-xxxxx`, visible en el panel del campo).

Mapeo del formulario de Bastión:

| Campo del formulario | Campo en Sperant |
|----------------------|------------------|
| Nombre   | `fname` |
| Apellido | `lname` |
| DNI      | `document` (+ `document_type_id`) |
| Email    | `email` |
| Teléfono | `phone` |
| Mensaje  | `observation` |
| Tipología | `extra_fields` o `unit_id` (ver sección 4) |

### Sección 4 — Tipología
El campo de tipología muestra unidades como `601 - 1 Ambiente`. Dos modos:

- **Campo personalizado (recomendado para empezar):** guarda el texto tal cual en `extra_fields`
  con la clave que definas (ej. `tipologia`). Esa clave debe existir como **campo personalizado en Sperant**.
- **unit_id real:** el `value` de cada `<option>` del select debe ser el **ID interno de la unidad** en Sperant
  (no el número "601"). Útil si luego generas la proforma.

### Sección 5 — Opciones
- **Form ID objetivo:** limita el envío a un único formulario (vacío = todos los que tengan acción Custom).
- **Modo debug:** escribe errores en `wp-content/debug.log` (requiere `WP_DEBUG_LOG` activo).

---

## 5. El dropdown de Tipología en Bricks

Configura el campo **Select** con `valor : etiqueta`.

**Modo campo personalizado** (texto): el valor y la etiqueta pueden ser iguales.
```
601 - 1 Ambiente : 601 - 1 Ambiente
701 - 1 Ambiente : 701 - 1 Ambiente
201 - 2 Ambientes : 201 - 2 Ambientes
```

**Modo unit_id real:** el valor es el ID interno de Sperant; la etiqueta es lo que ve el cliente.
```
4521 : 601 - 1 Ambiente
4522 : 701 - 1 Ambiente
4530 : 201 - 2 Ambientes
```

Las unidades del proyecto Bastión están listadas en [`UNIDADES.md`](UNIDADES.md) para que completes los `unit_id`.

---

## 6. Mapeo a la API (referencia)

`POST https://api.sperant.com/v3/clients`

```json
{
  "fname": "Juan",
  "lname": "Pérez",
  "document": "70123456",
  "document_type_id": 1,
  "email": "juan@correo.com",
  "phone": "+51987654321",
  "observation": "Mensaje del formulario",
  "project_id": 19,
  "input_channel_id": 8,
  "source_id": 2,
  "interest_type_id": 4,
  "extra_fields": { "tipologia": "601 - 1 Ambiente" }
}
```

**Obligatorios:** `fname`, `input_channel_id`, `source_id`, `interest_type_id`.
La API **deduplica** por DNI / email / teléfono: si el lead ya existe, lo actualiza en vez de duplicarlo.

---

## 7. Hooks para desarrolladores

Filtra el payload antes de enviarlo:

```php
add_filter( 'crm_sperant_client_payload', function ( $payload, $fields ) {
    // Ej. forzar un source distinto según una página
    // $payload['source_id'] = 7;
    return $payload;
}, 10, 2 );
```

---

## 8. (Opcional) Generar proforma además del lead

Crear el lead basta para captación. Si además quieres cotizar una unidad concreta:

1. Tras `create_client()`, toma el `client_id` devuelto.
2. Llama a `POST /v3/budgets` con `client_id` + `template_id` + `unit_id` (o `type_id`).

El cliente `CRM_Sperant_Client` ya incluye el método `create_budget()` listo para usarse.

### Referencia de endpoints de proforma (API v3)

| Acción | Endpoint |
|--------|----------|
| Crear proforma | `POST /v3/budgets` |
| Listar proformas | `GET /v3/budgets` (filtros: `q`, `client_id`, `created_start`, `created_end`) |
| Mostrar proforma | `GET /v3/budgets/{id}` |

**Body de `POST /v3/budgets`:**

```json
{
  "client_id": 15,      // OBLIGATORIO (lo devuelve POST /v3/clients)
  "template_id": 200,   // OBLIGATORIO (configurado en "medios digitales" del proyecto, o lo da Sperant)
  "unit_id": 45,        // unit_id O type_id (al menos uno)
  "type_id": null,
  "input_channel_id": 7,
  "source_id": 28,
  "agent_id": 74
}
```

**De dónde sale cada ID:**

| ID | Origen |
|----|--------|
| `client_id` | Respuesta de `POST /v3/clients` (`data.id`) |
| `template_id` | Configuración "medios digitales" del proyecto **o** pedirlo a Sperant (no hay endpoint para listarlo) |
| `unit_id` | `GET /v3/projects/473/units` → campo `id` (¡no es el número "601"!) |
| `type_id` | `GET /v3/projects/473/types` → campo `id` |

---

## 9. Pruebas

1. Activa **Modo debug**.
2. Envía el formulario con datos de prueba.
3. Revisa `wp-content/debug.log`:
   - `Lead creado en Sperant. client_id=XXXX` → OK.
   - `Error al crear lead. HTTP 401/422 ...` → revisa token / IDs / campos obligatorios.
4. Confirma el lead en Sperant (módulo Leads/Clientes del proyecto).

### Errores frecuentes
| Síntoma | Causa probable |
|---------|----------------|
| HTTP 401 | Token incorrecto o esquema de Authorization equivocado |
| HTTP 422 | Falta un obligatorio (`fname`, `input_channel_id`, `source_id`, `interest_type_id`) |
| Lead sin tipología | La clave de `extra_fields` no coincide con el campo personalizado en Sperant |
| No llega nada | El formulario no tiene la acción **Custom**, o el Form ID objetivo no coincide |

---

## 10. Estructura de archivos

```
crm-sperant/
├── crm-sperant.php                      # Bootstrap, activación, defaults
├── uninstall.php                        # Limpieza de opciones
├── includes/
│   ├── class-sperant-client.php         # Cliente API v3 (clients + budgets)
│   ├── class-sperant-settings.php       # Página de ajustes (admin)
│   └── class-sperant-form-handler.php   # Hook bricks/form/custom_action
├── README.md
└── UNIDADES.md                          # Tabla de unidades de Bastión
```
