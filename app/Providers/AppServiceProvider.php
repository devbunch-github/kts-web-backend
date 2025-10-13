<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\BeauticianRepositoryInterface;
use App\Repositories\Eloquent\BeauticianRepository;
use App\Repositories\Contracts\{PlanRepositoryInterface, ContactRepositoryInterface};
use App\Repositories\Eloquent\{PlanRepository, ContactRepository};
use App\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Repositories\Eloquent\ExpenseRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BeauticianRepositoryInterface::class, BeauticianRepository::class);
        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
        $this->app->bind(ContactRepositoryInterface::class, ContactRepository::class);
        $this->app->bind(ExpenseRepositoryInterface::class, ExpenseRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
