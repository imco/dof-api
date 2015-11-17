<?php
	use IMCO\CatalogoNOMsApi\NormaVigente;
?>
<html>

<head><title>Normas no localizadas</title></head>

<body>

<?php

	$vigentes = NormaVigente::has('menciones', '<', 1)->get();

	print_r('<table>');
	print_r('<tr><td>Clave</td><td>Fecha de publicación</td><td>Título</td><td>Archivo</td></tr>');
	foreach($vigentes as $norma){
		print_r('<tr>');
		print_r("<td><a href=\"/catalogonoms/detalle-norma/$norma->clave\">".$norma->clave."</a></td>");
		print_r("<td><a target=\"_blank\" href=\"http://anonymouse.org/cgi-bin/anon-www.cgi/http://www.dof.gob.mx/index.php?year=". explode('-', $norma->fecha_publicacion)[0] ."&month=". explode('-', $norma->fecha_publicacion)[1] ."&day=". explode('-', $norma->fecha_publicacion)[2] ."\">".$norma->fecha_publicacion."</td>");
		print_r("<td>".$norma->titulo."</td>");
		print_r("<td> <a href=\"$norma->archivo\">".$norma->archivo."</a></td>");
		print_r('</tr>');
	}

	print_r('</table>');

?>

</body>
</html>