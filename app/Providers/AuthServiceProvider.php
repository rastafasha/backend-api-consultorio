<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Payment;
use App\Models\Currency;
use App\Policies\PaymentPolicy;
use App\Policies\CurrencyPolicy;
use App\Policies\UsertPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Currency::class => CurrencyPolicy::class,
        Payment::class => PaymentPolicy::class,
        User::class => UsertPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function($user, $ability) {
            if($user->role == "SUPERADMIN") {
                return true;
            }
        });

        // Gate::define('permission', function (User $user, $perm){

        //     return $user->havePermission($perm);

        // });


        // Gate::define('isAdmin', function($user){
        //     return $user->role == 'ADMIN';
        // });
    }
}
