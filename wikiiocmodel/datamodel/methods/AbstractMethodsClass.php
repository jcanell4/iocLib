<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AbstractMethodsClass
 *
 * @author professor
 */
abstract class AbstractMethodsClass {
//    private $methods = [];
//    
//    public function __construct(array $methods) {
//        $this->methods = $methods;
//    }
//    
//    public function __call(string $method, array $arguments): Talk {
//        if ($func = $this->methods[$method] ?? false) {
//            $func(...$arguments);
//            
//            return $this;
//        }
//        
//        throw new \RuntimeException(sprintf('Missing %s method.'));
//    }    
    public abstract function setMethodToObject($object);
}
