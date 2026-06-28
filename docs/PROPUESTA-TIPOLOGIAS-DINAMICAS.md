# Propuesta — Tipologías/Unidades dinámicas desde Sperant en el formulario

> Estado: **EN DISCUSIÓN / pendiente de decidir** (jun 2026). No implementado todavía.
> Objetivo: que el `<select>` de tipología del formulario de Bricks se llene en vivo con las
> unidades/tipologías reales de Sperant, en vez de una lista escrita a mano (hardcodeada).

## Por qué hacerlo

- Hoy las opciones del select están escritas a mano (ej. "402 - 3 Ambientes"). Eso se
  desactualiza: si se vende un depto sigue apareciendo, y al pasar a producción los IDs cambian.
- Trayéndolas en vivo: la lista siempre está al día, se ocultan las no disponibles y **no se
  rompe nada al migrar a producción** aunque cambien los `unit_id`.

## Obstáculo técnico clave (define toda la solución)

La API de Sperant es **solo backend y bloquea CORS**. El navegador **NO puede** llamar directo
a `api.sperant.com`. Por tanto el flujo NO puede ser web → Sperant directo, sino:

```
Formulario (navegador)
   │  fetch a /wp-json/crm-sperant/v1/units   (mismo dominio, sin CORS)
   ▼
WordPress (este plugin)  ──►  Sperant /v3/projects/473/units   (token server-side, oculto)
   │  devuelve JSON limpio y cacheado
   ▼
JS rellena el <select> de tipologías (mantiene su ID form-field-xxxxx)
```

## Arquitectura recomendada

1. **Endpoint REST en el plugin** (ej. `/wp-json/crm-sperant/v1/units`) que internamente llama a
   Sperant (`/v3/projects/{id}/units` o `/types`) y devuelve solo lo necesario (id, nombre, estado).
   El token nunca llega al navegador.
2. **Caché con transients** (refrescar cada 15–60 min). Importante por rendimiento y por el
   **rate limit de Sperant (15 req/s)**: no se debe llamar a Sperant en cada visita.
3. **JS en la página** que al cargar consume ese endpoint y rellena las opciones del select
   existente (`form-field-tclsjr`). El campo conserva su ID, así que el plugin lo sigue mapeando
   igual; no hay que rehacer el formulario en Bricks.

## Decisiones pendientes (hablar con el cliente antes de implementar)

1. **¿Qué mostrar en el dropdown?**
   - Unidades específicas (`/units`): "402 - 3 Ambientes", "301 - 2 Ambientes"… (más detalle).
   - Tipologías genéricas (`/types`): "1 Ambiente", "2 Ambientes", "3 Ambientes" (más simple).
2. **¿Filtrar por disponibilidad?** Recomendado: mostrar solo las disponibles (ocultar
   vendido / en separación).
3. **¿Qué guardar al elegir una opción?**
   - Texto en `extra_fields.tipologia` (como ahora): simple.
   - `unit_id` real (modo "unit"): habilita el siguiente paso de **proformas**
     (`POST /v3/budgets`) enlazadas a la unidad real. Más potente a futuro.

   Recomendación si se quiere dejar "pro": **unidades disponibles + guardar unit_id**.

4. **Pregunta de contexto:** ¿el equipo comercial realmente filtra/contacta según la unidad que
   marcó la persona, o es informativo? Si es solo informativo, quizá no vale el esfuerzo de
   traerlas en vivo y basta una lista simple.

## Estado actual relacionado (para contexto)

- Integración formulario → lead YA funciona (POST /v3/clients, lead creado client_id de ejemplo).
- La tipología hoy va como **texto** en `extra_fields.tipologia` (modo "extra"); verificado en vivo
  que la API guarda y devuelve ese `extra_fields`.
- Mapeo real de Bricks documentado en el flujo del proyecto (fname=form-field-1f00fb, …,
  tipologia=form-field-tclsjr).
