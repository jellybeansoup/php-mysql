<?php
 /**
  * MySQL library
  */

	namespace MySQL;

 /**
  * Represents MySQL tables as objects.
  *
  * This class provides a number of methods for interacting with a MySQL database.
  *
  * @package Framework\MySQL
  * @author Daniel Farrelly <daniel@jellystyle.com>
  * @copyright 2014 Daniel Farrelly <daniel@jellystyle.com>
  * @license FreeBSD
  */

	class Table extends \Framework\Core\Object {

	 /**
	  * Array of properties that act as aliases of methods
	  *
	  * @var array
	  */

		protected static $_dynamicProperties = array(
			'database',
			'name',
			'model',
		);

//
// Creating a table object
//

	 /**
	  * Constructor magic method.
	  *
	  * @param \MySQL\Database $database
	  * @param string $table
	  * @return self
	  */

		public function __construct( \MySQL\Database $database, $name ) {
			// Validate the name
			if( ! is_string( $name ) ) {
				throw new \InvalidArgumentException;
			}
			// Store the given values
			$this->_database = $database;
			$this->_name = $name;
		}

//
// Table details
//

	 /**
	  * The database the table belongs to.
	  *
	  * @var \MySQL\Database
	  */

		private $_database = null;

	 /**
	  * Fetch the database the table belongs to.
	  *
	  * @return \MySQL\Database
	  */

		public function database() {
			return $this->_database;
		}

	 /**
	  * The name of the table.
	  *
	  * @var string
	  */

		private $_name = null;

	 /**
	  * Fetch the name of the table.
	  *
	  * @return string
	  */

		public function name() {
			return $this->_name;
		}

	 /**
	  * Fetch the name of the class used for fetching results from this table.
	  *
	  * @return string
	  */

		public function model() {
			return $this->_database->modelForTableNamed( $this->_name );
		}

//
// Querying a table
//

	 /**
	  * Insert values to the table as a new result.
	  *
	  * The values should be provided as an associative array, where keys match the names of database
	  * column names. You can also set a single value by providing the key as the first parameter and
	  * the value as the second, like so:
	  *
	  * ```
	  * $query->insert( array( 'column' => 'value' ) );
	  * ```
	  *
	  * @param array $values An associative array of values to insert.
	  * @return int The number of results updated.
	  */

		public function insert( $values ) {
			// Get the database, no point continuing without it
			if( ! ( $database = $this->database ) ) {
				throw new \DatabaseException( 'Database has not been provided.' );
			}

			// Create an array for arguments
			$arguments = array();

			// Parse the values
			if( func_num_args() == 2 && is_string( $values ) ) {
				$args = func_get_args();
				$values = array( $args[0] => $args[1] );
			}
			if( \is_assoc( $values ) ) {
				$set = array();
				foreach( $values as $key => $value ) {
					$set[] = sprintf('`%s` = ?',$key);
					$arguments[] = $value;
				}
			}

			// Prepare the SQL
			$sql = sprintf( "INSERT INTO `%s` SET %s", $this->name, implode( ', ', $set ) );

			// Prepare the statement
			$statement = $database->prepare( $sql );

			// If we fail to execute the query
			if( ! $statement->execute( $arguments ) ) {
				$info = $statement->errorInfo();
				throw new InvalidDatabaseException( $info[2], $info[1] );
			}

			// Return the number of rows deleted
			return $statement->rowCount();
		}

	 /**
	  * Create a query on this table.
	  *
	  * @return \MySQL\Query
	  */

		public function query( $conditions=null ) {
			// Create the query
			$query = new \MySQL\Query( $this );

			// Set the conditions if we have them
			if( func_num_args() > 0 ) {
				$query->callMethod( 'conditions', func_get_args() );
			}

			// Return the created query
			return $query;
		}

//
// Getting information about the table
//

	 /**
	  * Get the "CREATE TABLE" query.
	  *
	  * @return string
	  */

		private function _createTableQuery() {
			// Get the database, no point continuing without it
			if( ! ( $database = $this->database ) ) {
				throw new \DatabaseException( 'Database has not been provided.' );
			}

			// Prepare the SQL
			$sql = sprintf( "SHOW CREATE TABLE `%s`", $this->name );

			// Prepare the statement
			$results = $database->query( $sql );

			// If we don't get back a valid result, throw an exception.
			if( ! isset( $results[0] ) || ! $results[0]->hasProperty('Create Table') ) {
				throw new \Exception( 'Couldn\'t find the `Create Table` value in the query results.' );
			}

			// Return the query string.
			return $results[0]->valueOfProperty('Create Table');
		}

	 /**
	  * Get the column names used for creating the primary key.
	  *
	  * @return string
	  */

		public function primaryKey() {
			// Get the 'Create Table' query.
			$create_table = $this->_createTableQuery();

			// Try matching the primary key, and return null if none is found.
			if( ! preg_match("/PRIMARY\sKEY\s\(([^\)]+)\)/uix", $create_table, $match ) ) {
				return null;
			}

			// Default for tiny speed savings.
			if( $match[1] === '`id`' || $match[1] === 'id' ) {
				return array( 'id' );
			}

			// Turn the string of columns into an array
			$columns = explode( ',', $match[1] );

			// Trim the table names
			$columns = array_map( '\MySQL\Table::_trimTableName', $columns );

			// Return the array
			return $columns;
		}

//
// Utilities
//

	 /**
	  * Trim spaces and quotes from the extremities of the given table name.
	  *
	  * @param string $tableName The name of the table you want to trim.
	  * @return string The trimmed table name.
	  */

		private static function _trimTableName( $tableName ) {
			return trim( $tableName, "` \t\n\r\0\x0B" );
		}

	}
