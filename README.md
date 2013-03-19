Zend Framework 2, Doctrine ORM e DataTables
============

## Descrição

Este projeto contém um exemplo prático da utilização do [Zend Framework 2](http://framework.zend.com/manual/2.0/en/index.html) com o ORM [Doctrine](http://www.doctrine-project.org/) e o plugin JQuery [DataTables](http://www.datatables.net/). Trata-se da implementação de um [CRUD](http://en.wikipedia.org/wiki/Create,_read,_update_and_delete) simples de produtos onde a listagem destes será feita através do auxílio do plugin DataTables e Doctrine.

A configuração da máquina utilizada para realização deste tutorial foi:

* Ubuntu 13.04
* Apache 2.2.22
* MySQL 5.5.29
* PHP 5.4.6
* Git 1.7.10.4

## Pré-requesito

Este tutorial necessita que você faça e entenda este (tutorial)[https://github.com/thiagopelizoni/zf2-doctrine] pois, este que agora você está lendo é continuação para demonstração de algo específico que, no caso é o uso do (DataTables)[http://www.datatables.net/] para paginação, ordenação e busca de dados.

## Preparação do ambiente

#### Obtendo o Zend Framework 2

Este tutorial assume que o local deste projeto será no diretório **/var/www**.
```
cd /var/www
git clone git@github.com:thiagopelizoni/zf2-doctrine.git zf2-doctrine-datatable
```

## Instalando dependências

```
php composer.phar self-update && php composer.phar install
```

## VirtualHost

```
<VirtualHost *:80>
    ServerName zf2-datatable.local
    DocumentRoot /var/www/zf2-doctrine-datatable/public

    SetEnv APPLICATION_ENV "development"
    SetEnv PROJECT_ROOT "/var/www/zf2-doctrine-datatable"

    <Directory "/var/www/zf2-doctrine/public">
        DirectoryIndex index.php
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>

</VirtualHost>
```

## Hosts

```
echo "127.0.0.1 zf2-datatable.local" >> /etc/hosts
```

## Database (script para geração da base de dados)

```
DROP DATABASE IF EXISTS zf2;
CREATE DATABASE zf2;
USE zf2;

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;


INSERT INTO `products` (`id`, `name`, `description`) VALUES
(1, 'Achocolatado Nescau 2.0', 'NESCAU 2.0 é uma evolução do Nescau que todo mundo adora. Ele ganhou ainda mais vitaminas e um novo blend de Cacau surpreendente.'),
(2, 'Chocolate CHARGE', 'Combinação perfeita. Bombom de chocolate recheado com amendoim e caramelo.'),
(3, 'Chocolate Crunch', 'Chocolate ao leite NESTLÉ com flocos de arroz. O chocolate do barulho que todo mundo adora agora em versão 35g!');
```

## Configurando o projeto

#### config/autoload/local.php

```php
<?php
return array(
    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params' => array(
                    'host'     => 'localhost',
                    'port'     => '3306',
                    'user'     => 'root',
                    'password' => 'root',
                    'dbname'   => 'zf2',
                    'charset'  => 'UTF8',  
                ),
            ),
        ),
    ),
);
```

#### Adicionando o módulo nas configurações da aplicação

```php
<?php
// config/application.config.php
return array(
    // This should be an array of module namespaces used in the application.
    'modules' => array(
        'Application',
        'DoctrineModule',
        'DoctrineORMModule',
        'Stock',
        'DataTable',            // Adicionar (módulo que iremos criar)
    ),
    .
    .
    .
```

## Criação do Módulo

Iremos criar um módulo do Zend Framework 2 que irá conter os fontes de nosso projeto, portanto, dentro do diretório *zf2-doctrine/module* do projeto, devemos criar a seguinte estrutura de diretório:

```
DataTable
  config
  src
    DataTable
      Model
```

Criando a estrutura de diretórios.

```
mkdir DataTable
mkdir -p DataTable/config
mkdir -p DataTable/src/DataTable/Model
```

## DataTable/Module.php
```php
<?php
namespace DataTable;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
}
```

## DataTable/config/module.config.php

```php
<?php
namespace DataTable;

return array(

    // Doctrine configuration
    'doctrine' => array(
        'driver' => array(
            __NAMESPACE__ . '_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/' . __NAMESPACE__ . '/Entity')
            ),
            'orm_default' => array(
                'drivers' => array(
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver'
                ),
            ),
        ),
    ),

);
```

## DataTable/autoload_classmap.php
```php
<?php
return array();
```

## DataTable/Model/ModelAbstract.php

Esta classe contém funções básicas para trabalhar de maneira eficaz com as models e o Doctrine.

```php
<?php
namespace DataTable\Model;

/**
 * Abstract class with some methods that other classes can use it.
 *
 * @author  Thiago Pelizoni <thiago.pelizoni@gmail.com>
 */
abstract class ModelAbstract
{
    /**
     * Default class constructor. This model can be filled automatically
     * with the form data. 
     *
	 * @param 	array	$data
	 * @return	DataTable\Model\Abstract
	 */
	public function __construct($data = null)
	{
		$this->exchangeArray($data);
		
		return $this;
	}
	
    /**
     * Populate this object from an array.
     *
     * @param array $data
     */
	public function exchangeArray($data)
	{
	    if ($data != null) {
			foreach ($data as $attribute => $value) {
				if (! property_exists($this, $attribute)) {
					continue;
				}
				$this->$attribute = $value;
			}
		}
	}
	
	/**
	 * Magic method used to set a value in a attribute.
	 *
	 * @param string $attribute
	 * @param mixed  $value 
	 * @return DataTable\Model\Abstract;
	 */
	public function __set($attribute, $value)
	{
	    $this->$attribute = $value;
	    
	    return $this;
	}
	
	/**
	 * Magic method used to return a value of this class
	 *
	 * @param   string $attribute
	 * @return  DataTable\Model\Abstract;
	 */
	public function __get($attribute)
	{
	    return $this->$attribute;
	}
	
	/**
	 * Return this object in array format. Very useful when you need work with Zend\Form.
	 *
	 * @return array
	 */
	public function getArrayCopy()
	{
	    return get_object_vars($this);
	}
	
	/**
	 * Return this object in json format. Very useful when you need work with Restful API.
	 *
	 * @return json
	 */
	public function getJson()
	{
	    return json_encode($this->getArrayCopy());
	}
}
```

## DataTable/Model/DataTable.php
```php
<?php
namespace DataTable\Model;

use DataTable\Model\ModelAbstract;

/**
 * DataTable
 *
 * This classe allow you to work easily with DataTables using the 
 * pagination of Zend Paginator.
 *
 * @author  Thiago Pelizoni <thiago.pelizoni@gmail.com>
 */
abstract class DataTable extends ModelAbstract
{
    /**
     * Entity
     */
    protected $entityManager;
    
	/**
	 * It's a data will be sent to DataTable
	 * 
	 * @var array
	 */
	protected $aaData;
	
	/**
	 * Plugin control number 
	 * 
	 * @var int
	 */
	protected $sEcho;

	/**
	 * Term to be searched
	 * 
	 * @var string
	 */
	protected $sSearch;
	
	/**
	 * Initial number to paginate the records.
 	 * 
 	 * @var int
	 */
	protected $iDisplayStart;
	
	/**
	 * Total of records displayed per page
	 *
	 * @var int
	 */
	protected $iDisplayLength;
	
	/**
	 * Store the pagination results.
	 * 
	 * @var \Doctrine\ORM\Tools\Pagination\Paginator
	 */
	protected $paginator;
	
	/**
	 * Store the page number used from \Doctrine\ORM\Tools\Pagination\Paginator
	 * 
	 * @var int
	 */
	protected $page;
	
	/**
	 * Total of records found
	 * 
	 * @var int
	 */
	protected $iTotalRecords;
	
	/**
	 * Total of records displayed
	 *
     * Case this number to be a query result, this number is not total pagination 
     * records  but the total of records found in a query.
	 * 
	 * @var int
	 */
	protected $iTotalDisplayRecords;
	
	/**
	 * Store the column number that will be ordered.
	 *
	 * @var string
	 */
	protected $iSortCol_0;

    /**
     * Kind of ordination, can be asc or desc.
	 *
	 * @var string
	 */	
	protected $sSortDir_0;
	
    /**
	 * Store all columns into an array to order the datatable
	 * 
	 * @var array
	 */
	protected $configuration;
	
    /**
	 * All data that came of the requisition.
	 * 
	 * @var array
	 */
	protected $params;
	
	////////////////////////////////////////////////////////////////////////////
	
	public function __construct($data = null)
	{
	    $this->setParams($data);
	    
		parent::__construct($data);

		if (isset($data['sSearch'])) {
			$this->setSSearch($data['sSearch']);
		}

		return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function getAaData()
	{
	    return $this->aaData;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function setAaData($aaData)
	{
	    $this->aaData = $aaData;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function getSEcho()
	{
	    return $this->sEcho;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function setSEcho($sEcho)
	{
	    $this->sEcho = $sEcho;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function getSSearch()
	{
	    return $this->sSearch;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function setSSearch($sSearch)
	{
	    $this->sSearch = $sSearch;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function getDisplayStart()
	{
	    return $this->iDisplayStart;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function setDisplayStart($iDisplayStart)
	{
	    $this->iDisplayStart = (int) $iDisplayStart;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function getDisplayLength()
	{
	    return $this->iDisplayLength;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function setDisplayLength($iDisplayLength)
	{
	    $this->iDisplayLength = (int) $iDisplayLength;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function getPaginator()
	{
	    if (! $this->paginator) {
	        $entityManager = $this->getEntityManager();
	        
	        $alias = 'entity';

            $query = $entityManager->createQueryBuilder($alias)
               ->setFirstResult($this->getPage())
               ->setMaxResults($this->getDisplayLength())
               ->orderBy("{$alias}.{$this->configuration[$this->iSortCol_0]}",  $this->sSortDir_0);

            if ($this->getSSearch() != null) {               
                $sSearch = strtoupper($this->getSSearch());
	            $sSearch = preg_replace('/[^[:ascii:]]/', '%', $sSearch);
	            $sSearch = preg_replace('/[%]{1,}/', '%', $sSearch);  
	            $this->setSSearch($sSearch);               
                                       
                foreach ($this->getConfiguration() as $column) {
                    $query->orWhere("UPPER({$alias}.{$column}) LIKE '%{$this->getSSearch()}%'");
                } 
            }
            
            $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
            
            $this->setTotalRecords($paginator->count());
            $this->setTotalDisplayRecords($paginator->count());
            
            $this->paginator = $paginator;
        }
        
        return $this->paginator;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function setPaginator($paginator)
	{
	    $this->paginator = $paginator;
        
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function setPage($page)
	{
		$this->page = $page;
		
		return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function getPage()
	{
		if ($this->page == null) {
			$this->setPage($this->getDisplayStart());
		}
		
		return $this->page;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function getTotalRecords()
	{
	    return $this->iTotalRecords;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function setTotalRecords($iTotalRecords)
	{
	    $this->iTotalRecords = (int) $iTotalRecords;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function getTotalDisplayRecords()
	{
	    return $this->iTotalDisplayRecords;
	}
	
	////////////////////////////////////////////////////////////////////////////

	public function setTotalDisplayRecords($iTotalDisplayRecords)
	{
	    $this->iTotalDisplayRecords = (int) $iTotalDisplayRecords;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function setBSortable($bSortable)
	{
	    $this->bSortable = $bSortable;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function getBSortable()
	{
	    return $this->bSortable;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function setParams($params)
	{
	    $this->params = $params;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function getParams()
	{
	    return $this->params;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function setISortCol($iSortCol_0)
	{
	    $this->iSortCol_0 = $iSortCol_0;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function getISortCol()
	{
	    return $this->iSortCol_0;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function setSSortCol($sSortDir_0)
	{
	    $this->sSortDir_0 = $sSortDir_0;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function getSSortDir()
	{
	    return $this->sSortDir_0;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function setConfiguration($configuration)
	{
	    $this->configuration = $configuration;
	    
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function getConfiguration()
	{
	    return $this->configuration;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function setEntityManager($entityManager)
	{
	    $this->entityManager = $entityManager;
	
	    return $this;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function getEntityManager()
	{
	    return $this->entityManager;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	public function getArrayCopy()
	{
		$data = parent::getArrayCopy();
		
		unset($data['paginator']);
		unset($data['page']);
		unset($data['aaDataFound']);
		unset($data['entity']);
		unset($data['configuration']);
		unset($data['params']);
		
		return $data;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
}
```

## Stock/src/Stock/Entity/Product.php
```php
<?php
/**
 * Tutorial of Zend Framework 2 and Doctrine
 *
 * This entity is a simple example how to use Doctrine in ZF2
 *
 * @author Thiago Pelizoni <thiago.pelizoni@gmail.com>
 */
namespace Stock\Entity;

use DataTable\Model\ModelAbstract;

use Doctrine\ORM\Mapping as ORM;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface; 

/**
 * Product
 *
 * @ORM\Entity
 * @ORM\Table(name="products")
 * @property int $id
 * @property string $name
 * @property string $description
 */
class Product extends ModelAbstract implements InputFilterAwareInterface 
{
    /**
     * @var Zend\InputFilter\InputFilter
     */
    protected $inputFilter;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer");
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     */
    protected $description;

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used!");
    }

    public function getInputFilter()
    {
        if (! $this->inputFilter) {
            $inputFilter = new InputFilter();

            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                'name'       => 'id',
                'required'   => true,
                'filters' => array(
                    array('name'    => 'Int'),
                ),
            )));

            $inputFilter->add($factory->createInput(array(
                'name'     => 'name',
                'required' => true,
                'filters'  => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name'    => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'min'      => 1,
                            'max'      => 100,
                        ),
                    ),
                ),
            )));

            $inputFilter->add($factory->createInput(array(
                'name'     => 'description',
                'required' => true,
                'filters'  => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name'    => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'max'      => 1000,
                        ),
                    ),
                ),
            )));

            $this->inputFilter = $inputFilter;        
        }

        return $this->inputFilter;
    } 
}
```

## Stock/src/Stock/Model/ProductDataTable.php
```php
<?php
namespace Stock\Model;

use DataTable\Model\DataTable;

/**
 * ProductDataTable
 *
 * Classe responsável por fazer com que seja possível trabalhar com o plugin 
 * DataTables junto com o ORM Doctrine para efetuar paginações.
 *
 * Neste caso, utilizando as regras específicas para a entidade Product.
 *
 * @author  Thiago Pelizoni <thiago.pelizoni@gmail.com>
 */
class ProductDataTable extends DataTable
{
	public function findAll()
	{
	    if (! $this->getConfiguration()) {
	        // Este array deve ser na ordem das colunas da listagem
	        $configuration = array(
	            'id',
	            'name',
	            'description',
	        );
	        $this->setConfiguration($configuration);
        }	        
	    
	    /**
	     * Irá montar os dados que serão exibidos no DataTable
	     *
	     * Neste tutoria, a sequencia da listagem está sendo: 'id', 'name', 'description'.
	     * Desta forma, o array que será atribuido a variável DataTable::aaData deve estar
	     * na mesma sequencia.
	     */ 
		if (! $this->getAaData()) {
		    $aaData = array();
		    
		    foreach ($this->getPaginator() as $product) {
			    $data = array(
				    $product->id,
				    $product->name,
				    $product->description,
				    "<a class='btn' href='/product/edit/{$product->id}'>Editar</a> "
				        . "<a class='btn btn-danger' href='/product/delete/{$product->id}'>Excluir</a>",
			    );
			
			    $aaData[] = $data;
		    }
		
		    $this->setAaData($aaData);
		}
		
		return $this->getJson();
	}
	    
}
```

## Stock/src/Stock/Controller/ProductController.php
```php
<?php
namespace Stock\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel; 
use Doctrine\ORM\EntityManager;
use Stock\Entity\Product;
use Stock\Form\ProductForm;

class ProductController extends AbstractActionController
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }
 
    /**
     * Return a EntityManager
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        if ($this->em === null) {
            $this->em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        }
        
        return $this->em;
    } 

    ///////////////////////////////////////////////////////////////////////////

    public function indexAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $params = $this->params()->fromQuery();
    
            $entityManager = $this->getEntityManager()
                ->getRepository('Stock\Entity\Product');
        
            $dataTable = new \Stock\Model\ProductDataTable($params);
            $dataTable->setEntityManager($entityManager);
            $dataTable->findAll();
            
            return $this->getResponse()->setContent($dataTable->findAll());
        }
    }
    
    ///////////////////////////////////////////////////////////////////////////

    public function addAction()
    {
        $form = new ProductForm();
        $form->get('submit')->setAttribute('label', 'Add');

        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $product = new Product();
            
            $form->setInputFilter($product->getInputFilter());
            $form->setData($request->getPost());
            
            if ($form->isValid()) { 
                $product->exchangeArray($form->getData()); 
                
                $this->getEntityManager()->persist($product);
                $this->getEntityManager()->flush();

                // Redirect to list of Stocks
                return $this->redirect()->toRoute('product'); 
            }
        }

        return array('form' => $form);
    }
    
    ///////////////////////////////////////////////////////////////////////////

    public function editAction()
    {
        $id = (int) $this->getEvent()->getRouteMatch()->getParam('id');
        
        if (!$id) {
            return $this->redirect()->toRoute('product', array('action'=>'add'));
        } 
        
        $product = $this->getEntityManager()->find('Stock\Entity\Product', $id);

        $form = new ProductForm();
        $form->setBindOnValidate(false);
        $form->bind($product);
        $form->get('submit')->setAttribute('label', 'Edit');
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
        
            $form->setData($request->getPost());
            
            if ($form->isValid()) {
                $form->bindValues();
                $this->getEntityManager()->flush();

                // Redirect to list of Stocks
                return $this->redirect()->toRoute('product');
            }
        }

        return array(
            'id' => $id,
            'form' => $form,
        );
    }
    
    ///////////////////////////////////////////////////////////////////////////

    public function deleteAction()
    {
        $id = (int) $this->getEvent()->getRouteMatch()->getParam('id');
        
        if (!$id) {
            return $this->redirect()->toRoute('product');
        }

        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $del = $request->getPost('del', 'No');
            
            if ($del == 'Yes') {
                $id = (int) $request->getPost('id');
                $Stock = $this->getEntityManager()->find('Stock\Entity\Product', $id);
                
                if ($Stock) {
                    $this->getEntityManager()->remove($Stock);
                    $this->getEntityManager()->flush();
                }
            }

            return $this->redirect()->toRoute('product');
        }

        return array(
            'id' => $id,
            'product' => $this->getEntityManager()->find('Stock\Entity\Product', $id)
        );
    }
    
}
```
    public function listAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $params = $this->params()->fromQuery();
    
            $entityManager = $this->getEntityManager()
                ->getRepository('Stock\Entity\Product');
        
            $dataTable = new \Stock\Model\ProductDataTable($params);
            $dataTable->setEntityManager($entityManager);
            $dataTable->setConfiguration(array(
	            'id',
	            'name'
            ));
            
            $aaData = array();
		    
		    foreach ($dataTable->getPaginator() as $product) {
			    $aaData[] = array(
				    $product->id,
				    $product->name
			    );
		    }
		
		    $dataTable->setAaData($aaData);
            
            return $this->getResponse()->setContent($dataTable->findAll());
        }
    }

