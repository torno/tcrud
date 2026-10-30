<?php

namespace Torno\TCrud\Config;

use CodeIgniter\Config\BaseConfig;

class TCrud extends BaseConfig
{
    public bool $coreAssetsAlreadyIncluded = true;
    public string $language = 'es';
    public bool $enableCache = false;
    public int $cacheTTL = 3600;
    public string $cachePrefix = 'tcrud_';
    public array $assets = [];

    public function __construct()
    {
        parent::__construct();

        $this->assets = [
            'jquery_js' => base_url('assets/tcrud/js/jquery.min.js'),
            'datatables_css' => base_url('assets/tcrud/css/datatables.bootstrap5.min.css'),
            'datatables_js' => base_url('assets/tcrud/js/jquery.dataTables.min.js'),
            'datatables_bs5' => base_url('assets/tcrud/js/dataTables.bootstrap5.min.js'),
            'tomselect_css' => base_url('assets/tcrud/css/tom-select.bootstrap5.min.css'),
            'tomselect_js' => base_url('assets/tcrud/js/tom-select.complete.min.js'),
            'flatpickr_css' => base_url('assets/tcrud/css/flatpickr.min.css'),
            'flatpickr_js' => base_url('assets/tcrud/js/flatpickr.min.js'),
            'sweetalert2_css' => base_url('assets/tcrud/css/sweetalert2.min.css'),
            'sweetalert2_js' => base_url('assets/tcrud/js/sweetalert2.min.js'),
            'quill_css' => base_url('assets/tcrud/css/quill.snow.css'),
            'quill_js' => base_url('assets/tcrud/js/quill.js'),
            'crud_custom_css' => base_url('assets/tcrud/css/tcrud.css'),
            'crud_custom_js' => base_url('assets/tcrud/js/tcrud.js'),
            'bootstrap_css' => base_url('assets/tcrud/css/bootstrap.min.css'),
            'bootstrap_js' => base_url('assets/tcrud/js/bootstrap.bundle.min.js'),
            'bootstrap_icons' => base_url('assets/tcrud/css/bootstrap-icons.css'),
        ];
    }
}