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
    
    ///////////////////////////////////////////////////////////////////////////
    
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
    
    ///////////////////////////////////////////////////////////////////////////
    
}
