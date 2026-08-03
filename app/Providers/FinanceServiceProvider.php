<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\FeeStructureInterface;
use App\Repositories\FeeStructureRepository;
use App\Interfaces\PaymentInterface;
use App\Repositories\PaymentRepository;
use App\Interfaces\ExpenseInterface;
use App\Repositories\ExpenseRepository;
use App\Interfaces\TransactionInterface;
use App\Repositories\TransactionRepository;
use App\Interfaces\ReminderInterface;
use App\Repositories\ReminderRepository;

class FinanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(FeeStructureInterface::class, FeeStructureRepository::class);
        $this->app->bind(PaymentInterface::class, PaymentRepository::class);
        $this->app->bind(ExpenseInterface::class, ExpenseRepository::class);
        $this->app->bind(TransactionInterface::class, TransactionRepository::class);
        $this->app->bind(ReminderInterface::class, ReminderRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
