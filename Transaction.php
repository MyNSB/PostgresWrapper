<?php
/**
 * User: varun
 * Date: 5/05/2018
 * Time: 1:08 PM
 */

namespace PostgresWrapper;
require "Query.php";


class Transaction {
    private $queries;
    private $connection;


    // Constructor
    /*
     * The constructor just sets everything up for us
     * @connection pg_connection
     * @queries list of Query objects
     */
    public function __construct($connectionString, Query ...$queries) {
        $this->connection = pg_connect($connectionString);
        // Check if the connection is valid
        if (pg_connection_status($this->connection) === PGSQL_CONNECTION_OK) {
            $this->queries = array(new Query("BEGIN;"));
            // It is assumed that every query is of the type pg_query
            return array_push($this->queries, $queries);
        } else {
            throw new \Error("Could not start transaction");
        }
    }


    // Destructor
    public function __destruct() {
        // Close our connection
        pg_close($this->connection);
    }


    // pushQuery
    /*
     * Push query just inserts a query into our insertion queue
     * @query Query
     */
    public function pushQuery(Query $query) {
        return array_push($this->queries, $query);
    }



    // start
    /*
     * commit just begins our transaction
     */
    public function commit() {
        // Check to see if everything has been set up correctly and if not then throw our error
        if (sizeof($this->queries) === 0) {
            throw new \Error("No queries to be executed");
        }

        // Start the actual stuff now
        // Begin executing all the commands one by one, they wont be sent to the server until the commit command is sent
        foreach ($this->queries as $query) {
            // Execute the query and see if it was successful
            if (!($query->execute($this->connection))) {
                // If it did fail then rollback everything
                $this->rollback() or die("Could not rollback transaction");
            }
        }
        // Push our little commit command
        pg_query($this->connection, "COMMIT;") or die("Transaction Failed");
    }



    // rollback
    /*
     * Rollback rollbacks our last transaction
     */
    public function rollback() {
        return pg_query($this->connection, "ROLLBACK") or die("Could not rollback transaction");
    }


    // Close function
    public function close() {
        pg_close($this->connection);
    }
}