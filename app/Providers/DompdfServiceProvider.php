<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Barryvdh\DomPDF\Facade\Pdf;

class DompdfServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the DejaVuSans font with dompdf
        $fontDir = storage_path('fonts');
        $fontCache = storage_path('fonts');
        
        // Configure dompdf to use our custom font
        Pdf::setOptions([
            'font_dir' => $fontDir,
            'font_cache' => $fontCache,
            'default_font' => 'DejaVuSans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'enable_font_subsetting' => true,
        ]);
    }
}
