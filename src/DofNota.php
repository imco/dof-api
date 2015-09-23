<?php namespace IMCO\CatalogoNOMsApi;


use Illuminate\Database\Eloquent\Model;

class DofNota extends Model
{
    protected $connection = 'CatalogoNoms';
    protected $primaryKey = 'cod_nota';
    protected $fillable = array('cod_diario', 'cod_nota', 'titulo', 'contenido', 'pagina', 'secretaria', 'organismo', 'seccion');
}