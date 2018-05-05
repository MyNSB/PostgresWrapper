<?php
/**
 * Created by PhpStorm.
 * User: varun
 * Date: 5/05/2018
 * Time: 1:38 PM
 */

namespace PostgresWrapper;


// This class is rather simple and thus requires no commenting
class Query {
    public $query;
    public $params = array();
    private $lastResult = null;
    public $rowsAffected = null;
    public $numRows = null;

    public function __construct(string $query, ...$params) {
        $this->query = $query;
        array_push($this->params, $params);
    }

    public function execute($connection) {
        error_reporting(0);
        $statement = pg_prepare($connection, "query", $this->query);
        $this->lastResult = pg_execute($statement, "query", $this->params);
        $status = pg_result_status($this->lastResult);
        // Check if the query actually worked
        if (!(in_array($status, array(PGSQL_TUPLES_OK)))) {
            throw new \Error("Query was unsuccessful. Error ID: $status");
        }

        // Setup our vars
        $this->rowsAffected = (int) pg_affected_rows($this->lastResult);
        $this->numRows = (int) pg_num_rows($this->lastResult);
    }


    /* ====== GETTERS ====== */
    /*
     * These functions just retrieve the result from our queries
     */
    // Function fetches all results from our query
    public function fetchAll() {
        // Check if the query has even been executed
        if (!self::executed()) {
            throw new \Error("Query has not been executed and therefore there is no result to work with");
        }

        // Return the result as an associated array
        $result = array();
        for($i = 0; $i < $this->rowsAffected; $i++) {
            array_push($result, pg_fetch_array($this->lastResult, $i, PGSQL_ASSOC));
        }
        return $result;
    }
    // Function retrieves everything of a certain field
    public function getField(string $field) {
        $result = array();
        // Get all the results and iterate over it
        foreach (self::fetchAll() as $row) {
            array_push($result, $row[$field]);
        }
        return $result;
    }




    // Function checks if the query has been executed
    private function executed() {
        return $this->lastResult === null;
    }
}