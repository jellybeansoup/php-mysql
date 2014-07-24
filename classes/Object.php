<?php
 /**
  * MySQL library
  */

	namespace MySQL;

 /**
  * Represents MySQL data as objects.
  *
  * This class provides a number of methods for interacting with a MySQL database.
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