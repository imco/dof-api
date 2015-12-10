<?php namespace IMCO\CatalogoNOMsApi;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use IMCO\CatalogoNOMsApi;

class ClasificacionSeeder extends Seeder
{
    protected $connection = "catalogoNoms";
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menciones = MencionEnNota::with(['nota'=>function($query){
        $query->select('cod_nota', 'cod_diario', 'titulo');
        }])->whereNull('etiqueta')->get();


        print_r("Clasificando...\n");
        
        $trainingData = base_path('public/vendor/imco/catalogonoms-api/nmx-knowledgeBase.csv');

        $requestedFile = "/tmp/menciones-para-clasificar.csv";

        $file = fopen($requestedFile,"w");

        fputcsv($file,['id_mencion_en_nota', 'mencion', 'titulo']);

        foreach($menciones AS $mencion){
            fputcsv($file,[$mencion->id_mencion_en_nota, $mencion->mencion, $mencion->nota->titulo]);
        }
        fclose($file);
        
        $cmd = base_path("bin/clasificador.py")." -l --match=2,3 -t $trainingData -f $requestedFile";
        $result = shell_exec($cmd);

        if ($result){
            $result = explode("\n", $result);
            foreach($result AS $line){
                $line = trim($line);
                if (strlen($line)>1){
                    $classification = str_getcsv($line);
                    $mencion = MencionEnNota::find($classification[0]);
                    //$mencion->clave_normalizada =$classification[4];
                    $mencion->etiqueta = $classification[6];

                    $mencion->save();
                    //print_r("$classification[0]\t$classification[4]\t$classification[6]\n");
                }
            }
        }

        
    }
}
?>