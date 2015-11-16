<?php
	use IMCO\CatalogoNOMsApi\NormaVigente;
?>
<html>

<head><title>Estado del sistema</title></head>

<body>

Se han descargado <?php echo App::make('IMCO\CatalogoNOMsApi\NormaVigente')->count(); ?> normas vigentes utilizando el <a href="http://www.economia-nmx.gob.mx/normasmx/index.nmx">Catalogo de Normas Mexicanas</a> cómo base.

<p>Errores detectados:</p>
<ul>
	<li><a href="/catalogonoms/error/fecha-publicacion"><?php echo NormaVigente::conFechaPublicacionIncorrecta()->count();?> Normas vigentes</a> con una fecha de publicación en la que no existe publicación del Diario Oficial de la Federación</li>
</ul>
</body>
</html>