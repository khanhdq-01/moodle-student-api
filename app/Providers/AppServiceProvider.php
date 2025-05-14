<?php

namespace App\Providers;

use App\Repositories\ForumRepository;
use App\Repositories\CourseRepository;
use App\Repositories\MoodleRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\LanguageRepository;
use App\Repositories\MoodleGradeRepository;
use App\Interfaces\CourseRepositoryInterface;
use App\Interfaces\MoodleAssignmentInterface;
use App\Interfaces\ForumRepositoryInterface;
use App\Interfaces\LanguageRepositoryInterface;
use App\Interfaces\MoodleRepositoryInterface;
use App\Repositories\MoodleAssignmentRepository;
use App\Interfaces\MoodleGradeRepositoryInterface;
use App\Interfaces\MoodleQARepositoryInterface;
use App\Repositories\MoodleQARepository;
use App\Interfaces\EventRepositoryInterface;
use App\Repositories\EventRepository;
use App\Interfaces\QuizInterface;
use App\Repositories\QuizRepository;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CourseRepositoryInterface::class, CourseRepository::class);
        $this->app->bind(ForumRepositoryInterface::class, ForumRepository::class);
        $this->app->bind(LanguageRepositoryInterface::class, LanguageRepository::class);
        $this->app->bind(MoodleAssignmentInterface::class,MoodleAssignmentRepository::class
        );
        $this->app->bind(MoodleRepositoryInterface::class, MoodleRepository::class);
        $this->app->bind(MoodleGradeRepositoryInterface::class, MoodleGradeRepository::class);
        $this->app->bind(MoodleQARepositoryInterface::class, MoodleQARepository::class);
        $this->app->bind(EventRepositoryInterface::class, EventRepository::class);
         $this->app->bind(QuizInterface::class, QuizRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
