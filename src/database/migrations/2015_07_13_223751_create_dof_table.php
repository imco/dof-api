<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDofTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    protected $connection = 'CatalogoNoms';

    public function up()
    {
        Schema::connection($this->connection)->create('dof', function (Blueprint $table) {
            $table->integer('cod_diario');
            $table->date('fecha');
            $table->string('edicion');
            $table->timestamps();
            $table->primary('cod_diario');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->drop('dof');
    }
}
