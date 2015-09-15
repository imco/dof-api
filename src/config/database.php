<?php

return [
	'connections' => [
		'CatalogoNoms' => [
			'driver'   => env('CNDB_DRIVER', env('DB_DRIVER','pgsql')),
			'host'     => env('CNDB_HOST', env('DB_HOST', 'localhost')),
			'database' => env('CNDB_DATABASE', env('DB_DATABASE','homestead')),
			'username' => env('CNDB_USERNAME', env('DB_USERNAME', 'homestead')),
			'password' => env('CNDB_PASSWORD', env('DB_PASSWORD', 'secret')),
			'port'	   => env('CNDB_PORT',env('DB_PORT', 5432)),
			'charset'  => 'utf8',
			'prefix'   => (env('CNDB_DATABASE', true) == true? 'catalogonoms_' : ''),
			'schema'   => 'public',
		]
	]
];
?>
