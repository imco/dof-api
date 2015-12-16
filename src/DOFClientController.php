<?php namespace IMCO\CatalogoNOMsApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use DB;
use Response;
use Input; 

use Illuminate\Http\Request;

use DateTime;
use DateInterval;

/*
Para obtener las fechas que tienen publicación en un mes:
http://diariooficial.gob.mx/WS_getDiarioFecha.php?year=2012&month=08

Con éste podrán conocer a partir de una fecha específica, los códigos de diario de las 99 fechas anteriores, cada una identificada con fecha y edición:
http://diariooficial.gob.mx/BB_menuPrincipal.php?day=08&month=09&year=2014

Conocer si hay archivo PDF disponible para una fecha específica:
http://diariooficial.gob.mx/WS_getDiarioPDF.php?day=29&month=08&year=2012&edicion=MAT

Un modelo para obtener el sumario completo de una edición. Los acentos están mal codificacdos, sin embargo aparentemente están todas las notas.
http://diariooficial.gob.mx/BB_DetalleEdicion.php?cod_diario=253279

Sumario completo del día, la codificación es correcta pero algunas notas no aparecen.
http://diariooficial.gob.mx/WS_getDiarioFull.php?year=2013&month=07&day=31

Nota completa en HTML
http://diariooficial.gob.mx/nota_detalle_popup.php?codigo=5308661
*/


class DOFClientController extends Controller {
	protected $connection = 'CatalogoNoms';
	

	public static function reformatDateString($string){
		$date = DateTime::createFromFormat('d-M-Y', str_replace(array('Ene','Abr','Ago', 'Dic'),array('Jan','Apr', 'Aug', 'Dec'),$string));
		$dateReformat = $date->format('Y') .'-' . $date->format('m'). '-' . $date->format('d');
		
		return $dateReformat;
	}

	/** Busca los 5 diarios más recientes de los que aún no se han obtenido las notas y los inserta en la base de datos, la inserción se hace por bloque de notas para asegurar que se ha insertado el diario completo
	**/
	public static function fillNotes($batchSize=5){
		$diarios = DofDiario::select('dof_diarios.cod_diario')->leftJoin('dof_notas', 'dof_notas.cod_diario', '=', 'dof_diarios.cod_diario')->whereNull('cod_nota')->where('invalid', '=', false)->orderBy('fecha', 'desc')->limit($batchSize)->get();

		$faltantes = DofDiario::select('dof_diarios.cod_diario')->leftJoin('dof_notas', 'dof_notas.cod_diario', '=', 'dof_diarios.cod_diario')->whereNull('cod_nota')->where('invalid', '=', false)->count();

		print_r("Faltan $faltantes diarios por analizar.\n");
		$dofClient = new DOFClientController();

		$cod_diario = array();
		foreach($diarios AS $diario){
			array_push($cod_diario, $diario->cod_diario);
		}

		$diarios = DofDiario::findMany($cod_diario);

		
		foreach($diarios AS $diario){
			$diario->invalid = NULL;
			$diario->availablePdf = $diario->getAvailablePdf();
			$diario->save();
		}

		//$result = [];
		foreach($diarios AS $diario){
			print_r("cod_diario ". $diario->cod_diario . "\n");

			$newNotes = array();
			$date = DateTime::createFromFormat('Y-m-d', $diario->fecha);
			print_r("Downloading...\n");
			$sumarios = $diario->getSummary();
			//$result = array_merge($result, $sumarios);
			print_r("Downloaded\n");
	        foreach($sumarios AS $sumario){
	        	print_r("\tcod_nota\t" . $sumario->cod_nota . "\n");
	            array_push($newNotes, array_merge((array)$sumario, array('created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'))));
	        }

	        /* Verifica si una nota sin titulo está duplicada y el duplicado contiene el título */
	        foreach($newNotes AS $key =>$note){
	        	if ($note['titulo'] == null){
	        		foreach($newNotes AS $existingNote){
	        			if ($existingNote['titulo'] != null && $note['seccion'] == $existingNote['seccion'] && $note['pagina']== $existingNote['pagina']){
	        				unset($newNotes[$key]);
	        				break;
	        			}
	        		}
	        	}
	        }
	        if (count($newNotes) > 0 ){
		        $newNotes = array_values($newNotes);
		        DofNota::insert($newNotes);
		        $diario->invalid=false;
		        $diario->save();
		    }elseif ($diario->availablePdf == null){
		    	$diario->invalid=true;
		        $diario->save();
		    }
		}

