<?php

namespace Torno\TCrud\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PublishAssets extends BaseCommand
{
    protected $group       = 'TCrud';
    protected $name        = 'tcrud:publish';
    protected $description = 'Publica los assets y las vistas de T-CRUD';

    public function run(array $params)
    {
        // 1. Copiar assets
        $assetsSource = dirname(__DIR__, 2) . '/resources/assets/';
        $assetsDest   = FCPATH . 'assets/tcrud/';
        $this->copyRecursive($assetsSource, $assetsDest);
        CLI::write('Assets publicados en: ' . $assetsDest, 'green');

        // 2. Copiar vistas
        $viewsSource = dirname(__DIR__, 2) . '/resources/views/tcrud/';
        $viewsDest   = APPPATH . 'Views/tcrud/';
        $this->copyRecursive($viewsSource, $viewsDest);
        CLI::write('Vistas publicadas en: ' . $viewsDest, 'green');
    }

    protected function copyRecursive(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            CLI::error("No se encuentra la carpeta: $src");
            return;
        }

        if (!is_dir($dst)) {
            mkdir($dst, 0777, true);
        }

        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;

            if (is_dir($srcFile)) {
                $this->copyRecursive($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }

        closedir($dir);
    }
}