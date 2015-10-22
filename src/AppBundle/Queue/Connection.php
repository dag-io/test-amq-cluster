<?php
namespace AppBundle\Queue;

/**
 * Class Connection
 */
/**
 * Queue AMQP Stream Connection.
 */
class Connection extends \PhpAmqpLib\Connection\AMQPStreamConnection
{
    /**
     * Fetch a Channel object identified by the numeric channel_id, or
     * create that object if it doesn't already exist.
     *
     * @param string $channel_id Channel id, cached or created
     *
     * @return Channel
     */
    public function channel($channel_id = null)
    {
        if (isset($this->channels[$channel_id])) {
            return $this->channels[$channel_id];
        } else {
            $channel_id = $channel_id ? $channel_id : $this->get_free_channel_id();
            $ch = new Channel($this->connection, $channel_id);
            $this->channels[$channel_id] = $ch;

            return $ch;
        }
    }
}
