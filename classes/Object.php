<?php
 /**
  * MySQL library
  */

	namespace MySQL;

 /**
  * Base class for representing rows within a given MySQL table as objects.
  *
  * This class is not designed to be used directly, but should be subclassed to represent a given table's data.
  *
  * @package Framework\MySQL
  * @author Daniel Farrelly <daniel@jellystyle.com>
  * @copyright 2014 Daniel Farrelly <daniel@jellystyle.com>
  * @license FreeBSD
  */

	class Object extends Result {

//
// Default table
//

	 /**
	  * The class's default table for queries
	  */

		protected static $_tables = array();

	 /**
	  * Fetch the class's default table for queries
	  */

		final public static function table() {
			$class = self::className();
			return isset( self::$_tables[$class] ) ? self::$_tables[$class] : null;
		}

	 /**
	  * Provide the class's default table for queries
	  */

		final public static function setTable( \MySQL\Table $table ) {
			$class = self::className();
			if( isset( self::$_tables[$class] ) ) {
				throw new \Exception( 'Cannot provide a second table ('.$table->name.') to the '.$class.' class.' );
			}
			self::$_tables[$class] = $table;
		}

	}
