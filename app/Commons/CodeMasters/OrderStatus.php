<?php

namespace App\Commons\CodeMasters;

enum OrderStatus: int
{
  case PENDING = 0;
  case PROCESSING = 1;
  case SHIPPED = 2;
  case DELIVERED = 3;
  case CANCELLED = 4;

  public function label(): string
  {
    return match ($this) {
      self::PENDING => __('Chưa giải quyết'),
      self::PROCESSING => __('Đã lên đơn'),
      self::SHIPPED => __('Đang giao hàng'),
      self::DELIVERED => __('Đã giao'),
      self::CANCELLED => __('Đã hủy đơn'),
    };
  }
}
