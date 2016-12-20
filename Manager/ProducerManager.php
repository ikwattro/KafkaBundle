<?php
declare(strict_types = 1);

namespace M6Web\Bundle\KafkaBundle\Manager;

use M6Web\Bundle\KafkaBundle\Exceptions\EntityNotSetException;
use M6Web\Bundle\KafkaBundle\Exceptions\KafkaException;
use M6Web\Bundle\KafkaBundle\Exceptions\LogLevelNotSetException;
use M6Web\Bundle\KafkaBundle\Exceptions\NoBrokerSetException;
use M6Web\Bundle\KafkaBundle\Helper\NotifyEventTrait;

/**
 * Class ProducerManager
 * @package M6Web\Bundle\KafkaBundle
 *
 * @package M6Web\Bundle\KafkaBundle
 */
class ProducerManager
{
    use NotifyEventTrait;

    /**
     * @var \RdKafka\Producer
     */
    protected $producer;

    /**
     * @var \RdKafka\Topic[]
     */
    protected $topics = [];

    /**
     * @var int
     */
    protected $logLevel;

    /**
     * @var array
     */
    protected $brokers;

    /**
     * @return string
     */
    public function getOrigin(): string
    {
        return 'producer';
    }

    /**
     * @param \RdKafka\Producer $entity
     * @return ProducerManager
     */
    public function setProducer(\RdKafka\Producer $entity): self
    {
        $this->producer = $entity;

        return $this;
    }

    /**
     * @param int $logLevel
     * @return ProducerManager
     */
    public function setLogLevel(int $logLevel): self
    {
        $this->checkIfProducerSet();

        $this->producer->setLogLevel($logLevel);
        $this->logLevel = $logLevel;

        return $this;
    }

    /**
     * @param string $brokers
     * @return ProducerManager
     */
    public function addBrokers(string $brokers): self
    {
        $this->checkIfProducerSet();

        $this->producer->addBrokers($brokers);
        $this->brokers = $brokers;

        return $this;
    }

    /**
     * @param string             $name
     * @param \RdKafka\TopicConf $topicConfiguration
     */
    public function addTopic(string $name, \RdKafka\TopicConf $topicConfiguration)
    {
        $this->checkIfProducerSet();
        $this->checkIfBrokersSet();
        $this->checkIfLogLevelSet();

        $this->topics[] = $this->producer->newTopic($name, $topicConfiguration);
    }

    /**
     * @param string       $message
     * @param integer|null $key
     * @param integer      $partition
     *
     * @return void
     */
    public function produce(string $message, string $key = null, int $partition = RD_KAFKA_PARTITION_UA)
    {
        try {
            array_walk($this->topics, $this->produceForEachTopic($message, $partition, $key));
        } catch (\Exception $e) {
            throw new KafkaException($e->getMessage());
        }

        $this->notifyEvent($this->getOrigin());
    }

    /**
     * @param string       $message
     * @param integer      $partition
     * @param integer|null $key
     *
     * @return callable
     */
    protected function produceForEachTopic(string $message, int $partition, string $key = null): callable
    {
        return function ($topic) use ($message, $key, $partition) {
            /*The second argument is the msgflags. It must be 0 as seen in the documentation:
            https://arnaud-lb.github.io/php-rdkafka/phpdoc/rdkafka-producertopic.produce.html*/
            $topic->produce($partition, 0, $message, $key);
        };
    }

    /**
     * @throws EntityNotSetException
     */
    protected function checkIfProducerSet()
    {
        if (is_null($this->producer)) {
            throw new EntityNotSetException();
        }
    }

    /**
     * @throws NoBrokerSetException
     */
    protected function checkIfBrokersSet()
    {
        if (is_null($this->brokers)) {
            throw new NoBrokerSetException();
        }
    }

    /**
     * @throws LogLevelNotSetException
     */
    protected function checkIfLogLevelSet()
    {
        if (is_null($this->logLevel)) {
            throw new LogLevelNotSetException();
        }
    }
}
