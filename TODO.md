# TODO

## Catálogo de tipos de documento del receptor (CAT-022)

### Por qué

- Rutely POS ahora guarda sus propios clientes con los datos que necesita un receptor de DTE.
- Regla acordada: **cualquier código dado por Hacienda** (tipos de documento, departamentos, municipios, actividades económicas, etc.) se obtiene de este proyecto, que es la única fuente de los catálogos de MH. Así, si Hacienda cambia un código, se corrige en un solo lugar.
- Este proyecto hoy no expone los tipos de documento: solo están fijos en `StoreDteRequest` (`Rule::in(['13', '36', '03', '37'])`). Por eso el POS los tiene fijos temporalmente en `App\CustomerDocumentType` (`dashboard-pos/app/CustomerDocumentType.php`). Ese enum se reemplaza cuando exista este catálogo.

### Qué hacer

- [x] Tabla `document_types` (`code`, `description`) con su seeder, siguiendo el patrón de `establishment_types`.
- [x] Sembrar los códigos que define Hacienda: `13` DUI, `36` NIT, `02` Carnet de Residente, `03` Pasaporte, `37` Otro.
- [x] `GET /api/v1/data/document-types` en `routes/guess.php`, con la misma paginación y filtros (`id`, `code`, `description`) que los demás catálogos.
- [x] Validar `receptor.tipoDocumento` en `StoreDteRequest` contra `document_types` en lugar de la lista fija.

### Cómo lo consume el POS

El backend del POS llama a este endpoint por HTTP (`DTE_URL`), lo guarda en caché un día y le reenvía los datos a su frontend. El frontend del POS nunca habla directo con DTE.
