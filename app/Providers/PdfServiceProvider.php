<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Barryvdh\DomPDF\Facade as PDF;

class PdfServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('pdf', function($app) {
            return new PDF();
        });
    }

    public function boot()
    {
        // Konfigurasi default PDF
        config([
            'dompdf.defines.default_media_type' => 'print',
            'dompdf.defines.default_paper_size' => 'A4',
            'dompdf.defines.default_font' => 'arial',
            'dompdf.defines.dpi' => 96,
            'dompdf.defines.enable_php' => false,
            'dompdf.defines.enable_javascript' => true,
            'dompdf.defines.enable_remote' => true,
        ]);
    }
}