## Stock/view/stock/product/index.phtml
```php
<?php
$title = 'Produtos';
$this->headTitle($title);
?>
<h1><?php echo $this->escapeHtml($title); ?></h1>

<div class="container-fluid">
	<div class="row-fluid">
		<div class="span12">
			<div>
				<table class="table table-striped table-bordered" id="data">
				    <thead>
				        <tr>
				        	<th class="span1">Código</th>
				           	<th class="span4">Nome</th>
							<th class="span7">Descrição</th>
							<th>Opções</th>
				        </tr>
				    </thead>
				    <tbody>
				        <tr>
				            <td colspan="4">Carregando do servidor</td>
				        </tr>
				    </tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<a class="btn-large btn-primary" href="<?php echo $this->url('product', array('action'=>'add'));?>">Cadastrar</a>

<script>
	var URL = '<?php echo $this->serverUrl() . "/product"; ?>';
	ResultSet.paginate(URL);
</script>
```

## zf2-doctrine-datatable/public/js/ResultSet.js
```
/* Default class modification */
$.extend($.fn.dataTableExt.oStdClasses, {
	"sWrapper": "dataTables_wrapper form-inline"
});

/* API method to get paging information */
$.fn.dataTableExt.oApi.fnPagingInfo = function(oSettings) {
	return {
		"iStart":         oSettings._iDisplayStart,
		"iEnd":           oSettings.fnDisplayEnd(),
		"iLength":        oSettings._iDisplayLength,
		"iTotal":         oSettings.fnRecordsTotal(),
		"iFilteredTotal": oSettings.fnRecordsDisplay(),
		"iPage":          Math.ceil( oSettings._iDisplayStart / oSettings._iDisplayLength ),
		"iTotalPages":    Math.ceil( oSettings.fnRecordsDisplay() / oSettings._iDisplayLength )
	};
};

/* Bootstrap style pagination control */
$.extend( $.fn.dataTableExt.oPagination, {
	"bootstrap": {
		"fnInit": function(oSettings, nPaging, fnDraw) {
			var oLang = oSettings.oLanguage.oPaginate;
			var fnClickHandler = function (e) {
				e.preventDefault();
				if ( oSettings.oApi._fnPageChange(oSettings, e.data.action) ) {
					fnDraw( oSettings );
				}
			};

			$(nPaging).addClass('pagination').append(
				'<ul>'+
					'<li class="prev disabled"><a href="#">&larr; '+oLang.sPrevious+'</a></li>'+
					'<li class="next disabled"><a href="#">'+oLang.sNext+' &rarr; </a></li>'+
				'</ul>'
			);
			var els = $('a', nPaging);
			$(els[0]).bind( 'click.DT', { action: "previous" }, fnClickHandler );
			$(els[1]).bind( 'click.DT', { action: "next" }, fnClickHandler );
		},

		"fnUpdate": function ( oSettings, fnDraw ) {
			var iListLength = 5;
			var oPaging = oSettings.oInstance.fnPagingInfo();
			var an = oSettings.aanFeatures.p;
			var i, j, sClass, iStart, iEnd, iHalf=Math.floor(iListLength/2);

			if (oPaging.iTotalPages < iListLength) {
				iStart = 1;
				iEnd = oPaging.iTotalPages;
			} else if ( oPaging.iPage <= iHalf ) {
				iStart = 1;
				iEnd = iListLength;
			} else if ( oPaging.iPage >= (oPaging.iTotalPages-iHalf) ) {
				iStart = oPaging.iTotalPages - iListLength + 1;
				iEnd = oPaging.iTotalPages;
			} else {
				iStart = oPaging.iPage - iHalf + 1;
				iEnd = iStart + iListLength - 1;
			}

			for (i=0, iLen=an.length; i<iLen; i++) {
				// Remove the middle elements
				$('li:gt(0)', an[i]).filter(':not(:last)').remove();

				// Add the new list items and their event handlers
				for ( j=iStart ; j<=iEnd ; j++ ) {
					sClass = (j==oPaging.iPage+1) ? 'class="active"' : '';
					$('<li '+sClass+'><a href="#">'+j+'</a></li>')
						.insertBefore( $('li:last', an[i])[0] )
						.bind('click', function (e) {
							e.preventDefault();
							oSettings._iDisplayStart = (parseInt($('a', this).text(),10)-1) * oPaging.iLength;
							fnDraw( oSettings );
						} );
				}

				// Add / remove disabled classes from the static elements
				if ( oPaging.iPage === 0 ) {
					$('li:first', an[i]).addClass('disabled');
				} else {
					$('li:first', an[i]).removeClass('disabled');
				}

				if ( oPaging.iPage === oPaging.iTotalPages-1 || oPaging.iTotalPages === 0 ) {
					$('li:last', an[i]).addClass('disabled');
				} else {
					$('li:last', an[i]).removeClass('disabled');
				}
			}
		}
	}
} );

/**
 * Método utilizado para fazer com que a busca no plugin seja feito após a tecla
 * "enter" ser precionada, e não através do evento keyup. 
 */ 
jQuery.fn.dataTableExt.oApi.fnFilterOnReturn = function(oSettings) {
	var _that = this;

	this.each(function(i) {
		$.fn.dataTableExt.iApiIndex = i;
		
		var anControl = $('input', _that.fnSettings().aanFeatures.f);
		
		anControl.unbind('keyup').bind('keypress', function(e) {
			if (e.which == 13) {
				$.fn.dataTableExt.iApiIndex = i;
				_that.fnFilter(anControl.val());
			}
		});
		
		return this;
	});
	
	return this;
};

var ResultSet = new function() {

	return {

		paginate : function(URL) {
			var data = $('#data').dataTable({
				"sDom": "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span6'i><'span6'p>>",
				"sPaginationType": "bootstrap",
				"oLanguage": {
					"oPaginate": {
						"sFirst": "Primeira",
						"sNext": "Proxima",
						"sPrevious": "Anterior",
						"sLast": "Ultima"
					},
					"sLengthMenu": "Visualizar _MENU_ registros por página",
					"sZeroRecords": "Registro não encontrado",
					"sInfo": "Visualizando _START_ até _END_ de _TOTAL_ registros",
					"sInfoEmpty": "Sem registros para visualizar",
					"sInfoFiltered": "(Filtrado de _MAX_ total de registros)",
					"sSearch": "Buscar"
				},
		  		"bProcessing": true,
		    	"bServerSide": true,
		    	"sAjaxSource": URL,
			}).fnFilterOnReturn();
			
			return data;
		}
		
		
	};
	
};
```

