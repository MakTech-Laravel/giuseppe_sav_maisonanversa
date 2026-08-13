<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Incomplete = 'incomplete';
    case Paid = 'paid';
    case Canceled = 'canceled';
    case Failed = 'failed';
}
