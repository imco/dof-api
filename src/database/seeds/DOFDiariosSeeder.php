<?php namespace IMCO\CatalogoNOMsApi;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use IMCO\CatalogoNOMsApi;

class DOFDiariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        $dofClient = new DOFClientController;

        for ($year=1917 ; $year <= date("Y"); $year++){
            $dofDiario = $dofClient->getDatesPublishedOnMoth($year)->getData();

            foreach($dofDiario->list as $diario){
                $diario->fecha = DOFClientController::reformatDateString($diario->fecha);
                DofDiario::create((array)$diario);
            }
        }
	}
}
