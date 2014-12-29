<?php
 /**
  * MySQL library
  */

	namespace MySQL;

	use \PDO;
	use \PDOStatement;

 /**
  * Represents data from a MySQL query as objects.
  *
  * This class provides a number of methods for interacting with a MySQL database, and is used to provide the results
  * of a query in the absence of specialised subclasses of `Framework\MySQL\Object`.
  *
  * @package Framework\MySQL
  * @author Daniel Farrelly <daniel@jellystyle.com>
  * @copyright 2014 Daniel Farrelly <daniel@jellystyle.com>
  * @license FreeBSD
  */

	class Result extends \Framework\Core\Object {

	 /**
	  * Collection for storing the object's property values
	  *
	  * @var array
	  */

		private $_values = array();

	 /**
	  * Flag indicating whether the object has been constructed or not
	  *
	  * @var bool
	  */

		private $_constructed = false;

	 /**
	  * Collection for storing the object's property values
	  *
	  * @var array
	  */

		public function __construct() {
			$this->_constructed = true;
		}

	 /**
	  * The object as an array structure.
	  *
	  * @return array
	  */

		public function asArray() {
			return (array) $this->_values;
		}

	 /**
	  * Flag indicating if the object has the named property (true) or not (false).
	  *
	  * @param $property string The name of the property.
	  * @return bool Flag indicating if the object has the named property.
	  */

		public function hasProperty( $property ) {
			return ( parent::hasProperty( $property ) || isset( $this->_values[$property] ) );
		}

	 /**
	  * Flag indicating if the named property is mutable (true) or not (false).
	  *
	  * @param $property string The name of the property.
	  * @return bool Flag indicating if the property is mutable.
	  */

		public function propertyIsMutable( $property ) {
			return ( parent::propertyIsMutable( $property ) || ! $this->_constructed );
		}

	 /**
	  * Flag indicating if the named property is public (true) or not (false).
	  *
	  * @param $property string The name of the property.
	  * @return bool Flag indicating if the property is public.
	  */

		public function propertyIsPublic( $property ) {
			return ( parent::propertyIsPublic( $property ) || isset( $this->_values[$property] ) );
		}

	 /**
	  * Fetch the value of a given property.
	  *
	  * @param $property string The name of the property.
	  * @return mixed The value for the property. Null if the property does not exist.
	  */

		public function valueOfProperty( $property ) {
			// If the property exists, set the value.
			if( $this->propertyIsPublic( $property ) ) {
				return $this->_values[$property];
			}
			// Default to null
			return null;
		}

	 /**
	  * Set the value of a given property.
	  *
	  * @param $property string The name of the property.
	  * @param $value mixed The value to give the property.
	  * @return void
	  */

		public function setValueOfProperty( $property, $value ) {
			// If the property exists, set the value.
			if( $this->propertyIsMutable( $property ) ) {
				$this->_values[$property] = $value;
			}
		}

	}
