# Unidades y configuración de Bastión (proyecto 473)

> ⚠️ **Los `unit_id` de esta tabla son del ENTORNO DE PRUEBA** (`https://api.eterniasoft.com`).
> Al pasar a **producción** (`https://api.sperant.com`) los IDs cambian: vuelve a cargarlos
> con el botón "Cargar catálogos" usando el token de producción.

## Mapeo Tipología del formulario → unit_id (entorno de prueba)

| Etiqueta (lo que ve el cliente) | Ambientes | unit_id (PRUEBA) | Estado actual |
|---------------------------------|-----------|------------------|---------------|
| 601 - 1 Ambiente  | 1 Ambiente   | **7567** | no disponible |
| 701 - 1 Ambiente  | 1 Ambiente   | **7564** | disponible |
| 602 - 1 Ambiente  | 1 Ambiente   | **7566** | disponible |
| 702 - 1 Ambiente  | 1 Ambiente   | **7563** | disponible |
| 603 - 1 Ambiente  | 1 Ambiente   | **7565** | disponible |
| 703 - 1 Ambiente  | 1 Ambiente   | **7562** | disponible |
| 201 - 2 Ambientes | 2 Ambientes  | **7577** | disponible |
| 301 - 2 Ambientes | 2 Ambientes  | **7576** | proceso de separación |
| 401 - 2 Ambientes | 2 Ambientes  | **7529** | disponible |
| 501 - 2 Ambientes | 2 Ambientes  | **7570** | disponible |
| 801 - 2 Ambientes | 2 Ambientes  | **7561** | disponible |
| 302 - 3 Ambientes | 3 Ambientes  | **7575** | vendido |
| 402 - 3 Ambientes | 3 Ambientes  | **7572** | disponible |
| 502 - 3 Ambientes | 3 Ambientes  | **7569** | disponible |
| 802 - 3 Ambientes | 3 Ambientes  | **7560** | disponible |

## Configuración recomendada del plugin (catálogos globales — entorno de prueba)

| Campo del plugin | Valor recomendado | Notas |
|------------------|-------------------|-------|
| `project_id` | **473** | Bastión |
| `input_channel_id` | **44** | "web form" (ideal para el formulario de la web) |
| `source_id` | **4** | "página web" (alternativas: 45 "web real", 53 "página web seo") |
| `interest_type_id` | **15** | "por contactar" (lead nuevo sin gestionar) |
| `document_type_id` | **1** | DNI |
| API Base | `https://api.eterniasoft.com` | producción: `https://api.sperant.com` |
| Esquema Authorization | Token a secas (raw) | confirmado: header `Authorization: <token>` |

> Los `source_id` e `interest_type_id` son una recomendación; ajústalos al criterio comercial
> de Ve-Más / Bastión. La lista completa salió de los catálogos de Sperant.

## Opciones para el campo Select de Bricks

### Modo campo personalizado (texto) — más simple
```
601 - 1 Ambiente
701 - 1 Ambiente
602 - 1 Ambiente
702 - 1 Ambiente
603 - 1 Ambiente
703 - 1 Ambiente
201 - 2 Ambientes
301 - 2 Ambientes
401 - 2 Ambientes
501 - 2 Ambientes
801 - 2 Ambientes
302 - 3 Ambientes
402 - 3 Ambientes
502 - 3 Ambientes
802 - 3 Ambientes
```

### Modo unit_id real (valor : etiqueta) — entorno de PRUEBA
```
7567 : 601 - 1 Ambiente
7564 : 701 - 1 Ambiente
7566 : 602 - 1 Ambiente
7563 : 702 - 1 Ambiente
7565 : 603 - 1 Ambiente
7562 : 703 - 1 Ambiente
7577 : 201 - 2 Ambientes
7576 : 301 - 2 Ambientes
7529 : 401 - 2 Ambientes
7570 : 501 - 2 Ambientes
7561 : 801 - 2 Ambientes
7575 : 302 - 3 Ambientes
7572 : 402 - 3 Ambientes
7569 : 502 - 3 Ambientes
7560 : 802 - 3 Ambientes
```

## Endpoints usados (referencia)
- Canales: `GET /v3/input_channels`
- Medios: `GET /v3/captation_ways`
- Niveles de interés: `GET /v3/interest_types`
- Tipos de documento: `GET /v3/document_types`
- Unidades: `GET /v3/projects/473/units`
- Tipologías: `GET /v3/projects/473/types`
