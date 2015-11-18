<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNormasAliasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    protected $connection = 'catalogoNoms';

    public function up()
    {
        Schema::connection($this->connection)->create('normas_alias', function (Blueprint $table) {
            $table->increments('id_normas_alias');
            $table->string('clave');
            $table->string('alias');
            //$table->string('clave_normalizada')->nullable();
            $table->timestamps();

            //$table->index()
        });

        DB::connection($this->connection)->statement("create view catalogonoms_view_normas_alias as SELECT DISTINCT * FROM (select clave, alias from catalogonoms_normas_alias UNION SELECT clave, clave as alias from catalogonoms_normas_vigentes) t1;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection($this->connection)->statement('DROP VIEW catalogonoms_view_normas_alias');
        Schema::connection($this->connection)->drop('normas_alias');
    }
}
