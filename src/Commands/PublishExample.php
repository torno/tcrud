<?php

namespace Torno\TCrud\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PublishExample extends BaseCommand
{
    protected $group       = 'TCrud';
    protected $name        = 'tcrud:example';
    protected $description = 'Copia el controlador de ejemplo de T-CRUD a app/Controllers/ y añade las rutas';

    public function run(array $params)
    {
        $this->publishController();
        $this->publishRoutes();
    }

    protected function publishController(): void
    {
        $source = dirname(__DIR__, 2) . '/src/Examples/BooksController.php';
        $dest   = APPPATH . 'Controllers/Books.php';

        if (!file_exists($source)) {
            CLI::error("No se encuentra el controlador de ejemplo en: $source");
            return;
        }

        if (file_exists($dest)) {
            CLI::write("El controlador ya existe en: $dest", 'yellow');
            return;
        }

        $content = file_get_contents($source);
        $content = str_replace('namespace Torno\TCrud\Examples;', 'namespace App\Controllers;', $content);
        $content = str_replace('class BooksController extends \CodeIgniter\Controller', 'class Books extends BaseController', $content);

        file_put_contents($dest, $content);
        CLI::write('Controlador de ejemplo copiado a: ' . $dest, 'green');
    }

    protected function publishRoutes(): void
    {
        $routesPath = APPPATH . 'Config/Routes.php';

        if (!file_exists($routesPath)) {
            CLI::error("No se encuentra el archivo de rutas: $routesPath");
            return;
        }

        $content = file_get_contents($routesPath);

        // Verificar si ya existen las rutas de Books
        if (strpos($content, "'Books::index'") !== false) {
            CLI::write('Las rutas de Books ya existen en Routes.php', 'yellow');
            return;
        }

        $routesBlock = <<<'PHP'

// Rutas de ejemplo para T-CRUD (Books)
$routes->get('books', 'Books::index');
$routes->get('books/tcadd', 'Books::index');
$routes->post('books/tcadd', 'Books::index');
$routes->get('books/tcedit/(:num)', 'Books::index/$1');
$routes->post('books/tcedit/(:num)', 'Books::index/$1');
$routes->get('books/tcdelete/(:num)', 'Books::index/$1');
$routes->get('books/tcview/(:num)', 'Books::index/$1');
$routes->get('books/tcclone/(:num)', 'Books::index/$1');
$routes->post('books/tcclone/(:num)', 'Books::index/$1');
$routes->post('books/tcbulkdelete', 'Books::index');
$routes->post('books/tcfilter', 'Books::index');
$routes->post('books/tcclearfilters', 'Books::index');
$routes->post('books/tcexportcsv', 'Books::index');
$routes->post('books/tcexportpdf', 'Books::index');
$routes->post('books/tcprint', 'Books::index');
$routes->post('books/tcinlineedit/(:num)', 'Books::index/$1');
$routes->post('books/tcdependent', 'Books::index');

PHP;

        // Insertar las rutas al final del archivo (antes del cierre si existe)
        $content = rtrim($content);
        if (substr($content, -2) === '?>') {
            $content = substr($content, 0, -2) . $routesBlock . "\n?>";
        } else {
            $content .= $routesBlock;
        }

        file_put_contents($routesPath, $content);
        CLI::write('Rutas de ejemplo añadidas a: ' . $routesPath, 'green');
    }
}