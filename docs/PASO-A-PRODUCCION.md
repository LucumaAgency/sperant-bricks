# Checklist — Paso de prueba a producción

Sperant tiene dos entornos con **tokens e IDs distintos**. Esta es la lista para migrar el
conector de **prueba** (`api.eterniasoft.com`) a **producción** (`api.sperant.com`).

---

## Antes de empezar

- [ ] Confirmar con Sperant (`soporte@sperant.com`) la **reunión de validación**.
- [ ] Recibir el **token de producción**.
- [ ] Confirmar el **`project_id` de Bastión en producción** (puede diferir del 473 de prueba).

---

## En el plugin (Ajustes → CRM Sperant)

- [ ] **API Base URL** → `https://api.sperant.com`
- [ ] **Token** → el de producción
- [ ] **Esquema de Authorization** → Token a secas (igual que en prueba)
- [ ] Pulsar **"Cargar catálogos desde Sperant"** y **re-leer todos los IDs**:
  - [ ] `input_channel_id` (buscar el equivalente a "web form")
  - [ ] `source_id` (el equivalente a "página web")
  - [ ] `interest_type_id` (el equivalente a "por contactar")
  - [ ] `document_type_id` (DNI; casi siempre 1, pero verificar)
  - [ ] `project_id`
- [ ] Si usas **tipología en modo `unit_id` real**: recargar las **Unidades** y reemplazar
      TODOS los `value` del Select de Bricks por los nuevos `unit_id` de producción.

> ⚠️ Los `unit_id` de [`../UNIDADES.md`](../UNIDADES.md) son del entorno de PRUEBA y **no
> sirven en producción**.

---

## Verificación

- [ ] Activar **Modo debug** temporalmente.
- [ ] Enviar un lead de prueba real desde la web con datos **únicos** (teléfono/email/DNI).
- [ ] Confirmar en `debug.log`: `Lead creado en Sperant. client_id=XXXX`.
- [ ] Confirmar el lead en el panel de Sperant (proyecto correcto, canal/medio/interés correctos).
- [ ] Probar un envío **repetido** (mismo teléfono) y confirmar que **actualiza**, no duplica.
- [ ] Revisar que la **tipología** llegue (campo extra o unidad enlazada).

---

## Cierre

- [ ] **Desactivar Modo debug.**
- [ ] Dejar activas las acciones de respaldo en Bricks (**Email** + **Save submission**).
- [ ] Documentar en el repo los IDs finales de producción (sin incluir el token).

---

## Tabla comparativa (rellenar en producción)

| Campo | Prueba | Producción |
|-------|--------|------------|
| API Base | `https://api.eterniasoft.com` | `https://api.sperant.com` |
| project_id | 473 | _____ |
| input_channel_id | 44 (web form) | _____ |
| source_id | 4 (página web) | _____ |
| interest_type_id | 15 (por contactar) | _____ |
| document_type_id | 1 (DNI) | _____ |
| Token | (prueba) | (producción) |

> El **token nunca** se sube al repositorio. Guárdalo solo en los ajustes del plugin
> (base de datos de WordPress) o en un gestor de secretos.
