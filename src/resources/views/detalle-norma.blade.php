<html>

<head><title>Detalle de la norma <?php echo $clave ?></title></head>

<body>

<?php

	$norma = IMCO\CatalogoNOMsApi\NormaVigente::with(['menciones.nota'=>function ($query){
			$query->select('titulo', 'cod_nota', 'cod_diario')->with('diario');
		}])->where('clave', $clave)->first();
	print_r('<table>');
	print_r('<tr>');
	print_r("<td>Clave: </td>");
	print_r("<td>".$norma->clave."</td>");
	print_r('</tr>');

	print_r('<tr>');
	print_r("<td>Secretaría: </td>");
	print_r("<td>".$norma->secretaria."</td>");
	print_r('</tr>');

	print_r('<tr>');
	print_r("<td>Archivo: </td>");
	print_r("<td> <a href=\"$norma->archivo\">".$norma->archivo."</a></td>");
	print_r('</tr>');

	print_r('<tr>');
	print_r("<td>Fecha de publicación: </td>");
	print_r("<td><a target=\"_blank\" href=\"http://anonymouse.org/cgi-bin/anon-www.cgi/http://www.dof.gob.mx/index.php?year=". explode('-', $norma->fecha_publicacion)[0] ."&month=". explode('-', $norma->fecha_publicacion)[1] ."&day=". explode('-', $norma->fecha_publicacion)[2] ."\">".$norma->fecha_publicacion."</td>");
	print_r('</tr>');

	print_r('<tr>');
	print_r("<td>Tipo: </td>");
	print_r("<td>".$norma->tipo."</td>");
	print_r('</tr>');

	print_r('<tr>');
	print_r("<td>Producto: </td>");
	print_r("<td>".$norma->producto."</td>");
	print_r('</tr>');


	print_r('<tr>');
	print_r("<td>Rama económica: </td>");
	print_r("<td>".$norma->rama_economica."</td>");
	print_r('</tr>');

	print_r('<tr>');
	print_r("<td>CTNN: </td>");
	print_r("<td>".$norma->ctnn."</td>");
	print_r('</tr>');

	print_r('<tr>');
	print_r("<td>ONN: </td>");
	print_r("<td>".$norma->onn."</td>");
	print_r('</tr>');
	print_r('</table>');


	$menciones =$norma->menciones->sortBy(function($mencion, $key){
		return DateTime::createFromFormat ( 'Y-m-d' , $mencion->nota->diario->fecha);
	});
	
	print_r('<br/><br/>');

	print_r('<table>');
	print_r('<tr><td>Fecha de publicación</td><td>Título</td><td>Tipo</td></tr>');
	foreach($menciones as $mencion){
		print_r('<tr>');
		print_r("<td>".$mencion->nota->diario->fecha."</td>");
		print_r("<td><a target=\"_blank\" href=\"http://anonymouse.org/cgi-bin/anon-www.cgi/http://dof.gob.mx/nota_detalle.php?codigo=".$mencion->cod_nota."&fecha=". explode('-', $mencion->nota->diario->fecha)[2] ."/". explode('-', $mencion->nota->diario->fecha)[1] ."/". explode('-', $mencion->nota->diario->fecha)[0] ."\">".$mencion->nota->titulo."</a></td>");
		print_r("<td>".$mencion->etiqueta."</td>");
		print_r('</tr>');
	}
	print_r('</table>');

?>
</body>
</html>