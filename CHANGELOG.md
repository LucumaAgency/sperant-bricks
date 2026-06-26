# Changelog

Todas las novedades relevantes de este conector.

## [1.1.0] — 2026-06

### Añadido
- El botón **"Cargar catálogos y campos desde Sperant"** (antes "Cargar catálogos…") ahora,
  además de los catálogos, muestra:
  - **Campos estándar** del lead (`POST /v3/clients`): tabla fija con campo, si es obligatorio,
    descripción y a qué ajuste/mapeo del plugin corresponde.
  - **Campos personalizados** (`extra_fields`): detección *best-effort*, ya que la API v3 **no**
    expone un endpoint oficial. Se sondean endpoints candidatos (`/v3/custom_fields`,
    `/v3/extra_fields`, `/v3/projects/{id}/custom_fields`, etc.) y se leen las claves de
    `extra_fields` presentes en una muestra de leads recientes (`GET /v3/clients`). Incluye un
    desplegable "Ver endpoints sondeados" para diagnóstico.
- `CRM_Sperant_Client::get_json()`: GET genérico que devuelve el JSON decodificado tal cual,
  para sondear endpoints de formato no estándar.

### Nota
- Los campos personalizados solo se detectan si existen como endpoint o si ya hay leads que los
  usan. Si no aparece el que buscas, créalo en el panel de Sperant y usa esa misma clave en
  "Clave del campo extra".

## [1.0.0] — 2026-06

### Añadido
- Conector entre formularios de **Bricks Builder** y el **CRM Sperant** (API v3) vía el hook
  `bricks/form/custom_action`.
- Creación/actualización automática de leads en `POST /v3/clients` en cada envío de formulario.
- Página de ajustes en el admin de WordPress (**Ajustes → CRM Sperant**) con:
  - Conexión (API Base, token, esquema de Authorization).
  - IDs del proyecto (project_id, input_channel_id, source_id, interest_type_id, document_type_id).
  - Mapeo de campos del formulario de Bricks (`form-field-xxxxx` → campo de Sperant).
  - Manejo de tipología en dos modos: campo personalizado (`extra_fields`) o `unit_id` real.
  - Opciones: Form ID objetivo y Modo debug.
- Botón **"Cargar catálogos desde Sperant"**: consulta vía AJAX los catálogos
  (`input_channels`, `captation_ways`, `interest_types`, `document_types`) y, con `project_id`,
  también las **unidades** (`/v3/projects/{id}/units`) y **tipologías** (`/v3/projects/{id}/types`),
  mostrándolos en tablas `ID → nombre`.
- Cliente API (`CRM_Sperant_Client`) con `create_client()`, `create_budget()` (proformas) y
  `get_resource()` (catálogos).
- Filtro `crm_sperant_client_payload` para ajustar el payload antes de enviarlo.
- Respaldo: el plugin no bloquea el envío del formulario si la API falla (el lead queda en
  el Save submission / Email de Bricks).
- Documentación:
  - `README.md` — visión general.
  - `docs/GUIA-DE-USO.md` — guía completa de instalación, configuración y uso.
  - `docs/REFERENCIA-API-SPERANT.md` — referencia de endpoints e IDs.
  - `docs/PASO-A-PRODUCCION.md` — checklist de migración prueba → producción.
  - `UNIDADES.md` — mapeo Tipología → unit_id del proyecto Bastión (473) y config recomendada.

### Configuración por defecto
- **API Base** apunta al **entorno de prueba** (`https://api.eterniasoft.com`) mientras se
  integra. Producción: `https://api.sperant.com`.
- Autenticación confirmada: header `Authorization: <token>` (token a secas).

### Verificado
- Token de prueba válido contra `api.eterniasoft.com`.
- GET de catálogos, unidades y tipologías del proyecto 473 OK.
- `POST /v3/clients` responde HTTP 200; deduplicación por teléfono confirmada en vivo.
