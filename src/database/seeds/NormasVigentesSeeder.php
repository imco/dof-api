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

        $file = base_path('public/vendor/imco/catalogonoms-api') . '/NMX_Vigentes.xlsx';
        $input = str_getcsv(`xlsx2csv -d 'tab' -f %d-%m-%Y -m -e -n 'Matriz NMX' "$file" | tail -n+2`, "\n");

        foreach($input as $line){
            $data = str_getcsv($line, "\t");
            //var_dump($line);
            $formatedData = array(
                'clave'=>$data[0],
                'secretaria'=>$data[1],
                'titulo' =>$data[2],
                'archivo' =>$data[3],
                'fecha_publicacion' =>$data[4],
                'tipo' =>$data[5],
                'palabras_clave' =>array_map('trim', explode(";", $data[6])),
                'rama_economica' =>$data[7],
                'ctnn' => strlen($data[8])>0 ? array_map('trim', explode(";", $data[8])) : null,
                'onn' =>strlen($data[9])> 0 ? array_map('trim', explode(";", $data[9])) : null,
                'ctnn_slug' => strlen($data[8])>0 ? array_map([(new \Slug\Slugifier()), 'slugify'], array_map('trim', explode(";", $data[8])), array_fill(0,count(explode(";", $data[8])), '-'), array_fill(0,count(explode(";", $data[8])), True)): null,
                'onn_slug' => strlen($data[9])>0 ? array_map([(new \Slug\Slugifier()), 'slugify'], array_map('trim', explode(";", $data[9])), array_fill(0,count(explode(";", $data[8])), '-'), array_fill(0,count(explode(";", $data[9])), True)) : null,

                'rama_economica_slug' => (new \Slug\Slugifier())->slugify($data[7], '-', True)
            );

            var_dump($formatedData);

            NormaVigente::create($formatedData);
        }


/*        $count = 0;

        if (($gestor = fopen(base_path('database/data/nmx-activas.csv'), "r")) !== FALSE) {
            while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
                if ($count>0){
                    NormaVigente::create(array('clave' =>$datos[0], 'secretaria' =>$datos[1], 'titulo' =>$datos[2], 'archivo' =>$datos[3], 'fecha_publicacion' =>$datos[4], 'tipo' =>$datos[5], 'producto' =>$datos[6], 'rama_economica' =>$datos[7], 'ctnn' =>$datos[8], 'onn' =>$datos[9]));
                }
                $count++;
            }
            fclose($gestor);
        }
*/
    }
}
?>