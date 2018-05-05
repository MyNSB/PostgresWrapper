<?php
namespace PostgresWrapper;

require "Transaction.php";
require "Query.php";


// Base Postgres class
class Postgres {

    // Just some globals
    private $connection;
    // Tells the user if there is an active connection
    public $connected;
    // Basic config stuff
    private $host;
    private $db;
    private $port;
    private $user;
    private $password;
    private $connectionString;
    // Callback stuff
    public $lastQuery;
    public $lastResult;
    // Transaction stuff
    private $activeTransaction = false;
    /* @var Transaction */
    public $currentTransaction;



    // Constructor
    /*
     * @config KeyValueArray
     * This function just sets up our connection string and our private variables
    */
    public function __construct(array $config) {
        // Setup our vars
        $this->host     = $config["host"];
        $this->db       = $config["db"];
        $this->port     = $config["port"];
        $this->user     = $config["user"];
        $this->password = $config["password"];

        // Setup our connection string
        $this->connectionString = 'host='     . $this->host      . ' '
                                . 'port='     . $this->port      . ' '
                                . 'dbname='   . $this->db        . ' '
                                . 'user='     . $this->user      . ' '
                                . 'password=' . $this->password  . ' '
                                . "options='--client_encoding=UTF8'";
    }



    // Destructor
    /*
     *  The destructor just closes the active connection so we don't have multiple connections to our DB server, pre sure max is 27, don't quote me on that
     */
    public function __destruct() {
        // Close the active connection
        $this->close();
        try {
            unset($this->currentTransaction);
        } catch (\Exception $e) {}
    }



    // Connect
    /*
     * @forceNew boolean
     * The connect function just reads from the config string and then just connects to the
     * postgres database with that connection string
    */
    public function connect(bool $forceNew) {
        // Determine if there is an active connection
        if($this->connected) {
            // Return the current connection
            return $this->connection;
        }

        // Time to actually connect
        if ($forceNew) {
             $this->connection = pg_connect($this->connectionString, PGSQL_CONNECT_FORCE_NEW);
        } else {
            $this->connection = pg_connect($this->connectionString);
        }

        // Check if the connection worked and if it didn't then throw an error
        $connectionStatus = pg_connection_status($this->connection);
        if ($connectionStatus !== PGSQL_CONNECTION_OK) {
            pg_close($this->connection);
            throw new \Error("Could not connect to database server");
        }

        // Set our connection flag
        $this->connected = true;
        // Return our connection
        return $this->connection;
    }


    // Close
    /*
     * The close function just closes the active connection
     */
    public function close() {
        // Check if there is an open connection
        if ($this->connected) {
            // Determine if the connection is actually a connection
            if (is_resource($this->connection)) {
                $this->connected = false;
                // Close the connection
                return pg_close($this->connection);
            }
        }
        // Looks like we couldn't close it
        return false;
    }



    // Query
    /*
     * @query string
     * @params array
     * Query just takes a query string and params, it then prepares the statement to be executed
     */
    public function query(Query $query) {
        $this->lastQuery = $query;
        $query->execute($this->connection);
        $this->lastResult = $query->fetchAll();
        return $query;
    }



    // startTransaction
    /*
     * StartTransaction starts a postgres transaction, it returns a transaction object
     */
    public function startTransaction(...$queries) {
        if (!($this->activeTransaction)) {
            $this->activeTransaction = true;
            $this->currentTransaction = new Transaction($this->connectionString, $queries);
        }
        throw new \Error("Transaction is open, please close that before creating a new Transaction");
    }


    /*
     * Transaction utilities
     */
    public function transactionPushQuery(Query $query) {
        return $this->currentTransaction->pushQuery($query);
    }
    public function transactionCommit() {
        $this->currentTransaction->commit();
    }
    public function transactionRollback() {
        return $this->currentTransaction->rollback();
    }
    /*
     * End of this
     */


    // closeTransaction
    /*
     * closeCurrentTransaction just closes the currently active PostgreSQL transaction
     */
    public function closeCurrentTransaction() {
        if (!($this->activeTransaction)) {
            throw new \Error("There is no active connection");
        }

        // Set our transaction flag
        $this->activeTransaction = false;
        $this->currentTransaction->close();
        unset($this->currentTransaction);
    }


    // Getters and utility functions
    /* ====== UTILITY ====== */
    // Escape
    public function escape(\string $toEscape) {
        return $toEscape !== null ? pg_escape_string($this->connection, $toEscape) : null;
    }
    // parseJSON
    public function parseJson($json) {
        return json_decode(self::escape($json));
    }
    // prepareJSON
    public function prepareJson(array $data) {
        return self::escape(json_encode($data));
    }
    // TODO: Make this a lot more efficient
    public function prepareHStore(array $data) {
        // Check if this contains any arrays
        foreach ($data as $value) {
            if (is_array($value)) {
                throw new \Error("HStore cannot contain an array");
            }
        }
        $json = $this->prepareJson($data);
        $json = str_replace(":", "=>", $json);
        $json = str_replace("{", "", $json);
        $hstore = str_replace("}", "", $json);
        return $hstore;
    }
    public function parseHStore(string $hstore) {
        // This is an easy peasy function
        return json_decode('{' . str_replace('"=>"', '":"', $hstore) . '}', true);
    }
    /* ====== GETTERS ====== */
}