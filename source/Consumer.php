<?php

class Logger
{
    public static function log($statement)
    {
        echo $statement, "\n";
    }

    public static function logObject($statement, $object)
    {
        if (!is_object($object)) {
            return;
        }

        self::log($statement . ': ' . var_export($object, true));

    }
}

class Connection
{
    private $connectionString;

    /**
     * @var Stomp
     */
    private $internalConnection;

    public function __construct($connectionString)
    {
        $this->connectionString = $connectionString;
    }

    public function establish()
    {
        //@TODO will connect using connection options

        try {
            $this->internalConnection = new Stomp($this->connectionString);
        } catch (StompException $e) {

            throw new ConnectionException('Error establishing connection', $e);;
        }
    }

    public function subscribeToQueue($queueName)
    {
        $this->internalConnection->subscribe($queueName);
    }

    public function close()
    {
       unset($this->internalConnection);
    }

    public function readMessage()
    {
        $message = $this->internalConnection->readFrame();
        return $message;
    }

    public function acknowledgeMessage($message)
    {
        $this->internalConnection->ack($message);
    }
}

class ConnectionException extends  Exception
{
    public function __construct($message, Exception $previousException)
    {
        parent::__construct($message, 0, $previousException);
    }
}


class Consumer
{
    private $connection;

    private $message;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    public function run()
    {
        Logger::log('Opening connection...');

        $this->connection->establish();


        Logger::logObject('Connection', $this->connection);


        $this->subscribe();

        while(true) {

            $this->readMessage();

            if ($this->message) {

                $this->outputMessage();

                $this->acknowledgeMessage();
            }
        }


        $this->connection->close();
    }

    public function subscribe()
    {
        $this->connection->subscribeToQueue('consumable-queue');
    }

    public function readMessage()
    {
        $this->message = $this->connection->readMessage();

    }

    public function outputMessage()
    {
        Logger::logObject('Message read: ', $this->message);
    }

    public function acknowledgeMessage()
    {
        $this->connection->acknowledgeMessage($this->message);
    }
}

$connectionString =  'tcp://10.161.21.144:61613';

$connection = new Connection($connectionString);

$consumer = new Consumer($connection);
$consumer->run();


