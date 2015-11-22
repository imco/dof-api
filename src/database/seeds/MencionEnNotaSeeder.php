<?php namespace IMCO\CatalogoNOMsApi;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use IMCO\CatalogoNOMsApi;
//use IMCO\CatalogoNOMsApi\DofNota;

use Response;
use DB;

class MencionEnNotaSeeder extends Seeder
{

    protected $connection = "catalogoNoms";
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        $pattern = '((d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}|[^\s>]*(nmx)))(([^\s])?[\w\d/]+)+(?=(,\s|\.\s|\s|<|\.?$$)))';
//        $pattern = '((d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}|[^\s>]*(nmx|nom(?!\w|\d|&\wacute;)))(([^\s])?[\w\d/]+)+(?=(,\s|\.\s|\s|<|\.?$$)))';

        $menciones = DofNota::bodyContains($pattern);

        print_r($menciones->toSql() . "\n");
        foreach($menciones->get() AS $mencion){
            if (strpos($mencion->titulo, $mencion->mencion)==False){
                $mencion->ubicacion= 'Contenido';
            }else{
                $mencion->ubicacion= 'Título';
            }

            if (!in_array($mencion->mencion, ['dgn1.html', 'dgnon','dgning', 'dgn.karla@economia.gob.mx', 'nmx.gob.mx/normasmx/index.nmx', 'nmx.gob.mx/normasmx/', 'nmx@prodigy.net.mx', "d;'>gnidad", 'nmx.carbonoforestal@semarnat.gob.mx', "d;'>gnar"])){
                print_r("Match:\t$mencion->mencion\t$mencion->ubicacion\t$mencion->cod_nota\n");
                MencionEnNota::create(array('cod_nota'=>$mencion->cod_nota, 'mencion'=> $mencion->mencion, 'ubicacion'=>$mencion->ubicacion));
            }
        }

/*
        $errorEnFecha = NormaVigente::conFechaPublicacionIncorrecta()->get();
        foreach ($errorEnFecha AS $norma){
            print_r("Buscando $norma->clave\t...\n");
            $menciones = DofNota::bodyContains($norma->clave_patron());

            print_r($menciones->toSql() . "\n");

            foreach($menciones->get() AS $mencion){
                print_r("Match:\t$mencion->mencion\t$mencion->ubicacion\t$mencion->cod_nota\n");
                MencionEnNota::create(array('cod_nota'=>$mencion->cod_nota, 'mencion'=> $mencion->mencion, 'ubicacion'=>$mencion->ubicacion));
            }
        }

*/

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
