<?php

namespace Malmatari\Drivers\Whatsapp\Events;

class Delivered extends WhatsappEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'delivered';
    }
}
