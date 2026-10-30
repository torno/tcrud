<<<<<<< HEAD
# T-CRUD

CRUD virtual para CodeIgniter 4 con Bootstrap 5.

T-CRUD permite crear CRUDs completos (listado, formularios, relaciones, archivos, exportación, etc.) sin generar archivos físicos para cada tabla. Todo se define en un controlador mediante una API fluida.

## Características

- Listado con DataTables Server-Side (búsqueda, ordenamiento, paginación).
- Filtros por columna con persistencia en sesión.
- 16 tipos de campo (text, textarea, dropdown, date, time, datetime, number, email, url, password, checkbox, color, image, file, wysiwyg, hidden).
- Relaciones 1 a N, N a N y dependientes.
- Subida de archivos e imágenes con thumbnails.
- Editor de texto enriquecido (Quill).
- 16 callbacks para inyectar lógica personalizada.
- Exportación a CSV, PDF e impresión con selección de columnas.
- Edición en línea.
- Internacionalización (es, en).
- Cacheo de configuración.
- Soft deletes.
- Todo pasa por el modelo (GenericCrudModel).

## Requisitos

- PHP 8.1 o superior.
- CodeIgniter 4.3 o superior.
- Composer.
- Base de datos MySQL / MariaDB / PostgreSQL.
- Bootstrap 5 (incluido en los assets).

## Instalación

### 1. Instalar el paquete

```bash
composer require torno/tcrud
php spark tcrud:publish
php spark tcrud:config
php spark tcrud:database
php spark tcrud:example

## Documentación completa

La documentación completa está disponible en el archivo [`docs/tcrud-documentacion.html`](docs/tcrud-documentacion.html).

Ábrelo en Word o Chrome para exportarlo a PDF.

Incluye:

- Introducción
- Instalación
- Configuración
- Uso básico
- API completa de todos los métodos
- Tipos de campo
- Relaciones (1 a N, N a N, dependientes)
- Callbacks (16)
- Acciones y botones
- Exportación
- Edición en línea
- Campos ocultos
- Filtros
- Soft deletes
- Cacheo
- Internacionalización
- Ejemplos completos
- Guía para IA

b12708a5ba483e9b940f83d5ba3aef1169f9fc48
