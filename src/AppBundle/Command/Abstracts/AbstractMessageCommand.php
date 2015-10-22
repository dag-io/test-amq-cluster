<?php
namespace AppBundle\Command\Abstracts;

use AppBundle\Queue\Channel;
use AppBundle\Queue\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractMessageCommand
 */
abstract class AbstractMessageCommand extends ContainerAwareCommand
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @param OutputInterface $output
     * @param int             $serverKey
     */
    public function boot(OutputInterface $output, $serverKey)
    {
        $serverHostname = $this
            ->getContainer()
            ->getParameter('amq_server_hosts')[$serverKey];


        $serverPort = $this->getContainer()->getParameter('amq_server_port');

        $output->writeln(
            sprintf(
                'Connecting on server %s on port %d',
                $serverHostname,
                $serverPort
            )
        );

        /*
         * open Amqp connection
         */
        $this->connection = new Connection(
            $serverHostname,
            $serverPort,
            $this->getContainer()->getParameter('amq_server_user'),
            $this->getContainer()->getParameter('amq_server_pass'),
            $this->getContainer()->getParameter('amq_server_vhost')
        );

        $this->channel = $this->connection->channel();
        $this->channel->queue_declare(
            $this->getContainer()->getParameter('amq_queue_name'),
            false,
            false,
            false,
            false
        );

        /*
         * Set the prefetch messages to 1
         *
         * In order to defeat that we can use the basic_qos method with the
         * prefetch_count = 1 setting. This tells RabbitMQ not to give more than one
         * message to a worker at a time. Or, in other words, don't dispatch a new
         * message to a worker until it has processed and acknowledged the previous one.
         * Instead, it will dispatch it to the next worker that is not still busy.
         *
         * @link http://www.rabbitmq.com/consumer-prefetch.html
         * @link https://www.rabbitmq.com/tutorials/tutorial-two-php.html
         */
        $this->channel->basic_qos(
            null,
            1,
            null
        );

//        $this->logger->info(
//            sprintf('[Consumer] Consuming queue: %s', $queueName)
//        );
    }
}
