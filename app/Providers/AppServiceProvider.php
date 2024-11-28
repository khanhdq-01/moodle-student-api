<?php

namespace App\Providers;

use App\Services\CourseService;
use App\Repositories\CourseRepository;
use Illuminate\Support\ServiceProvider;
use App\Interfaces\CourseRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
