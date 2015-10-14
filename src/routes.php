<?php
use Swagger\Annotations as SWG;

use IMCO\CatalogoNOMsApi\DofDiario;
use IMCO\CatalogoNOMsApi\DofNota;
use IMCO\CatalogoNOMsApi\DOFClientController;
//use Response;


Route::group(array('prefix' => 'catalogonoms', 'namespace'=>'IMCO\CatalogoNOMsApi'), function () {
$connection = 'CatalogoNomsOld';

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
		$locale='es_MX.UTF-8';
		setlocale(LC_ALL,$locale);
		putenv('LC_ALL='.$locale);

		$path = base_path('bin');
		$data = base_path('database/data');
		
		$notas = DofNota::where('titulo', '~', 'NMX')->limit(100)->get();

		$result = "cod_nota\ttitulo\ttipo\tclave\tclasificación";
		foreach($notas AS $nota){
			//$nota = DofNota::find($cod_nota);
			$subject = `$path/clasificador.py -i $data/knowledgebase.csv "$nota->titulo"`;

			if (strlen($subject)>0){
				foreach(preg_split("/((\r?\n)|(\r\n?))/", $subject) as $line){
					if (strlen($line)>0){
						$result .= "$nota->cod_nota\t$nota->titulo\t$line\n";
					}
				}
			}else{
				$result .= "$nota->cod_nota\t$nota->titulo\n";	
			}
		}
		
		return \Response::make($result,200, ['Content-Type'=>'text/plain']);
		
		//DOFClientController::fillNotes();


/*		DOFNota::find(763543)->updateTitulo();
		return DofNota::find(763543)->titulo;
//*/
/*
		print_r("STARTING...\n");
		$notes = DofNota::whereRaw('titulo ~ \'\?\?\' and contenido is not null')->limit(1)->get();
		foreach($notes AS $nota){
			print_r("$nota->titulo \t=> ");
			$nota->updateTitulo();
			print_r("$nota->titulo\n");
		}
/
		//return Response::make()->header("Content-Type", "plain/text");
//*/
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

		$result = DB::connection($connection)->select(DB::raw($sqlQuery));
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

		$result = DB::connection($connection)->select(DB::raw($sqlQuery));
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

		$result = DB::connection($connection)->select(DB::raw($sqlQuery));

		return json_encode($result);

	});

	/* END OLD CODE */

});


?>
