<?php

namespace Plugin\Rental;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RentalPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [];
    }
}
