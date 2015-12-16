<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDofNotasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    protected $connection = 'catalogoNoms';

    public function up()
    {
        Schema::connection($this->connection)->create('dof_notas', function (Blueprint $table) {
            $table->integer('cod_nota');
            $table->integer('cod_diario');
            $table->text('titulo')->nullable();
            $table->integer('seccion')->nullable();
            $table->string('organismo')->nullable();
            $table->string('secretaria');
            $table->integer('pagina')->nullable();
            $table->text('contenido')->nullable();
            $table->text('contenido_plano')->nullable();

            $table->timestamps();
            $table->primary('cod_nota');
            $table->foreign('cod_diario')->references('cod_diario')->on('dof_diarios');
        });

        DB::connection($this->connection)->statement("create view catalogonoms_view_nmx as select diario.cod_diario, fecha, cod_nota, titulo from catalogonoms_dof_notas nota JOIN catalogonoms_dof_diarios diario ON diario.cod_diario = nota.cod_diario where titulo ~ 'NMX|normas?\s*mexicanas?';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {   DB::connection($this->connection)->statement('DROP VIEW catalogonoms_view_nmx');
        Schema::connection($this->connection)->drop('dof_notas');
    }
}
