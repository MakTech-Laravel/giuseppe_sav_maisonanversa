<?php

namespace App\Enums;

enum SubscriberSource: string
{
    case Home = 'home';
    case Story = 'story';
    case Modal = 'modal';
    case Waitlist = 'waitlist';
    case SoldOut = 'sold_out';
    case Member = 'member';
}
