<?php

namespace Malmatari\Drivers\Whatsapp\Events;

class Viewed extends WhatsappEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'viewed';
    }
}
