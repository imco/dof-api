<?php namespace IMCO\CatalogoNOMsApi;


use Illuminate\Database\Eloquent\Model;
use DOMDocument;
use DB;

class DofNota extends Model
{
    protected $connection = 'catalogoNoms';
    protected $primaryKey = 'cod_nota';
    protected $table = 'dof_notas';
    protected $fillable = array('cod_diario', 'cod_nota', 'titulo', 'contenido', 'contenido_plano', 'pagina', 'secretaria', 'organismo', 'seccion', 'created_at', 'updated_at');

    public $pattern = '((d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}|[^\s>]*(nmx|nom(?!\w|\d|&\wacute;)))(([^\s])?[\w\d\/]+)+(?=(,\s|\)|\.\s|\s|<|\.?$)))';


    public function diario(){
    	return $this->belongsTo('IMCO\CatalogoNOMsApi\DofDiario', 'cod_diario', 'cod_diario');
    }

    public function scopeContains($query, $patron=null){
    	if (!$patron){
    		$patron = $this->pattern;
    	}
    	return $query->select(DB::raw("DISTINCT cod_nota, CASE WHEN entity2char(titulo)  ~* '".$patron. "' THEN 'Título' ELSE 'Contenido' END AS ubicacion, entity2char((regexp_matches(titulo || ' '|| contenido, '".$patron."', 'gi'))[1]) as mencion"));
    }

    public function scopeBodyContains($query, $clave){
    	return $query->select(DB::raw("cod_nota, CASE WHEN titulo  ~* '".$clave. "' THEN 'Título' ELSE 'Contenido' END AS ubicacion, (regexp_matches(contenido, '".$clave."', 'gi'))[1] as mencion"));
    }

    public function scopeTitleContains($query, $clave){
    	return $query->select(DB::raw("cod_nota, CASE WHEN titulo  ~* '".$clave. "' THEN 'Título' ELSE 'Contenido' END AS ubicacion, (regexp_matches(titulo, '".$clave."', 'ig'))[1] as mencion"));
    }

    public function findClaves(){
    	return null;
    }


    public function updateTitulo(){
    	$decretoFull = NULL;
    	$tries = 5;
    	while (strlen($decretoFull)<=1 && $tries>0){
			$decretoFull = DOFClientController::http_get('http://diariooficial.gob.mx/nota_detalle_popup.php?codigo='. $this->cod_nota);

			//print_r('http://diariooficial.gob.mx/nota_detalle_popup.php?codigo='. $this->cod_nota . "\n");
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
						$this->titulo = trim(preg_replace('/\s+/',' ',html_entity_decode($h1->item(0)->nodeValue)));
					}else{
						$font = $decretoDOM->getElementsByTagName('font');
						if ($font->length >0 ){
							for ($i=0; $i<$font->length;$i++){
								$found = preg_replace('/\s+/', ' ', trim(strtolower(preg_replace('/[^\w\s]/', '', html_entity_decode($font->item($i)->nodeValue)))));
								$current = preg_replace('/\s+/', ' ', trim(strtolower(preg_replace('/[^\w\s]/', '',$this->titulo))));

								//print_r("$found\n$current\n". strcmp($found,$current) . "\n");
								if (strcmp($found,$current)==0){
									$this->titulo = trim(preg_replace('/\s+/',' ',html_entity_decode($font->item($i)->nodeValue)));
									break;
								}
							}
						}
					}
				} catch(ErrorException $exception ){

				}
			}

			$tries--;
		}
		$this->save();
    }
}