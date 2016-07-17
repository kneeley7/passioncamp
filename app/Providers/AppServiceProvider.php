<?php

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Collection::macro('sometimes', function ($condition, $method, ...$parameters) {
            return $condition ? call_user_func_array([(new static($this->items)), $method], $parameters) : $this;
        });

        Collection::macro('dd', function () {
            dd($this);
        });

        view()->composer('ticket.partials.form', function ($view) {
            $gradeOptions = [];
            
            foreach (range(6,12) as $grade) {
                $gradeOptions[$grade] = number_ordinal($grade);
            }

            $view->with('gradeOptions', $gradeOptions);
        });

        view()->composer('user.partials.form', function ($view) {
            $organizationOptions = [];

            \App\Organization::with('church')->each(function ($organization) use (&$organizationOptions) {
                $organizationOptions[$organization->church->id] = $organization->church->name . ' - ' . $organization->church->location;
            });

            $view->with('organizationOptions', collect($organizationOptions)->sort()->toArray());
        });

        view()->composer('checkin.printer.index', 'App\Http\ViewComposers\CheckinPrinterIndexComposer');
        view()->composer('roominglist.printer.index', 'App\Http\ViewComposers\RoominglistPrinterIndexComposer');

        $this->app->when(\App\Http\ViewComposers\RoominglistPrinterIndexComposer::class)
                  ->needs(\PrintNode\Client::class)
                  ->give(function () {
                    $credentials = new \PrintNode\Credentials\ApiKey(env('ROOMINGLIST_PRINTNODE_API_KEY'));

                    return new \PrintNode\Client($credentials);
                  });
        $this->app->when(\App\Http\ViewComposers\CheckinPrinterIndexComposer::class)
                  ->needs(\PrintNode\Client::class)
                  ->give(function () {
                    $credentials = new \PrintNode\Credentials\ApiKey(env('CHECKIN_PRINTNODE_API_KEY'));

                    return new \PrintNode\Client($credentials);
                  });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
