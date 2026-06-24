# Guía de uso — CRM Sperant × Bricks (proyecto Bastión)

Guía completa para instalar, configurar y operar el conector entre los formularios de
**Bricks Builder** y el **CRM Sperant** (API v3).

> Resumen en una frase: el formulario vive en tu web (Bricks); en cada envío, el plugin
> crea o actualiza el lead dentro de Sperant vía `POST /v3/clients`. El visitante nunca
> ve Sperant.

---

## Índice

1. [Cómo funciona (arquitectura)](#1-cómo-funciona-arquitectura)
2. [Requisitos previos](#2-requisitos-previos)
3. [Instalación](#3-instalación)
4. [Configuración paso a paso](#4-configuración-paso-a-paso)
5. [Crear el formulario en Bricks](#5-crear-el-formulario-en-bricks)
6. [El dropdown de Tipología](#6-el-dropdown-de-tipología)
7. [Probar la integración](#7-probar-la-integración)
8. [Paso a producción](#8-paso-a-producción)
9. [Solución de problemas](#9-solución-de-problemas)
10. [Preguntas frecuentes](#10-preguntas-frecuentes)

---

## 1. Cómo funciona (arquitectura)

```
Visitante  →  Formulario en la web (Bricks)  →  [plugin CRM Sperant]  →  API Sperant /v3/clients  →  Lead en el CRM
```

- El **formulario** se construye en Bricks, en tu WordPress.
- El plugin se engancha al hook `bricks/form/custom_action` de Bricks.
- En cada envío toma los campos, los mapea a los nombres que espera Sperant y hace la
  petición a la API.
- Sperant **deduplica** por DNI / email / teléfono: si el lead ya existe, lo actualiza en
  vez de duplicarlo.

**Importante:** Sperant NO te da un formulario para embeber. Lo de "crear formulario dentro
de Sperant" en su manual aplica solo a **Facebook Lead Ads**, no a una web propia.

---

## 2. Requisitos previos

| Requisito | Detalle |
|-----------|---------|
| WordPress + Bricks Builder | El tema/builder Bricks instalado y activo |
| Plugin CRM Sperant | Este repo, instalado en `wp-content/plugins/` |
| Token de Sperant | Primero el de **prueba**; luego el de **producción** (lo entrega Sperant) |
| IDs de catálogos | Se obtienen con el botón "Cargar catálogos" (ver §4) |

Notas de la API de Sperant (de su documentación oficial):
- La API es **solo backend**: tiene **CORS bloqueado**, no se puede llamar desde JavaScript del navegador. Por eso este plugin hace la llamada desde PHP (servidor). ✔️
- **Rate limit:** 15 peticiones por segundo.
- El token es **API Key**, único por aplicación y **no caduca**.

---

## 3. Instalación

Como el plugin se obtiene desde GitHub:

**Opción A — git clone en el servidor (Plesk / SSH):**
```bash
cd wp-content/plugins/
git clone https://github.com/LucumaAgency/sperant-bricks.git crm-sperant
```

**Opción B — descargar ZIP:**
1. En GitHub → *Code* → *Download ZIP*.
2. Descomprime y **renombra la carpeta** `sperant-bricks-main` → `crm-sperant`.
3. Súbela a `wp-content/plugins/`.

Luego: WordPress → **Plugins** → activar **CRM Sperant — Bricks Connector**.

---

## 4. Configuración paso a paso

Ve a **Ajustes → CRM Sperant**.

### Paso 1 — Conexión
- **API Base URL:** ya viene `https://api.eterniasoft.com` (entorno de **prueba**).
  En producción se cambia a `https://api.sperant.com`.
- **Token:** pega el token de Sperant.
- **Esquema de Authorization:** déjalo en **Token a secas** (confirmado que así funciona).

### Paso 2 — Cargar catálogos (clave)
Haz clic en **"Cargar catálogos desde Sperant"**. El plugin consulta la API y muestra en
pantalla las tablas `ID → nombre` de:
- Canales de entrada (`input_channel_id`)
- Medios de captación (`source_id`)
- Niveles de interés (`interest_type_id`)
- Tipos de documento (`document_type_id`)
- **Si pusiste el `project_id`:** además las **Unidades** (con su `unit_id`) y **Tipologías**.

Copia los IDs que correspondan a los campos de la sección 2.

### Paso 3 — IDs del proyecto
Valores confirmados para Bastión en el **entorno de prueba** (en producción cambian):

| Campo | Valor | Significado |
|-------|-------|-------------|
| `project_id` | **473** | Proyecto Bastión |
| `input_channel_id` | **44** | "web form" |
| `source_id` | **4** | "página web" |
| `interest_type_id` | **15** | "por contactar" |
| `document_type_id` | **1** | DNI |

### Paso 4 — Mapeo de campos
Por cada campo de tu formulario escribe el **ID del campo de Bricks** (formato
`form-field-xxxxx`, visible en el panel del campo). Mapeo de Bastión:

| Campo del form | → Sperant |
|----------------|-----------|
| Nombre   | `fname` |
| Apellido | `lname` |
| DNI      | `document` (+ `document_type_id`) |
| Email    | `email` |
| Teléfono | `phone` |
| Mensaje  | `observation` |
| Tipología | `extra_fields` o `unit_id` (ver §6) |

### Paso 5 — Tipología y opciones
- **Modo tipología:** "Campo personalizado" (texto) para empezar; "unit_id real" si vas a
  enlazar la unidad de verdad.
- **Form ID objetivo:** opcional, limita el envío a un solo formulario.
- **Modo debug:** actívalo durante las pruebas para ver errores en `wp-content/debug.log`.

Guarda los ajustes.

---

## 5. Crear el formulario en Bricks

1. Inserta un elemento **Form** y agrega los campos: Nombre, Apellido, DNI, Email,
   Teléfono, Tipología (Select), Mensaje (Textarea).
2. Anota el **ID de cada campo** (panel del campo en Bricks) y ponlos en el mapeo (§4, paso 4).
3. En **Actions after submit** activa la acción **Custom**.
   Puedes dejar también **Email** y **Save submission** como respaldo, así no pierdes el
   lead si el CRM falla.

> El plugin solo actúa si el formulario tiene la acción **Custom** activada.

---

## 6. El dropdown de Tipología

El campo de tipología muestra unidades como `601 - 1 Ambiente`. Hay dos modos:

### Modo "Campo personalizado" (recomendado para empezar)
Guarda el texto tal cual en `extra_fields`. En el Select de Bricks, valor = etiqueta:
```
601 - 1 Ambiente
701 - 1 Ambiente
201 - 2 Ambientes
...
```
La clave del campo extra (por defecto `tipologia`) debe existir como **campo personalizado
en Sperant** para que se guarde enlazada.

### Modo "unit_id real"
El `value` de cada opción es el **ID interno de la unidad** en Sperant (no el número "601").
En el Select de Bricks, `valor : etiqueta`:
```
7567 : 601 - 1 Ambiente
7564 : 701 - 1 Ambiente
7529 : 401 - 2 Ambientes
...
```
La tabla completa de `unit_id` (entorno de prueba) está en
[`../UNIDADES.md`](../UNIDADES.md). **Ojo:** estos IDs cambian en producción.

---

## 7. Probar la integración

1. Activa **Modo debug** en los ajustes.
2. Envía el formulario con datos de prueba (usa un teléfono/DNI/email **únicos**, porque la
   API deduplica y, si ya existen, devolverá el lead existente).
3. Revisa `wp-content/debug.log`:
   - `[CRM Sperant] Lead creado en Sperant. client_id=XXXX` → ✔️ OK.
   - `[CRM Sperant] Error al crear lead. HTTP 401/422 ...` → revisa token / IDs / campos.
4. Confirma el lead en Sperant (módulo Leads/Clientes del proyecto 473).

**Comprobado en la integración:** el `POST /v3/clients` responde **HTTP 200** y la
deduplicación por teléfono funciona en vivo (un teléfono repetido devolvió el lead
existente en lugar de duplicarlo).

---

## 8. Paso a producción

Cuando Sperant entregue el **token de producción** (tras su reunión de validación):

- [ ] Cambiar **API Base URL** a `https://api.sperant.com`.
- [ ] Pegar el **token de producción**.
- [ ] Pulsar **"Cargar catálogos"** otra vez: **los IDs cambian** entre prueba y producción
      (canales, medios, interés, documento, y **todos los `unit_id`**).
- [ ] Actualizar en los ajustes: `input_channel_id`, `source_id`, `interest_type_id`,
      `document_type_id` y el dropdown de tipología (si usas `unit_id` real).
- [ ] Enviar un lead de prueba real y confirmarlo en Sperant.
- [ ] Desactivar **Modo debug**.

Ver checklist detallado en [`PASO-A-PRODUCCION.md`](PASO-A-PRODUCCION.md).

---

## 9. Solución de problemas

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| HTTP 401 Unauthorized | Token incorrecto, o token de prueba apuntando a producción (o viceversa) | Verifica que API Base y token sean del **mismo entorno** |
| HTTP 422 | Falta un obligatorio | Asegura `fname`, `input_channel_id`, `source_id`, `interest_type_id` |
| Lead llega sin tipología | La clave de `extra_fields` no coincide con el campo personalizado de Sperant | Ajusta "Clave del campo extra" para que coincida |
| No llega nada | El form no tiene la acción **Custom**, o el "Form ID objetivo" no coincide | Activa Custom / vacía el Form ID objetivo |
| "Escribe primero el token" al cargar catálogos | El campo token está vacío | Pega el token y reintenta |
| Unidades no aparecen al cargar catálogos | Falta el `project_id` | Pon 473 y vuelve a cargar |

Los obligatorios de la API (`POST /v3/clients`) son: **`fname`, `input_channel_id`,
`source_id`, `interest_type_id`**.

---

## 10. Preguntas frecuentes

**¿El formulario se crea dentro de Sperant?**
No. El formulario vive en tu web (Bricks). Sperant solo recibe los datos por API.

**¿Por qué hay dos URLs (eterniasoft y sperant)?**
Sperant tiene entorno de **prueba** (`api.eterniasoft.com`) y de **producción**
(`api.sperant.com`). Se integra y prueba en el primero; producción se habilita después.

**¿El token caduca?**
No. Es una API Key única por aplicación que no expira. Pero el de **prueba** y el de
**producción** son distintos.

**¿Puedo además generar una proforma/cotización?**
Sí. Tras crear el lead, se puede llamar a `POST /v3/budgets` con el `client_id` devuelto +
`template_id` + `unit_id`. El cliente API ya incluye `create_budget()`. Ver
[`REFERENCIA-API-SPERANT.md`](REFERENCIA-API-SPERANT.md).

**¿Se pierden leads si Sperant está caído?**
No, si dejas activas las acciones **Email** y **Save submission** de Bricks como respaldo.
El plugin no bloquea el envío del formulario aunque la API falle.
