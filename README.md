# SearchComponent-cakephp
Componente de Busqueda para Cakephp 2.x

## Probarlo
        chmod 777 app/tmp -R
        cp app/Config/database.php.default app/Config/database.php
        app/Console/cake schema create

## Instalación en otros proyectos
	cp -v app/Controller/Component/SearchComponent.php /ruta/proyecto/app/Controller/Component/
	cp -v app/View/Helper/SearchHelper.php /ruta/proyecto/app/View/Helper/

# Uso
En el view del modulo a implementar, ej. app/View/Usuario/index.ctp agregar las siguientes lineas

	<?php $this->Search->activatePaginator($this->Paginator); ?>
	...
	<?php
		echo $this->Search->create();
		echo $this->Search->input(array('id','username'),array('type'=>'search','autoSubmit'=>true));
		echo $this->Search->end();
	?>

En el controller colocar los siguiente
	...
	public function index() {
		$this->User->recursive = 0;
		$types = $this->Paginator->paginate('User', $this->Search->getConditions() );
		$this->set('users', $users);
	}
