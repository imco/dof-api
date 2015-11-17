<?php
	use IMCO\CatalogoNOMsApi\NormaVigente;
?>
<?php

	if (\Input::get('download')){
		$requestedFile = '/tmp/mencionesNmx.csv';

		if (!file_exists($requestedFile)){
			$vigentes = NormaVigente::with(['menciones.nota.diario', 'menciones.nota'])->has('menciones')->get();
		
			$file = fopen($requestedFile,"w");

			fputcsv($file,['clave', 'fecha', 'cod_nota', 'titulo']);
			foreach($vigentes as $norma){
				foreach($norma->menciones AS $mencion){
					fputcsv($file,[$norma->clave, $mencion->nota->diario->fecha, $mencion->cod_nota, $mencion->nota->titulo]);
				}
			}

			fclose($file);

			return \Response::download($requestedFile);
		}else{
			return \Response::download($requestedFile);
		}
	}else{

		$vigentes = NormaVigente::with(['menciones.nota.diario', 'menciones.nota'])->has('menciones')->paginate(50);

		print_r('<html>
			<head><title>Menciones de NMX en el DOF</title></head>
			<body>');

		print_r('<table>');
				print_r('<tr><td><a href="'.$vigentes->previousPageUrl().'">Anterior</a></td><td><a href="/catalogonoms/resultados/menciones?download=1">Descargar CSV completo</a></td><td><a href="'.$vigentes->nextPageUrl().'">Siguiente</a></td><!--td>Archivo</td--></tr>');
		print_r('<tr><td>Clave</td><td>Fecha de publicación</td><td>Título</td><!--td>Archivo</td--></tr>');
		foreach($vigentes as $norma){
			foreach($norma->menciones AS $mencion){
				print_r('<tr>');
				print_r("<td><a href=\"/catalogonoms/detalle-norma/$norma->clave\">".$norma->clave."</a></td>");
				print_r("<td><a target=\"_blank\" href=\"http://anonymouse.org/cgi-bin/anon-www.cgi/http://www.dof.gob.mx/index.php?year=". explode('-', $norma->fecha_publicacion)[0] ."&month=". explode('-', $norma->fecha_publicacion)[1] ."&day=". explode('-', $norma->fecha_publicacion)[2] ."\">".$norma->fecha_publicacion."</td>");
				print_r("<td><a target=\"_blank\" href=\"http://anonymouse.org/cgi-bin/anon-www.cgi/http://dof.gob.mx/nota_detalle.php?codigo=".$mencion->cod_nota."&fecha=". explode('-', $mencion->nota->diario->fecha)[2] ."/". explode('-', $mencion->nota->diario->fecha)[1] ."/". explode('-', $mencion->nota->diario->fecha)[0] ."\">".$mencion->nota->titulo."</a></td>");
				//print_r("<td> <a href=\"$norma->archivo\">".$norma->archivo."</a></td>");
				print_r('</tr>');
			}
		}

		print_r('</table>');
		print_r('</body>
				</html>');
				
	}
?>