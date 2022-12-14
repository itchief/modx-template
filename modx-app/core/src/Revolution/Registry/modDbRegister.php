<?php
/*
 * This file is part of the MODX Revolution package.
 *
 * Copyright (c) MODX, LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MODX\Revolution\Registry;


use MODX\Revolution\modX;
use MODX\Revolution\Registry\Db\modDbRegisterMessage;
use MODX\Revolution\Registry\Db\modDbRegisterQueue;
use MODX\Revolution\Registry\Db\modDbRegisterTopic;
use PDO;

/**
 * A simple, database implementation of modRegister.
 *
 * @package MODX\Revolution\Registry
 */
class modDbRegister extends modRegister
{
    /**
     * The queue object representing this modRegister instance.
     *
     * @access protected
     * @var modDbRegisterQueue $_queue
     */
    protected $_queue = null;

    /**
     * Construct a new modDbRegister instance.
     *
     * @param modX & $modx    A reference to the modX instance
     * @param string $key     The key of the registry to load
     * @param array  $options An array of options to set
     */
    function __construct(modX &$modx, $key, array $options = [])
    {
        parent:: __construct($modx, $key, $options);
        $this->_queue = $this->_initQueue($key, $options);
    }

    /**
     * Initialize a new queue
     *
     * @param string $key     The new name of the queue
     * @param array  $options An array of options
     *
     * @return modDbRegisterQueue A reference to the new Queue object
     */
    protected function _initQueue($key, $options)
    {
        /** @var modDbRegisterQueue $queue */
        $queue = $this->modx->getObject(modDbRegisterQueue::class, [
            'name' => $key,
        ]);
        if (!$queue) {
            $queue = $this->modx->newObject(modDbRegisterQueue::class);
            $queue->set('name', $key);
            $queue->set('options', $options);
        } elseif (!empty($options)) {
            $queue->set('options', $options);
        }
        if ($queue && $queue->isDirty('options')) {
            $queue->save();
        }

        return $queue;
    }

    /**
     * Connect to the register service implementation. If we made it here, we connected fine.
     *
     * @param array $attributes A collection of attributes required for
     *                          connection to the register.
     *
     * @return boolean Indicates if the connection was successful.
     */
    public function connect(array $attributes = [])
    {
        return true;
    }

    /**
     * Clear the register messages.
     *
     * {@inheritdoc}
     */
    public function clear($topic)
    {
        $topicObject = $this->modx->getObject(modDbRegisterTopic::class, [
            'queue' => $this->_queue->get('id'),
            'name' => $topic,
        ]);
        if (!$topicObject) {
            return false;
        }

        return (bool)$this->modx->removeCollection(modDbRegisterMessage::class, [
            'topic' => $topicObject->get('id'),
        ]);
    }

    /**
     * This implementation supports the following options and default behavior:
     * <ul>
     * <li>msg_limit: Only poll until the specified limit of messages has
     * been digested. Default is 5 messages.</li>
     * <li>time_limit: Poll for new messages for a specified number of
     * seconds. Default is the result of the php time_limit system variable.</li>
     * <li>poll_limit: Only poll for new subscriptions a specified number
     * of times. Default is unlimited.</li>
     * <li>poll_interval: Wait a specified number of seconds between each
     * additional polling iteration, after the initial one. Default is no
     * interval.</li>
     * <li>remove_read: Remove the message immediately upon digesting it.
     * Default is true.</li>
     * <li>include_keys: Include the message keys in the array of messages returned.
     * Default is false.</li>
     * </ul>
     *
     * @param array $options An array of general or protocol specific options.
     *
     * @return mixed The resulting message from the register.
     */
    public function read(array $options = [])
    {
        $this->__kill = false;
        $messages = [];
        $topicMessages = [];
        $msgLimit = isset($options['msg_limit']) ? intval($options['msg_limit']) : 5;
        $timeLimit = isset($options['time_limit']) ? intval($options['time_limit']) : ini_get('max_execution_time');
        $pollLimit = isset($options['poll_limit']) ? intval($options['poll_limit']) : 0;
        $pollInterval = isset($options['poll_interval']) ? intval($options['poll_interval']) : 0;
        $removeRead = isset($options['remove_read']) ? (boolean)$options['remove_read'] : true;
        $includeKeys = isset($options['include_keys']) ? (boolean)$options['include_keys'] : false;
        $startTime = microtime(true);
        $time = $timeLimit <= 0 ? -1 : $startTime;
        $expires = $startTime + $timeLimit;
        $msgCount = 0;
        $iteration = 0;
        while ($time < $expires && $msgCount < $msgLimit && !$this->__kill) {
            if ($iteration > 0) {
                if ($pollLimit > 0 && $iteration >= $pollLimit) {
                    break;
                }
                if ($pollInterval > 0) {
                    sleep($pollInterval);
                }
            }
            $iteration++;
            foreach ($this->subscriptions as $subIdx => $topic) {
                $topicMessages = [];
                $balance = $msgLimit - $msgCount;
                $args = [
                    &$this,
                    $topic,
                    dirname($topic) . '/',
                    basename($topic),
                    $balance,
                    ['fetchMode' => PDO::FETCH_OBJ],
                ];
                foreach ($this->modx->call(modDbRegisterMessage::class, 'getValidMessages', $args) as $msg) {
                    $newMsg = $this->_readMessage($msg, $removeRead);
                    if ($newMsg !== null) {
                        if (!$includeKeys) {
                            $topicMessages[] = $newMsg;
                        } else {
                            $topicMessages[$msg->id] = $newMsg;
                        }
                        $msgCount++;
                    } else {
                        $this->modx->log(modX::LOG_LEVEL_INFO, 'Message was null or expired: ' . print_r($msg, 1));
                    }
                    if ($this->__kill) {
                        break;
                    }
                }
            }
            if (!empty($topicMessages)) {
                if (!$includeKeys) {
                    $messages = $messages + $topicMessages;
                } else {
                    $messages = array_merge($messages, $topicMessages);
                }
            }
            $time = microtime(true);
        }

        return $messages;
    }

