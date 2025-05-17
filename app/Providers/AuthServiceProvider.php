<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    
    protected $policies = [
        User::class => UserPolicy::class,
        'App\Models\User' => 'App\Policies\UserPolicy',
        'App\Models\Task' => 'App\Policies\TaskPolicy',
        'App\Models\ActivityLog' => 'App\Policies\ActivityLogPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
