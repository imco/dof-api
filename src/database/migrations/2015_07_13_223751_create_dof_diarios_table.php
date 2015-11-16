<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDofDiariosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    protected $connection = 'catalogoNoms';

    public function up()
    {
        Schema::connection($this->connection)->create('dof_diarios', function (Blueprint $table) {
            $table->integer('cod_diario');
            $table->date('fecha');
            $table->string('edicion');
            $table->string('availablePdf')->nullable();
            $table->boolean('invalid')->nullable()->default(false);
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
        Schema::connection($this->connection)->drop('dof_diarios');
    }
}
