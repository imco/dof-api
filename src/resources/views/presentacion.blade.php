<?php 
	use IMCO\CatalogoNOMsApi\NormaVigente;
	use IMCO\CatalogoNOMsApi\DofNota;
	use IMCO\CatalogoNOMsApi\MencionEnNota;
	//use Cache;
?>
<html>

<head><title>Estado del sistema</title></head>

<body>

Se han descargado <?php echo App::make('IMCO\CatalogoNOMsApi\NormaVigente')->count(); ?> normas vigentes utilizando el <a href="http://www.economia-nmx.gob.mx/normasmx/index.nmx">Catalogo de Normas Mexicanas</a> cómo base.

<p>Errores detectados:</p>
<ul>
	<li><a href="/catalogonoms/error/fecha-publicacion"><?php echo \Cache::remember('fechaErronea', 200, function() { return NormaVigente::conFechaPublicacionIncorrecta()->count();});?> Normas vigentes</a> con una fecha de publicación en la que no existe publicación del Diario Oficial de la Federación</li>
	<li><a href="/catalogonoms/error/no-localizadas"><?php echo \Cache::remember('noLocalizada', 200, function() { return NormaVigente::has('menciones', '<', 1)->count();});?> Normas vigentes</a> que con el método actuál no han podido ser localizadas en publicaciones del DOF</li>
</ul>

Publicaciones del DOF <?php echo \Cache::remember('cuentaDOF', 200, function() { return DofNota::count();});?>
<br/>
Menciones de claves <?php echo \Cache::remember('cuentaMenciones', 200, function() { return MencionEnNota::count();});?>
<br/>
Menciones de claves únicas <?php echo \Cache::remember('cuentaMencionesUnicas', 200, function() { return MencionEnNota::select('mencion')->distinct('mencion')->count('mencion');});?>
<br/>

<p>Resultados:</p>
<ul>
	<li>De las <?php echo \Cache::remember('cuentaNormasVigentes', 200, function() { return NormaVigente::count();});?> normas vigentes se han localizado <a href="/catalogonoms/resultados/menciones"><?php echo \Cache::remember('cuentaNormasVigentesLocalizadas', 200, function() { return NormaVigente::has('menciones', '>=', 1)->count();});?> con menciones en el DOF</a></li>
	<li><a href="/catalogonoms/resultados/proyectos"><?php echo \Cache::remember('cuentaNormasConProyecto', 200, function() { return NormaVigente::whereHas('menciones', function ($query){
		$query->where('etiqueta', 'Proyecto');})->count();}); ?> Normas con proyecto</a></li>

	
</ul>





</body>
</html>