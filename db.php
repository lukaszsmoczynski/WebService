<?php

class DBConnection {

  private static $connectionInfo = array('hostName' => 'localhost',
      'userName' => 'root',
      'password' => '',
      'databaseName' => 'smoczynskiuk_inzynierka');
  private $connection;

  function __construct() {
    $this->connection = new mysqli(DBConnection::$connectionInfo['hostName'], DBConnection::$connectionInfo['userName'], DBConnection::$connectionInfo['password'], DBConnection::$connectionInfo['databaseName']);

    mysqli_set_charset($this->connection, "utf8");
  }

  function __destruct() {
    $this->rollback();
    $result = $this->connection->close();
    if (!$result) {
      throw new Exception($this->connection->errno . ' - ' . $this->connection->error);
    }
    return $result;
  }

  function execQuery($query) {
    $result = $this->connection->query($query);
    if (!$result) {
      var_dump($query);
      var_dump($this->connection->errno);
      var_dump($this->connection->error);
      throw new Exception($this->connection->errno . ' - ' . $this->connection->error);
    }
    return $result;
  }

  function prepareQuery($query) {
    $result = $this->connection->prepare($query);
    if (!$result) {
      var_dump($query);
      var_dump($this->connection->errno);
      var_dump($this->connection->error);
      throw new Exception($this->connection->errno . ' - ' . $this->connection->error);
    }
    return $result;
  }

  function beginTransaction() {
    $result = $this->connection->begin_transaction();
    if (!$result) {
      throw new Exception($this->connection->errno . ' - ' . $this->connection->error);
    }
    return $result;
  }

  function commit() {
    $result = $this->connection->commit();
    if (!$result) {
      throw new Exception($this->connection->errno . ' - ' . $this->connection->error);
    }
    return $result;
  }

  function rollback() {
    $result = $this->connection->rollback();
    if (!$result) {
      throw new Exception($this->connection->errno . ' - ' . $this->connection->error);
    }
    return $result;
  }
}

function quotedStr($string) {
  return '\'' . str_replace('\'', '\'\'', $string) . '\'';
}

$connection = new DBConnection();
