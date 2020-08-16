<?php

namespace Malmatari\Drivers\Whatsapp;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use BotMan\BotMan\Users\User;

class WhatsappLocationDriver extends WhatsappDriver
{
    const DRIVER_NAME = 'WhatsappLocation';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {

        //Log::info('matchesRequest location');
        $messages = Collection::make($this->payload->get('messages'))->filter(function ($msg) {
          //  Log::info($msg['type']);
            return (isset($msg['type'])) && $msg['type'] === 'location';
        });

        return ! $messages->isEmpty();
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        if (empty($this->messages)) {
            $this->loadMessages();
        }

        return $this->messages;
    }

    /**
     * Load Facebook messages.
     */
    protected function loadMessages()
    {
        $messages = Collection::make($this->payload->get('messages'))->filter(function ($msg) {

            return (isset($msg['type'])) && $msg['type'] === 'location';

        })->transform(function ($msg) {

           // Log::info('matchesRequest msg');

            $message = new IncomingMessage(Location::PATTERN, $msg['author'], $msg['chatId'], $msg);
            $location = $this->getLocation($msg);

            // $location->getLatitude();
            // $location->getLongitude();
            // Log::info( '$location->getLatitude()');
            // Log::info( $location->getLatitude());
            // Log::info( $location->getLongitude());

            $message->setLocation($this->getLocation($msg));

            return $message;
        })->toArray();

        if (count($messages) === 0) {
            $messages = [new IncomingMessage('', '', '')];
        }

        $this->messages = $messages;
    }

      /**
     * Retrieve User information.
     * @param IncomingMessage $matchingMessage
     * @return UserInterface
     */
    public function getUser(IncomingMessage $matchingMessage)
    {

     //   Log::debug('getUser');
        // Log::info($matchingMessage->getPayload());
        return new User($matchingMessage->getSender(), null, null, $matchingMessage->getRecipient());

    }


    /**
     * Retrieve location from an incoming message.
     *
     * @param array $messages
     * @return \BotMan\BotMan\Messages\Attachments\Location
     */
    public function getLocation(array $messages)
    {
        return new Location($messages['lat'], $messages['lng'], $messages);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasMatchingEvent()
    {
        return false;
    }
}
