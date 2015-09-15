<?php namespace IMCO\CatalogoNOMsApi;


use Illuminate\Database\Eloquent\Model;

class DofDiario extends Model
{
    protected $connection = 'CatalogoNoms';
    protected $primaryKey = 'cod_diario';
}