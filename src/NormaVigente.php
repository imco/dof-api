<?php namespace IMCO\CatalogoNOMsApi;


use Illuminate\Database\Eloquent\Model;
use DOMDocument;

class NormaVigente extends Model
{
    protected $connection = 'catalogoNoms';
    protected $primaryKey = 'id_norma';
    protected $table = 'normas_vigentes';
    protected $fillable = ["clave","secretaria","titulo","archivo","fecha_publicacion","tipo","producto","rama_economica","ctnn","onn"];


    public function scopeConFechaPublicacionIncorrecta($query){

    	return $query->whereNotIn('fecha_publicacion', DofDiario::select('fecha')->get()->toArray());

    }
}