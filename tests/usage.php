<?php
/**
 * Created by PhpStorm.
 * User: varun
 * Date: 5/05/2018
 * Time: 3:12 PM
 */

include "../PostgresWrapper.php";
use \PostgresWrapper as DB;



$config = array(
    "host" => "localhost",
    "db" => "testDB",
    "port" => "5312",
    "user" => "tester",
    "password" => "password123"
);

// Startup the db
$db = new DB\Postgres($config);
$db->connect(false);

// Transactions
$db->startTransaction();
$db->transactionPushQuery(new DB\Query("DROP TABLE test;"));
$db->transactionCommit();
$db->closeCurrentTransaction();


// Regular old queries
$result = $db->query(new DB\Query("SELECT * FROM table"));
$result->fetchAll();
echo $result->rowsAffected;
$json = $result[0]["field"];

// Parse sum json
$json2 = $db->parseJson($json);
// Convert it to hstore ;)
echo $db->prepareHStore($json2);
$db->close();