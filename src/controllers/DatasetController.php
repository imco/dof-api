<?php namespace IMCO\CatalogoNOMsApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use DateTime;

class DatasetController extends Controller {
	protected $connection = 'catalogoNoms';
	
	public function getMencionesCSV(){
		//ini_set('memory_limit', '-1');

		$requestedFile = '/tmp/mencionesNmx.csv';

		//if (!file_exists($requestedFile)){
			$vigentes = NormaVigente::with(['menciones.nota'=>function ($query){
				$query->select('titulo', 'cod_nota', 'cod_diario')->with('diario');
			}])->has('menciones')->orderBy('clave')->get();
		
			$file = fopen($requestedFile,"w");

			fputcsv($file,['clave', 'etiqueta', 'fecha', 'cod_nota', 'titulo']);
			foreach($vigentes as $norma){
				$menciones =$norma->menciones->sortBy(function($mencion, $key){
					return DateTime::createFromFormat ( 'Y-m-d' , $mencion->nota->diario->fecha);
				});
				foreach($menciones AS $mencion){
					fputcsv($file,[$norma->clave, $mencion->etiqueta, $mencion->nota->diario->fecha, $mencion->cod_nota, $mencion->nota->titulo]);
				}
			}

			fclose($file);
		//}

		return \Response::download($requestedFile);
		
	}

	/**
	 *		@SWG\Path(
	 *			path = "/dof/ultimo",
	 *			@SWG\Get(
	 *				summary = "Última publicación descargada del Diario Oficial de la Federación.",
	 *				tags = {"Diario Oficial de la Federación"},
	 *				@SWG\Response(response = "200", description = "Descripción de la útima publicación descargada.", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */
	public function getDOFLastDownloadedPublication(){
		return (DofDiario::orderBy('fecha', 'DESC')->first());
	}


	/**
	 *		@SWG\Path(
	 *			path = "/dof/primero",
	 *			@SWG\Get(
	 *				summary = "Primera publicación descargada del Diario Oficial de la Federación.",
	 *				tags = {"Diario Oficial de la Federación"},
	 *				@SWG\Response(response = "200", description = "Descripción de la primera publicación descargada.", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */
	public function getDOFFirstDownloadedPublication(){
		return (DofDiario::orderBy('fecha', 'ASC')->first());
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
