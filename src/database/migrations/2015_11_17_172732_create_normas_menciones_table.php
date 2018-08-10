<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNormasMencionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    protected $connection = 'catalogoNoms';

    public function up()
    {
        Schema::connection($this->connection)->create('normas_menciones', function (Blueprint $table) {
            $table->increments('id_norma_mencion');
            $table->string('clave');
            $table->string('mencion');
            //$table->string('clave_normalizada')->nullable();
            $table->timestamps();

            //$table->index()
        });

        // DB::connection($this->connection)->statement("create view catalogonoms_view_normas_menciones as SELECT DISTINCT * FROM (select clave, mencion from catalogonoms_normas_menciones UNION SELECT clave, clave as mencion from catalogonoms_normas_vigentes) t1;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // DB::connection($this->connection)->statement('DROP VIEW catalogonoms_view_normas_menciones');
        Schema::connection($this->connection)->drop('normas_menciones');
    }
}
