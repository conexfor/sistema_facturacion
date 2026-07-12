# Impresión de comprobantes (PDF A4 + Ticket 80 mm) e integración SUNAT/RENIEC

Documento de la corrección aplicada al sistema de emisión/impresión de documentos
electrónicos: **Factura, Boleta de venta, Nota de crédito, Nota de débito y Guía de
remisión**.

> **Nota sobre "Boleta de remuneración":** este ERP es de **ventas**, no de planillas.
> No existe (ni se emite ante SUNAT desde aquí) una boleta de remuneración/pago de
> haberes. El requerimiento se interpretó como la **Boleta de venta** (comprobante 03),
> que sí genera el sistema y quedó cubierta por esta corrección.

---

## 1. Causa raíz

Se encontraron tres focos independientes que producían "impresión incompleta" y
"sin datos de SUNAT/RENIEC":

| # | Problema | Causa raíz |
|---|----------|-----------|
| 1 | La **Guía de remisión no se podía imprimir** | No existía método `despatch()` en `DocumentPdfService`, ni plantilla, ni ruta `guias.pdf`. Solo se generaba XML/CDR. |
| 2 | **No existía impresión en ticket** para ningún documento | Solo había una plantilla A4 (`comprobante.blade.php`). No existía formato térmico de 80 mm. |
| 3 | **SUNAT/RENIEC no traía datos** (RUC/DNI, razón social, domicilio, estado) | `PERU_API_TOKEN` no estaba definido en `.env` → `DocumentLookupService::isEnabled()` devolvía `false` y la consulta quedaba deshabilitada. Además la consulta de RUC no extraía **estado del contribuyente**, **condición de domicilio** ni el **domicilio fiscal completo**. |
| 4 | El **domicilio del cliente salía vacío** en el comprobante | El PDF leía la dirección de la relación viva `customer->direccion`. Si el cliente no tenía dirección (porque la consulta estaba apagada) se imprimía "—". La venta no guardaba un *snapshot* del domicilio. |

---

## 2. Cambios aplicados

