<?php
App::uses('Component', 'Controller');


class SearchComponent extends Component {

/**
 * prefijo de para enviar y recibir las variables
 *
 * @_prefix string
 */
	private $_prefix = '__';

/**
 * Configuraciones generales del Componente
 * 
 * - 'pass'	 parametros de las variables
 * 
 * @var array
 */
	private $configs = array('pass' => array());


/**
 * simbolos de tokens a utilizar para realizar las comparaciones
 *
 * @var array
 */
	private $tokens = array( 		// Importante mantener orden
		0 => array('nombre'=>'esp', 'simbol'=>' ', 'pos'=>'mid'),
		1 => array('nombre'=>'con', 'simbol'=>'+', 'pos'=>'mid'),

		2 => array('nombre'=>'dif', 'simbol'=>'<>', 'pos'=>'ini'),
		3 => array('nombre'=>'dif', 'simbol'=>'!=', 'pos'=>'ini'),

		4 => array('nombre'=>'maigual', 'simbol'=>'>=', 'pos'=>'ini'),
		5 => array('nombre'=>'meigual', 'simbol'=>'<=', 'pos'=>'ini'),

		6 => array('nombre'=>'ma', 'simbol'=>'>', 'pos'=>'ini'),
		7 => array('nombre'=>'me', 'simbol'=>'<', 'pos'=>'ini'),

		8 => array('nombre'=>'igual', 'simbol'=>'==', 'pos'=>'ini'),
		9 => array('nombre'=>'igual', 'simbol'=>'=', 'pos'=>'ini'),

		10 => array('nombre'=>'like', 'simbol'=>'%', 'pos'=>'ini'),
		11 => array('nombre'=>'ast', 'simbol'=>'*', 'pos'=>'end'),
	);


/**
 * Search settings.
 *
 * @var array
 */
	public $settings = array(
			'helper'=>true,
		);


/**
 * Constructor
 *
 * @param ComponentCollection $collection
 * @param array $settings
 * @return void
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$settings = array_merge($this->settings, (array)$settings);
		$this->Controller = $collection->getController();

		if(isset($settings['helper']) and ($settings['helper'] !== false))
			$this->Controller->helpers['Search'] = array('prefix'=>$this->_prefix); // <-- Cargar helper Search

		$this->setRequest($this->configs);
		parent::__construct($collection, $settings);
	}


/**
 * getConditions
 *
 * @param array $data arreglo desde donde vienen los datos, si se deja null se asume $this->Controller->request->query
 * @return $conditions arrello con las condiciones de busqueda para el metodo $this->Model->find
 */
	public function getConditions($data = null, $modelClass = null) {
		$this->setRequest('pass',$this->cleanData($this->getData($data),false));
		$data = $this->cleanData($this->getData($data),true);
		$conditions = array();
		foreach ($data as $values) {
			$concatCondition = ( count($values) <=1 ? 'AND' : 'OR');
			foreach ($values as $key => $value) {
				if($value !== ''){
					$conditions[$concatCondition][] = $this->_buildingCondition($this->_getToken($key,$value),$modelClass);
					//$conditions[$this->getComparationClave($key, $value)] = $this->getComparationValor($key,$value);
				}
			}
		}
		return $conditions;
	}


	private function _getToken($key, $values){

		$valores = null;
		$values = trim($values);

		$out['field'] = $key;

		$valores_separados = $this->_getValues($values);


		$out['value'] = $values;


		foreach ($valores_separados as $orValues) {
			$aux2 = null;
			foreach ($orValues as $andValues) {
				$aux = null;
						foreach ($this->tokens as $token) {
						$aux['posToken'] = strpos($andValues,$token['simbol']);

						$aux['value'] = $andValues;

						if($aux['posToken'] !== false){
							$aux['nombre'] = $token['nombre'];
							$aux['simbol'] = $token['simbol'];

							if($aux['posToken'] === 0){
								$aux['position'] = 'ini';
							}elseif($aux['posToken'] === (strlen($andValues)-1)){
								$aux['position'] = 'end';
							}else{
								$aux['position'] = 'mid';
							}
							break;
						}else{
							if( strpos($out['field'], '_id')){
								$aux['posToken'] = true;
								$aux['nombre'] = 'id';
							}
						}
					}
					$aux2['and'][] = $aux;
			}
			$out['values']['or'][] = $aux2;
		}
		return $out;
	}

