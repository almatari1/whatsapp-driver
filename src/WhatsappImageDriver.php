<?php

namespace Malmatari\Drivers\Whatsapp;

use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use Illuminate\Support\Collection;

use BotMan\BotMan\Users\User;

class WhatsappImageDriver extends WhatsappDriver
{
    const DRIVER_NAME = 'WhatsappImage';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {


        $messages = Collection::make($this->payload->get('messages'))->filter(function ($msg) {
            return (isset($msg['type'])) && $msg['type'] === 'image';
        });

        return ! $messages->isEmpty() ;


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

            return (isset($msg['type'])) && $msg['type'] === 'image';

        })->transform(function ($msg) {

            $message = new IncomingMessage(Image::PATTERN,$msg['author'], $msg['chatId'], $msg);

            $message->setImages($this->getImagesUrls($msg));
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


        return new User($matchingMessage->getSender(), null, null, $matchingMessage->getRecipient());

    }

    /**
     * Retrieve image urls from an incoming message.
     *
     * @param array $message
     * @return array A download for the image file.
     */
    public function getImagesUrls(array $message)
    {


        $image = [ 0 => new Image( str_replace(env('FILES_URL')  , env('WHATSAPP_URL') ,$message['filelink']),  $message) ];

        return $image;


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
