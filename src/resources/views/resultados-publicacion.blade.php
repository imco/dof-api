<?php
	use IMCO\CatalogoNOMsApi\NormaVigente;
?>
<?php

	$vigentes = NormaVigente::with(['menciones'=>function ($query){
			$query->with(['nota'=>function ($query){
				$query->select('titulo', 'cod_nota', 'cod_diario')->with('diario');
			}]);
		}])->has('menciones')->orderBy('clave')->paginate(50);

	print_r('<html>
		<head><title>Menciones de NMX en el DOF</title></head>
		<body>');

	print_r('<table>');
	print_r('<tr><td><a href="'.$vigentes->previousPageUrl().'">Anterior</a></td><td><a href="/catalogonoms/download/publicacion-nmx">Descargar CSV completo</a></td><td><a href="'.$vigentes->nextPageUrl().'">Siguiente</a></td><!--td>Archivo</td--></tr>');
	print_r('<tr><td>Clave</td><td>CTNN</td><td>ONN</td><td>Fecha de entrada en vigor</td><td>Primera publicacion</td><td>Diferencia</td><td>Título primera publicacion</td></tr>');
	foreach($vigentes as $norma){

		$menciones =$norma->menciones->sortBy(function($mencion, $key){
			return DateTime::createFromFormat ( 'Y-m-d' , $mencion->nota->diario->fecha);
		});
		foreach($menciones AS $mencion){
			print_r('<tr>');
			print_r("<td><a href=\"/catalogonoms/detalle-norma/$norma->clave\">".$norma->clave."</a></td>");
			print_r("<td>".$norma->ctnn."</td>");
			print_r("<td>".$norma->onn."</td>");
			print_r("<td><a target=\"_blank\" href=\"http://anonymouse.org/cgi-bin/anon-www.cgi/http://www.dof.gob.mx/index.php?year=". explode('-', $norma->fecha_publicacion)[0] ."&month=". explode('-', $norma->fecha_publicacion)[1] ."&day=". explode('-', $norma->fecha_publicacion)[2] ."\">".$norma->fecha_publicacion."</td>");

			print_r("<td>".$mencion->nota->diario->fecha."</td>");
			//$days = ;

			//var_dump($days);
			print_r("<td>". date_diff(DateTime::createFromFormat ( 'Y-m-d' , $mencion->nota->diario->fecha),DateTime::createFromFormat ( 'Y-m-d' , $norma->fecha_publicacion))->format("%R%a")."</td>");

			print_r("<td><a target=\"_blank\" href=\"http://anonymouse.org/cgi-bin/anon-www.cgi/http://dof.gob.mx/nota_detalle.php?codigo=".$mencion->cod_nota."&fecha=". explode('-', $mencion->nota->diario->fecha)[2] ."/". explode('-', $mencion->nota->diario->fecha)[1] ."/". explode('-', $mencion->nota->diario->fecha)[0] ."\">".$mencion->nota->titulo."</a></td>");
			print_r('</tr>');
			break;
		}
	}

	print_r('</table>');
	print_r('</body>
			</html>');
			
?>