<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNormasMencionesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    protected $connection = 'catalogoNoms';

    public function up()
    {
        DB::connection($this->connection)->statement("create view catalogonoms_view_normas_menciones as SELECT DISTINCT * FROM (select clave, mencion from catalogonoms_normas_menciones UNION SELECT clave, clave as mencion from catalogonoms_normas_vigentes) t1;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection($this->connection)->statement('DROP VIEW catalogonoms_view_normas_menciones');
    }
}
