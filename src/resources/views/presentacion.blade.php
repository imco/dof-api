<?php 
	use IMCO\CatalogoNOMsApi\NormaVigente;
	use IMCO\CatalogoNOMsApi\DofNota;
	use IMCO\CatalogoNOMsApi\MencionEnNota;
?>
<html>

<head><title>Estado del sistema</title></head>

<body>

Se han descargado <?php echo App::make('IMCO\CatalogoNOMsApi\NormaVigente')->count(); ?> normas vigentes utilizando el <a href="http://www.economia-nmx.gob.mx/normasmx/index.nmx">Catalogo de Normas Mexicanas</a> cómo base.

<p>Errores detectados:</p>
<ul>
	<li><a href="/catalogonoms/error/fecha-publicacion"><?php echo NormaVigente::conFechaPublicacionIncorrecta()->count();?> Normas vigentes</a> con una fecha de publicación en la que no existe publicación del Diario Oficial de la Federación</li>
	<li><a href="/catalogonoms/error/no-localizadas"><?php echo NormaVigente::has('menciones', '<', 1)->count();?> Normas vigentes</a> que con el método actuál no han podido ser localizadas en publicaciones del DOF</li>
</ul>

<?php echo DofNota::count();?>

<?php echo MencionEnNota::count();?>

<?php echo MencionEnNota::select('clave')->distinct('clave')->count('clave');?>


<p>Resultados:</p>
<ul>
	<li>De las <?php echo NormaVigente::count();?> normas vigentes se han localizado <a href="/catalogonoms/resultados/menciones"><?php echo NormaVigente::has('menciones', '>=', 1)->count();?> con menciones en el DOF</a></li>
	<li><a href="/catalogonoms/resultados/proyectos"><?php echo NormaVigente::whereHas('menciones', function ($query){
		$query->where('etiqueta', 'Proyecto');})->count(); ?> Normas con proyecto</a></li>

	
</ul>





</body>
</html>