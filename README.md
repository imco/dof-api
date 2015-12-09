# Diario Oficial de la Federeción / Normas Mexicanas - API
## Descripción
Paquete de Laravel 5.
Utiliza el Webservice del DOF para copiar la información a una base de datos local y poder realizar operaciones más comodamente en las publicaciones del DOF. Así mismo aporta una extensión del API actual del DOF ofreciendo funcionalidades extras cómo clasificación de las Normas Mexicanas.

## Contenido
*[Instalación](#instalacion)
*[Agredecimientos](#agradecimientos)
*[Licencia](#licencia)

## Instalación
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
        DOFClientController::getYesterdayDof();
    })->twiceDaily(1, 22);

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

