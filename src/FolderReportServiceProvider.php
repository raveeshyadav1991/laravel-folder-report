<?php

namespace Raveesh\FolderReport;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Raveesh\FolderReport\Http\Controllers\FolderReportController;

class FolderReportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Automatically register the route
        Route::middleware('web')
            ->group(function () {
                Route::get('/folder-report', [FolderReportController::class, 'index']);
            });
    }

    public function register()
    {
        //
    }
}
