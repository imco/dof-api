<?php namespace IMCO\CatalogoNOMsApi;


use Illuminate\Database\Eloquent\Model;
use DateTime;
use DOMDocument;

class DofDiario extends Model
{
    protected $connection = 'catalogoNoms';
    protected $primaryKey = 'cod_diario';
    protected $fillable = array('cod_diario', 'fecha', 'edicion','availablePdf');


    public function getAvailablePdf(){

		$date = DateTime::createFromFormat('Y-m-d', $this->fecha);
		
		$response = json_decode(DOFClientController::http_get('http://diariooficial.gob.mx/WS_getDiarioPDF.php?day='.$date->format('d').'&month='.$date->format('m').'&year='.$date->format("Y").'&edicion=' . $this->edicion));

		if ($response && count ($response->availablePDF) == 1){
			$this->availablePdf = $response->availablePDF[0];

			if (DOFClientController::getHttpCode($this->availablePdf) == 200){
				return $response->availablePDF[0];
			}
		}else{
			error_log("No PDF for: " . $this->cod_diario);
		}
		return NULL;
    }

	public function getSummary(){
/* Crear resultado en JSON con el mejor output posible combinando

Conocer si hay archivo PDF disponible para una fecha específica:
http://diariooficial.gob.mx/WS_getDiarioPDF.php?day=29&month=08&year=2012&edicion=MAT

Un modelo para obtener el sumario completo de una edición. Los acentos están mal codificacdos, sin embargo aparentemente están todas las notas.
http://diariooficial.gob.mx/BB_DetalleEdicion.php?cod_diario=253279

Sumario completo del día, la codificación es correcta pero algunas notas no aparecen.
http://diariooficial.gob.mx/WS_getDiarioFull.php?year=2013&month=07&day=31

Nota completa
http://diariooficial.gob.mx/nota_detalle_popup.php?codigo=5308662
*/

		$result = array();
		
		$diario = json_decode(DOFClientController::http_get('http://diariooficial.gob.mx/BB_DetalleEdicion.php?cod_diario='.$this->cod_diario));
		if($diario->secciones){
			foreach($diario->secciones AS $seccion){
				foreach($seccion->organismos AS $organismo){
					foreach($organismo->contentSecretearias AS $contentSecretaria){
						foreach($contentSecretaria->contentDecretos AS $contentDecreto){
							$decretoFull = NULL;
					    	$tries = 5;
							while (strlen($decretoFull)<=1 && $tries>0){
								$decretoFull = DOFClientController::http_get('http://diariooficial.gob.mx/nota_detalle_popup.php?codigo='. $contentDecreto->cod_nota);

								$matches = array();
								preg_match('/<!DOCTYPE HTML .* <\/HTML>/', $decretoFull, $matches);
								if (!$matches && strlen($decretoFull)>0){
									$matches[0] = $decretoFull;
								}
								if ($matches){
									$decretoFull = $matches[0];
									$decretoFull = $testHTML = preg_replace('/(&#\d{4});?/', '\1;', $decretoFull);
									
									try{
										$decretoDOM = new \DOMDocument();
										libxml_use_internal_errors(true);

										$decretoDOM->loadHtml($decretoFull);
										libxml_clear_errors();

										$h1 = $decretoDOM->getElementsByTagName('h1');
										if ($h1->length >0 ){
											$contentDecreto->tituloDecreto = trim(preg_replace('/\s+/',' ',html_entity_decode($h1->item(0)->nodeValue)));
										}else{
											$font = $decretoDOM->getElementsByTagName('font');
											if ($font->length >0 ){
												for ($i=0; $i<$font->length;$i++){
													$found = preg_replace('/\s+/', ' ', trim(strtolower(preg_replace('/[^\w\s]/', '', html_entity_decode($font->item($i)->nodeValue)))));
													$current = preg_replace('/\s+/', ' ', trim(strtolower(preg_replace('/[^\w\s]/', '',$contentDecreto->tituloDecreto))));

													//print_r("$found\n$current\n". strcmp($found,$current) . "\n");
													if (strcmp($found,$current)==0){
														$contentDecreto->tituloDecreto = trim(preg_replace('/\s+/',' ',html_entity_decode($font->item($i)->nodeValue)));
														break;
													}
												}
//												$contentDecreto->tituloDecreto = trim(preg_replace('/\s+/',' ',html_entity_decode($font->item(0)->nodeValue)));
											}
										}
									} catch(ErrorException $exception ){

									}
								}

								$tries--;
							}
							$contenidoPlano = trim(html_entity_decode (preg_replace('/(<style.*?<\/style>)|(<script.*?<\/script>)|(<[^>]+>)/i', '', $decretoFull)));
							array_push($result, array('cod_diario' => $diario->cod_diario, 'cod_nota'=>$contentDecreto->cod_nota, 'titulo'=> $contentDecreto->tituloDecreto,'contenido' =>$decretoFull, 'contenido_plano'=>$contenidoPlano,'pagina'=>$contentDecreto->pagina, 'secretaria'=>$contentSecretaria->nombreSecretaria, 'organismo' =>$organismo->nomOrganismo, 'seccion'=>$seccion->numSeccion));
						}
					}
				}
			}
		}


		$date = DateTime::createFromFormat('Y-m-d', $this->fecha);

		$diario2 = json_decode(DOFClientController::http_get('http://diariooficial.gob.mx/WS_getDiarioFull.php?year='.$date->format('Y').'&month='.$date->format('m').'&day='.$date->format('d')));
		

		if($diario2->ejemplares){
			foreach($diario2->ejemplares AS $ejemplar){
				if ($ejemplar->secciones){
					foreach($ejemplar->secciones AS $seccion){
						if ($seccion->contentsection){
							//if($seccion->contentsection as $organismo){
							$organismo = $seccion->contentsection;

								var_dump($organismo);
								if ($organismo->content){
									foreach($organismo->content AS $secretario){
										if($secretaria->content){
											foreach($secretaria->content AS $nota){
												$newNota = true;

												foreach($result as $key=>$oldNota){
													if ($oldNota['cod_nota']== $nota->id){
														$newNota = false;

														$result[$key]['titulo'] = $nota->titulo;
														break;
													}
												}

												if ($newNota){
													$decretoFull = NULL;
											    	$tries = 5;
													while (strlen($decretoFull)<=1 && $tries>0){
														$decretoFull = DOFClientController::http_get('http://diariooficial.gob.mx/nota_detalle_popup.php?codigo='. $nota->id);

														$matches = array();
														preg_match('/<!DOCTYPE HTML .* <\/HTML>/', $decretoFull, $matches);
														if (!$matches && strlen($decretoFull)>0){
															$matches[0] = $decretoFull;
														}
														if ($matches){
															$decretoFull = $matches[0];
														}

														$tries--;
													}
													$contenidoPlano = trim(html_entity_decode (preg_replace('/(<style.*?<\/style>)|(<script.*?<\/script>)|(<[^>]+>)/i', '', $decretoFull)));

													array_push($result, array('cod_diario' => $ejemplar->id, 'cod_nota'=>$nota->id, 'titulo'=> $nota->titulo,'contenido' =>$decretoFull, 'contenido_plano'=>$contenidoPlano,'secretaria'=>$secretaria->name, 'organismo' =>$organismo->name, 'seccion'=>$seccion->secc));

												}
											}
										}
									}
								}
							//}
						}
					}
				}
			}
		}
		return $result;

	}
}