<?php
	use IMCO\CatalogoNOMsApi\NormaVigente;
?>
<?php

	$vigentes = NormaVigente::with(['menciones.nota'=>function ($query){
			$query->select('titulo', 'cod_nota', 'cod_diario')->with('diario');
		}])->has('menciones')->orderBy('clave')->paginate(50);

	print_r('<html>
		<head><title>Menciones de NMX en el DOF</title></head>
		<body>');

	print_r('<table>');
	print_r('<tr><td><a href="'.$vigentes->previousPageUrl().'">Anterior</a></td><td><a href="/catalogonoms/download/menciones-nmx">Descargar CSV completo</a></td><td><a href="'.$vigentes->nextPageUrl().'">Siguiente</a></td><!--td>Archivo</td--></tr>');
	print_r('<tr><td>Clave</td><td>Fecha de publicación</td><td>Título</td><!--td>Archivo</td--></tr>');
	foreach($vigentes as $norma){

		$menciones =$norma->menciones->sortBy(function($mencion, $key){
			return DateTime::createFromFormat ( 'Y-m-d' , $mencion->nota->diario->fecha);
		});
		foreach($menciones AS $mencion){
			print_r('<tr>');
			print_r("<td><a href=\"/catalogonoms/detalle-norma/$norma->clave\">".$norma->clave."</a></td>");
			print_r("<td>".$mencion->nota->diario->fecha."</td>");
			print_r("<td><a target=\"_blank\" href=\"http://anonymouse.org/cgi-bin/anon-www.cgi/http://dof.gob.mx/nota_detalle.php?codigo=".$mencion->cod_nota."&fecha=". explode('-', $mencion->nota->diario->fecha)[2] ."/". explode('-', $mencion->nota->diario->fecha)[1] ."/". explode('-', $mencion->nota->diario->fecha)[0] ."\">".$mencion->nota->titulo."</a></td>");
			print_r('</tr>');
		}
	}

	print_r('</table>');
	print_r('</body>
			</html>');
			
?>