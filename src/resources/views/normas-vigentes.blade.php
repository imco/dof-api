<html>

<head><title>Estado del sistema</title></head>

<body>

<?php

	$vigentes = App::make('IMCO\CatalogoNOMsApi\NormaVigente')->limit(10)->get();
	print_r('<table>');
	foreach($vigentes as $norma){
		print_r('<tr>');

		print_r("<td><a href=\"/catalogonoms/detalle-norma/$norma->clave\">".$norma->clave."</a></td>");
		print_r('</tr>');
	}

	print_r('</table>');
?>
</body>
</html>