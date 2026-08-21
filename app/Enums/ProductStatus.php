<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Active = 'active';
    case ComingSoon = 'coming_soon';
    case Archived = 'archived';
}
