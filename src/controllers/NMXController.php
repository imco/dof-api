<?php namespace IMCO\CatalogoNOMsApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use DateTime;

class NMXController extends Controller {
	protected $connection = 'catalogoNoms';
	
	/*
	 *		@SWG\Path(
	 *			path = "/catalogonoms/nmx/vigentes",
	 *			@SWG\Get(
	 *				summary = "Resumen de las normas vigentes",
	 *				tags = {"CatalogoNMX"},
	 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */

	public function getNMXVigentes(){
		$vigentes = NormaVigente::with(['menciones'=>function ($query){
				$query->where('etiqueta', 'Vigencia')->with(['nota'=>function ($query){
					$query->select('titulo', 'cod_nota', 'cod_diario')->with('diario');
				}]);
			}])->orderBy('clave')->get();	
		return \Response::json($vigentes);
	}


	public function getPublicacionCSV(){
		//ini_set('memory_limit', '-1');

		$requestedFile = '/tmp/publicacion.csv';

		//if (!file_exists($requestedFile)){
			$vigentes = NormaVigente::with(['menciones'=>function ($query){
				$query->with(['nota'=>function ($query){
					$query->select('titulo', 'cod_nota', 'cod_diario')->with('diario');
				}]);
			}])->has('menciones')->orderBy('clave')->get();

			$file = fopen($requestedFile,"w");

			fputcsv($file,['Clave', 'CTNN','ONN','Fecha de entrada en vigor','Primera publicacion','Diferencia','Título primera publicacion']);
			//var_dump($vigentes);
			foreach($vigentes as $norma){
				$menciones =$norma->menciones->sortBy(function($mencion, $key){
					return DateTime::createFromFormat ( 'Y-m-d' , $mencion->nota->diario->fecha);
				});
				foreach($menciones AS $mencion){
					fputcsv($file,[$norma->clave, $norma->ctnn, $norma->onn, $norma->fecha_publicacion,$mencion->nota->diario->fecha, date_diff(DateTime::createFromFormat ( 'Y-m-d' , $mencion->nota->diario->fecha),DateTime::createFromFormat ( 'Y-m-d' , $norma->fecha_publicacion))->format("%R%a"), $mencion->nota->titulo]);
					break;
				}
			}

			fclose($file);
		//}

		return \Response::download($requestedFile);
		
	}

}
