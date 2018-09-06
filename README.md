# Diario Oficial de la Federación / Normas Mexicanas - API

Paquete para [Laravel5](https://laravel.com/docs/5.7#installing-laravel).

Copia de la Base de Datos del Diario Oficial de la Federación. Paquete de Laravel5. Este paquete descarga el Diario Oficial de la Federación periodicamente y permite  las publicacicones de Normas Oficiales Mexicanas (NOM) y Normas Mexicanas (NMX) y su estado actual.

## Tabla de Contenido
* [Instalación](#instalacion)
* [Agredecimientos](#agradecimientos)
* [Licencia](#licencia)

## Dependencias
* PostgreSQL 10
  * PLPython3u
  ```
  sudo apt-get install postgresql-plpython-10
  ```
* PHP 7.2
* [csvquery](https://pypi.org/project/csvquerytool/) `pip3 install csvquerytool`
* Phyton3
   * [NLTK](https://www.nltk.org/) `pip3 install -U nltk`
* Laravel5
* http://diariooficial.gob.mx/WS_getDiarioFecha.php
* http://diariooficial.gob.mx/BB_menuPrincipal.php
* http://diariooficial.gob.mx/WS_getDiarioPDF.php
* http://diariooficial.gob.mx/BB_DetalleEdicion.php
* http://diariooficial.gob.mx/WS_getDiarioFull.php
* http://diariooficial.gob.mx/nota_detalle_popup.php

ERROR:  language "plpython3u" does not exist
```


```

ERROR:  role "admin_catalogonoms" does not exist
```
create role admin_catalogonoms with password 'secret';
create database ${MYAPI} with owner admin_catalogonoms;
```

```
CREATE EXTENSION plpython3u;
```


### Modulos extras contenidos
* <a target="\_blank" href="http://noms.imco.org.mx">Catalogo de NOMs</a>
* <a target="\_blank" href="http://nmx.imco.org.mx">Catalogo de NMX</a>

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

## Instalación

Este paquete se instala en una aplicación de Laravel. Para instalar laravel y crear una nueva aplicaicón consulta la [documentación de Laravel5](https://laravel.com/docs/5.6/installation)

#### 1.A Default

Esta distribución cuanta con un Makefile para automatizar al instalación. Creara una nueva aplicación *apiv3* e el directorio padre

```
make
```

#### 1.B Desarrollo

Si se desea instalar el paquete con fines de desarrollo, para visualizar los cambios en cuanto se realicen, es posible clonar el repositorio e indicarle a composer desde dónde ha de leer las clases, el el archivo `composer.json` la información para cargar las clases del paquete:

```
...
"autoload": {
  ...
  "psr-4": {
    ...
    "IMCO\\CatalogoNOMsApi\\" :"/path/to/catalogonoms-api/src/"
    ...
  }
  ...
}
...
```
#### 1.C Producción/Stagging

En caso de que le paquete se vaya a instalar en un entorno de desarrollo o producción, utilizando la versión que esté publicada, es necesario agregar el repositorio correspondiente a la configuración de dependencias:


```
...
"repositories": [
    ...
    {
        "type": "git",
        "url": "https://github.com/imco/dof-api.git"
    }
    ...
],
...
"require-dev": {
    ...
    "imco/catalogonoms-api": "*",
    ...
},
...
```

Alternativamente se puede instalar el paquete ejecutando mediante composer:

```
cd api
../composer.phar config repositories.catalogonoms-api git ssh://dev.imco.org.mx/var/git/catalogonoms-api.git
../composer.phar require imco/catalogonoms-api:dev-nmx
```


### 2. Carga el Service Provider

En el archivo `config/app.php` agregar el Service Provider del paquete.


```
'providers' => [
      ...
      /*
       * Package Service Providers...
       */
      ...

      IMCO\CatalogoNOMsApi\CatalogoNOMsApiServiceProvider::class,
      ...
  ]
```

### 3. Publicar los assets del paquete

Publicar los documentos de los proveedores y limpiar el cache de configuración
```
php artisan vendor:publish
../composher.phar config:cache
```

### 4. Configura las variables de ambiente

En el archivo `.env` de la aplicación de Laravel se ha de configurar el ambiente de la Base de Datos utilizando el prefijo `CN` en las variables de ambiente, de no existir el archivo puedes utilizar `.env.example` cómo guia, ejemplo:
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

### 5. Ejecuta las migraciones

`php artisan migrate`

### 6. Inicializar Base de Datos
`php artisan db:seed --class="IMCO\CatalogoNOMsApi\CatalogoNOMsDatabaseSeeder"`


### Base de datos inicial
Se puede descargar un respaldo de la Base de Datos (hasta )

### Clasificador
El clasificador es un script de Python3, el cuál utiliza la libreria NLTK http://www.nltk.org/ junto con el paquete "punkt"


>> # Mi Mismo: ¿es el único script de python utilizado?


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

### Analytics

Para rastrear el uso del API es necesario agregar el código de rastreo al archivo de variables de ambiente `.env` y registrar el Middleware en nuestra aplicación.

    TRACKINGCODE="UA-XXXXX-1"



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

