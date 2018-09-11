<?php

class Antminer {

  public static $ip;
  public static $pw;

  public static $state;
  public static $summary;
  public static $pools;
  public static $stats;

  function getIp() {
    return self::$ip;
  }

  function getState() {
    return self::$state;
  }

  function __construct($ip, $pw) {
    self::$ip = $ip;
    self::$pw = $pw;
  }

}
