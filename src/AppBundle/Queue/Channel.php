<?php
namespace AppBundle\Queue;

/**
 * Class Channel
 */
class Channel extends \PhpAmqpLib\Channel\AMQPChannel
{
    protected $callbacksForked = [];

    /**
     * start a queue consumer.
     */
    public function fork_consume(
        $queue = '',
        $consumer_tag = '',
        $no_local = false,
        $no_ack = false,
        $exclusive = false,
        $nowait = false,
        $callback = null,
        $ticket = null
    ) {
        $consumer_tag = $this->basic_consume($queue, $consumer_tag, $no_local, $no_ack, $exclusive, $nowait, $callback, $ticket);

        $this->callbacksForked[$consumer_tag] = true;

        return $consumer_tag;
    }

    /**
     * notify the client of a consumer message.
     */
    protected function basic_deliver($args, $msg)
    {
        $consumer_tag = $args->read_shortstr();
        $delivery_tag = $args->read_longlong();
        $redelivered = $args->read_bit();
        $exchange = $args->read_shortstr();
        $routing_key = $args->read_shortstr();

        $msg->delivery_info = array(
            'channel' => $this,
            'consumer_tag' => $consumer_tag,
            'delivery_tag' => $delivery_tag,
            'redelivered' => $redelivered,
            'exchange' => $exchange,
            'routing_key' => $routing_key,
        );

        if (isset($this->callbacks[$consumer_tag])) {
            $func = $this->callbacks[$consumer_tag];
        } else {
            $func = null;
        }

        /*
         * changes for Silicium starts HERE
         *
         * We try to call the function in a forked process if the consumer is
         * set as a fork_consume instead a basic_consume
         * This behaviour is a Silicium extension of PhpAmqpLib
         */
        if ($func != null) {
            if (isset($this->callbacksForked[$consumer_tag]) && $this->callbacksForked[$consumer_tag] === true) {
                $this->fork_user_func($func, $msg);
            } else {
                call_user_func($func, $msg);
            }
        }
    }

    protected function fork_user_func($func, $msg)
    {
        $pid = pcntl_fork();

        if ($pid) {
            $myPid = getmypid();
            echo "main process $myPid submit $pid \n";
//			pcntl_waitpid($pid, $status, WUNTRACED);
        } else {
            //child process
            try {
                call_user_func($func, $msg);
                exit(0);
            } catch (Exception $exc) {
                throw $exc;
                exit(1);
            }
        }
    }
}