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

  	class Query extends \Framework\Core\Object implements \Iterator {

	 /**
	  * Array of properties that act as aliases of methods
	  *
	  * @var array
	  */

		protected static $_dynamicProperties = array(
			'table',
			'options',
			'objects',
		);

	 /**
	  * Convert the object to an array.
	  *
	  * @return array The properties of the object as an array.
	  */

		public function asArray() {
			return $this->objects;
		}

//
// Query details
//

	 /**
	  * The database table where the results are stored.
	  *
	  * @var \MySQL\Table
	  */

		private $_table = null;

	 /**
	  * Fetch the database table where the results are stored.
	  *
	  * @return \MySQL\Table The database table.
	  */

	  	public function table() {
	  		return $this->_table;
	  	}

	 /**
	  * The query options.
	  *
	  * @var array
	  */

		private $_options = array();

	 /**
	  * Fetch the query options.
	  *
	  * @return array The options for this query
	  */

	  	public function options() {
	  		return $this->_options;
	  	}

	 /**
	  *
	  *
	  * @param \MySQL\Database $database
	  * @param string $table
	  * @return self
	  */

		public function __construct( $database, $table=null ) {
			// If we're given a table object
			if( $database instanceof \MySQL\Table ) {
				$this->_table = $database;
			}
			// If we're given a database and a table name
			else if( $database instanceof \MySQL\Database && is_string( $table ) ) {
				$this->_table = $database->table( $table );
			}
		}

//
// Query building
//

	 /**
	  * Filter the query results.
	  *
	  * @param array $conditions
	  * @return self
	  */

		public function conditions( $conditions ) {
			// Not an integer
			if( ! is_string( $conditions ) && ! is_array( $conditions ) && $conditions !== null ) {
				throw new \InvalidArgumentException;
			}
			// Remove the conditions
			if( $conditions == null ) {
				unset( $this->_options['where'] );
				// Return the query for chaining
				return $this;
			}
			// We've got some simple conditions
			if( is_string( $conditions ) ) {
				$conditions = func_get_args();
			}
			// Set the conditions
			$this->_options['where'] = $conditions;
			// Return the query for chaining
			return $this;
		}

	 /**
	  * Alias for the `conditions` method.
	  *
	  * @see `\MySQL\Query::conditions`
	  * @return self
	  */

		public function where( $conditions ) {
			return $this->conditions( $conditions );
		}

	 /**
	  * Group the query results by the given columns.
	  *
	  * Optionally you can provided conditions for grouping by the provided columns.
	  *
	  * @param array $key
	  * @param array $conditions
	  * @return self
	  */

		public function groupBy( $key, $conditions=null ) {
			// Not an integer
			if( ! is_string( $key ) && ! is_array( $key ) && $key !== null ) {
				throw new \InvalidArgumentException;
			}
			// Remove the grouping options
			if( $key == null ) {
				unset( $this->_options['group'], $this->_options['having'] );
				// Return the query for chaining
				return $this;
			}
			// Create an array with a single key
			else if( is_string( $key ) ) {
				$key = array( $key );
			}


			// TODO: Add validation of keys here


			// Set the sorting options
			$this->_options['group'] = $key;
			// Set the conditions
			if( is_array( $conditions ) ) {
				$this->_options['having'] = $conditions;
			}
			// Return the query for chaining
			return $this;
		}

	 /**
	  * Alias for the `groupBy` method.
	  *
	  * @see `\MySQL\Query::groupBy`
	  * @return self
	  */

		public function group( $key, $conditions=null ) {
			return $this->groupBy( $key, $conditions );
		}

	 /**
	  * Sort the results according to the given columns.
	  *
	  * @param array $sort
	  * @param string $direction
	  * @return self
	  */

		public function sortBy( $key, $direction='ASC' ) {
			// Not an integer
			if( ! is_string( $key ) && ! is_array( $key ) && $key !== null ) {
				throw new \InvalidArgumentException;
			}
			// Remove the sorting options
			if( $key == null ) {
				unset( $this->_options['order'] );
				// Return the query for chaining
				return $this;
			}
			// Create an array with a single key
			else if( is_string( $key ) ) {
				$key = array( $key => $direction );
			}
			// Do some basic validation
			$order = array();
			foreach( $key as $column => $direction ) {
				// Direction not provided
				if( is_numeric( $column ) ) {
					$column = $direction;
					$direction = 'ASC';
				}
				// Direction given
				else if( strtoupper( $direction ) !== 'ASC' && strtoupper( $direction ) !== 'DESC' ) {
					$direction = 'ASC';
				}
				// Update the value
				$order[$column] = strtoupper( $direction );
			}
			// Set the sorting options
			$this->_options['order'] = $order;
			// Return the query for chaining
			return $this;
		}

	 /**
	  * Alias for the `sortBy` method.
	  *
	  * @see `\MySQL\Query::sortBy`
	  * @return self
	  */

		public function sort( $key, $direction=null ) {
			return $this->sortBy( $key, $direction );
		}

	 /**
	  * Limit the number of results fetched.
	  *
	  * @param int $limit
	  * @return self
	  */

		public function limit( $limit ) {
			// Not an integer
			if( ! is_integer( $limit ) && $limit !== null ) {
				throw new \InvalidArgumentException;
			}
			// Remove the limit
			if( $limit == null || $limit <= 0 ) {
				unset( $this->_options['limit'] );
			}
			// Set the limit
			else {
				$this->_options['limit'] = $limit;
			}
			// Return the query for chaining
			return $this;
		}

	 /**
	  * Offset the results fetched by the given amount.
	  *
	  * @param int $offset
	  * @return self
	  */

		public function offset( $offset ) {
			// Not an integer
			if( ! is_integer( $offset ) && $offset !== null ) {
				throw new \InvalidArgumentException;
			}
			// Remove the offset
			if( $offset == null || $offset <= 0 ) {
				unset( $this->_options['offset'] );
			}
			// Set the offset
			else {
				$this->_options['offset'] = $offset;
			}
			// Return the query for chaining
			return $this;
		}

//
// Executing
//

	 /**
	  * Fetch an array of the results matching the current query.
	  *
	  * You can optionally specify a set of keys which match database columns. These keys can also
	  *	alias a given column by providing the alias as the array key. For example:
	  *
	  * ```
	  * $query->fetch( array( 'column', 'alias' => 'column' ) );
	  * ```
	  *
	  * This will fetch a results array containing two items containing the value of the DB column
	  * named "column". One will have the key matching the column's name, the other will have the
	  * provided key:
	  *
	  * ```
	  * array(
	  *		'column' => 'value',
	  * 	'alias' => 'value'
	  * );
	  * ```
	  *
	  * @param array $keys An array of keys (columns) to fetch from the current query's results.
	  * 	Defaults to null, which will return the entire result object.
	  * @return array
	  */

		public function fetch( $keys=null ) {
			// If we have invalid clauses, we throw an exception
			$clauses = array( 'where', 'group', 'having', 'order', 'limit', 'offset' );
			if( count( $diff = array_diff_key( $this->_options, array_flip( $clauses ) ) ) ) {
				throw new \Exception;
			}
			// Filter the fetched keys
			$columns = '*';
			if( is_string( $keys ) ) {
				$keys = array( $keys );
			}
			if( is_array( $keys ) ) {
				$columns_array = array();
				foreach( $keys as $alias => $equation ) {
					// The value is a table name
					if( preg_match('/^[\w\d$_]+$/i',$equation) ) {
						$equation = '`'.$equation.'`';
					}
					// The value is an equation
					else {
						$equation = '( '.$equation.' )';
					}
					// Prepare the alias
					if( is_string( $alias ) ) {
						$equation = sprintf('%s AS `%s`',$equation,$alias);
					}
					// Add to the array
					$columns_array[] = $equation;
				}
				$columns = implode( ', ', $columns_array );
			}
			// Prepare the SQL
			$sql = sprintf( "SELECT %s FROM `%s`", $columns, $this->_table->name );
			$sql .= $this->_parseOptions( $clauses, $arguments );
			// Execute the SQL
			$statement = $this->_executeSQL( $sql, $arguments );
			// Fetching the objects
			if( ! is_array( $keys ) ) {
				$this->_results = $statement->fetchAll( \PDO::FETCH_CLASS, $this->table->model );
			}
			// Fetching specific columns
			else {
				$this->_results = $statement->fetchAll( \PDO::FETCH_CLASS, 'MySQL\\Result' );
			}
			// Return the results
			return $this->_results;
		}

	 /**
	  * Alias for the `fetch` method.
	  *
	  * Returns an array of objects.
	  *
	  * @see `\MySQL\Query::groupBy`
	  * @return self
	  */

		public function objects() {
			return $this->fetch( null );
		}

	 /**
	  * Count the results matching the current query.
	  *
	  * @return int
	  */

		public function count() {
			// Count the existing results
			if( ! empty( $this->_results ) ) {
				return count( $this->_results );
			}
			// If we have invalid clauses, we throw an exception
			$clauses = array( 'where', 'group', 'having', 'order', 'limit', 'offset' );
			if( count( $diff = array_diff_key( $this->_options, array_flip( $clauses ) ) ) ) {
				throw new \Exception;
			}
			// Prepare the SQL
			$sql = sprintf( "SELECT COUNT(*) FROM `%s`", $this->_table->name );
			$sql .= $this->_parseOptions( $clauses, $arguments );
			// Execute the SQL
			$statement = $this->_executeSQL( $sql, $arguments );
			// Fetching the objects
			return intval( $statement->fetchColumn() );
		}

	 /**
	  * Update the results matching the current query.
	  *
	  * The values should be provided as an associative array, where keys match the names of database
	  * column names. You can also set a single value by providing the key as the first parameter and
	  * the value as the second, like so:
	  *
	  * ```
	  * $query->fetch( 'column', 'value' );
	  * ```
	  *
	  * @param array $values An associative array of values to set on the current query's results.
	  * @return int The number of results updated.
	  */

		public function set( $values ) {
			// If we have invalid clauses, we throw an exception
			$clauses = array( 'where', 'order', 'limit', 'offset' );
			if( count( $diff = array_diff_key( $this->_options, array_flip( $clauses ) ) ) ) {
				throw new \Exception;
			}
			// Clear the current results
			$this->_results = array();
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
			$sql = sprintf( "UPDATE `%s` SET %s", $this->_table->name, implode( ', ', $set ) );
			$sql .= $this->_parseOptions( $clauses, $arguments );
			// Execute the SQL
			$statement = $this->_executeSQL( $sql, $arguments );
			// Return the number of rows deleted
			return $statement->rowCount();
		}

	 /**
	  * Delete the results matching the current query.
	  *
	  * @return int The number of results deleted.
	  */

		public function delete() {
			// If we have invalid clauses, we throw an exception
			$clauses = array( 'where', 'order', 'limit', 'offset' );
			if( count( $diff = array_diff_key( $this->_options, array_flip( $clauses ) ) ) ) {
				throw new \Exception;
			}
			// Clear the current results
			$this->_results = array();
			// Prepare the SQL
			$sql = sprintf( "DELETE FROM `%s`", $this->_table->name );
			$sql .= $this->_parseOptions( $clauses, $arguments );
			// Execute the SQL
			$statement = $this->_executeSQL( $sql, $arguments );
			// Return the number of rows deleted
			return $statement->rowCount();
		}

	 /**
	  * Delete the results matching the current query.
	  *
	  * @return int The number of results deleted.
	  */

		private function _executeSQL( $sql, $arguments ) {
			// Get the database, no point continuing without it
			if( ! $this->table || ! ( $database = $this->table->database ) ) {
				throw new \Exception;
			}
			// Prepare the statement
			$statement = $database->prepare( $sql );
			// If we fail to execute the query
			if( ! $statement->execute( $arguments ) ) {
				$info = $statement->errorInfo();
			  	throw new InvalidDatabaseException( $info[2], $info[1] );
			}
			// Return the statement
			return $statement;
		}

//
// Iterator
//

	 /**
	  * Storage for rows fetched from the database.
	  *
	  * This property acts as a cache while iterating over the results.
	  *
	  * @var array
	  */

		private $_results = null;

	 /**
	  * Rewind the array pointer to the first result.
	  *
	  * @return mixed
	  */

		public function rewind() {
			// Fetch new results.
			$this->fetch();
			// Return the value
			return reset( $this->_results );
		}

	 /**
	  * Alias for the `rewind` method.
	  *
	  * @see `\MySQL\Query::rewind`
	  * @return mixed
	  */

		public function first() {
			return $this->rewind();
		}

	 /**
	  *
	  *
	  * @return void
	  */

		public function next() {
			// If we don't have results, get them.
			if( $this->_results === null ) {
				$this->fetch();
			}
			// Return the value
			return next( $this->_results );
		}

	 /**
	  * Determine if the current result is valid or not.
	  *
	  * @return boolean
	  */

		public function valid() {
			// If we don't have results, return null.
			if( $this->_results === null ) {
				return false;
			}
			// Return the current object
			return ( key( $this->_results ) !== null );
		}

	 /**
	  * Get the current result.
	  *
	  * @return mixed
	  */

		public function current() {
			// If we don't have results, return null.
			if( $this->_results === null ) {
				return null;
			}
			// Return the current object
			return current( $this->_results );
		}

	 /**
	  * Get the key (the index) of the current result.
	  *
	  * @return scalar
	  */

		public function key() {
			// If we don't have results, return null.
			if( $this->_results === null ) {
				return null;
			}
			// Return the current object's key
			return key( $this->_results );
		}

//
// Utilities
//

	 /**
	  * Pluck the .
	  *
	  * @return scalar
	  */

		public function pluck( $key ) {
			// If we don't have results, get them.
			if( $this->_results === null ) {
				$this->fetch();
			}
			// Get a copy of the results
		  	$objects = array_values( $this->_results );
		  	// Walk through the array and replace them with the plucked value
		  	array_walk($objects,function(&$value) use($key) {
			  	$value = $value->$key;
		  	});
		  	// Return a unique array
		  	return array_values( array_unique( $objects, SORT_REGULAR ) );
		}

//
// Parsing options
//

	 /**
	  * Parse the query options into a string.
	  *
	  * @param array $keys An array of the options keys to include in the parsed string.
	  * @param array $arguments An array for collecting arguments found in the given conditions array.
	  * 	When the method has returned, the array (passed by reference) will have been updated with
	  *		all of the arguments found within the conditions array.
	  * @return string The parsed options.
	  */

		private function _parseOptions( $keys, &$arguments=null ) {
			// Get the options
			$options = $this->_options;
			// Prepare the arguments
			if( ! is_array( $arguments ) ) {
				$arguments = array();
			}
			// Given options is not an array, or has no contents
	   		if( ! is_array( $options ) || empty( $options ) || ! is_array( $keys ) || empty( $keys ) ) {
	   			return array();
	   		}
			// Go through the clauses in order, and combine the query together
			$query = array();
			foreach( $keys as $key ) {
				// Conditions (WHERE)
				if( $key === 'where' && isset( $options['where'] ) ) {
					$query[] = self::_parseConditions( $options['where'], $arguments, 'WHERE' );
				}
				// Grouping (GROUP BY)
				else if( $key === 'group' && isset( $options['group'] ) ) {
					$query[] = self::_parseModifiers( $options['group'], 'GROUP BY' );
				}
				// Conditions (HAVING)
				else if( $key === 'having' && isset( $options['having'] ) ) {
					$query[] = self::_parseConditions( $options['having'], $arguments, 'HAVING' );
				}
				// Sorting (ORDER BY)
				else if( $key === 'order' && isset( $options['order'] ) ) {
					$query[] = self::_parseModifiers( $options['order'], 'ORDER BY' );
				}
				// Limits and Offset (LIMIT)
				else if( $key === 'limit' && isset( $options['limit'] ) ) {
					// If we have an offset
					if( isset( $options['offset'] ) ) {
						$query[] = sprintf( 'LIMIT %s, %s', $options['offset'], $options['limit'] );
					}
					// If there is no offset provided
					else {
						$query[] = sprintf( 'LIMIT %s', $options['limit'] );
					}
				}
			}
			// Return as a string
			return ' '.implode( ' ', $query );
		}

	 /**
	  * Parse conditions into a valid SQL clause.
	  *
	  * @param array $conditions A conditions array.
	  * @param array $arguments An array for collecting arguments found in the given conditions array.
	  * 	When the method has returned, the array (passed by reference) will have been updated with
	  *		all of the arguments found within the conditions array.
	  * @param string $clause The type of SQL clause to generate, defaults to ORDER BY
	  * @return string The given conditions as a valid SQL clause.
	  */

		private static function _parseConditions( $conditions, &$arguments=array(), $clause='WHERE' ) {
			// With a string
			if( is_string( $conditions ) ) {
				return sprintf( '%s %s', $clause, $conditions );
			}
			// With an array
			elseif( is_array( $conditions ) ) {
				return $clause.' '.self::_traverseConditions( $conditions, $arguments );
			}
		}

	 /**
	  * Traverse the conditions array to generate a valid SQL clause.
	  *
	  * @param array $conditions A conditions array.
	  * @param array $arguments An array for collecting arguments found in the given conditions array.
	  * 	When the method has returned, the array (passed by reference) will have been updated with
	  *		all of the arguments found within the conditions array.
	  * @return string The given conditions as a valid SQL clause.
	  */

		private static function _traverseConditions( $conditions, &$arguments ) {
			// If the first item is a string, we have a condition
			if( isset( $conditions[0] ) && is_string( $conditions[0] ) ) {
				$args = $conditions;
				$conditions = array_shift( $args );
				// Prepare the arguments
				foreach( $args as $i => $arg ) {
				  	// Uh oh! An array!
				  	if( is_array( $arg ) ) {
					  	// Create some placeholders and stick them into the conditions
					  	$placeholders = implode( ',', array_fill( 0, count($arg), '?' ) );
					  	// Find the placeholder to replace
					  	$pos = -1;
					  	for( $x = 0; $x < $i; $x++ ) {
						  	$pos = strpos( $conditions, '?', $pos+1 );
					  	}
					  	// Insert our placeholder
					  	$conditions = substr_replace( $conditions, '('.$placeholders.')', $pos, 1 );
					  	// Add all the arguments
					  	$arguments = array_merge( $arguments, $arg );
					  	// Move on.
					  	continue;
				  	}
				  	// Add this argument IT'S TOTALLY SAFE YOU GUYS
				  	$arguments[] = $arg;
				}
				// Return the conditions
				return $conditions;
			}
			// Otherwise we have a group of conditions
			else {
				$x = 0;
				$results = '';
				foreach( $conditions as $i => $subarray ) {
					if( $x++ !== 0 ) {
						if( is_numeric( $i ) ) {
							$results .= ' && ';
						}
						elseif( $i == 'or' ) {
							$results .= ' || ';
						}
						elseif( $i == 'xor' ) {
							$results .= ' XOR ';
						}
					}
					$results .= self::_traverseConditions( $subarray, $arguments );
				}
				return '( '.$results.' )';
			}
		}

	 /**
	  * Parse modifiers into a valid SQL clause.
	  *
	  * @param mixed $modifiers
	  * @param string $clause The type of SQL clause to generate, defaults to ORDER BY
	  * @return string The SQL clause
	  */

		private static function _parseModifiers( $modifiers, $clause='ORDER BY' ) {
			// With an array
			foreach( $modifiers as $key => $order ) {
				$modifiers[$key] = sprintf( '`%s` %s', $key, $order );
			}
			// Return the parsed modifiers
			return sprintf( '%s %s', $clause, implode( ', ', $modifiers ) );
		}

	}