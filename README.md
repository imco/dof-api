# Diario Oficial de la Federación / Normas Mexicanas - API
## Descripción
Copia de la Base de Datos del Diario Oficial de la Federación. Paquete de Laravel 5.

### Modulos extras contenidos
* <a href="//noms.imco.org.mx">Catalogo de NOMs</a>
* <a href="//nmx.imco.org.mx">Catalogo de NMX</a>

### Nodos de acceso
Este API utiliza los siguientes Idendificadores de Recurso Uniforme (URL) para actualizar la Base de Datos:

* Para obtener las fechas que tienen publicación en un mes:
http://diariooficial.gob.mx/WS_getDiarioFecha.php?year=2012&month=08

* Conocer si hay archivo PDF disponible para una fecha específica:
http://diariooficial.gob.mx/WS_getDiarioPDF.php?day=29&month=08&year=2012&edicion=MAT

* Un modelo para obtener el sumario completo del día con un parámetro menos (en éste sólo se pasa el cod_diario):
http://diariooficial.gob.mx/BB_DetalleEdicion.php?cod_diario=253279

* Con éste podrán conocer a partir de una fecha específica, los códigos de diario de las 99 fechas anteriores, cada una identificada con fecha y edición:
http://diariooficial.gob.mx/BB_menuPrincipal.php?day=08&month=09&year=2014

#### Observaciones

Uno de los nodos para consultar las notas devuelve el resultado con una codificación de carácteres incorrecta. El otro las devuelve correctamente codificadas pero se han identificado *notas* que existen en el primer nodo más no en el segundo.

## Tabla de Contenido
*[Instalación](#instalacion)
*[Agredecimientos](#agradecimientos)
*[Licencia](#licencia)

## Instalación

### Base de datos inicial
Se puede descargar un respaldo de la Base de Datos (hasta )

### Clasificador
El clasificador es un script de Python3, el cuál utiliza la libreria NLTK http://www.nltk.org/ junto con el paquete "punkt"

### Actualización automática de la Base de Datos
Para mantener actualizada la Base de datos se hace uso de la funcionalidad de tareas programadas de Laravel, para ejecutarlo es necesario agregar las siguientes lineas en `app/Console/Kernel.php`

`use IMCO\CatalogoNOMsApi\DOFClientController;`

y dentro del método `schedule`:

    $schedule->call(function () {
        DOFClientController::fillNotes();
    })->everyFiveMinutes()->name('fillNotes')->withoutOverlapping();

    $schedule->call(function () {
        DOFClientController::getDofOnDate();
    })->twiceDaily(1, 22)->name('downloadDOF');

Agregar la siguiente entrada en Cron
`* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

### Base de datos

En el archivo `.env` de la aplicación de Laravel se ha de configurar el ambiente de la Base de Datos utilizando el prefijo `CN` en las variables de ambiente, ejemplo:

    CNDB_DRIVER=pgsql
    CNDB_HOST=localhost
    CNDB_DATABASE=homestead
    CNDB_USERNAME=homestead
    CNDB_PASSWORD=secret
    CNDB_PORT=5432

Si se omite el prefijo `CN`, el paquete intentará conectarse utilizando las variables default, i.e.:

    DB_DRIVER=pgsql
    DB_HOST=localhost
    DB_DATABASE=homestead
    DB_USERNAME=homestead
    DB_PASSWORD=secret
    DB_PORT=5432

En cuyo caso al nombre de las tablas generadas se les incluirá el prefijo `catalognoms_` para poder distinguirlas de cualquier otra tabla que se encuentre en la misma base de datos

## Agradecimientos
* [PROPEM (Programa de Política Económica para México)](https://propem.org/es/)

## Licencia
The MIT License (MIT)

Copyright (c) 2015 Instituto Mexicano para la Competitividad, A.C.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

