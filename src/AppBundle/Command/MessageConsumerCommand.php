<?php
namespace AppBundle\Command;

use AppBundle\Command\Abstracts\AbstractMessageCommand;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MessageConsumerCommand
 */
class MessageConsumerCommand extends AbstractMessageCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('app:queue:consume')
            ->addArgument(
                'server',
                InputArgument::REQUIRED,
                'RabbitMQ server key'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->boot($output, $input->getArgument('server'));

        $this->channel->basic_consume(
            $this->getContainer()->getParameter('amq_queue_name'),
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($output) {
                $output->writeln('Consuming a message');

                $message
                    ->delivery_info['channel']
                    ->basic_ack($message->delivery_info['delivery_tag']);

                $output->writeln('Message consumed');
            }
        );

        /*
         * infinite loop waiting for messages
         */
        while (true) {
            $output->writeln('Waiting for new messages to consume...');

            $this->channel->wait();
        }
    }
}