	private function _buildingCondition($datos,$modelClass=null){
		$out=null;
		$outF=null;
		foreach ($datos['values']['or'] as $orValues) {
			$aux=null;
			$auxF=null;
			foreach ($orValues['and'] as $andValues) {
				$andValues['field'] = $datos['field'];
				$aux[] = $this->buildDataValue($andValues,$modelClass);
			}

			if(count($aux)>1){ $auxF['AND'] = $aux; }else{ $auxF = $aux[0]; }

			$out[] = $auxF;
		}


		if(count($out)>1){ $outF['OR'] = $out; }else{ $outF = $out[0]; }

		return $outF;
	}


	private function buildDataValue($datos,$modelClass=null){
		$out['pre'] = '';
		$out['pos'] = '';

		$modelClass = ( $modelClass == null ? $this->Controller->modelClass : $modelClass );


		if($datos['posToken'] === false){
			$out['pre'] =	$modelClass.'.'.$datos['field'].' LIKE';
			$out['pos'] = 	'%'.$datos['value'].'%';
		}else{
			switch ($datos['nombre']) {
				case 'igual': 	$out['pre'] = $modelClass.'.'.$datos['field']; 		break;
				case 'dif': 	$out['pre'] = $modelClass.'.'.$datos['field'].' !='; 	break;
				case 'maigual': $out['pre'] = $modelClass.'.'.$datos['field'].' >=';	break;
				case 'meigual': $out['pre'] = $modelClass.'.'.$datos['field'].' <='; 	break;
				case 'ma': 		$out['pre'] = $modelClass.'.'.$datos['field'].' >'; 	break;
				case 'me': 		$out['pre'] = $modelClass.'.'.$datos['field'].' <'; 	break;

				case 'id': 		$out['pre'] = $modelClass.'.'.$datos['field']; 	break;

				default:		$out['pre'] = $modelClass.'.'.$datos['field'].' LIKE'; break;
			}

			switch ($datos['nombre']){
				case 'like':	$out['pos'] = ltrim($datos['value']);	break;
				case 'ast':		$out['pos'] = str_replace('*', '%', ltrim($datos['value']));	break;
				case 'id':	$out['pos'] = ltrim($datos['value']);	break;

				default:		$out['pos'] = str_replace($datos['simbol'], '', ltrim($datos['value']));	break;
			}
		}

		return array($out['pre']=>$out['pos']);
	}

	private function _getValues($text){
		$out=null;
		$parts_or = explode(' ',trim($text));
		foreach ($parts_or as $part_text) {
			$parts_and = explode('+',trim($part_text));
			$aux=null;
			foreach ($parts_and as $asd) {
				if($asd != ''){
					$aux[] = $asd;
				}
			}
			if($aux){
				$out[] = $aux;
			}
		}
		//debug($out);
		return $out;
	}

/*
	private function _multiText($field, $text, $sep=' ',$modelClass=null){
		$porc = explode($sep,trim($text));
		foreach ($porc as $value) {

			//debug($value);
			//debug($field);

			$out[] = array($this->Controller->modelClass.'.'.$field.' LIKE' => '%'.$value.'%');   // INGRESAR RECURSIVIDAD AQUI ***************************

		}

		return $out;
	} */





/**
 * getData
 *
 * @param array $data
 * @return array $data
 */
	private function getData($data = null){
		return ( $data ? $data : $this->Controller->request->query );
	}


/**
 * setRequest
 * Guardar datos en Controller->request
 *
 * @param array $data
 * @return array $out datos formateados
 */
	private function cleanData($data, $clearPrefix = false){
		$out = array();
		$i=0;
		foreach ($data as $key => $value) {
			if($value !== '' and strpos($key,$this->_prefix)===0){
				if($clearPrefix){
					$names = explode($this->_prefix,$key);
					array_shift($names);
					foreach ($names as $name) {
						$out[$i][$name] = $value;
					}
					$i++;
				}else{
					$out[$key] = $value;
				}
			}
		}
		//debug($out);
		return $out;
	}

/**
 * setRequest
 * Guardar datos en Controller->request
 *
 * @param string $key
 * @return void
 */
	private function setRequest($key, $value=null, $param = 'search'){
		if(is_array($key) and $value===null){
			foreach ($key as $k => $v) {
				$this->Controller->request->params[$param][$k] = $v;
			}
		}else{
			$this->Controller->request->params[$param][$key] = $value;
		}
	}








