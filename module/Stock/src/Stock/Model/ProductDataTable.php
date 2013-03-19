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
