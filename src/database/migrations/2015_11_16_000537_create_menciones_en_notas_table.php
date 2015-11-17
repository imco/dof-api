<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMencionesEnNotasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    protected $connection = 'catalogoNoms';

    public function up()
    {
        Schema::connection($this->connection)->create('menciones_en_notas', function (Blueprint $table) {
            $table->increments('id_mencion_en_nota');
            $table->string('clave');
            $table->string('clave_normalizada')->nullable();
            $table->integer('cod_nota');
            $table->string('ubicacion')->nullable();
            $table->string('etiqueta')->nullable();
            $table->timestamps();

            //$table->index()
        });

        //DB::connection($this->connection)->statement("create view catalogonoms_view_nmx as select diario.cod_diario, fecha, cod_nota, titulo from catalogonoms_dof_notas nota JOIN catalogonoms_dof_diarios diario ON diario.cod_diario = nota.cod_diario where titulo ~ 'NMX|normas?\s*mexicanas?';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //DB::connection($this->connection)->statement('DROP VIEW catalogonoms_view_nmx');
        Schema::connection($this->connection)->drop('menciones_en_notas');
    }
}
