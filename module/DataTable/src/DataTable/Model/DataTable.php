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
