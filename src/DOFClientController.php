<?php namespace IMCO\CatalogoNOMsApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use DB;
use Response;
use Input; 

use Illuminate\Http\Request;

use Swagger\Annotations as SWG;
use DateTime;
use DateInterval;

/*
Para obtener las fechas que tienen publicación en un mes:
http://diariooficial.gob.mx/WS_getDiarioFecha.php?year=2012&month=08

Conocer si hay archivo PDF disponible para una fecha específica:
http://diariooficial.gob.mx/WS_getDiarioPDF.php?day=29&month=08&year=2012&edicion=MAT

Un modelo para obtener el sumario completo del día con un parámetro menos (en éste sólo se pasa el cod_diario):
diariooficial.gob.mx/BB_DetalleEdicion.php?cod_diario=253279

Con éste podrán conocer a partir de una fecha específica, los códigos de diario de las 99 fechas anteriores, cada una identificada con fecha y edición:
http://diariooficial.gob.mx/BB_menuPrincipal.php?day=08&month=09&year=2014
*/
class DOFClientController extends Controller {
	protected $connection = 'CatalogoNoms';
	

	function getDatesPublishedOnMoth($year,$month=0, $day = 0){
		//return http_get("http://diariooficial.gob.mx/WS_getDiarioFecha.php?year=$year&month=$month");		

		if (!function_exists('curl_init')){
		    die('Sorry cURL is not installed!');
		}

		if ($year<1917 || $year > date("Y") || $month<0 || $month >12 || $day < 0 || $day > 31){
			die('Invalid date');
		}

		// OK cool - then let's create a new cURL resource handle
		$ch = curl_init();

		// Now set some options (most are optional)

		// Set URL to download
		curl_setopt($ch, CURLOPT_URL, "http://diariooficial.gob.mx/WS_getDiarioFecha.php?year=$year&month=$month");

		// Set a referer
		//curl_setopt($ch, CURLOPT_REFERER, "http://www.example.org/yay.htm");

		// User agent
		curl_setopt($ch, CURLOPT_USERAGENT, "scrapper");

		// Include header in result? (0 = yes, 1 = no)
		curl_setopt($ch, CURLOPT_HEADER, 0);

		// Should cURL return or print out the data? (true = return, false = print)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		// Download the given URL, and return output
		$output = json_decode(curl_exec($ch));

		$meses = array("Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic");
		$date = DateTime::createFromFormat('d-m-Y', "$day-$month-$year");
		$dateStr = $date->format('d') .'-' . $meses[$date->format('n')-1] . '-' . $date->format('Y');

		if($day!=0) {
			if (in_array($day, $output->availableDays)){
				$result = array();
				

				curl_setopt($ch, CURLOPT_URL, "http://diariooficial.gob.mx/BB_menuPrincipal.php?day=". $date->format('d') ."&month=" . $date->format('n') ."&year=" . $date->format('Y'));
				
				$output = json_decode(curl_exec($ch));
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

			//print_r ($dateStart->format('d-M-Y') . "\t" . $dateEnd->format('d-M-Y'));
			while (!$finished){
				curl_setopt($ch, CURLOPT_URL, "http://diariooficial.gob.mx/BB_menuPrincipal.php?day=". $date->format('d') ."&month=" . $date->format('n') ."&year=" . $date->format('Y'));
					
				$output = json_decode(curl_exec($ch));
				foreach($output->list AS $publicacion){
					
					$datePublicacion = DateTime::createFromFormat('d-M-Y', str_replace(array('Ene','Abr','Ago', 'Dic'),array('Jan','Apr', 'Aug', 'Dec'),$publicacion->fecha));
					$datePublicacionStr = $datePublicacion->format('d') .'-' . $meses[$datePublicacion->format('n')-1] . '-' . $datePublicacion->format('Y');
					
					if ($datePublicacion >=$dateStart && $datePublicacion <=$dateEnd){
						array_push($result, $publicacion);
						if ($datePublicacion < $date) {
							$date = clone $datePublicacion;
							$date->sub(DateInterval::createFromDateString('1 day'));}
					}else{
						if ($datePublicacion < $dateStart) $finished = true;
					}
				}
			}

		}
		// Close the cURL resource, and free system resources
		curl_close($ch);
		return response::json(array('total'=> count($result), 'list'=>$result));
	}

}
