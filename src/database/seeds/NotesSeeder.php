<?php namespace IMCO\CatalogoNOMsApi;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use IMCO\CatalogoNOMsApi;

class NotesSeeder extends Seeder
{
    protected $connection = "catalogoNoms";
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      \IMCO\CatalogoNOMsApi\DOFClientController::fillNotes(-1);      
    }
}
?>