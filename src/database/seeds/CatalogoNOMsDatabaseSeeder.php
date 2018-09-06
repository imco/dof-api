<?php namespace IMCO\CatalogoNOMsApi;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class CatalogoNOMsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $this->call('IMCO\CatalogoNOMsApi\ClasificacionSeeder');
        $this->call('IMCO\CatalogoNOMsApi\MencionEnNotaSeeder');
        $this->call('IMCO\CatalogoNOMsApi\NormasVigentesSeeder');
        $this->call('IMCO\CatalogoNOMsApi\DOFDiariosSeeder');
        $this->call('IMCO\CatalogoNOMsApi\NotesSeeder');
	}
}
