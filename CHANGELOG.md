# Changelog

Todas las novedades relevantes de este conector.

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
