<?php

class ChartService
{
 public $symbol;
 public $resolution;
 public $from;
 public $to;
 
 public $debugmode;
 
 private $DB;
 
 //-----------------------------------------------------------------------------
 public function __construct($DB)
 {
  $this->DB = $DB;  
  $this->DB->query("SET time_zone='+00:00'");
 }
 
 //-----------------------------------------------------------------------------
 public function setSymbol($symbol)
 {
  $this->symbol = $symbol;
 }
 
 //-----------------------------------------------------------------------------
 public function setResolution($resolution)
 {
  $this->resolution = $resolution;
 } 
 
 //-----------------------------------------------------------------------------
 public function getHistoricalData($from, $to)
 {
  if (!ctype_digit($from) || !ctype_digit($to))
  {
   $output = new stdClass();
   $output->s = "error";
   $output->errmsg = "Invalid from or to value";
   echo json_encode($output);
   exit;
  }
  
  switch($this->resolution)
  {
   case "1S":
   case "10S":
   case "30S":
   case "60S":
     $params[symbol] = $this->symbol;
     $params[from] = $from;
     $params[to] = $to;
     
     $query = "SELECT
               unixtime,
               close, high, low, open
               FROM intraday_second
               WHERE
               symbol = :symbol
               AND unixtime BETWEEN :from AND :to
               ";
     break;
     
   //-----------------------------------------------------------------------------
   case "1":
   case "5":
   case "15":
   case "30":
   case "60":
     $params[symbol] = $this->symbol;
     $params[from] = $from;
     $params[to] = $to;
     
     $query = "SELECT
               unixtime,
               close, high, low, open
               FROM intraday
               WHERE
               symbol = :symbol
               AND unixtime BETWEEN :from AND :to
               ";
     break;
     
   //-----------------------------------------------------------------------------
   case "D":
   case "1D":
   case "1W":
   case "1M":
     $params[symbol] = $this->symbol;
     $params[from] = $from;
     $params[to] = $to;
     
     $query = "SELECT
               unixtime,
               close, high, low, open
               FROM dailyprice
               WHERE
               symbol = :symbol
               AND unixtime BETWEEN :from AND :to
               ";        
     break;
  }
  
  if ($this->debugmode) { $this->debugQuery($query, $params); }  
  
  $stmt = $this->DB->prepare($query);
  $stmt->execute($params);    
  
  while ($row = $stmt->fetchObject()) // Fetch Object to retain value type (float).
  {
   $data[$row->unixtime] = $row;
  }  
    
  /******************************************************************************
  * CONVERT TO UDF FORMAT
  ******************************************************************************/
  echo $this->_generateUDF($data);   
 }
 
 //-----------------------------------------------------------------------------
 public function getLastUnixtime()
 {
  $query = "SELECT IF (value, value, UNIX_TIMESTAMP()) AS lastunixtime FROM cron WHERE cronid = 'lastunixtime'";
  //$query = "SELECT UNIX_TIMESTAMP() AS lastunixtime";
  $stmt = $this->DB->query($query);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  
  return $row[lastunixtime];
 }
 
 //-----------------------------------------------------------------------------
 public function getLastUnixtime2()
 {
  //$query = "SELECT IF (value, value, UNIX_TIMESTAMP()) AS lastunixtime FROM cron WHERE cronid = 'lastunixtime'";
  $query = "SELECT UNIX_TIMESTAMP() AS lastunixtime";
  $stmt = $this->DB->query($query);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  
  return $row[lastunixtime];
 } 
 
 //-----------------------------------------------------------------------------
 private function _generateUDF($data)
 {
  $output = new stdClass();
  
  if (count($data))
  {      
   $output->lastunixtime = $this->getLastUnixtime(); // Realtime server time
   $output->lastunixtime2 = $this->getLastUnixtime2(); // Delayed based on last tick
   $output->s = "ok";
   
   foreach($data AS $unixtime => $row)
   {
    $output->t[] = ($unixtime);
    $output->c[] = (float) $row->close;
    $output->h[] = (float) $row->high;
    $output->l[] = (float) $row->low;
    $output->o[] = (float) $row->open;
   }   
  }
  else
  {
   $output->s = "no_data";   
   //$output->nextime = "1476280999";   
  }    
  return json_encode($output, JSON_PRETTY_PRINT);
 }
 
 //-----------------------------------------------------------------------------
 private function debugQuery($query, $params)
 {
  foreach ($params AS $key => $value)
  {
   $query = str_replace(":{$key}", "'{$value}'", $query);
  }
  echo $query;
 }

}

?>