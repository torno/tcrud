<?php

namespace Torno\TCrud\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PublishConfig extends BaseCommand
{
    protected $group       = 'TCrud';
    protected $name        = 'tcrud:config';
    protected $description = 'Copia la configuración de T-CRUD a app/Config/TCrud.php';

	public function run(array $params)
	{
		$source = dirname(__DIR__, 2) . '/src/Config/TCrud.php';
		$dest   = APPPATH . 'Config/TCrud.php';

		if (!file_exists($source)) {
			CLI::error("No se encuentra el archivo de configuración en: $source");
			return;
		}

		if (file_exists($dest)) {
			CLI::warning("El archivo ya existe en: $dest");
			CLI::write('No se sobrescribe para no perder tu configuración.', 'yellow');
			return;
		}

		// Leer el contenido y cambiar el namespace
		$content = file_get_contents($source);
		$content = str_replace(
			'namespace Torno\TCrud\Config;',
			'namespace Config;',
			$content
		);
		$content = str_replace(
			'use CodeIgniter\Config\BaseConfig;',
			"use CodeIgniter\Config\BaseConfig;",
			$content
		);

		file_put_contents($dest, $content);

		CLI::write('Configuración de T-CRUD copiada a: ' . $dest, 'green');
	}
}