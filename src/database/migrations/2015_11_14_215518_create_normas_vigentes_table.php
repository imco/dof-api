<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNormasVigentesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    protected $connection = 'catalogoNoms';

    public function up()
    {
        Schema::connection($this->connection)->create('normas_vigentes', function (Blueprint $table) {
            $table->increments('id_norma');
            $table->string('clave');
            $table->string('secretaria');
            $table->string('titulo');
            $table->string('archivo');
            $table->date('fecha_publicacion');
            $table->string('tipo');
            $table->string('producto');
            $table->string('rama_economica');
            $table->string('ctnn');
            $table->string('onn');
            

            $table->timestamps();
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
        Schema::connection($this->connection)->drop('normas_vigentes');
    }
}
