<html>

<head><title>Detalle de la norma <?php echo $clave ?></title></head>

<body>

<?php

	$norma = App::make('IMCO\CatalogoNOMsApi\NormaVigente')->where('clave', $clave)->first();
	print_r('<table>');
	//foreach($vigentes as $norma){
		print_r('<tr>');
		print_r("<td>Clave: </td>");
		print_r("<td>".$norma->clave."</td>");
		print_r('</tr>');

		print_r('<tr>');
		print_r("<td>Patrón de la clave: </td>");
		print_r("<td>".$norma->clave_patron()."</td>");
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
		print_r("<td>".$norma->fecha_publicacion."</td>");
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
	//}

	print_r('</table>');
?>
</body>
</html>