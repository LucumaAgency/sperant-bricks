# Referencia — API v3 de Sperant

Referencia técnica de los endpoints de Sperant que usa el conector, con ejemplos reales
verificados durante la integración del proyecto **Bastión (473)**.

---

## Entornos

| Entorno | Base URL |
|---------|----------|
| **Prueba** | `https://api.eterniasoft.com` |
| **Producción** | `https://api.sperant.com` |

- Flujo oficial: se solicita un **token de prueba** a `soporte@sperant.com`, se integra, se
  agenda una **reunión de validación**, y recién entregan el **token de producción**.
- **Autenticación:** header `Authorization: <token>` (token a secas, **no** Bearer).
- **API Key** única por aplicación, **no caduca**.
- **Solo backend** (CORS bloqueado). **Rate limit:** 15 req/s.
- Respuestas y envíos en **JSON**. Paginación por defecto: 20 elementos.

---

## Crear lead / cliente

`POST /v3/clients`

**Headers**
```
Authorization: <token>
Content-Type: application/json
```

**Body**
```json
{
  "fname": "Juan",                 // OBLIGATORIO
  "input_channel_id": 44,          // OBLIGATORIO
  "source_id": 4,                  // OBLIGATORIO
  "interest_type_id": 15,          // OBLIGATORIO
  "lname": "Pérez",
  "email": "juan@correo.com",
  "phone": "+51987654321",
  "document": "70123456",
  "document_type_id": 1,
  "project_id": 473,
  "observation": "Mensaje del formulario",
  "utm_source": "google",
  "utm_medium": "cpc",
  "utm_campaign": "bastion",
  "extra_fields": { "tipologia": "601 - 1 Ambiente" }
}
```

**Comportamiento:** la API **deduplica** por DNI / email / teléfono. Si el lead ya existe,
**actualiza** los campos permitidos y devuelve el registro existente (no duplica).
Verificado en vivo: un teléfono repetido devolvió el lead existente con HTTP 200.

**Respuesta (200)**
```json
{
  "data": {
    "type": "clients",
    "id": "22004",
    "attributes": {
      "id": 22004,
      "fname": "...",
      "email": "...",
      "phone": "...",
      "document_type_id": 1,
      "document_type": "DNI",
      "interest_type_id": 15,
      "interest_type": "por contactar",
      "status": "interested",
      "uuid": "..."
    }
  }
}
```

---

## Proforma / cotización

Una proforma cotiza una unidad para un cliente que **ya existe**.

| Acción | Endpoint |
|--------|----------|
| Crear | `POST /v3/budgets` |
| Listar | `GET /v3/budgets` (filtros: `q`, `client_id`, `created_start`, `created_end`) |
| Mostrar | `GET /v3/budgets/{id}` |

**Body de `POST /v3/budgets`**
```json
{
  "client_id": 22004,    // OBLIGATORIO (lo devuelve POST /v3/clients)
  "template_id": 200,    // OBLIGATORIO
  "unit_id": 7567,       // unit_id O type_id (al menos uno)
  "type_id": null,
  "input_channel_id": 44,
  "source_id": 4,
  "agent_id": 74
}
```

**De dónde sale cada ID**

| ID | Origen |
|----|--------|
| `client_id` | Respuesta de `POST /v3/clients` (`data.id`) |
| `template_id` | Se configura en "medios digitales" del proyecto **o** lo entrega Sperant. **No hay endpoint para listarlo.** |
| `unit_id` | `GET /v3/projects/{project_id}/units` → campo `id` |
| `type_id` | `GET /v3/projects/{project_id}/types` → campo `id` |

---

## Catálogos (endpoints GET)

| Recurso | Endpoint | Se usa para |
|---------|----------|-------------|
| Canales de entrada | `GET /v3/input_channels` | `input_channel_id` |
| Medios de captación | `GET /v3/captation_ways` | `source_id` |
| Niveles de interés | `GET /v3/interest_types` | `interest_type_id` |
| Tipos de documento | `GET /v3/document_types` | `document_type_id` |
| Unidades del proyecto | `GET /v3/projects/{id}/units` | `unit_id` |
| Tipologías del proyecto | `GET /v3/projects/{id}/types` | `type_id` |

Formato de respuesta de un catálogo:
```json
{ "data": [
  { "type": "input_channels", "id": "44", "attributes": { "id": 44, "name": "web form" } }
]}
```

### Valores reales (entorno de PRUEBA — cambian en producción)

**Tipos de documento (`document_type_id`)**
| ID | Nombre |
|----|--------|
| 1 | DNI |
| 2 | Carné de Extranjería |
| 3 | RUC |
| 4 | Pasaporte |

**Canal de entrada recomendado:** `44` = "web form"
**Medio de captación recomendado:** `4` = "página web" (alt.: `45` "web real", `53` "página web seo")
**Nivel de interés recomendado:** `15` = "por contactar" (lead nuevo)

> Las listas completas de canales (40+), medios (80+) y niveles de interés (30+) se obtienen
> con el botón "Cargar catálogos" del plugin o llamando a los endpoints de arriba.

---

## Probar con curl (entorno de prueba)

> El consumo es backend. Estos `curl` son solo para inspección/diagnóstico manual.

```bash
TOKEN="tu_token"
B="https://api.eterniasoft.com"

# Listar catálogos
curl -s -H "Authorization: $TOKEN" "$B/v3/input_channels"
curl -s -H "Authorization: $TOKEN" "$B/v3/captation_ways"
curl -s -H "Authorization: $TOKEN" "$B/v3/interest_types"
curl -s -H "Authorization: $TOKEN" "$B/v3/document_types"

# Unidades y tipologías del proyecto 473
curl -s -H "Authorization: $TOKEN" "$B/v3/projects/473/units"
curl -s -H "Authorization: $TOKEN" "$B/v3/projects/473/types"

# Crear lead
curl -s -X POST -H "Authorization: $TOKEN" -H "Content-Type: application/json" \
  -d '{"fname":"Test","input_channel_id":44,"source_id":4,"interest_type_id":15,"project_id":473}' \
  "$B/v3/clients"
```

---

## Documentación oficial

- Índice / introducción: https://sperant.gitbook.io/apiv3/
- Crear cliente: https://sperant.gitbook.io/apiv3/clientes/crear-cliente
- Crear proforma: https://sperant.gitbook.io/apiv3/proformas/crear-proforma
- Recursos (catálogos): https://sperant.gitbook.io/apiv3/recursos/
- Soporte: soporte@sperant.com
