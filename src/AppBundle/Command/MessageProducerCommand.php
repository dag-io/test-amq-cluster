<?php
namespace AppBundle\Command;

use AppBundle\Command\Abstracts\AbstractMessageCommand;
use AppBundle\Queue\Message;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MessageProducerCommand
 */
class MessageProducerCommand extends AbstractMessageCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('app:queue:produce')
            ->addArgument(
                'server',
                InputArgument::REQUIRED,
                'RabbitMQ server key'
            )
            ->addArgument('quantity', InputArgument::REQUIRED);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->boot($output, $input->getArgument('server'));

        $this->channel->queue_declare(
            $this->getContainer()->getParameter('amq_queue_name'),
            false,
            false,
            false,
            false
        );

        $quantity = (int)$input->getArgument('quantity');

        for ($i = 0; $i < $quantity; $i++) {
            $correlationId = uniqid();

            $data = ['foo' => 'bar'];

            $msg = new Message(
                serialize($data),
                [
                    'delivery_mode' => 2,
                    'correlation_id' => $correlationId,
                ]
            );

            $output->writeln('Sending a message');

            $this->channel->basic_publish(
                $msg,
                '',
                $this->getContainer()->getParameter('amq_queue_name')
            );
        }
    }
}
