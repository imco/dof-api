<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class ComparaCarrerasDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call('CampoAmplioTableSeeder');
        $this->call('CampoEspecificoTableSeeder');
        $this->call('CampoDetalladoTableSeeder');
        $this->call('CampoUnitarioTableSeeder');
        $this->call('EntidadTableSeeder');
        $this->call('EstadoTableSeeder');
        $this->call('NombreComunTableSeeder');
        $this->call('SectoresTableSeeder');
        $this->call('RankingExternoTableSeeder');
        $this->call('RankingExternoValoresTableSeeder');
		$this->call('UniversidadesTableSeeder');
		
		$this->call('UniversidadesMatriculaTableSeeder');
		$this->call('EstatidisticasTableSeeder');
		$this->call('CostoCarrerasTableSeeder');
		$this->call('ParticipacionSectorTableSeeder');
		$this->call('MaterializedViewsSeeder');
		
		$this->call('ActualizacionComparaCarreras2014Q4Seeder');
	
	}
}
