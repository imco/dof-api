<?php namespace IMCO\CatalogoNOMsApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class DatasetController extends Controller {
	protected $connection = 'catalogoNoms';
	
	public function getMencionesCSV(){
		//ini_set('memory_limit', '-1');

		$requestedFile = '/tmp/mencionesNmx.csv';

		//if (!file_exists($requestedFile)){
			$vigentes = NormaVigente::with(['menciones.nota'=>function ($query){
				$query->select('titulo', 'cod_nota', 'cod_diario')->with('diario');
			}])->has('menciones')->get();
		
			$file = fopen($requestedFile,"w");

			fputcsv($file,['clave', 'fecha', 'cod_nota', 'titulo']);
			foreach($vigentes as $norma){
				foreach($norma->menciones AS $mencion){
					fputcsv($file,[$norma->clave, $mencion->nota->diario->fecha, $mencion->cod_nota, $mencion->nota->titulo]);
				}
			}

			fclose($file);
		//}

		return \Response::download($requestedFile);
		
	}

}
