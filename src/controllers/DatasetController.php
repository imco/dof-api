<?php namespace IMCO\CatalogoNOMsApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class DatasetController extends Controller {
	protected $connection = 'catalogoNoms';
	

	public function getCSV($dataset){
		var_dump(DofNota::first()->get());
		return \Response::json();
	}

}
