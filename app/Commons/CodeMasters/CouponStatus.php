<?php

namespace App\Commons\CodeMasters;

enum CouponStatus: int
{
  case INACTIVE = 0;
  case ACTIVE = 1;
  case EXPIRED = 2;
  case USED = 3;

  public function label(): string
  {
    return match ($this) {
      self::INACTIVE => __('Inactive'),
      self::ACTIVE => __('Active'),
      self::EXPIRED => __('Expired'),
      self::USED => __('Used'),
    };
  }
}