 // --------------------------------------------------------- REVISION










/** 
 *	Para ser usado junto al PaginatorComponent
 *
 *		$data
 *			Array de entrada de datos
 *
 *		$defaultPageLimit
 *
 *	Ejemplos:
 *		En el Controller:
 *
 *	Toma los valores de $this->request->query y manda 20 elementos por pagina si no se ha cambiado ese valor en la vista
 *		$this->Paginator->settings = array(
 *			'limit'=>$this->Search->pageLimit(),
 *		);
 *
 *	@return $pageLimit
 */
	public function pageLimit($data = null, $setPageLimit = null) {
		$defaultPageLimit = '20';

		$data = $this->getData($data);

		$pageLimit = ( (isset($data['pageLimit']) and $data['pageLimit']!='') ? $data['pageLimit'] : ( !is_null($setPageLimit) ? $setPageLimit : $defaultPageLimit) );

		$this->setRequest('pageLimit',$pageLimit);
		return $pageLimit;
	}



	// -----------------------------

	public function reset($url=null, $data=null, $admin=null){

		if($admin === null){
			$admin = ( isset($this->Controller->params['admin']) ? $this->Controller->params['admin'] : false );
		}

		$out = array();
		$out = ( $url ? $url : array('action'=>$this->Controller->params['action']) );

		$data = $this->cleanData($this->getData($data),false);

		$out['admin'] = $admin;
		$out['?'] = $data;

		return $this->Controller->redirect($out);
	}

	/*private function getToken($valor){
		$tokens = false;
		$token=false;

		foreach ($this->tokens as $key => $value) {
			$token['posToken'] = strpos($valor,$value['simbol']);
			if($token['posToken'] !== false){
				$token['id'] = $key;
				$token['simbol'] = $value['simbol'];

				if($token['posToken'] === 0){
					$token['pos'] = 'ini';
				}elseif($token['posToken'] === (strlen($valor)-1)){
					$token['pos'] = 'end';
				}else{
					$token['pos'] = 'mid';
				}

				$tokens = $token;
				break;
			}
		}
		return $tokens;
	} */

/*	private function getComparationClave($clave, $valor){
		$token = $this->getToken($valor);
		if($token){
			switch ($this->tokens[$token['id']]['nombre']) {
				case 'igual': 	$out = $this->Controller->modelClass.'.'.$clave; 		break;
				case 'dif': 	$out = $this->Controller->modelClass.'.'.$clave.' !='; 	break;
				case 'maigual': $out = $this->Controller->modelClass.'.'.$clave.' >=';	break;
				case 'meigual': $out = $this->Controller->modelClass.'.'.$clave.' <='; 	break;
				case 'ma': 		$out = $this->Controller->modelClass.'.'.$clave.' >'; 	break;
				case 'me': 		$out = $this->Controller->modelClass.'.'.$clave.' <'; 	break;

				default:	$out = $this->Controller->modelClass.'.'.$clave.' LIKE'; break;
			}
		}else{
			$out = $this->Controller->modelClass.'.'.$clave.' LIKE';
		}
		return $out;
	}

	private function getComparationValor($clave, $valor){
		$valor = trim($valor);
		$token = $this->getToken($valor);

		//debug($token); exit();

		if($token){
			switch ($this->tokens[$token['id']]['nombre']) {
				case 'like':	$out = ltrim($valor); 											break;
				case 'ast': 	$out = str_replace('*', '%', ltrim($valor));					break;

				default: 		$out = ltrim($valor, $this->tokens[$token['id']]['simbol']); 	break;
			}
		}else{
			$out = '%'.$valor.'%';
		}

		return $out;
	} */




/*	private function searchMulti($texto,$campos){
		// ***************************************************
		// EN PROCESO REVISAR!!!!!!!!!!
		// ***************************************************

		$search = explode(" ",trim($texto));

		$conditions=null;

		foreach ($search as $record) {
			if(is_array($campos)){
				foreach ($campos as $campo) {
					$conditions[] = array($this->Controller->modelClass.'.'.$campo.' LIKE' => '%'.$record.'%');
				}
			}else{
				$conditions[] = array($this->Controller->modelClass.'.'.$campos.' LIKE' => '%'.$record.'%');
			}
		}

		$conditions = array('OR'=>$conditions);

		return $conditions;
	} */

}
