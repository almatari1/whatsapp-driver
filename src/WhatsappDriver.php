<?php

namespace Malmatari\Drivers\Whatsapp;

use Illuminate\Support\Str;
use BotMan\BotMan\Users\User;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Drivers\Events\GenericEvent;
use  Malmatari\Drivers\Whatsapp\Events\Delivered;
use  Malmatari\Drivers\Whatsapp\Events\Sent;
use  Malmatari\Drivers\Whatsapp\Events\Viewed;
use  Malmatari\Drivers\Whatsapp\Events\Read;

class WhatsappDriver extends HttpDriver
{
    /**
     * @const string
     */
    const DRIVER_NAME = 'WhatsApp';
    /**
     * @var string
     */
    protected $endpoint = 'sendMessage';

    protected $hsaAttachment = false;
    /**
     * @var array
     */
    protected $messages = [];
    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */

    /** @var DriverEventInterface */
    protected $driverEvent;

    public function matchesRequest()
    {

        $messages = Collection::make($this->payload->get('messages'))->filter(function ($msg) {

            return (isset($msg['type'])) && $msg['type'] === 'chat';
        });
        return !$messages->isEmpty();
    }

    /**
     * Retrieve the chat message(s).
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
     * @return bool|DriverEventInterface
     */
    public function hasMatchingEvent()
    {

        $event = Collection::make($this->payload->get('ack'))->filter(function ($msg) {

            return Collection::make($msg)->except([
                'messages',

            ])->isEmpty() === false;
        })->transform(function ($msg) {
            return Collection::make($msg)->toArray();
        })->first();
        if (!is_null($event)) {

            $this->driverEvent = $this->getEventFromEventData($event);

            return $this->driverEvent;
        }

        return false;
    }

    /**
     * @param array $eventData
     * @return DriverEventInterface
     */
    protected function getEventFromEventData(array $eventData)
    {
        $name = Collection::make($eventData)->except(['messages'])->values()->first();
        switch ($name) {
            case 'sent':
                return new Sent($eventData);
                break;
            case 'read':
                return new Read($eventData);
                break;
            case 'viewed':
                return new Viewed($eventData);
                break;
            case 'delivered':
                return new Delivered($eventData);
                break;

            default:
                $event = new GenericEvent($eventData);
                $event->setName($name);

                return $event;
                break;
        }
    }


    /**
     * @return void
     */
    protected function loadMessages()
    {
        if ($this->payload->get('messages') !== null) {
            $messages = collect($this->payload->get('messages'))
                ->filter(function ($value) {
                    return !$value['fromMe'];
                })
                ->map(function ($value) {
                    $message = new IncomingMessage($value['body'], $value['author'], $value['chatId'], $this->payload);
                    $message->addExtras('userName', $value['chatName']);
                    return $message;
                })->toArray();
        }

        $this->messages = $messages ?? [];
    }


    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return !empty($this->config->get('url')) && !empty($this->config->get('token'));
    }

    // /**
    //  * Retrieve User information.
    //  * @param IncomingMessage $matchingMessage
    //  * @return UserInterface
    //  */
    // public function getUser(IncomingMessage $matchingMessage)
    // {

    //     return new User($matchingMessage->getSender(), null, null, $matchingMessage->getRecipient());

    // }

    /**
     * @param IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * @param string|\BotMan\BotMan\Messages\Outgoing\Question $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return array
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {

        $this->endpoint = 'sendMessage';
        if ($message instanceof OutgoingMessage) {
            $attachment = $message->getAttachment();

            if ($attachment instanceof Image || $attachment instanceof Video || $attachment instanceof  File) {
                $this->endpoint = 'sendFile';
                if ($attachment->getTitle() == "Visit Website") {
                    $this->endpoint = 'sendLinkPreview';
                    $payload =   [
                        'chatId' => $matchingMessage->getRecipient(),
                        'link' =>  $attachment->getUrl(),
                        "text" => ""
                    ];
                    return    $payload;
                }
                $payload =   [
                    'chatId' => $matchingMessage->getRecipient(),

                    'body' =>  $attachment->getUrl(),
                    "filename" => Str::random(6),
                    "caption" =>  $attachment->getTitle()
                ];

                return    $payload;
            } elseif ($attachment instanceof Location) {
                $this->endpoint = 'sendLocation';
                $payload =   [
                    'chatId' => $matchingMessage->getRecipient(),
                    'lat' => $attachment->getLatitude(),
                    "lng" => $attachment->getLongitude(),
                    "address" =>  ""
                ];
                return    $payload;
            } else {
                return   [
                    'chatId' => $matchingMessage->getRecipient(),
                    'body' =>   $message->getText()

                ];
            }
        } elseif ($message instanceof Question) {
            if (count($message->getButtons()) > 0) {
                return  [
                    'chatId' => $matchingMessage->getRecipient(),
                    'body'  => $this->convertQuestion($message)

                ];
            }
            return    [
                'chatId' => $matchingMessage->getRecipient(),
                'body'  => $message->getText()
            ];
        }
    }



    private function convertQuestion(Question $question)
    {
        $buttons = $question->getButtons();

        if ($buttons) {
            $options =  Collection::make($buttons)->transform(function ($button) {
                return '*' . $button['value'] . '* .' . $button['text'];
            })->toArray();

            return $question->getText() . "\n" . implode("\n", $options)
                . "\n"
                . "\n"
                . "\n"
                . __('replys.tip');
        }
    }



    /**
     * @param mixed $payload
     * @return Response
     */

    public function sendPayload($payload)
    {

        return $this->http->post(
            $this->buildApiUrl($this->endpoint),
            [],
            $payload,
            [
                'Accept: application/json',
                'Content-Type: application/json'
            ],
            true
        );
    }

    /**
     * @param Request $request
     * @return void
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make((array) $this->payload->get('messages'));
        $this->content = $request->getContent();
        $this->config = Collection::make($this->config->get('whatsapp', []));
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return void
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        $parameters = array_replace_recursive([
            'chatId' => $matchingMessage->getRecipient(),
        ], $parameters);

        return $this->http->post($this->buildApiUrl($endpoint), [], $parameters);
    }



    /**
     * @param IncomingMessage $matchingMessage
     * @return User
     * @throws TelegramException
     * @throws TelegramConnectionException
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        $parameters = [
            'chatId' => $matchingMessage->getRecipient(),
        ];

        $response =    $this->http->post($this->buildApiUrl('getChatById'), [], $parameters);

        $responseData = json_decode($response->getContent(), true);

        // if ($response->getStatusCode() !== 200) {
        //     throw new TelegramException('Error retrieving user info: ' . $responseData['description']);
        // }

        $userData = Collection::make($responseData['data']['contact']);

        return new User(
            $userData->get('id'),
            $userData->get('shortName'),
            $userData->get('pushname'),
            $userData->get('name'),
            $responseData['data']
        );
    }

    /**
     * @param IncomingMessage $matchingMessage
     * @return void
     */
    public function types(IncomingMessage $matchingMessage)
    {

        $parameters = [
            'chatId' => $matchingMessage->getRecipient(),
            'state' => true,
        ];
        return $this->http->post($this->buildApiUrl('typing'), [], $parameters);
    }


    /**
     * @param $endpoint
     * @return string
     */
    protected function buildApiUrl($endpoint)
    {
        return $this->config->get('url') . '/' . $this->config->get('instance') . '/' . $endpoint . '/?token=' . $this->config->get('token');
    }
}
