<?php
/**
 * PHP Library package of Les Ateliers Pierrot
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <https://github.com/atelierspierrot/library>
 */

namespace Library;

use \Library\FactoryInterface;
use \Patterns\Abstracts\AbstractStaticCreator;
use \Library\Helper\Code as CodeHelper;
use \Library\Helper\Text as TextHelper;
use \ReflectionClass;

/**
 * @author 		Piero Wbmstr <piero.wbmstr@gmail.com>
 */
class Factory
    extends AbstractStaticCreator
    implements FactoryInterface
{

	/**
	 * String added to error messages to identify the caller
	 *
	 * @var string A name to identify factory error messages
	 * @use $factory->factoryName( $name )
	 */
	protected $factory_name = '';

	/**
	 * Current builder flag value
	 *
	 * @var int A class contant
	 * @use $factory->flag( const )
	 */
	protected $flag = null;

	/**
	 * Method called on the object's builder class to create the instance
	 *
	 * Final object class MUST implement this method.
	 *
	 * If this is not the constructor but the class do have a public constructor, it will
	 * be called first to create the instance.
	 *
	 * @var string Method to call for object construction
	 * @use $factory->callMethod( $method )
	 */
	protected $call_method = '__construct';

	/**
	 * Array of masks used to construct the final class name using `printf()` method
	 *
	 * Final object class CAN be named following one of these masks.
	 *
	 * @var string Printf expression (%s will be replaced by the `$name` in CamelCase)
	 * @use $factory->classNameMask( array( $maskX, $maskY ) ) or $factory->classNameMask( $maskX )
	 */
	protected $class_name_mask = array('%s');

	/**
	 * Array of possible optional namespaces used to search the class
	 *
	 * Final object class CAN be included in one of these namespaces.
	 *
	 * @var array
	 * @use $factory->defaultNamespace( array( $nameX, $nameY ) ) or $factory->defaultNamespace( $maskX )
	 */
	protected $default_namespace = array();

	/**
	 * Array of possible namespaces the class MUST be included in
	 *
	 * Final object class MUST be included in one of these namespaces.
	 *
	 * @var array
	 * @use $factory->mandatoryNamespace( array( $nameX, $nameY ) ) or $factory->mandatoryNamespace( $maskX )
	 */
	protected $mandatory_namespace = array();

	/**
	 * Array of possible interfaces the class MUST implement
	 *
	 * Final object class MUST implement one of these items.
	 *
	 * @var array
	 * @use $factory->mustImplement( array( $nameX, $nameY ) ) or $factory->mustImplement( $maskX )
	 */
	protected $must_implement = array();

	/**
	 * Array of interfaces the class MUST implement
	 *
	 * Final object class MUST implement ALL these items.
	 *
	 * @var array
	 * @use $factory->mustImplementAll( array( $nameX, $nameY ) )
	 */
	protected $must_implement_all = array();

	/**
	 * Array of possible classes the class MUST extend
	 *
	 * Final object class MUST extend one of these items.
	 *
	 * @var array
	 * @use $factory->mustExtend( array( $nameX, $nameY ) ) or $factory->mustExtend( $maskX )
	 */
	protected $must_extend = array();

	/**
	 * Array of possible interfaces or classes the class MUST implement or extend
	 *
	 * Final object class MUST implement or extend one of these items.
	 *
	 * @var array
	 * @use $factory->mustImplementOrExtend( array( $nameX, $nameY ) )
	 */
	protected $must_implement_or_extend = array();

	/**
	 * Initialize the factory with an array of options
	 *
	 * The options must be defined like `property => value`
	 *
	 * @param array $options
	 *
	 * @return void
	 */
	public function init(array $options = null)
	{
	    if (!empty($options)) {
	        $this->setOptions($options);
	    }
	}

    /**
     * Magic method to allow usage of `$factory->propertyInCamelCase()` for each property
     *
     * @param string $name
     * @param array $arguments
     *
     * @return self
     */
    public function __call($name, array $arguments)
    {
        $property_name = CodeHelper::getPropertyName($name);
        if (property_exists($this, $property_name)) {
            $param = array_shift($arguments);
            $this->setOptions(array(
                $property_name => is_array($this->{$property_name}) ? (
                    is_array($param) ? $param : array($param)
                ) : $param
            ));
        }
        return $this;
    }

    /**
     * Set the object options like `property => value`
     *
     * @param array $options
     *
     * @return self
     */
    public function setOptions(array $options)
    {
        foreach ($options as $index=>$val) {
            if (property_exists($this, $index)) {
                $this->{$index} = $val;
            }
        }
        return $this;
    }

    /**
     * Build the object instance following current factory settings
     *
     * Errors are thrown by default but can be "gracefully" skipped using the flag `GRACEFULLY_FAILURE`.
     * In all cases, error messages are loaded in final parameter `$logs` passed by reference.
     *
     * @param string $name
     * @param array $parameters
     * @param int $flag One of the class constants flags
     * @param array $logs Passed by reference
     *
     * @return object
     *
     * @throws RuntimeException if the class is not found
     * @throws RuntimeException if the class doesn't implement or extend some required dependencies
     * @throws RuntimeException if the class method for construction is not callable
     */
    public function build($name, array $parameters = null, $flag = self::ERROR_ON_FAILURE, array &$logs = array())
    {
        $this->flag($flag);
        $object = null;
        $builder_class_name = $this->findBuilder($name, $flag, $logs);

        if (!empty($builder_class_name)) {
            $reflection_obj = new ReflectionClass($builder_class_name);
            if (
                $reflection_obj->hasMethod('__construct') &&
                $reflection_obj->getConstructor()->isPublic()
            ) {
                if ($this->call_method==='__construct') {
                    $_caller = call_user_func_array(array($reflection_obj, 'newInstance'), $parameters);
                } else {
                    $_caller = call_user_func_array(array($reflection_obj, 'newInstance'), array());
                }
            } else {
                try {
                    if ($this->call_method==='__construct') {
                        $_caller = new $builder_class_name($parameters);
                    } else {
                        $_caller = new $builder_class_name;
                    }
                } catch (Exception $e) {
                    $logs[] = $this->_getErrorMessage('Constructor method for class "%s" is not callable!', $builder_class_name);
                    if ($flag & self::ERROR_ON_FAILURE) {
                        throw new \RuntimeException(end($logs));
                    }
                }
            }
            if ($this->call_method==='__construct') {
                $object = $_caller;
            } else {
                if (
                    $reflection_obj->hasMethod($this->call_method) &&
                    $reflection_obj->getMethod($this->call_method)->isPublic()
                ) {
                    if ($reflection_obj->getMethod($this->call_method)->isStatic()) {
                        $object = call_user_func_array(array($builder_class_name, $this->call_method), $parameters);
                    } else {
                        $object = call_user_func_array(array($_caller, $this->call_method), $parameters);
                    }
                } else {
                    $logs[] = $this->_getErrorMessage('Method "%s" for factory construction of class "%s" is not callable!',
                        $this->call_method, $builder_class_name);
                    if ($flag & self::ERROR_ON_FAILURE) {
                        throw new \RuntimeException(end($logs));
                    }
                }
            }

        } else {
            $logs[] = $this->_getErrorMessage('No matching class found for factory build "%s"!', $name);
            if ($flag & self::ERROR_ON_FAILURE) {
                throw new \RuntimeException(end($logs));
            }
        }

        return $object;
    }

    /**
     * Find the object builder class following current factory settings
     *
     * Errors are thrown by default but can be "gracefully" skipped using the flag `GRACEFULLY_FAILURE`.
     * In all cases, error messages are loaded in final parameter `$logs` passed by reference.
     *
     * @param string $name
     * @param int $flag One of the class constants flags
     * @param array $logs Passed by reference
     *
     * @return null|string
     *
     * @throws RuntimeException if the class is not found
     * @throws RuntimeException if the class doesn't implement or extend some required dependencies
     * @throws RuntimeException if the class method for construction is not callable
     */
    public function findBuilder($name, $flag = self::ERROR_ON_FAILURE, array &$logs = array())
    {
        $this->flag($flag);
        $cc_name = array(TextHelper::toCamelCase($name));
        if (!$this->_findClasses($cc_name)) {
            $cc_name = $this->_buildClassesNames($cc_name, $this->class_name_mask);
        }

        if (!$this->_findClasses($cc_name)) {
            $namespaces = array();
            if (!empty($this->default_namespace)) {
                $namespaces = array_merge($namespaces, $this->default_namespace);
            }
            if (!empty($this->mandatory_namespace)) {
                $namespaces = array_merge($namespaces, $this->mandatory_namespace);
            }
            if (!empty($namespaces)) {
                $cc_name = $this->_addNamespaces($cc_name, $namespaces);
            }
        }

        if (false!==$_cls = $this->_findClasses($cc_name)) {
        
            // required namespace
            if (!empty($this->mandatory_namespace) && !$this->_classesInNamespaces($_cls, $this->mandatory_namespace)) {
                $logs[] = $this->_getErrorMessage(
                    count($this->mandatory_namespace)>1 ? 'Class "%s" must be included in one of the following namespaces "%s"!' : 'Class "%s" must be in namespace "%s"!',
                    $_cls, implode('", "', $this->mandatory_namespace));
                if ($flag & self::ERROR_ON_FAILURE) {
                    throw new \RuntimeException(end($logs));
                }
                return null;
            }

            // required interface
            if (!empty($this->must_implement) && !$this->_classesImplements($_cls, $this->must_implement, false, $logs)) {
                $logs[] = $this->_getErrorMessage(
                    count($this->must_implement)>1 ? 'Class "%s" must implement one of the following interfaces "%s"!' : 'Class "%s" must implement interface "%s"!',
                    $_cls, implode('", "', $this->must_implement));
                if ($flag & self::ERROR_ON_FAILURE) {
                    throw new \RuntimeException(end($logs));
                }
                return null;
            }

            // required interfaces
            if (!empty($this->must_implement_all) && !$this->_classesImplements($_cls, $this->must_implement_all, true, $logs)) {
                $logs[] = $this->_getErrorMessage(
                    count($this->must_implement_all)>1 ? 'Class "%s" must implement the following interfaces "%s"!' : 'Class "%s" must implement interface "%s"!',
                    $_cls, implode('", "', $this->must_implement_all));
                if ($flag & self::ERROR_ON_FAILURE) {
                    throw new \RuntimeException(end($logs));
                }
                return null;
            }

            // required inheritance
            if (!empty($this->must_extend) && !$this->_classesExtends($_cls, $this->must_extend, $logs)) {
                $logs[] = $this->_getErrorMessage(
                    count($this->must_extend)>1 ? 'Class "%s" must extend one of the following classes "%s"!' : 'Class "%s" must extend class "%s"!',
                    $_cls, implode('", "', $this->must_extend));
                if ($flag & self::ERROR_ON_FAILURE) {
                    throw new \RuntimeException(end($logs));
                }
                return null;
            }

            // required interface OR inheritance
            if (!empty($this->must_implement_or_extend) &&
                !$this->_classesImplements($_cls, $this->must_implement_or_extend) &&
                !$this->_classesExtends($_cls, $this->must_implement_or_extend)
            ) {
                $logs[] = $this->_getErrorMessage('Class "%s" doesn\'t implement or extend the following required interfaces or classes "%s"!', 
                            $_cls, implode('", "', $this->must_implement_or_extend));
                if ($flag & self::ERROR_ON_FAILURE) {
                    throw new \RuntimeException(end($logs));
                }
                return null;
            }

            return $_cls;
        }
        return null;
    }

// -----------------------
// Processes
// -----------------------

    /**
     * Build the class name filling the `$class_name_mask`
     *
     * @param string|array $class_names
     *
     * @return misc The found class name if it exists, false otherwise
     */
    protected function _findClasses($class_names)
    {
        if (!is_array($class_names)) {
            $class_names = array($class_names);
        }
        foreach ($class_names as $_cls) {
            if (true===class_exists($_cls)) {
                return $_cls;
            }
        }
        return false;
    }

    /**
     * Build the class name filling a set of masks
     *
     * @param string|array $names
     * @param array $masks
     *
     * @return array
     */
    protected function _buildClassesNames($names, array $masks)
    {
        if (!is_array($names)) {
            $names = array($names);
        }
        $return_names = array();
        foreach ($names as $_name) {
            foreach ($masks as $_mask) {
                $return_names[] = sprintf($_mask, TextHelper::toCamelCase($_name));
            }
        }
        return $return_names;
    }

    /**
     * Add a set of namespaces to a list of class names
     *
     * @param string|array $names
     * @param array $namespaces
     * @param array $logs Passed by reference
     *
     * @return array
     */
    protected function _addNamespaces($names, array $namespaces, array &$logs = array())
    {
        if (!is_array($names)) {
            $names = array($names);
        }
        $return_names = array();
        foreach ($names as $_name) {
            foreach ($namespaces as $_namespace) {
                if (CodeHelper::namespaceExists($_namespace)) {
                    $tmp_namespace = rtrim(TextHelper::toCamelCase($_namespace), '\\').'\\';
                    $return_names[] = $tmp_namespace.str_replace($tmp_namespace, '', TextHelper::toCamelCase($_name));
                } else {
                    $logs[] = $this->_getErrorMessage('Namespace "%s" not found!', $_namespace);
                }
            }
        }
        return $return_names;
    }

    /**
     * Test if a set of class names implements a list of interfaces
     *
     * @param string|array $names
     * @param array $interfaces
     * @param bool $must_implement_all
     * @param array $logs Passed by reference
     *
     * @return bool
     */
    protected function _classesImplements($names, array $interfaces, $must_implement_all = false, array &$logs = array())
    {
        if (!is_array($names)) {
            $names = array($names);
        }
        $ok = false;
        foreach ($names as $_name) {
            foreach ($interfaces as $_interface) {
                if (interface_exists($_interface)) {
                    if (CodeHelper::impelementsInterface($_name, $_interface)) {
                        $ok = true;
                    } elseif ($must_implement_all) {
                        $ok = false;
                    }
                } else {
                    $logs[] = $this->_getErrorMessage('Interface "%s" not found!', $_interface);
                }
            }
        }
        return $ok;
    }

    /**
     * Test if a set of class names extends a list of classes
     *
     * @param string|array $names
     * @param array $classes
     * @param array $logs Passed by reference
     *
     * @return bool
     */
    protected function _classesExtends($names, array $classes, array &$logs = array())
    {
        if (!is_array($names)) {
            $names = array($names);
        }
        foreach ($names as $_name) {
            foreach ($classes as $_class) {
                if (class_exists($_class)) {
                    if (CodeHelper::extendsClass($_name, $_class)) {
                        return true;
                    }
                } else {
                    $logs[] = $this->_getErrorMessage('Class "%s" not found!', $_class);
                }
            }
        }
        return false;
    }

    /**
     * Test if a classes names set is in a set of namespaces
     *
     * @param string|array $names
     * @param array $namespaces
     * @param array $logs Passed by reference
     *
     * @return string|bool
     */
    protected function _classesInNamespaces($names, array $namespaces, array &$logs = array())
    {
        if (!is_array($names)) {
            $names = array($names);
        }
        foreach ($names as $_name) {
            foreach ($namespaces as $_namespace) {
                if (CodeHelper::namespaceExists($_namespace)) {
                    $tmp_namespace = rtrim(TextHelper::toCamelCase($_namespace), '\\').'\\';
                    if (substr_count(TextHelper::toCamelCase($_name), $tmp_namespace)>0) {
                        return $_name;
                    }
                } else {
                    $logs[] = $this->_getErrorMessage('Namespace "%s" not found!', $_namespace);
                }
            }
        }
        return false;
    }

    /**
     * Build a factory error message adding it the `$factory_name` if so
     *
     * @return string
     */
    protected function _getErrorMessage()
    {
        return (!empty($this->factory_name) ? '['.$this->factory_name.'] ' : '')
            .call_user_func_array('sprintf', func_get_args());
    }

}

// Endfile