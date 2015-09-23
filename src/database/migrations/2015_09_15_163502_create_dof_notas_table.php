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
    protected $connection = 'CatalogoNoms';

    public function up()
    {
        Schema::connection($this->connection)->create('dof_notas', function (Blueprint $table) {
            $table->integer('cod_nota');
            $table->integer('cod_diario');
            $table->text('titulo')->nullable();
            $table->integer('seccion');
            $table->string('organismo')->nullable();
            $table->string('secretaria');
            $table->integer('pagina');
            $table->text('contenido')->nullable();
            $table->timestamps();
            $table->primary('cod_nota');
            $table->foreign('cod_diario')->references('cod_diario')->on('dof_diarios');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->drop('dof_notas');
    }
}
