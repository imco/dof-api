<?php namespace IMCO\CatalogoNOMsApi;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use IMCO\CatalogoNOMsApi;

class NormasVigentesSeeder extends Seeder
{
    protected $connection = "catalogoNoms";
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        $count = 0;

        if (($gestor = fopen(base_path('database/data/nmx-activas.csv'), "r")) !== FALSE) {
            while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
                if ($count>0){
                    NormaVigente::create(array('clave' =>$datos[0], 'secretaria' =>$datos[1], 'titulo' =>$datos[2], 'archivo' =>$datos[3], 'fecha_publicacion' =>$datos[4], 'tipo' =>$datos[5], 'producto' =>$datos[6], 'rama_economica' =>$datos[7], 'ctnn' =>$datos[8], 'onn' =>$datos[9]));
                }
                $count++;
            }
            fclose($gestor);
        }

    }
}
?>