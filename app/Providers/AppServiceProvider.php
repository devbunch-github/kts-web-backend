<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\BeauticianRepositoryInterface;
use App\Repositories\Eloquent\BeauticianRepository;
use App\Repositories\Contracts\{AccountEmailTemplateRepositoryInterface, GiftCardRepositoryInterface, PromoCodeRepositoryInterface, PlanRepositoryInterface, ContactRepositoryInterface, CustomerRepositoryInterface, AppointmentRepositoryInterface};
use App\Repositories\Eloquent\{AccountEmailTemplateRepository, GiftCardRepository, PromoCodeRepository, PlanRepository, ContactRepository, CustomerRepository, AppointmentRepository};
use App\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Repositories\Eloquent\ExpenseRepository;
use App\Models\Account;
use App\Observers\AccountObserver;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Client\ClientRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Account::observe(AccountObserver::class);

        $this->app->bind(BeauticianRepositoryInterface::class, BeauticianRepository::class);
        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
        $this->app->bind(ContactRepositoryInterface::class, ContactRepository::class);
        $this->app->bind(ExpenseRepositoryInterface::class, ExpenseRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(AppointmentRepositoryInterface::class, AppointmentRepository::class);
        $this->app->bind(PromoCodeRepositoryInterface::class, PromoCodeRepository::class);
        $this->app->bind(GiftCardRepositoryInterface::class, GiftCardRepository::class);
        $this->app->bind(AccountEmailTemplateRepositoryInterface::class, AccountEmailTemplateRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}