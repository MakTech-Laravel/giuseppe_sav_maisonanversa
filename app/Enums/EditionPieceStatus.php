<?php

namespace App\Enums;

enum EditionPieceStatus: string
{
    case Archive = 'archive';
    case Available = 'available';
    case Reserved = 'reserved';
    case Allocated = 'allocated';
}
