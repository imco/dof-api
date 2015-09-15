<?php namespace IMCO\CatalogoNOMsApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use DB;
use Response;
use Input; 

use Illuminate\Http\Request;

class CatalogoNOMsController extends Controller {
	protected $connection = 'CatalogoNoms';
	
	public function getNomsPublications(){
		return json_encode(DB::connection($connection)->select(DB::raw("WITH fechaPublicacion AS (SELECT trim(both '-' FROM substring(clavenomnorm from '-.*-')) subclavenomnorm, max(fecha) AS fecha_nom, NULL::date AS fecha_modificacion FROM notasnom  WHERE etiqueta= 'NOM' GROUP BY trim(both '-' FROM substring(clavenomnorm from '-.*-'))),
		
			fechaModificacion AS (SELECT trim(both '-' FROM substring(clavenomnorm from '-.*-')) subclavenomnorm, NULL::date as fecha_nom, max(fecha) AS fecha_modificacion FROM notasnom  WHERE etiqueta= 'Modificación' GROUP BY trim(both '-' FROM substring(clavenomnorm from '-.*-'))),
			
			nomreciente AS (SELECT subclavenomnorm, COALESCE(max(fecha_nom),max(fecha_modificacion)) AS fecha FROM (SELECT * FROM fechaPublicacion UNION SELECT * FROM fechaModificacion) union_table GROUP BY subclavenomnorm),
		
			
			notasNOMRecientes AS (SELECT DISTINCT notasnom.* from nomreciente JOIN notasnom ON clavenomnorm like '%'||subclavenomnorm ||'%' WHERE subclavenomnorm IS NOT NULL AND notasnom.fecha = nomreciente.fecha)
			
			SELECT DISTINCT fecha,vigencianoms.clavenomnorm,trim(both '-' FROM (regexp_matches(vigencianoms.clavenomnorm,'NOM(?:[^a-z0-9])(\d[a-z0-9\/]*[^a-z0-9])?([a-z][a-z0-9\/]*(?:[^a-z0-9](?:[a-z][a-z0-9\/]*[^a-z0-9]?)?)?)?(\d[a-z0-9\/]*[^a-z0-9])?','gi'))[2]) as comite, titulo
			FROM vigencianoms LEFT JOIN notasnomrecientes ON substring(vigencianoms.clavenomnorm from '-.*-') = substring(notasnomrecientes.clavenomnorm from '-.*-') WHERE  estatus='Vigente' ORDER BY  clavenomnorm ASC")));
	}
	
	public function getNomsListByDependency($dependencia = NULL){
		if ($dependencia == null) {
			$sqlQuery = "SELECT DISTINCT secretaria AS dependencia from comite ORDER BY secretaria;";
		} else {
			$sqlQuery = "WITH detalleDependencia AS (SELECT secretaria AS dependencia, nombre_secretaria as nombre_dependencia, comite, descripcion_comite, reseña_comite from comite WHERE lower(secretaria)=lower('$dependencia')),

			nomReciente AS (SELECT trim(both '-' FROM substring(clavenomnorm from '-.*-')) subclavenomnorm, max(fecha) AS fecha FROM notasnom  WHERE etiqueta= 'NOM' GROUP BY trim(both '-' FROM substring(clavenomnorm from '-.*-'))),
		
			notasNOMRecientes AS (SELECT DISTINCT notasnom.* from nomreciente JOIN notasnom ON clavenomnorm like '%'||subclavenomnorm ||'%' WHERE subclavenomnorm IS NOT NULL AND notasnom.fecha = nomreciente.fecha AND etiqueta= 'NOM'),

			nomsDetalle AS (SELECT fecha,vigencianoms.clavenomnorm,trim(both '-' from (regexp_matches(vigencianoms.clavenomnorm,'NOM(?:[^a-z0-9])(\d[a-z0-9\/]*[^a-z0-9])?([a-z][a-z0-9\/]*(?:[^a-z0-9](?:[a-z][a-z0-9\/]*[^a-z0-9]?)?)?)?(\d[a-z0-9\/]*[^a-z0-9])?','gi'))[2]) as comites, titulo from vigencianoms LEFT JOIN notasnomrecientes ON substring(vigencianoms.clavenomnorm from '-.*-') = substring(notasnomrecientes.clavenomnorm from '-.*-')),

			nomsPorComite AS (SELECT UNNEST(string_to_array(comites, '/')) comite, clavenomnorm FROM nomsDetalle),

			nomsDeLaDependencia AS (SELECT * FROM nomsPorComite NATURAL JOIN detalleDependencia)

			SELECT dependencia, nombre_dependencia, comite, descripcion_comite, reseña_comite, '['||string_agg('{\"clavenomnorm\":\"'||clavenomnorm||'\"'
				|| ',\"fecha\":\"'||fecha||'\"'
				|| ',\"comite\":\"'||comite||'\"'
				|| ',\"titulo\":\"'||titulo||'\"'
				, '},')||'}]' as normas FROM nomsDeLaDependencia Natural JOIN nomsDetalle GROUP BY dependencia, nombre_dependencia, comite, descripcion_comite, reseña_comite";

		}
		$result = DB::connection($connection)->select(DB::raw($sqlQuery));
		foreach ($result as $row) {
			if (property_exists($row, 'normas')) {
				$row->normas = json_decode($row->normas);
			}
		}
		return json_encode($result);
	}
	
	function getNOMPublications($clave) {
		$clave = urldecode($clave);

		$historial = DB::connection($connection)->select(DB::raw("
			WITH fechaPublicacion AS (SELECT trim(both '-' FROM substring(clavenomnorm from '-.*-')) subclavenomnorm, max(fecha) AS fecha_nom, NULL::date AS fecha_modificacion FROM notasnom  WHERE etiqueta= 'NOM' GROUP BY trim(both '-' FROM substring(clavenomnorm from '-.*-'))),
		
			fechaModificacion AS (SELECT trim(both '-' FROM substring(clavenomnorm from '-.*-')) subclavenomnorm, NULL::date as fecha_nom, max(fecha) AS fecha_modificacion FROM notasnom  WHERE etiqueta= 'Modificación' GROUP BY trim(both '-' FROM substring(clavenomnorm from '-.*-'))),
			
			nomreciente AS (SELECT subclavenomnorm, COALESCE(max(fecha_nom),max(fecha_modificacion)) AS fecha FROM (SELECT * FROM fechaPublicacion UNION SELECT * FROM fechaModificacion) union_table GROUP BY subclavenomnorm),
		
			
			notasNOMRecientes AS (SELECT DISTINCT notasnom.* from nomreciente JOIN notasnom ON clavenomnorm like '%'||subclavenomnorm ||'%' WHERE subclavenomnorm IS NOT NULL AND notasnom.fecha = nomreciente.fecha)

			SELECT fecha,vigencianoms.clavenomnorm,trim(both '-' from (regexp_matches(vigencianoms.clavenomnorm,'NOM(?:[^a-z0-9])(\d[a-z0-9\/]*[^a-z0-9])?([a-z][a-z0-9\/]*(?:[^a-z0-9](?:[a-z][a-z0-9\/]*[^a-z0-9]?)?)?)?(\d[a-z0-9\/]*[^a-z0-9])?','gi'))[2]) as comite, titulo from vigencianoms LEFT JOIN notasnomrecientes ON substring(vigencianoms.clavenomnorm from '-.*-') = substring(notasnomrecientes.clavenomnorm from '-.*-') WHERE vigencianoms.clavenomnorm like :clavenomnorm;
			"), array('clavenomnorm' => '%' . substr($clave, 3, -4) . '%'));
		return json_encode($historial);

	}
	
	function getNOM($clave) {
		$clave = urldecode($clave);

		$historial = DB::select(DB::raw("SELECT  fecha,cod_nota, clavenomnorm, etiqueta, entity2char(titulo), urlnota AS url
			FROM (SELECT * FROM notasnom UNION SELECT fecha, null as cod_nota, null as clavenom, clavenomnorm, 'Manifestación de Impacto Regulatorio' as titulo, etiqueta, urlmir, null as revisionhumana FROM mir) notasnom where clavenomnorm like :clavenomnorm ORDER BY fecha ASC;"),
			array('clavenomnorm' => '%' . substr($clave, 3, -4) . '%'));

		$ramayProducto = DB::select(DB::raw("
			Select array_to_json(rama::text[]) as rama, array_to_json(producto::text[]) as producto from vigencianoms where clavenomnorm like :clavenomnorm limit 1;
			"), array('clavenomnorm' => '%' . substr($clave, 3, -4) . '%'));

		$result = new stdClass;
		foreach ($ramayProducto as $row) {
			$result->rama = json_decode($row->rama);
			$result->producto = json_decode($row->producto);
		}

		$result->historial = $historial;

		$comite = DB::select(DB::raw("SELECT  secretaria, nombre_secretaria, comite, descripcion_comite from comite WHERE :clavenomnorm like ('%'||comite||'%')ORDER BY secretaria, comite ASC;"),
			array('clavenomnorm' => '%' . substr($clave, 3, -4) . '%'));

		$result->comite = $comite;

		return json_encode($result);
	}
}
