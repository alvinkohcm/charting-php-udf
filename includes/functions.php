<?php

include(__DIR__."/../config/config.php");

/******************************************************************************
* PDO DATABASE
******************************************************************************/
try
{
 $PDO = new PDO("mysql:dbname={$database[database]};host={$database[host]}", $database[username], $database[password]);
}
catch (PDOException $e)
{
 echo "Database Connection Error: " . $e->getMessage();
}

?>