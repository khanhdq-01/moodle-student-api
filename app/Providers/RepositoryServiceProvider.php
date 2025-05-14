<?php

namespace App\Providers;

use App\Repositories\CourseRepository;
use Illuminate\Support\ServiceProvider;
use App\Interfaces\CourseRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
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
        $this->app->bind(CourseRepositoryInterface::class, CourseRepository::class);
    }
}
