<?php namespace IMCO\CatalogoNOMsApi;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use IMCO\CatalogoNOMsApi;

use Response;

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
            $dofDiario = $dofClient->getEditionsOnDate($year)->getData();

            foreach($dofDiario->list as $diario){
                $diario->fecha = DOFClientController::reformatDateString($diario->fecha);

                print_r($diario->fecha . "\n");
                DofDiario::firstOrCreate((array)$diario);
            }
        }
	}
}
