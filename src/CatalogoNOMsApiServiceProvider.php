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
		
		//$this->publishes([__DIR__.'/database/data' => base_path('database/data')]);
		$this->publishes([__DIR__.'/database/migrations' => base_path('database/migrations')]);
		//$this->publishes([__DIR__.'/database/seeds' => base_path('database/seeds')]);
		
		Config::set('database.connections.CatalogoNoms' , Config::get('catalogonoms.database.connections.CatalogoNoms'));
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
	}

}

