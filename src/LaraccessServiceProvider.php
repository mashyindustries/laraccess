<?php

namespace Mashy\Laraccess;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class LaraccessServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../resources/config/laraccess.php' => $this->app->configPath().'/'.'laraccess.php',
        ], 'config');

        if (! class_exists('CreateLaraccessTables')) {
            // Publish the migration
            $timestamp = date('Y_m_d_His', time());
            $this->publishes([
                __DIR__.'/../resources/migrations/create_laraccess_tables.php.stub' => $this->app->databasePath().'/migrations/'.$timestamp.'_create_laraccess_tables.php',
            ], 'migrations');
        }

        $this->mergeConfigFrom(
            __DIR__.'/../resources/config/laraccess.php',
            'laraccess'
        );
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->registerBladeExtensions();
    }

    /**
     * Register the blade extensions.
     */
    protected function registerBladeExtensions()
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $bladeCompiler->directive('role', function ($role) {
                return "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>";
            });
            $bladeCompiler->directive('anyrole', function ($roles) {
                return "<?php if(auth()->check() && auth()->user()->hasAnyRole({$roles})): ?>";
            });
            $bladeCompiler->directive('allroles', function ($roles) {
                return "<?php if(auth()->check() && auth()->user()->hasAllRoles({$roles})): ?>";
            });

            $bladeCompiler->directive('endrole', function () {
                return '<?php endif; ?>';
            });
        });
    }
}
