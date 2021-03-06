<?php
/**
 * Base class for all HTML_QuickForm2 elements
 *
 * PHP version 5
 *
 * LICENSE:
 * 
 * Copyright (c) 2006, 2007, Alexey Borzov <avb@php.net>,
 *                           Bertrand Mansion <golgote@mamasam.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the 
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products 
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   HTML
 * @package    HTML_QuickForm2
 * @author     Alexey Borzov <avb@php.net>
 * @author     Bertrand Mansion <golgote@mamasam.com>
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Node.php,v 1.3 2007/04/17 13:51:25 mansion Exp $
 * @link       http://pear.php.net/package/HTML_QuickForm2
 */

/**
 * HTML_Common2 - base class for HTML elements   
 */
require_once 'HTML/Common2.php';

/**
 * Exception classes for HTML_QuickForm2  
 */
require_once 'HTML/QuickForm2/Exception.php';

/**
 * Abstract base class for all QuickForm2 Elements and Containers
 *
 * This class is mostly here to define the interface that should be implemented
 * by the subclasses. It also contains static methods handling generation
 * of unique ids for elements which do not have ids explicitly set.
 *
 * @category   HTML
 * @package    HTML_QuickForm2
 * @author     Alexey Borzov <avb@php.net>
 * @author     Bertrand Mansion <golgote@mamasam.com>
 * @version    Release: 0.1.0
 */
abstract class HTML_QuickForm2_Node extends HTML_Common2
{
   /**
    * Array containing the parts of element ids
    * @var array
    */
    protected static $ids = array();

   /**
    * Label(s) for the element 
    * @var string|array
    */
    protected $label = null;

   /**
    * Element's "frozen" status
    * @var boolean
    */
    protected $frozen = false;

   /**
    * Whether element's value should persist when element is frozen
    * @var boolean
    */
    protected $persistent = false;

   /**
    * Element containing current 
    * @var HTML_QuickForm2_Container
    */
    protected $container = null;

   /**
    * Contains options and data used for the element creation
    * @var  array
    */
    protected $data = null;


   /**
    * Class constructor
    *
    * @param    string  Element name
    * @param    array   Element data (options and data useful for the element creation)
    * @param    mixed   Label for the element (may be an array of labels)
    * @param    mixed   Attributes (either a string or an array)
    */
    public function __construct($name = null, $data = null, $label = null, $attributes = null)
    {
        parent::__construct($attributes);
        $this->setName($name);
        $this->setLabel($label);
        // Autogenerating the id if not set on previous steps
        if ('' == $this->getId()) {
            $this->setId();
        }
        if (!is_null($data)) {
            if (!is_array($data)) {
                throw new HTML_QuickForm2_InvalidArgumentException("Element data must be an array");
            } else {
                $this->data = $data;
            }
        }
    }


   /**
    * Generates an id for the element
    *
    * Called when an element is created without explicitly given id
    *
    * @param  string   Element name
    * @return string   The generated element id
    */
    protected static function generateId($elementName)
    {
        $tokens    =  strlen($elementName)? 
                      explode('[', str_replace(']', '', $elementName)):
                      array('qfauto');
        $container =& self::$ids;
        $id        =  '';

        do {
            $token = array_shift($tokens);
            // Handle the 'array[]' names
            if ('' === $token) {
                if (empty($container)) {
                    $token = 0;
                } else {
                    $keys  = array_keys($container);
                    $token = end($keys); 
                    while (isset($container[$token])) {
                        $token++;
                    }
                }
            }
            $id .= '-' . $token;
            if (!isset($container[$token])) {
                $container[$token] = array();
            }
            $container =& $container[$token];
        } while (!empty($tokens));

        // Append the final index
        $index = count($keys = array_keys($container))? end($keys): 0;
        while (isset($container[$index])) {
            $index++;
        }
        $container[$index] = array();
        $id .= '-' . $index;

        return substr($id, 1);
    }


