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
	 *			path = "/catalogonoms/nmx/vigentes/{filter}/{value}",
	 *			@SWG\Get(
	 *				summary = "NMX vigentes por CTNN",
	 *				tags = {"CatalogoNMX"},
	 *				@SWG\Parameter(
	 * 					name="filter",
	 * 					in="path",
	 * 					required=true,
	 * 					type="string",
	 *					default = "no-aplica",
	 *					enum = {"byctnn", "byonn", "bykeyword", "byramaeconomica"},
	 * 					description="Tipo de filtro a utilizar"
	 *				),
	 *				@SWG\Parameter(
	 * 					name="value",
	 * 					in="path",
	 * 					required=true,
	 * 					type="string",
	 *					default = "no-aplica",
	 * 					description="Valor del Slug por el que se ha de filtrar"
	 *				),
	 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */
	public function getNMXVigentes($filterType = null, $value=null){
		ini_set('memory_limit', '-1');

		$vigentes = NormaVigente::with(['menciones'=>function ($query){
				$query->where('etiqueta', 'Vigencia')->with(['nota'=>function ($query){
					$query->select('titulo', 'cod_nota', 'cod_diario')->with('diario');
				}]);
			}])->orderBy('clave');

		if ($filterType){
			switch($filterType){
				case 'byctnn':
					$vigentes->whereRaw("'$value' = ANY (ARRAY(SELECT btrim(json_array_elements::text, '\"') FROM json_array_elements(ctnn_slug))::varchar[] )");
					break;
				case 'byramaeconomica':
					$vigentes->where('rama_economica_slug', $value);
					break;
				case 'byonn':
					$vigentes->whereRaw("'$value' = ANY (ARRAY(SELECT btrim(json_array_elements::text, '\"') FROM json_array_elements(onn_slug))::varchar[] )");
					break;
				case 'bykeyword':
					$vigentes->whereRaw("'$value' = ANY (ARRAY(SELECT btrim(json_array_elements::text, '\"') FROM json_array_elements(palabras_clave))::varchar[] )");
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

		$norma = NormaVigente::with(['menciones'=>function ($query){
			$query->with(['nota' => function ($query) {
				$query->select('titulo', 'cod_nota', 'cod_diario')
				->whereRaw("titulo !~* '(norma oficial mexicana)|(programa nacional de normalizaci[oó]n)'")
				->distinct()->with('diario');
			}])->has('nota');
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
		$ctnns = NormaVigente::selectRaw('json_array_elements(ctnn)::varchar AS ctnn')->whereNotNull('ctnn')->orderBy('ctnn')->distinct()->get();
		foreach($ctnns AS $key=>$value){
			$ctnns[$key]->ctnn_slug = \Slug\Slugifier::slugify($value->ctnn, '-', True);
		}
		return \Response::json($ctnns);
		
	}


	/**
	 *		@SWG\Path(
	 *			path = "/catalogonoms/nmx/onn",
	 *			@SWG\Get(
	 *				summary = "Lista de ONN",
	 *				tags = {"CatalogoNMX"},
	 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */

	public function getONNList(){
		$onns = NormaVigente::selectRaw('json_array_elements(onn)::varchar AS onn')->whereNotNull('onn')->distinct()->orderBy('onn')->get();
		foreach($onns AS $key=>$value){
			$onns[$key]->onn_slug = \Slug\Slugifier::slugify($value->onn, '-', True);
		}
		return \Response::json($onns);
		
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
		$keywords = NormaVigente::selectRaw('json_array_elements(palabras_clave)::varchar AS keyword')->distinct()->orderBy('keyword')->get();
		return \Response::json($keywords);
	}

	/**
	 *		@SWG\Path(
	 *			path = "/catalogonoms/nmx/ramas",
	 *			@SWG\Get(
	 *				summary = "Lista de ramas económicas",
	 *				tags = {"CatalogoNMX"},
	 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
	 * 			)
	 *		)
	 */
	public function getRamasEconomicas(){
		$ramas = NormaVigente::select('rama_economica', 'rama_economica_slug')->whereNotNull('rama_economica')->distinct()->get();
		/*foreach($ramas AS $key=>$value){
			$ramas[$key]->rama_economica_slug = \Slug\Slugifier::slugify($value->rama_economica);
			$ramas[$key]->save();
		}*/
		return \Response::json($ramas);
	}

}
