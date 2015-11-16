<?php namespace IMCO\CatalogoNOMsApi;


use Illuminate\Database\Eloquent\Model;
use DOMDocument;

class MencionEnNota extends Model
{
    protected $connection = 'catalogoNoms';
    protected $primaryKey = 'id_mencion_en_nota';
    protected $table = 'menciones_en_notas';
    protected $fillable = ["clave","cod_nota", "ubicacion"];
    
}