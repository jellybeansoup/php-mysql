<?php
 /**
  * MySQL library
  */

	namespace MySQL;

	use \PDO;
	use \PDOStatement;

 /**
  * Represents MySQL databases as objects.
  *
  * This class provides a number of methods for interacting with a MySQL database.
  *
  * @package Framework\MySQL
  * @author Daniel Farrelly <daniel@jellystyle.com>
  * @copyright 2014 Daniel Farrelly <daniel@jellystyle.com>
  * @license FreeBSD
  */

  	class Database extends \Framework\Core\Object {

	 /**
	  * Dynamic properties
	  *
	  * @see Framework\Core\Object::$_dynamicProperties
	  * @var array
	  */

		protected static $_dynamicProperties = array(
			'server',
			'name',
		);

//
// Database details
//

	 /**
	  * The server on which the database is hosted.
	  *
	  * @var \Framework\Core\URL
	  */

	  	private $_server = null;

	 /**
	  * Fetch the address for the server.
	  *
	  * @return \Framework\Core\URL URL representing the server address. Does *not* include the
	  *   database name.
	  */

	  	public function server() {
	  		return $this->_server;
	  	}

	 /**
	  * The name of the database.
	  *
	  * @var string
	  */

	  	private $_name = null;

	 /**
	  * Fetch the name of the database.
	  *
	  * @return string
	  */

	  	public function name() {
	  		return $this->_name;
	  	}

	 /**
	  * The connection used by this database for making MySQL queries.
	  *
	  * @var array
	  */

	  	private $_connection = null;

//
// Factory
//

	 /**
	  * Storage for connected databases
	  *
	  * @var array
	  */

	  	private static $_instances = array();

	 /**
	  * Private constructor
	  *
	  * @param \Framework\Core\URL $database A URL with the details required for connecting to the
	  *   desired MySQL database. See `connect` for more information.
	  * @return self
	  */

	  	private function __construct( \Framework\Core\URL $database ) {
		  	// Store the database name
		  	$this->_name = $database->path->lastComponent;
		  	// Store the server URL
		  	$database->path = $database->path->pathByDeletingLastComponent();
		  	$this->_server = $database;
		  	// Let's try and connect to the database
		  	try {
				$dsn = 'mysql:host='.$this->_server->host.';dbname='.$this->_name;
			  	$this->_connection = new \PDO( $dsn, $this->_server->user, $this->_server->password );
		  	}
		  	// Rethrow as an InvalidDatabaseException.
		  	catch( \PDOException $e ) {
			  	throw new InvalidDatabaseException( $e->getMessage(), $e->getCode() );
			}
	  	}

	 /**
	  * Connect to a database
	  *
	  * @param \Framework\Core\URL $database A URL with the details required for connecting to the
	  *   desired MySQL database. The URL should contain both the host address for the server, as
	  *   well as the username and password required to connect.
	  *
	  *   ```php
	  *   $url = url( 'mysql://username:password@localhost/database_name' );
	  *   $db = \MySQL\Database::connect( $url );
	  *   ```
	  *
	  *	  The `mysql` scheme is not required, but is strongly suggested, as it will provide context
	  *   if used outside of the class.
	  * @return self
	  */

	  	public static function connect( \Framework\Core\URL $database ) {
	  		// Get the URL's hash
	  		$hash = $database->hash();
	  		// If we have an instance already
	  		if( isset( self::$_instances[$hash] ) && self::$_instances[$hash] instanceof self ) {
		  		return self::$_instances[$hash];
	  		}
	  		// Create and return a new instance
	  		return self::$_instances[$hash] = new self( $database );
	  	}

//
// Table objects
//

	 /**
	  * The stored collection of registered models.
	  *
	  * @var array
	  */

	  	private $_models = array();

	 /**
	  * Register a collection of classes that represent row data as objects.
	  *
	  * Each element in the given array should respond to one table and one class, with the table name as
	  * the key, and the full class name (including the relevant namespace) as the value.
	  *
	  * ```php
	  * $db->registerModels(array(
	  * 	'users' => 'Example\User',
	  * ));
	  * ```
	  *
	  * @param array $models A collection of models to register. Results of a `SELECT` query on a table listed
	  *   will automatically be passed back as objects of the registered class.
	  * @return void
	  */

	  	public function registerModels( $models ) {
	  		// Not a legit array
	  		if( $models === null || ! is_array( $models ) ) {
		  		throw new \InvalidArgumentException;
	  		}
		  	// Let's ensure we don't have any weird things in the given array
		  	foreach( $models as $table => $class ) {
			  	if( ! is_string( $table ) || ! is_string( $class ) ) {
				  	unset( $models[$table] );
			  	}
		  	}
		  	// Merge the given models with the stored models
		  	$this->_models = array_merge( $this->_models, $models );
	  	}

	 /**
	  * Fetch the name of the model for the given table name.
	  *
	  * @param string $tableName The name of the table you want a model for.
	  * @var string
	  */

	  	public function modelForTableNamed( $tableName ) {
	  		// Not a legit table name
	  		if( ! is_string( $tableName ) ) {
		  		throw new \InvalidArgumentException;
	  		}
		  	// Let's ensure we don't have any weird things in the given array
		  	if( isset( $this->_models[$tableName] ) ) {
		  		return $this->_models[$tableName];
		  	}
		  	// Default to the basic model class
		  	return 'MySQL\\Object';
	  	}

//
// Querying the database
//

	 /**
	  *
	  *
	  * @param string $query
	  * @return PDOStatement
	  */

		public function prepare( $query ) {
			return $this->_connection->prepare( $query );
		}

	 /**
	  *
	  *
	  * @param \MySQL\Query $query
	  * @return mixed
	  */

		public function query( $query, $vars=null, $class='MySQL\\Object' ) {
			// Prepare the statement
			$statement = $this->prepare( $query );
			// If we fail to execute the query
			if( ! $statement->execute( $vars ) ) {
				$info = $statement->errorInfo();
			  	throw new InvalidDatabaseException( $info[2], $info[1] );
			}
			// We're going to parse the query
			$scanner = new \Framework\Core\Scanner( $query );
			$scanner->scanUpToCharactersIntoString( ' \t\n\r\0\x0B', $type );
			$type = strtolower( $type );
			// SELECT
			if( $type == 'select'  ) {
				// Find a class for the main table
				if( $class === 'MySQL\\Object' ) {
					$name_syntax = "([\w_\-]+|`[\w_\-]+`)";
					$name_alias = "{$name_syntax}(\s+AS\s+{$name_syntax})?";
					$tables = array();
					// Get the tables in the regular style
					$regex = "/(FROM|JOIN)\s+{$name_alias}/uism";
					if( preg_match_all( $regex, $query, $matches, PREG_SET_ORDER ) ) {
						foreach( $matches as $match ) {
							if( isset( $match[2] ) ) {
								$alias = $this->trim_table_name( ( isset( $match[4] ) && strlen( $match[4] ) > 0 ) ? $match[4] : $match[2] );
								$tables[$alias] = trim( $match[2], "` \t\n\r\0\x0B" );
							}
						}
					}
					// Get the tables in the compacted style
					$regex = "/JOIN\s+\(\s*({$name_alias}((\s*,\s*|\s+CROSS\s+JOIN\s+){$name_alias})+)\s*\)/uism";
					if( preg_match( $regex, $query, $match ) && isset( $match[1] ) ) {
						if( strpos( $match[1], ',' ) !== false ) {
							$names = explode( ',', $match[1] );
							foreach( $names as $segment ) {
								if( preg_match( "/{$name_alias}/uism", $segment, $match ) ) {
									$alias = $this->trim_table_name( ( isset( $match[3] ) && strlen( $match[3] ) > 0 ) ? $match[3] : $match[1] );
									$tables[$alias] = trim( $match[1], "` \t\n\r\0\x0B" );
								}
							}
						}
						else {
							$alias = trim_table_name( ( isset( $match[4] ) && strlen( $match[4] ) > 0 ) ? $match[4] : $match[2] );
							$tables[$alias] = trim( $match[2], "` \t\n\r\0\x0B" );
						}
					}
					// Go through and find a matching class
					if( ( $main_table = reset( $tables ) ) ) {
						foreach( $this->_models as $model_table => $model_class ) {
							if( $main_table === $model_table ) {
								$class = $model_class;
								break;
							}
						}
					}
				}
				// Perform the fetch and return the results
				return $statement->fetchAll( PDO::FETCH_CLASS, $class );
			}
			// DELETE, INSERT, OR UPDATE
			else if( $type == 'delete' || $type == 'insert' || $type == 'update' ) {
				return $statement->rowCount();
			}
			// For all other queries
			else {
				return $statement->fetchAll( PDO::FETCH_CLASS, 'MySQL\\Result' );
			}
			return $type;
		}

//
// Tables
//

	 /**
	  * Fetch a collection of the tables in this database.
	  *
	  * @return array A collection of `\MySQL\Table` objects.
	  */

	  	public function tables() {
	  	}

	 /**
	  * Get the table matching the given name.
	  *
	  * @param string $tableName The name of the table you want.
	  * @return \MySQL\Table
	  */

	  	public function table( $tableName ) {
	  		// Not a legit table name
	  		if( ! is_string( $tableName ) ) {
		  		throw new \InvalidArgumentException;
	  		}
	  		// Return a table object
		  	return new \MySQL\Table( $this, $tableName );
	  	}

//
// Utilities
//

	 /**
	  * Trim spaces and quotes from the extremities of the given table name.
	  *
	  * @param string $table_name The name of the table you want to trim.
	  * @return string The trimmed table name.
	  */

		private function trim_table_name( $table_name ) {
			return trim( $table_name, "` \t\n\r\0\x0B" );
		}

	}

 /**
  * Class Manager Exception
  */

  	class DatabaseException extends \Exception {
  	}

 /**
  * Class Manager Exception
  */

  	class InvalidDatabaseException extends DatabaseException {
  	}
