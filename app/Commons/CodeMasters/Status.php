<?php

namespace App\Commons\CodeMasters;

class Status
{
  private static $_HIDDEN = 0;
  private static $_SHOW = 1;
  public static function HIDDEN()
  {
    return self::$_HIDDEN;
  }
  public static function SHOW()
  {
    return self::$_SHOW;
  }
  public static function toArray()
  {
    return [
      self::$_HIDDEN => __('Ẩn'),
      self::$_SHOW => __('Hiện')
    ];
  }
}
