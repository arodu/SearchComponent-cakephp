<?php

class SearchHelper extends AppHelper {
	var $helpers = array('Form');

	private $_prefix = '__';


	/**
	 * Persistent default options used by input(). Set by FormHelper::create().
	 *
	 * @var array
	 */
	protected $_inputDefaults = array();


	/**
	 *	$_autoSubmit
	 *
	 *  Si autoSubmit es falso, entonces es necesario tener un boton de tipo submit en la vista
	 *  en caso contrario se usara un codigo de jquery para hacer los submit
	 *
	 *	para modificar se puede hacer en $this->Search->setOptions(array('autoSubmit'=>true));
	 *
	 */
	protected $_autoSubmit = false;

	public function setOptions($options=array()){

		if(isset($options['inputDefaults'])){	$this->_inputDefaults = $options['inputDefaults'];	}
		if(isset($options['autoSubmit'])){	$this->_autoSubmit = $options['autoSubmit'];	}

	}

	public function __construct(View $View, $settings = array()) {
		$this->_prefix = ( isset($settings['prefix']) ? $settings['prefix'] : $this->_prefix );
		$this->_View = $View;
		$this->request = $View->request;
		parent::__construct($View, $settings);
	}

	public function create($model = null, array $options = array()){
		$this->setOptions($options);
		$options = array_merge($options, array('type'=>'get','role'=>'form'));
		return $this->output($this->Form->create($model, $options));
	}

	protected function cleanQuery($query){
		unset($query['_method']);
		//unset($query['paginas']);
		//unset($query['pageLimit']);
		return $query;
	}

	public function end($options = null){
		$this->setOptions($options);
		return $this->output($this->Form->end($options));
	}

	public function activatePaginator(&$paginator,$admin=false){
		$pre = $this->params['search']; unset($pre['pass']);
		$pass = $this->params['search']['pass'];
		$out = array_merge($pre,$pass);
		$paginator->options(array( 'url'=>array('admin'=>$admin,'?'=>$out) ));
	}

	private function construcName($names = null, $usePrefix = true){
		$out = '';
		if(is_array($names)){
			foreach ($names as $name){
				if($usePrefix){
					$out .= $this->_prefix.$name;
				}else{
					$out .= ucfirst($name);
				}
			}
		}else{
			if($usePrefix){
				$out = $this->_prefix.$names;
			}else{
				$out = ucfirst($names);
			}
		}
		return $out;
	}

	public function input($fieldName, $attributes = array()){
		$select = false;
		$output = null;

		$defaultAttributes = array(
			//'name'=>$this->_prefix.$fieldName,
			'name'=> $this->construcName($fieldName,true),

			'label'=>false,
			'div'=>false,
			'value'=>$this->_getValue($fieldName),
			'class'=>'',
			'options'=>null,
			//'id'=>"search",
			//'placeholder'=>'', //'placeholder'=>'$fieldName',
			);

		//	check select


			if(!is_array($fieldName)){
				$varName = Inflector::variable(Inflector::pluralize(preg_replace('/_id$/', '', $fieldName)));
				$varOptions = $this->_View->get($varName);

				if(isset($attributes['options']) or is_array($varOptions)){
					$select=true;
					$defaultAttributes['empty'] = '-- seleccione --';
					$defaultAttributes['options'] = $varOptions;
				}
			}

		// 	autoSumbit
			$autoSubmit = $this->_autoSubmit;
			if(isset($attributes['autoSubmit'])){
				$autoSubmit = $attributes['autoSubmit'];
				unset($attributes['autoSubmit']);
			}
			if($autoSubmit){
				if($select){
					$attributes['onchange']='this.form.submit()';
				}else{
					$attributes['onkeypress']='(event.keyCode==13 ? this.form.submit() : true)';
				}
			}


		//	field
			$field = 'search'.ucfirst($this->construcName($fieldName,false));

		//	finaly
			$attributes = array_merge($defaultAttributes, $this->_inputDefaults, $attributes);
			$attributes['class'] = trim('searchInput '.$attributes['class']); // Agregar class searchInput
			$options = $attributes['options']; unset($attributes['options']);

		//	return
			if($select){
				return $this->output($this->Form->select($field, $options, $attributes));
			}else{
				return $this->output($this->Form->text($field, $attributes));
			}
	}

	public function submit($value = 'Submit'){
		return $this->Form->buttom('buscar',array('type'=>'submit','value'=>$value));
	}

	protected function _getValue($fieldName = null){
		if($fieldName == 'pageLimit'){
			//return @$this->params['search']['pageLimit'];
			return @$this->request->query['pageLimit'];
		}else{
			return ( (isset($this->params['search']['pass'][$this->construcName($fieldName)]) ) ? $this->params['search']['pass'][$this->construcName($fieldName)] : '' );
		}
	}

	public function inputPageLimit($attributes = array()){
		$defaultAttributes = array(
				'options'=>array(
					'Elementos por pagina'=>array('1'=>'1','10'=>'10','20'=>'20','30'=>'30','50'=>'50','100'=>'100'),
					),
				'class'=>'',
				'name'=>'pageLimit',
				'value'=>$this->_getValue('pageLimit'),
				'label'=>false,
				'empty'=>false,
			);

		//	autoSubmit
			if((isset($attributes['autoSubmit']) and $attributes['autoSubmit']===true) or ($this->_autoSubmit===true)){
				$attributes['onchange']='this.form.submit()';
				unset($attributes['autoSubmit']);
			}

		//	finaly
			$attributes = array_merge($defaultAttributes, $this->_inputDefaults, $attributes);
			$attributes['class'] = trim('searchPageLimit '.$attributes['class']); // Agregar class .searchPageLimit
			$options = $attributes['options']; unset($attributes['options']);

		//	return
			return $this->output( $this->Form->select($attributes['name'], $options, $attributes) );
	}

}
?>
