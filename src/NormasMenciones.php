<?php namespace IMCO\CatalogoNOMsApi;


use Illuminate\Database\Eloquent\Model;
use DOMDocument;

class NormasMenciones extends Model
{
    protected $connection = 'catalogoNoms';
    //protected $primaryKey = 'id_mencion_en_nota';
    protected $table = 'view_normas_menciones';
    //protected $fillable = ["mencion","cod_nota", "ubicacion"];
    
/*
    public function nota(){
    	return $this->hasOne('IMCO\CatalogoNOMsApi\DofNota', 'cod_nota', 'cod_nota');
    }
*/
}