    /**
     * Read a message record from the queue topic.
     *
     * @todo Implement support for reading various message types, other than
     * executable PHP format.
     *
     * @param object  $obj    The message data to read.
     * @param boolean $remove Indicates if the message should be deleted once it is read.
     *
     * @return mixed The message returned
     */
    protected function _readMessage($obj, $remove = true)
    {
        $message = null;
        if (is_object($obj) && !empty($obj->payload)) {
            $message = eval($obj->payload);
            if ($remove || ($obj->expires > 1 && $obj->expires < time())) {
                $this->modx->removeObject(modDbRegisterMessage::class, ['topic' => $obj->topic, 'id' => $obj->id]);
            }
            if ($obj->kill) {
                $this->__kill = true;
            }
        }

        return $message;
    }

    /**
     * This implementation provides support for sending messages using either
     * time-based indexes so they are consumed in the order they are produced,
     * or named indexes typically used when consumers want to subscribe to a
     * specific, unique message. Individual messages or message collections
     * passed in numerically indexed arrays are treated as time-based messages
     * and message collections passed in associative arrays are treated as named
     * messages. e.g., to send a single message as named, wrap it in an array
     * with the intended message name as the key.
     *
     * This implementation also supports a message_type option to indicate the
     * format of the message being sent to the register. Currently only supports
     * executable PHP format.
     *
     * Other implementation specific options include:
     * <ul>
     * <li>delay: Number of seconds to delay the message. This option is only
     * supported for time-based messages.</li>
     * <li>ttl: Number of seconds the message is valid in the queue.
     * Default is forever or 0.</li>
     * <li>kill: Tells a message consumer to stop consuming any more
     * messages after reading any message sent with this option.</li>
     * </ul>
     *
     * @param string $topic   A topic container in which to broadcast the message.
     * @param mixed  $message A message, or collection of messages to be sent to
     *                        the register.
     * @param array  $options An optional array of general or protocol
     *                        specific message properties.
     *
     * @return boolean Indicates if the message was recorded.
     *
     * @todo Implement support for sending various message types, other than
     * executable PHP format.
     */
    public function send($topic, $message, array $options = [])
    {
        $sent = false;
        if (empty($topic) || $topic[0] != '/') {
            $topic = $this->_currentTopic . $topic;
        }
        $topicIdx = array_search($topic, $this->subscriptions);
        $queueId = $this->_queue->get('id');
        if ($queueId && $topicIdx !== false) {
            $error = false;
            $messageType = isset($options['message_type']) ? $options['message_type'] : 'php';
            if (!$topicObj = $this->modx->getObject(modDbRegisterTopic::class,
                ['queue' => $queueId, 'name' => $topic])) {
                $topicObj = $this->modx->newObject(modDbRegisterTopic::class);
                $topicObj->set('queue', $queueId);
                $topicObj->set('name', $topic);
                $topicObj->set('created', date('Y-m-d H:i:s'));
                if (!$topicObj->save()) {
                    $error = true;
                }
            }
            if (!$error) {
                if (!is_array($message)) {
                    $message = [$message];
                }
                foreach ($message as $msgIdx => $msg) {
                    $payload = '';
                    if (is_scalar($msg) || is_array($msg) || is_object($msg)) {
                        switch ($messageType) {
                            //TODO: implement more message types
                            case 'php' :
                            default :
                                $timestamp = isset($options['delay']) ? time() + intval($options['delay']) : time();
                                $expires = isset($options['ttl']) && intval($options['ttl']) ? time() + intval($options['ttl']) : 0;
                                $kill = isset($options['kill']) ? (boolean)$options['kill'] : false;
                                if (!is_int($msgIdx)) {
                                    $msgKey = $msgIdx;
                                } else {
                                    $msgKey = date('Ymd\THis', $timestamp) . '-' . sprintf("%03d", $msgIdx);
                                }
                                if ($expires > 0) {
                                    $payload .= "if (time() > {$expires}) return null;\n";
                                }
                                $payload .= 'return ' . var_export($msg, true) . ";\n";
                                $messageObj = $this->modx->getObject(modDbRegisterMessage::class,
                                    ['topic' => $topicObj->get('id'), 'id' => $msgKey]);
                                if (!$messageObj) {
                                    $messageObj = $this->modx->newObject(modDbRegisterMessage::class);
                                    $messageObj->set('topic', $topicObj->get('id'));
                                    $messageObj->set('id', $msgKey);
                                }
                                if ($messageObj) {
                                    $messageObj->set('created', date('Y-m-d H:i:s'));
                                    $messageObj->set('valid', date('Y-m-d H:i:s', $timestamp));
                                    $messageObj->set('expires', $expires);
                                    $messageObj->set('payload', $payload);
                                    $messageObj->set('kill', $kill);
                                    $sent = $messageObj->save();
                                }
                        }
                    }
                }
            } else {
                $this->modx->log(modX::LOG_LEVEL_ERROR,
                    "Could not send message to queue {$queueId}, topic {$topic}. Message payload is " . print_r($message,
                        1));
            }
        }
        if (!$sent) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,
                "Could not send message to queue {$queueId}, topic {$topic}. Message payload is " . print_r($message,
                    1));
        }

        return $sent;
    }

    /**
     * Close the connection to the register service implementation.
     *
     * @return boolean Indicates if the connection was closed successfully.
     */
    public function close()
    {
        return true;
    }
}
