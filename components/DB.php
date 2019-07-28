<?php


abstract class DB
{
  private static $connection;

  public static function getConnection()
  {
    if (!self::$connection)
    {
      $paramsPath = ROOT . '/config/db_params.php';
      $params = include($paramsPath);
      $dsn = "mysql:host={$params['host']};dbname={$params['dbname']};charset=utf8";
      self::$connection = new PDO($dsn, $params['user'], $params['password']);
    }
    return self::$connection;
  }
}