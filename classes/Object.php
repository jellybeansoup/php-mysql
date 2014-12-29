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
// Creating the object
//

	 /**
	  * Flag indicating if the object is pulled from the database or not.
	  */

		private $_new = true;

	 /**
	  * Collection for storing the object's property values
	  *
	  * @var array
	  */

		public function __construct() {
			// If we've started with values, the object is not "new".
			if( count( parent::asArray() ) !== 0 ) {
				$this->_new = false;
			}
		}

//
// Default table
//

	 /**
	  * The class's default table for queries
	  */

		protected static $_tables = array();

	 /**
	  * Fetch the class's default table for queries
	  *
	  * @return \MySQL\Table The table that this object is part of.
	  */

		final public static function table() {
			$class = self::className();
			return isset( self::$_tables[$class] ) ? self::$_tables[$class] : null;
		}

	 /**
	  * Provide the class's default table for queries
	  *
	  * @param \MySQL\Table $table The table that this object is part of.
	  * @return void
	  */

		final public static function setTable( \MySQL\Table $table ) {
			$class = self::className();
			if( isset( self::$_tables[$class] ) ) {
				throw new \Exception( 'Cannot provide a second table ('.$table->name.') to the '.$class.' class.' );
			}
			self::$_tables[$class] = $table;
		}

//
// Operations
//

	 /**
	  * Array of property names used as a primary key to identify the object in the database.
	  *
	  * e.g. `array( 'name', 'date' )`
	  *
	  * If null, the primary keys will be automatically determined from the database. This is handy, but can be costly,
	  * so it is suggested that you specify this property when defining your subclass.
	  *
	  * @var array
	  */

		protected static $_primaryKey = null;

	 /**
	  * Gets the primary key for the reciever.
	  *
	  * @param bool $include_values If true, the array keys represent the column name and the value is the column value. If false, just the column names are returned.
	  * @return \MySQL\Query A query used to match the current object. If the object is not in the database, returns null.
	  */

		public function primaryKey( $include_values=true ) {
			// Determine the primary key if we haven't got it already.
			if( self::$_primaryKey === null ) {
				self::$_primaryKey = self::table()->primaryKey();
			}

			// Just the keys, thanks.
			if( ! $include_values ) {
				return self::$_primaryKey;
			}

			// No primary key for this table
			if( count( self::$_primaryKey ) == 0 ) {
				return null;
			}

			// Determine the values
			$primary_key = array_flip( self::$_primaryKey );
			foreach( $primary_key as $property_name => $value ) {
				$primary_key[$property_name] = $this->valueOfProperty( $property_name );
			}

			// Return the array of values
			return $primary_key;
		}

//
// Operations
//

	 /**
	  * Creates a query for matching the reciever in the database.
	  *
	  * @return \MySQL\Query A query used to match the current object. If the object is not in the database, returns null.
	  */

		final private function _selfQuery() {
			return self::table()->query( $this->primaryKey() );
		}

	 /**
	  * Saves the object to the database, inserting and getting the id as necessary.
	  *
	  * Will call the methods `beforeSaving` and `afterSaving` on the reciever before and after it
	  * is saved, respectively. The `afterDeleting` method is provided with one argument, a boolean
	  * flag indicating the success (true) or failure (false) of the save.
	  *
	  * @return bool Flag indicating if the object was saved successfully.
	  */

		final public function save() {
			// Call the "before saving" method, if it exists.
			if( $this->hasMethod( 'beforeSaving' ) ) {
				$this->beforeSaving();
			}

			// Result is defaulted to false
			$success = false;

			// If the object is new, we insert
			if( $this->_new ) {
				// Try inserting the data
				$success = self::table()->insert( parent::asArray() );

				// Apply the incremented primary key
				$primary_key = $this->primaryKey( false );
				if( count( $primary_key ) === 1 && ( $insert_id = self::table()->database()->lastInsertId() ) !== 0 ) {
					$this->setValueOfProperty( $primary_key[0], $insert_id );
				}

				// Not new any more.
				$this->_new = ! boolval( $success );
			}

			// Otherwise we update
			else if( ( $query = $this->_selfQuery() ) !== null ) {
				$success = $query->update( parent::asArray() );
			}

			// Call the "after saving" method, if it exists.
			if( $this->hasMethod( 'afterSaving' ) ) {
				$this->afterSaving( $success );
			}

			// Return
			return boolval( $success );
		}

	 /**
	  * Delete the object from the database.
	  *
	  * Will call the methods `beforeDeleting` and `afterDeleting` on the reciever before and after
	  * it is deleted, respectively. The `afterDeleting` method is provided with one argument, a boolean
	  * flag indicating the success (true) or failure (false) of the deletion.
	  *
	  * @return bool Flag indicating if the object was deleted successfully.
	  */

		final public function delete() {
			// Build a query to match the current object
			if( ( $query = $this->_selfQuery() ) === null ) {
				return false;
			}

			// Call the "before deleting" method, if it exists.
			if( $this->hasMethod( 'beforeDeleting' ) ) {
				$this->beforeDeleting();
			}

			// Perform the deletion using the built query
			$success = $query->delete();

			// Call the "after deleting" method, if it exists.
			if( $this->hasMethod( 'afterDeleting' ) ) {
				$this->afterDeleting( $success );
			}

			// Return the result of our deletion.
			return boolval( $success );
		}

	}
