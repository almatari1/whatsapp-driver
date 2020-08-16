<?php

namespace Malmatari\Drivers\Whatsapp\Events;

class Sent extends WhatsappEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'sent';
    }
}
