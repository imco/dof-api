<?php namespace IMCO\CatalogoNOMsApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use DateTime;
use DB; 

class NMXController extends Controller {
	protected $connection = 'catalogoNoms';
	
	/**
	 *		@SWG\Path(
	 *			path = "/catalogonoms/nmx/vigentes",
	 *			@SWG\Get(
	 *				summary = "Resumen de las normas vigentes",
	 *				tags = {"CatalogoNMX"},
	 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */


	/**
	 *		@SWG\Path(
	 *			path = "/catalogonoms/nmx/vigentes/byctnn/{ctnn}",
	 *			@SWG\Get(
	 *				summary = "NMX vigentes por CTNN",
	 *				tags = {"CatalogoNMX"},
	 *				@SWG\Parameter(
	 * 					name="ctnn",
	 * 					in="path",
	 * 					required=true,
	 * 					type="string",
	 *					default = "no-aplica",
	 * 					description="Slug de la CTNN"
	 *				),
	 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */

	/**
	 *		@SWG\Path(
	 *			path = "/catalogonoms/nmx/vigentes/bykeyword/{keyword}",
	 *			@SWG\Get(
	 *				summary = "NMX vigentes por palabra clave",
	 *				tags = {"CatalogoNMX"},
	 *				@SWG\Parameter(
	 * 					name="keyword",
	 * 					in="path",
	 * 					required=true,
	 * 					type="string",
	 *					default = "abrasion",
	 * 					description="Keyword"
	 *				),
	 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */

	public function getNMXVigentes($filterType = null, $value=null){
		$vigentes = NormaVigente::with(['menciones'=>function ($query){
				$query->where('etiqueta', 'Vigencia')->with(['nota'=>function ($query){
					$query->select('titulo', 'cod_nota', 'cod_diario')->with('diario');
				}]);
			}])->orderBy('clave');

		if ($filterType){
			switch($filterType){
				case 'byctnn':
					$vigentes->where('ctnn_slug', $value);
					break;
				case 'bykeyword':
					$vigentes->whereRaw("palabras_clave @> '{". strtoupper($value) ."}'");
					break;
			}

		}
		return \Response::json($vigentes->get());
	}


	/**
	 *		@SWG\Path(
	 *			path = "/catalogonoms/nmx/detalle/{clave}",
	 *			@SWG\Get(
	 *				summary = "Resumen de las normas vigentes",
	 *				tags = {"CatalogoNMX"},
	 *				@SWG\Parameter(
	 * 					name="clave",
	 * 					in="path",
	 * 					required=true,
	 * 					type="string",
	 *					default = "NMX-A-001-1965",
	 * 					description="Clave de la Norma"
	 *				),
	 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */

	public function getNMXDetalle($clave){
		$clave = strtoupper($clave);
		$norma = NormaVigente::with(['menciones.nota'=>function ($query){
			$query->select('titulo', 'cod_nota', 'cod_diario')->with('diario');
		}])->where('clave', $clave)->first();
		return \Response::json($norma);
		
	}

	/**
	 *		@SWG\Path(
	 *			path = "/catalogonoms/nmx/ctnn",
	 *			@SWG\Get(
	 *				summary = "Lista de CTNN",
	 *				tags = {"CatalogoNMX"},
	 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */

	public function getCTNNList(){
		$ctnns = NormaVigente::select('ctnn', 'ctnn_slug')->distinct()->get();
		//foreach($ctnns AS $key=>$value){
		//	$ctnns[$key]->ctnn_slug = \Slug\Slugifier::slugify($value->ctnn);
			//$ctnns[$key]->save();
		//}
		return \Response::json($ctnns);
		
	}

	/**
	 *		@SWG\Path(
	 *			path = "/catalogonoms/nmx/keywords",
	 *			@SWG\Get(
	 *				summary = "Lista de palabras clave",
	 *				tags = {"CatalogoNMX"},
	 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */
	public function getKeywords(){
		$keywords = NormaVigente::select(DB::raw('unnest(palabras_clave) AS keyword'))->distinct()->orderBy('keyword')->get();
		return \Response::json($keywords);
	}

}