   /**
    * Stores the explicitly given id to prevent duplicate id generation
    *
    * @param    string  Element id
    */
    protected static function storeId($id)
    {
        $tokens    =  explode('-', $id);
        $container =& self::$ids;

        do {
            $token = array_shift($tokens);
            if (!isset($container[$token])) {
                $container[$token] = array();
            }
            $container =& $container[$token];
        } while (!empty($tokens));
    }


   /**
    * Returns the element's type
    *
    * @return   string
    */
    abstract public function getType();


   /**
    * Returns the element's name
    *
    * @return   string
    */
    abstract public function getName();


   /**
    * Sets the element's name
    *
    * @param    string
    */
    abstract public function setName($name);


   /**
    * Returns the element's id  
    *
    * @return   string
    */
    abstract public function getId();


   /**
    * Sets the elements id
    *
    * Please note that elements should always have an id in QuickForm2 and 
    * therefore it will not be possible to remove the element's id or set it to
    * an empty value. If id is not explicitly given, it will be autogenerated. 
    *
    * @param    string  Element's id, will be autogenerated if not given
    */
    abstract public function setId($id = null);


   /**
    * Returns the element's value
    *
    * @return   mixed
    */
    abstract public function getValue();


   /**
    * Sets the element's value
    *
    * @param    mixed
    */
    abstract public function setValue($value);


   /**
    * Returns the element's label(s)
    *
    * @return   string|array
    */
    public function getLabel()
    {
        return $this->label;
    }


   /**
    * Sets the element's label(s)
    *
    * @param    string|array    Label for the element (may be an array of labels)
    */
    public function setLabel($label)
    {
        $this->label = $label;
    }


   /**
    * Changes the element's frozen status
    *
    * @param    bool    Whether the element should be frozen or editable. If
    *                   omitted, the method will not change the frozen status,
    *                   just return its current value
    * @return   bool    Old value of element's frozen status
    */
    public function toggleFrozen($freeze = null)
    {
        $old = $this->frozen;
        if (null !== $freeze) {
            $this->frozen = (bool)$freeze;
        }
        return $old;
    }


   /**
    * Changes the element's persistent freeze behaviour
    *
    * If persistent freeze is on, the element's value will be kept (and 
    * submitted) in a hidden field when the element is frozen.
    *
    * @param    bool    New value for "persistent freeze". If omitted, the 
    *                   method will not set anything, just return the current
    *                   value of the flag. 
    * @return   bool    Old value of "persistent freeze" flag 
    */
    public function persistentFreeze($persistent = null)
    {
        $old = $this->persistent;
        if (null !== $persistent) {
            $this->persistent = (bool)$persistent;
        }
        return $old;
    }


   /**
    * Adds the link to the element containing current
    * 
    * @param    HTML_QuickForm2_Container  Element containing the current one,
    *                                      null if the link should really be 
    *                                      removed (if removing from container)
    * @throws   HTML_QuickForm2_InvalidArgumentException   If trying to set a 
    *                               child of an element as its container
    */
    protected function setContainer(HTML_QuickForm2_Container $container = null)
    {
        if (null !== $container) {
            $check = $container;
            do {
                if ($this === $check) {
                    throw new HTML_QuickForm2_InvalidArgumentException(
                        'Cannot set an element or its child as its own container'
                    );
                }
            } while ($check = $check->getContainer());
            if (null !== $this->container && $container !== $this->container) {
                $this->container->removeChild($this);
            }
        }
        $this->container = $container;
        if (null !== $container) {
            $this->updateValue();
        }
    }


   /**
    * Returns the element containing current 
    *
    * @return   HTML_QuickForm2_Container|null
    */
    public function getContainer()
    {
        return $this->container;
    }

   /**
    * Returns the data sources for this element
    *
    * @return   array
    */
    protected function getDataSources()
    {
        if (empty($this->container)) {
            return array();
        } else {
            return $this->container->getDataSources();
        }
    }

   /**
    * Called when the element needs to update its value from form's data sources
    */
    abstract protected function updateValue();
}
?>
