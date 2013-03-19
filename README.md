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

Este tutorial necessita que você faça e entenda este [tutorial](https://github.com/thiagopelizoni/zf2-doctrine) pois, este que agora você está lendo é continuação para demonstração de algo específico que, no caso é o uso do DataTables para paginação, ordenação e busca de dados.

Existe a necesidade de você efetuar o download do plugin DataTables colocando dentro de *public/js* bem como, fazendo um "prepend" deste arquivo em no layout/view de sua aplicação. O arquivo *ResultSet.js* que encontra-se neste projeto consiste em uma biblioteca customizada para o uso do DataTables facilitando a obtenção dos dados.

```
<?php echo $this->headScript()->prependFile($this->basePath() . '/js/ResultSet.js'); ?>
<?php echo $this->headScript()->prependFile($this->basePath() . '/js/jquery.dataTables.js'); ?>
```

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

## Exemplo de uso

O DataTables possui dezenas de parâmetros onde, os que são obrigatórios são contemplados por pela classe *DataTable/src/DataTable/Model/DataTable.php*. Para efetuar uma listagem de uma entidade específica, faz-se necessário extender da classe supracida, conforme o exemplo abaixo:

### Stock/src/Stock/Model/ProductDataTable.php

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
 * @author Thiago Pelizoni <thiago.pelizoni@gmail.com>
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
	     * Neste tutorial, a sequencia da listagem está sendo: 'id', 'name', 'description'.
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

Aqui será demonstrado como fica 
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
    // Muito código antes
    
    /**
     * Listagem dos dados referente a entidade Product com todos os dados.
     *
     * @return json
     */
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
    
    // Muito código depois
    
}

## Stock/view/stock/index.phtml

```
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

Na listagem acima, todos os ítens desta entidade estão sendo listados visto que são poucos, porém, quando uma entidade possuir muitos atributos, obviamente que você terá de escolher quais serão listados, desta forma, a variável $configuration é utilizada onde, ela representa as colunas que a listagem específica irá exibir.

Os dados que serão exibidos estão armazenados nessa variável $aaData. No caso de uma listagem mais personalizada, basta passar por parâmetro as configurações personalizadas, conforme exemplo abaixo:

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
    // Muito código antes
    
    /**
     * Listagem dos dados referente a entidade Product com dados personalizados.
     *
     * @return json
     */
    public function listAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $params = $this->params()->fromQuery();
    
            $entityManager = $this->getEntityManager()
                ->getRepository('Stock\Entity\Product');
        
            $dataTable = new \Stock\Model\ProductDataTable($params);
            $dataTable->setEntityManager($entityManager);
            // Nessa listagem só será exibido o 'id' e 'name'
            $dataTable->setConfiguration(array(
	            'id',
	            'name'
            ));
            
            $aaData = array();
		    // Os dados para a listagem deve seguir a regra acima citada
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
```

```<?php
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
				        </tr>
				    </thead>
				    <tbody>
				        <tr>
				            <td colspan="2">Carregando do servidor</td>
				        </tr>
				    </tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<a class="btn-large btn-primary" href="<?php echo $this->url('product', array('action'=>'add'));?>">Cadastrar</a>

<script>
	var URL = '<?php echo $this->serverUrl() . "/product/list"; ?>';
	ResultSet.paginate(URL);
</script>
```
