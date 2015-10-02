<?php namespace IMCO\CatalogoNOMsApi;

use Illuminate\Support\ServiceProvider;
use \Config;

class CatalogoNOMsApiServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([__DIR__.'/config/database.php' => config_path('catalogonoms/database.php')]);
		
		$this->publishes([__DIR__.'/database/data' => base_path('database/data')]);
		$this->publishes([__DIR__.'/database/migrations' => base_path('database/migrations')]);
		$this->publishes([__DIR__.'/database/seeds' => base_path('database/seeds')]);
		$this->publishes([__DIR__.'/../bin' => base_path('bin')]);
		
		Config::set('database.connections.CatalogoNoms' , Config::get('catalogonoms.database.connections.CatalogoNoms'));
		Config::set('database.connections.CatalogoNomsOld' , Config::get('catalogonoms.database.connections.CatalogoNomsOld'));
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		require __DIR__.'/routes.php';
		$this->app->make('IMCO\CatalogoNOMsApi\CatalogoNOMsController');
		$this->app->make('IMCO\CatalogoNOMsApi\DOFClientController');
		$this->app->make('IMCO\CatalogoNOMsApi\DofDiario');
		$this->app->make('IMCO\CatalogoNOMsApi\DofNota');
	}

}

