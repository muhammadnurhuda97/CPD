<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {}

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        // DB::listen(function ($query) {

        //     // Hanya mencatat query yang membutuhkan waktu lebih lama dari 0.001 detik
        //     if ($query->time > 0.001) {
        //         Log::info("SQL: " . $query->sql, $query->bindings);
        //         Log::info("Query Execution Time: " . $query->time . " seconds");
        //     }

        //     // Mencatat query INSERT, UPDATE, DELETE
        //     if (preg_match('/^(insert|update|delete)/i', $query->sql)) {
        //         Log::info("SQL: " . $query->sql, $query->bindings);
        //         Log::info("Query Execution Time: " . $query->time . " seconds");
        //     }
        // });
    }
}
