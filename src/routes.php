<?php
use Swagger\Annotations as SWG;

use IMCO\CatalogoNOMsApi\DofDiario;
use IMCO\CatalogoNOMsApi\DofNota;
use IMCO\CatalogoNOMsApi\DOFClientController;
//use Response;

Route::group(array('prefix' => 'dof', 'namespace'=>'IMCO\CatalogoNOMsApi'), function () {
	Route::get('ultimo', ['middleware'=>'analytics', 'uses'=>'DatasetController@getDOFLastDownloadedPublication']);
	Route::get('primero', ['middleware'=>'analytics', 'uses'=>'DatasetController@getDOFFirstDownloadedPublication']);
});

Route::group(array('prefix' => 'catalogonoms', 'namespace'=>'IMCO\CatalogoNOMsApi'), function () {

/**
 *		@SWG\Path(
 *			path = "/catalogonoms/dof/notas",
 *			@SWG\Get(
 *				summary = "Notas publicadas en el DOF",
 *				description = "CSV con las notas descargadas de publicaciones del DOF.",
 *				tags = {"CatalogoNOMs"},
 *				@SWG\Response(response = "200", description = "CSV con las notas descargadas de publicaciones del DOF", @SWG\Schema(type = "object"))
 * 			)
 *		)
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
 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "object"))
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
 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "object"))
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
 *				@SWG\Response(response = "200", description = "JSON de respuesta", @SWG\Schema(type = "object"))
 * 			)
 *		)
 */

	Route::group(array('prefix' => 'dof'), function () {
		//Route::get('csv/{resource}', 'DatasetController@getCSV');
		Route::get('edicion/{year}/{month?}/{day?}', 'DOFClientController@getEditionsOnDate');
		Route::get('sumario/{year}/{month}/{day}', 'DOFClientController@getDateSummary');	
	});


	Route::get('/', function(){
	    return view('catalogonoms::presentacion');
	});
	Route::get('/normas-vigentes', function(){
	    return view('catalogonoms::normas-vigentes');
	});

	Route::get('/detalle-norma/{clave}', function($clave){
	    return view('catalogonoms::detalle-norma', ['clave'=>$clave]);
	})->where('clave', '(.*)');


	Route::group(array('prefix' => 'error'), function () {
		Route::get('/fecha-publicacion', function(){
		    return view('catalogonoms::error-fecha-publicacion');
		});

		Route::get('/no-localizadas', function(){
		    return view('catalogonoms::no-localizadas');
		});

	});


	Route::group(array('prefix' => 'nmx'), function () {
		Route::get('/vigentes', 'NMXController@getNMXVigentes');
		Route::get('/vigentes/{filter}/{value}', 'NMXController@getNMXVigentes');
		Route::get('/detalle/{clave}', 'NMXController@getNMXDetalle');
		Route::get('/ctnn', 'NMXController@getCTNNList');
		Route::get('/onn', 'NMXController@getONNList');
		Route::get('/keywords', 'NMXController@getKeywords');
		Route::get('/ramas', 'NMXController@getRamasEconomicas');
	});

	Route::group(array('prefix' => 'download'), function () {
		Route::get('/menciones-nmx', 'DatasetController@getMencionesCSV');
		Route::get('/publicacion-nmx', 'DatasetController@getPublicacionCSV');
		Route::get('/no-localizadas', function(){
		    return view('catalogonoms::no-localizadas');
		});

	});

	Route::group(array('prefix' => 'resultados'), function () {
		Route::get('/menciones', function(){
		    return view('catalogonoms::resultados-menciones');
		});
		Route::get('/proyectos', function(){
		    return view('catalogonoms::resultados-proyectos');
		});
		Route::get('/publicacion', function(){
		    return view('catalogonoms::resultados-publicacion');
		});

	});

	Route::get('test', function(){
		

	});




	/* BEGIN Old code */

	Route::get('noms', 'CatalogoNOMsController@getNomsPublications');
	Route::get('dependencia/{dependencia?}', 'CatalogoNOMsController@getNomsListByDependency');

	Route::get('noms/{clave} ', 'CatalogoNOMsController@getNOMPublications')->where('clave', '(.*)');

	Route::get('nom/{clave}', 'CatalogoNOMsController@getNOM')->where('clave', '(.*)');


	Route::get('producto/{producto?}', function ($producto = null) {
		$connection = 'CatalogoNomsOld';
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

		$result = DB::connection($connection)->select(DB::connection($connection)->raw($sqlQuery));
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
		$connection = 'CatalogoNomsOld';
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

		$result = DB::connection($connection)->select(DB::connection($connection)->raw($sqlQuery));
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
		$connection = 'CatalogoNomsOld';

		$sqlQuery = "WITH nomReciente AS (SELECT clavenomnorm, min(fecha) AS fecha FROM notasnom GROUP BY clavenomnorm),
		notasNOMRecientes AS (SELECT * from nomreciente NATURAL JOIN notasnom)
		SELECT fecha,clavenomnorm,trim(both '-' from (regexp_matches(vigencianoms.clavenomnorm,'NOM(?:[^a-z0-9])(\d[a-z0-9\/]*[^a-z0-9])?([a-z][a-z0-9\/]*(?:[^a-z0-9](?:[a-z][a-z0-9\/]*[^a-z0-9]?)?)?)?(\d[a-z0-9\/]*[^a-z0-9])?','gi'))[2]) as comite, titulo from vigencianoms NATURAL LEFT JOIN notasnomrecientes WHERE estatus='Proyecto';";

		$result = DB::connection($connection)->select(DB::connection($connection)->raw($sqlQuery));

		return json_encode($result);

	});

	/* END OLD CODE */

});


?>
