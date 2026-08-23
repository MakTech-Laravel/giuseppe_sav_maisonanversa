<?php

namespace App\Enums;

enum CommunityEventStatus: string
{
    case Opening = 'opening';
    case Ongoing = 'ongoing';
    case Closed = 'closed';
}