		//return $result;
	}

	public static function http_get($url){
		if (!function_exists('curl_init')){
		    die('Sorry cURL is not installed!');
		}

		// OK cool - then let's create a new cURL resource handle
		$ch = curl_init();

		// Now set some options (most are optional)

		// Set URL to download
		curl_setopt($ch, CURLOPT_URL, $url);

		// Set a referer
		//curl_setopt($ch, CURLOPT_REFERER, "http://www.example.org/yay.htm");

		// User agent
		curl_setopt($ch, CURLOPT_USERAGENT, "DOF Scrapper v1.0.1");

		// Include header in result? (0 = yes, 1 = no)
		curl_setopt($ch, CURLOPT_HEADER, 0);

		// Should cURL return or print out the data? (true = return, false = print)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);

		// Download the given URL, and return output
		$output = curl_exec($ch);

		$info = curl_getinfo($ch);
		$header = curl_exec($ch);
		
		// Close the cURL resource, and free system resources
		curl_close($ch);
//		var_dump($header);

//		var_dump ($info);
//		var_dump($output);

		return $output;
	}

	public static function getHttpCode($url){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FILETIME, true);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$header = curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);
		return $info['http_code'];
	}


	public static function getTodaysDof(){

		$dofClient = new DOFClientController;

        $dofDiario = $dofClient->getEditionsOnDate(date("Y"), date("m"), date("d"))->getData();

        foreach($dofDiario->list as $diario){
            $diario->fecha = DOFClientController::reformatDateString($diario->fecha);
            $diario = DofDiario::firstOrCreate((array)$diario);
            if($diario->availablePdf == null){
            	$diario->availablePdf = $diario->getAvailablePdf();	
            }
        }

        return $diario;
	}

	public static function getDofOnDate($fecha = null){

		$dofClient = new DOFClientController;

        $dofDiario = $dofClient->getEditionsOnDate(date("Y", $fecha ?:strtotime('-1 day')), date("m", $fecha ?:strtotime('-1 day')), date("d", $fecha ?:strtotime('-1 day')))->getData();

        foreach($dofDiario->list as $diario){
            $diario->fecha = DOFClientController::reformatDateString($diario->fecha);
            $diario = DofDiario::firstOrCreate((array)$diario);
            if($diario->availablePdf == null){
            	//print_r("No PDF Available");
            	$diario->availablePdf = $diario->getAvailablePdf();
            	//$diario->save();
            }

            var_dump($diario->cod_diario);
            var_dump(DofNota::where('cod_diario', $diario->cod_diario)->count());

            if (DofNota::where('cod_diario', $diario->cod_diario)->count()==0){
	            //$result = [];
				$newNotes = array();
				$date = DateTime::createFromFormat('Y-m-d', $diario->fecha);
				$sumarios = $diario->getSummary();
				//$result = array_merge($result, $sumarios);
		        foreach($sumarios AS $sumario){
		            array_push($newNotes, array_merge((array)$sumario, array('created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'))));
		        }

		        /* Verifica si una nota sin titulo está duplicada y el duplicado contiene el título */
		        foreach($newNotes AS $key =>$note){
		        	if ($note['titulo'] == null){
		        		foreach($newNotes AS $existingNote){
		        			if ($existingNote['titulo'] != null && $note['seccion'] == $existingNote['seccion'] && $note['pagina']== $existingNote['pagina']){
		        				unset($newNotes[$key]);
		        				break;
		        			}
		        		}
		        	}
		        	print_r($note['titulo']. "\n");
		        }

		        if (count($newNotes) > 0 ){
			        $newNotes = array_values($newNotes);
			        DofNota::insert($newNotes);
			        $diario->invalid=false;
			    }elseif ($diario->availablePdf == null){
			    	$diario->invalid=true;
			    }
			}
		    $diario->save();

        }


        return $dofDiario;
	}

	function getEditionsOnDate($year,$month=0, $day = 0){
		//return http_get("http://diariooficial.gob.mx/WS_getDiarioFecha.php?year=$year&month=$month");		
		if (!function_exists('curl_init')){
		    abort(500);
		}

		if ($year<1917 || $year > date("Y") || $month<0 || $month >12 || $day < 0 || $day > 31){
			abort(404);
		}

		$meses = array("Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic");
		$date = DateTime::createFromFormat('d-m-Y', "$day-$month-$year");
		$dateStr = $date->format('d') .'-' . $meses[$date->format('n')-1] . '-' . $date->format('Y');

		$output = json_decode(self::http_get("http://diariooficial.gob.mx/WS_getDiarioFecha.php?year=$year&month=$month"));

		if($day!=0) {
			if (in_array($day, $output->availableDays)){
				$result = array();
								
				$output = json_decode(self::http_get("http://diariooficial.gob.mx/BB_menuPrincipal.php?day=". $date->format('d') ."&month=" . $date->format('n') ."&year=" . $date->format('Y')));
				foreach($output->list AS $publicacion){
					
					$datePublicacion = DateTime::createFromFormat('d-M-Y', str_replace(array('Ene','Abr','Ago', 'Dic'),array('Jan','Apr', 'Aug', 'Dec'),$publicacion->fecha));
					$datePublicacionStr = $datePublicacion->format('d') .'-' . $meses[$datePublicacion->format('n')-1] . '-' . $datePublicacion->format('Y');
					
					if ($dateStr == $publicacion->fecha){
						array_push($result, $publicacion);
					}
				}
			}else{
				abort(404);
			}
		}else{
			$result = array();
			
			if ($month != 0){
				$dateStart = DateTime::createFromFormat('d-m-Y', "1-$month-$year");
				$dateEnd = DateTime::createFromFormat('d-m-Y', "0-$month-$year");
				$dateEnd->add(DateInterval::createFromDateString($dateStart->format('t') . ' day'));
			}else{
				$dateStart = DateTime::createFromFormat('d-m-Y', "1-1-$year");
				$dateEnd = DateTime::createFromFormat('d-m-Y', "31-12-$year");
			}

			$date = clone $dateEnd;
			$finished = false;

			while (!$finished){
				$output = json_decode(self::http_get("http://diariooficial.gob.mx/BB_menuPrincipal.php?day=". $date->format('d') ."&month=" . $date->format('n') ."&year=" . $date->format('Y')));
				foreach($output->list AS $publicacion){
					
					$datePublicacion = DateTime::createFromFormat('d-M-Y', str_replace(array('Ene','Abr','Ago', 'Dic'),array('Jan','Apr', 'Aug', 'Dec'),$publicacion->fecha));
					$datePublicacionStr = $datePublicacion->format('d') .'-' . $meses[$datePublicacion->format('n')-1] . '-' . $datePublicacion->format('Y');
					
					if ($datePublicacion >=$dateStart && $datePublicacion <=$dateEnd){
						array_push($result, $publicacion);
						if ($datePublicacion <= $date) {
							$date = clone $datePublicacion;
							$date->sub(DateInterval::createFromDateString('1 day'));
						}
					} else{
						if ($datePublicacion < $dateStart) $finished = true;
					}
				}
			}

		}
		
		return response::json(array('total'=> count($result), 'list'=>$result));
	}

}