### Integración SUNAT / RENIEC
- **`config/services.php`** + **`.env` / `.env.example`**: se añadieron `PERU_API_URL`,
  `PERU_API_TOKEN`, `PERU_API_TIMEOUT`. **Para activar la consulta hay que poner un token**
  (proveedor por defecto: [decolecta.com](https://www.decolecta.com); también compatible con apis.net.pe).
- **`app/Services/DocumentLookupService.php`**:
  - RUC ahora normaliza y devuelve **estado** (ACTIVO/BAJA…), **condición** (HABIDO/NO HABIDO),
    **domicilio fiscal completo**, ubigeo y departamento/provincia/distrito.
  - `habilitado`: indica si el contribuyente es válido para emitir (ACTIVO + HABIDO).
  - Manejo de errores robusto: reintentos, mensajes claros para 401/403/422/404 y timeouts.
- **`Customers/Index.tsx`**: tras consultar, muestra el **estado/condición SUNAT** y advierte
  en rojo si el contribuyente **no está habilitado**.

### Snapshot de domicilio fiscal
- Migración `..._add_cliente_direccion_to_sales_table.php` (columna `cliente_direccion`, nullable).
- `SaleService::registrar()` guarda el domicilio del cliente al momento de la venta
  (escritura defensiva con `Schema::hasColumn`, así no rompe si aún no se corrió la migración).
- Los PDFs usan `cliente_direccion` con *fallback* a `customer->direccion`.

### Impresión: A4 + Ticket, y Guía de remisión
- **`app/Services/DocumentPdfService.php`**:
  - Todos los métodos aceptan `$formato` (`'a4'` | `'ticket'`).
  - **Nuevo** `despatch()`: representación impresa de la Guía de remisión (motivo/modalidad de
    traslado, partida/llegada, peso/bultos, transportista o vehículo+chofer, documento relacionado, QR).
  - `render()` elige plantilla y papel: A4 para `comprobante`/`guia`, y **80 mm** (226,77 pt de
    ancho, alto estimado por contenido) para ticket.
- **Nuevas plantillas**:
  - `resources/views/documents/guia.blade.php` (Guía A4).
  - `resources/views/documents/ticket.blade.php` (ticket térmico, sirve comprobante y guía).
- **Rutas/Controladores**: `guias.pdf` nuevo; `ventas.pdf`, `notas.pdf`, `compras.pdf`,
  `guias.pdf` aceptan `?formato=ticket`.
- **Frontend**: botones **PDF A4** y **Ticket** en Venta (Show), Notas (Index) y Guías (Index).

---

## 3. Cómo activar / usar

1. Definir el token de consulta en `.env`:
   ```env
   PERU_API_URL=https://api.decolecta.com/v1
   PERU_API_TOKEN=tu_token_aqui
   ```
   Luego `php artisan config:clear`.
2. Correr la migración del snapshot de domicilio:
   ```bash
   php artisan migrate
   ```
3. Compilar el frontend: `npm run build` (o `npm run dev`).
4. Imprimir:
   - **A4**: `GET /ventas/{id}/pdf` · `/notas/{id}/pdf` · `/guias/{id}/pdf`
   - **Ticket**: la misma ruta con `?formato=ticket`.

---

## 4. Verificación realizada

- `php -l` en todos los PHP modificados: sin errores.
- `php artisan view:cache`: las plantillas Blade compilan.
- `npx tsc --noEmit`: el frontend typechea sin errores.
- Render real con DomPDF (datos de ejemplo): **factura A4, factura ticket, guía A4 y guía
  ticket** generan PDFs válidos (`%PDF-`). La guía comparte el ticket con el comprobante.

---

## 5. Mejoras implementadas (2.ª iteración)

Todas las mejoras propuestas quedaron implementadas:

1. **Caché de consultas RUC/DNI** — `DocumentLookupService` cachea el resultado exitoso
   `PERU_API_CACHE_TTL` segundos (24 h por defecto; `0` lo desactiva). No depende del tiempo
   de respuesta del proveedor en cada venta y ahorra cuota de la API.
2. **Estado/condición del contribuyente persistidos** — nuevas columnas
   `customers.estado_contribuyente` y `customers.condicion_domicilio` (+ `ubigeo`), que se
   llenan al consultar. En `Customers/Index` se muestra un **badge rojo "No habido"** y, tras
   consultar, el estado SUNAT en el modal. Accesor `Customer::habilitado_sunat`.
3. **Logo de la empresa** — se incrusta (`companies.logo_path` → data URI) en el encabezado de
   PDF A4, guía y ticket. Si no hay logo o falla la lectura, se omite sin romper el render.
4. **QR de la Guía (GRE)** — se ajustó a la estructura documentada por SUNAT para la
   representación impresa: `RUC | 09 | serie | correlativo(8díg) | fecha | tipoDocDest | numDocDest`.
5. **Consulta automática de CDR de guías** — comando `php artisan guias:consultar-pendientes`
   (procesa las guías en estado `enviado` con ticket) programado cada 30 min en
   `routes/console.php`. Requiere tener el *scheduler* corriendo (`php artisan schedule:work`
   o un cron a `schedule:run`).
6. **Tests de característica** — `tests/Feature/DocumentPrintingTest.php` renderiza en A4 y
   ticket la venta, la nota de crédito y la guía, y valida el *snapshot* de domicilio.

### Pendientes / ideas futuras
- QR de la guía como **URL de consulta** de un PSE/OSE específico (si se adopta uno).
- Subida del logo desde la pantalla de Configuración (hoy `logo_path` se setea por datos/seed).
- Bloqueo duro (no solo advertencia) de factura a RUC no habido, si el negocio lo requiere.

## 6. Verificación (2.ª iteración)

- `php -l` de todos los PHP nuevos/modificados: sin errores.
- `npx tsc --noEmit`: sin errores.
- `php artisan test` — **DocumentPrintingTest (4)** + regresión **DespatchTest / NotesTest /
  SalesTest / BranchesAndPdfTest (16)**: todo verde.
- `php artisan schedule:list`: la tarea `guias:consultar-pendientes` aparece cada 30 min.
