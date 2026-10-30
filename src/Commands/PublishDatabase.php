<?php

namespace Torno\TCrud\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PublishDatabase extends BaseCommand
{
    protected $group       = 'TCrud';
    protected $name        = 'tcrud:database';
    protected $description = 'Copia el SQL de ejemplo de T-CRUD a la raíz del proyecto';

    public function run(array $params)
    {
        $source = dirname(__DIR__, 2) . '/resources/database/tcrud_example.sql';
        $dest   = ROOTPATH . 'tcrud_example.sql';

        if (!file_exists($source)) {
            CLI::error("No se encuentra el archivo SQL en: $source");
            return;
        }

        if (file_exists($dest)) {
            CLI::warning("El archivo ya existe en: $dest");
            return;
        }

        copy($source, $dest);

        CLI::write('SQL de ejemplo copiado a: ' . $dest, 'green');
        CLI::write('Importa este archivo en tu base de datos (phpMyAdmin o similar).', 'yellow');
    }
}