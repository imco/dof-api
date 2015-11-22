<?php namespace IMCO\CatalogoNOMsApi;


use Illuminate\Database\Eloquent\Model;
use DOMDocument;

class NormaVigente extends Model
{
    protected $connection = 'catalogoNoms';
    protected $primaryKey = 'clave';
    protected $table = 'normas_vigentes';
    protected $fillable = ["clave","secretaria","titulo","archivo","fecha_publicacion","tipo","producto","rama_economica","ctnn","onn"];


    public function scopeConFechaPublicacionIncorrecta($query){

    	return $query->whereNotIn('fecha_publicacion', DofDiario::select('fecha')->get()->toArray());

    }

    public function clave_patron(){
    	$result = $this->clave;

    	// Toda secuencia  númerica puede estar o no precedida de ceros
    	$result = preg_replace('/0*(\d+)/', '0*\1', $result);
    	// Los años, pueden estár escritos con 2 o 4 dígitos
    	$result = preg_replace('/(19|20)(\d{2})/', '(\1)?\2', $result);
    	// Las claves de normas no necesariamente tienen el prefijo NMX
    	$result = preg_replace('/.*nmx[^\d\w]*/i', '[^\s>]*', $result);
    	// El separador podría ser cualquier cosa no alafanumérica
    	$result = preg_replace('/[-\/]/', '[^\d\w]{0,3}', $result);


    	return "($result)";
    }

/*
    public function menciones(){
    	return $this->hasMany('IMCO\CatalogoNOMsApi\MencionEnNota', 'mencion', 'clave');
    }
*/
    public function menciones(){
        return $this->hasManyThrough('IMCO\CatalogoNOMsApi\MencionEnNota', 'IMCO\CatalogoNOMsApi\NormasMenciones', 'clave', 'mencion');
    }
/*
    public function primeraMencion(){
        return $this->hasManyThrough('IMCO\CatalogoNOMsApi\MencionEnNota', 'IMCO\CatalogoNOMsApi\NormasMenciones', 'clave', 'mencion')->where;
    }
    */
}