<?php
	use IMCO\CatalogoNOMsApi\NormaVigente;
?>
<html>
<head><title>Proyectos de normas</title></head>
<body>
<?php

	$vigentes = NormaVigente::with(['menciones' =>function ($query){
		$query->with(['nota'=>function($query){
			$query->with('diario')->select('cod_nota', 'cod_diario', 'titulo');
		}])->where('etiqueta', 'Proyecto');
	}])->orderBy('clave')->get();


	//var_dump($vigentes);
	print_r('<table>');
	//print_r('<tr><td><a href="'.$vigentes->previousPageUrl().'">Anterior</a></td><td><a href="/catalogonoms/download/menciones-nmx">Descargar CSV completo</a></td><td><a href="'.$vigentes->nextPageUrl().'">Siguiente</a></td><!--td>Archivo</td--></tr>');
	print_r('<tr><td>Clave</td><td>Fecha de publicación</td><td>Título</td><!--td>Archivo</td--></tr>');
	foreach($vigentes as $norma){

		//var_dump($norma);
		//$norma->load('menciones.nota.diario');
		$menciones =$norma->menciones->sortBy(function($mencion, $key){
			return DateTime::createFromFormat ( 'Y-m-d' , $mencion->nota->diario->fecha);
		});

		//var_dump($menciones);
		foreach($menciones AS $mencion){
			print_r('<tr>');
			print_r("<td><a href=\"/catalogonoms/detalle-norma/$norma->clave\">".$norma->clave."</a></td>");
			print_r("<td>".$mencion->nota->diario->fecha."</td>");
			print_r("<td><a target=\"_blank\" href=\"http://anonymouse.org/cgi-bin/anon-www.cgi/http://dof.gob.mx/nota_detalle.php?codigo=".$mencion->cod_nota."&fecha=". explode('-', $mencion->nota->diario->fecha)[2] ."/". explode('-', $mencion->nota->diario->fecha)[1] ."/". explode('-', $mencion->nota->diario->fecha)[0] ."\">".$mencion->nota->titulo."</a></td>");
			print_r('</tr>');
		}
	}

	print_r('</table>');
?>

</body>
</html>