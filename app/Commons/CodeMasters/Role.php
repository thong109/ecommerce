<?php

namespace App\Commons\CodeMasters;

class Role
{
  private static $_USER = 0;
  private static $_ADMIN = 1;
  public static function USER()
  {
    return self::$_USER;
  }
  public static function ADMIN()
  {
    return self::$_ADMIN;
  }
  public static function toArray()
  {
    return [
      self::$_USER => __('User'),
      self::$_ADMIN => __('Admin')
    ];
  }
}
