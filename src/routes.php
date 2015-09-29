<?php
use Swagger\Annotations as SWG;

use IMCO\CatalogoNOMsApi\DofDiario;
use IMCO\CatalogoNOMsApi\DOFClientController;
//use Response;

Route::group(array('prefix' => 'catalogonoms', 'namespace'=>'IMCO\CatalogoNOMsApi'), function () {
/**
 *		@SWG\Path(
 *			path = "/catalogonoms/dof/edicion/{año}/{mes}/{dia}",
 *			@SWG\Get(
 *				summary = "Lista de ediciones por día.",
 *				description = "Verifica si existe una publicación del DOF en un día determinado.",
 *				tags = {"CatalogoNOMs"},
 *				@SWG\Parameter(
 * 					name="dia",
 * 					in="path",
 * 					required=true,
 * 					type="integer",
 *					default = 1,
 * 					description="Día a consultar"
 * 				),
 *				@SWG\Parameter(
 * 					name="mes",
 * 					in="path",
 * 					required=true,
 * 					type="integer",
 *					default = 1,
 * 					description="Mes a consultar"
 * 				),
 *				@SWG\Parameter(
 * 					name="año",
 * 					in="path",
 * 					required=true,
 * 					type="integer",
 *					default = 2012,
 * 					description="Año a consultar"
 *				),
 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
 * 			)
 *		)
 *		@SWG\Path(
 *			path = "/catalogonoms/dof/edicion/{año}/{mes}/",
 *			@SWG\Get(
 *				summary = "Lista de ediciones por mes.",
 *				description = "Verifica si existe una publicación del DOF en un Mes determinado.",
 *				tags = {"CatalogoNOMs"},
 *				@SWG\Parameter(
 * 					name="mes",
 * 					in="path",
 * 					required=true,
 *					default=1,
 * 					type="integer",
 * 					description="Mes a consultar"
 * 				),
 *				@SWG\Parameter(
 * 					name="año",
 * 					in="path",
 * 					required=true,
 *					default = 2012,
 * 					type="integer",
 * 					description="Año a consultar"
 *				),
 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
 * 			)
 *		)
 *		@SWG\Path(
 *			path = "/catalogonoms/dof/edicion/{año}/",
 *			@SWG\Get(
 *				summary = "Lista de ediciones publicadas por año.",
 *				tags = {"CatalogoNOMs"},
 *				@SWG\Parameter(
 * 					name="año",
 * 					in="path",
 * 					required=true,
 * 					type="integer",
 *					default = 2012,
 * 					description="Año a consultar"
 *				),
 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "json"))
 * 			)
 *		)
 */

	Route::group(array('prefix' => 'dof'), function () {
		Route::get('edicion/{year}/{month?}/{day?}', 'DOFClientController@getEditionsOnDate');
		Route::get('sumario/{year}/{month}/{day}', 'DOFClientController@getDateSummary');	
	});


	Route::get('test', function(){
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://caminoalexito.firebaseio.com/entries.json');

		// Include header in result? (0 = yes, 1 = no)
		curl_setopt($ch, CURLOPT_HEADER, 0);

		// Should cURL return or print out the data? (true = return, false = print)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);

		// Download the given URL, and return output
		$entries = json_decode(curl_exec($ch));
		curl_close($ch);


		$header = array("cct" => "CCT","school" => "Nombre de escuela","representativeNames" => "Nombre de representante","email" => "Correo electrónico de contacto","phone" => "Teléfono de contacto","associationNames" => "Nombres de integrantes de Asociación de Padres","schoolFeatures" => "Características de la escuela","studentsNumber" => "Número de alumnos en martícula","environmentDescription" => "Descripción entorno familiar y social de población beneficiada","schoolProblems"=> "Principales problemas en entorno de la escuela","problematic" => "Problemática que busca resolver","justification" => "Justificación","activityDescription" => "Descripción de actividades que se planean realizar","namesList"=> "Quiénes estarían involucrados?","date" => "Fechas estimadas para realización del proyecto","decisionDescription"=> "Descripción de cómo se tomarán las decisiones","evaluation"=> "¿Cómo vamos a verificar si avanzamos como lo planeamos?","interaction"=> "¿Cómo interactuarán con los maestros y directivos de la escuela?");

		$result ="";

		foreach($header AS $key => $param){

			$result = $result . '"'. str_replace('"', '""', $param) . '"' .(($key == count($header)-1) ? '' : ',');
		}
		$result = $result  ."\n";

		foreach($entries AS $entry){
			foreach($header AS $key=>$param){
				if (property_exists($entry, $key)){
					$result = $result . '"' .str_replace('"', '""', $entry->$key) . '"';
				}
				$result = $result . (($key == count($header)-1) ? '' : ',');
			}
			$result = $result  ."\n";
		}

		$result = mb_convert_encoding($result, 'iso-8859-1', 'utf-8');

		return \Response::make($result,200, array('Content-type'=>'"text/csv"; charset="iso-8859-1"', 'Content-Disposition' => 'attachment; filename="ExportFileName.csv"'));
	});




	/* BEGIN Old code */

	Route::get('noms', 'CatalogoNOMsController@getNomsPublications');
	Route::get('dependencia/{dependencia?}', 'CatalogoNOMsController@getNomsListByDependency');

	Route::get('noms/{clave} ', 'CatalogoNOMsController@getNOMPublications')->where('clave', '(.*)');

	Route::get('nom/{clave}', 'CatalogoNOMsController@getNOM')->where('clave', '(.*)');


	Route::get('producto/{producto?}', function ($producto = null) {

		if ($producto == null) {
			$sqlQuery = 'WITH productos AS (select DISTINCT unnest(producto::text[]) as "producto" from vigencianoms ORDER BY producto)
			SELECT array_to_json(array_agg(producto)) as producto from productos';
		} else {
			$producto = urldecode($producto);
			$sqlQuery = "WITH nomReciente AS (SELECT clavenomnorm, max(fecha) AS fecha FROM notasnom  WHERE etiqueta= 'NOM' GROUP BY clavenomnorm),
			notasNOMRecientes AS (SELECT * from nomreciente NATURAL JOIN notasnom),
			detalleNOM AS (SELECT fecha,vigencianoms.clavenomnorm,trim(both '-' from (regexp_matches(vigencianoms.clavenomnorm,'NOM(?:[^a-z0-9])(\d[a-z0-9\/]*[^a-z0-9])?([a-z][a-z0-9\/]*(?:[^a-z0-9](?:[a-z][a-z0-9\/]*[^a-z0-9]?)?)?)?(\d[a-z0-9\/]*[^a-z0-9])?','gi'))[2]) as comite, titulo from vigencianoms LEFT JOIN notasnomrecientes ON substring(vigencianoms.clavenomnorm from '-.*-') = substring(notasnomrecientes.clavenomnorm from '-.*-'))

			select clavenomnorm, fecha, titulo, estatus, array_to_json(producto::text[]) producto, array_to_json(rama::text[]) rama, comite from vigencianoms NATURAL JOIN detalleNOM WHERE (lower(producto))::text[] @> ARRAY[lower('$producto')] ORDER BY clavenomnorm";
		}

		$result = DB::select(DB::raw($sqlQuery));
		foreach ($result as $row) {
			if (property_exists($row, 'producto')) {
				$row->producto = json_decode($row->producto);
			}

			if (property_exists($row, 'rama')) {
				$row->rama = json_decode($row->rama);
			}
		}

		return json_encode($result);
	});

	Route::get('rama/{rama?}', function ($rama = null) {
		if ($rama == null) {
			$sqlQuery = "WITH ramas AS (select DISTINCT unnest(rama::text[]) as rama from vigencianoms ORDER BY rama)
			SELECT array_to_json(array_agg(rama)) as rama from ramas";
		} else {
			$rama = urldecode($rama);
			$sqlQuery = "WITH nomReciente AS (SELECT clavenomnorm, max(fecha) AS fecha FROM notasnom  WHERE etiqueta= 'NOM' GROUP BY clavenomnorm),
			notasNOMRecientes AS (SELECT * from nomreciente NATURAL JOIN notasnom),
			detalleNOM AS (SELECT fecha,vigencianoms.clavenomnorm,trim(both '-' from (regexp_matches(vigencianoms.clavenomnorm,'NOM(?:[^a-z0-9])(\d[a-z0-9\/]*[^a-z0-9])?([a-z][a-z0-9\/]*(?:[^a-z0-9](?:[a-z][a-z0-9\/]*[^a-z0-9]?)?)?)?(\d[a-z0-9\/]*[^a-z0-9])?','gi'))[2]) as comite, titulo from vigencianoms LEFT JOIN notasnomrecientes ON substring(vigencianoms.clavenomnorm from '-.*-') = substring(notasnomrecientes.clavenomnorm from '-.*-'))
			select clavenomnorm,titulo,  fecha, estatus, array_to_json(producto::text[]) producto, array_to_json(rama::text[]) rama, comite from vigencianoms NATURAL JOIN detalleNOM WHERE (lower(rama)::text[]) @> ARRAY[lower('$rama')] ORDER BY clavenomnorm";
		}

		$result = DB::select(DB::raw($sqlQuery));
		foreach ($result as $row) {
			if (property_exists($row, 'producto')) {
				$row->producto = json_decode($row->producto);
			}

			if (property_exists($row, 'rama')) {
				$row->rama = json_decode($row->rama);
			}
		}

		return json_encode($result);
	});

	Route::get('proyecto', function () {
		$sqlQuery = "WITH nomReciente AS (SELECT clavenomnorm, min(fecha) AS fecha FROM notasnom GROUP BY clavenomnorm),
		notasNOMRecientes AS (SELECT * from nomreciente NATURAL JOIN notasnom)
		SELECT fecha,clavenomnorm,trim(both '-' from (regexp_matches(vigencianoms.clavenomnorm,'NOM(?:[^a-z0-9])(\d[a-z0-9\/]*[^a-z0-9])?([a-z][a-z0-9\/]*(?:[^a-z0-9](?:[a-z][a-z0-9\/]*[^a-z0-9]?)?)?)?(\d[a-z0-9\/]*[^a-z0-9])?','gi'))[2]) as comite, titulo from vigencianoms NATURAL LEFT JOIN notasnomrecientes WHERE estatus='Proyecto';";

		$result = DB::select(DB::raw($sqlQuery));

		return json_encode($result);

	});

	/* END OLD CODE */

});


?>
