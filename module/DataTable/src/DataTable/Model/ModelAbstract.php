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
     * @param   array   $data
     * @return  DataTable\Model\Abstract
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
