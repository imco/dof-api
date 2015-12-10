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


//        $pattern = '((d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}|[^\s>]*(nmx))(([^\s])?[\w\d/]+)+(?=(,\s|\.\s|\s|<|\.?$$)))';
        $pattern = '((d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}|[^\s>]*(nmx|nom(?!\w|\d|&\wacute;)))(([^\s])?[\w\d\/]+)+(?=(,\s|\)|\.\s|\s|<|\.?$)))';

        $menciones = DofNota::contains($pattern);

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

	}
}
