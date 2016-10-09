<?php

namespace ReactLineStream;

use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

/**
 * @event line
 */
class LineStream extends EventEmitter implements ReadableStreamInterface
{
    /**
     * @var string
     */
    protected $eol;

    /**
     * @var ReadableStreamInterface
     */
    protected $stream;

    /**
     * @var string
     */
    protected $buffer = '';

    /**
     * @var bool
     */
    protected $closed = false;

    /**
     * @param ReadableStreamInterface $stream
     */
    public function __construct(ReadableStreamInterface $stream, $eol = PHP_EOL)
    {
        $this->eol = $eol;
        $this->stream = $stream;
        $this->stream->on('data', array($this, 'handleData'));
        $this->stream->on('end',  array($this, 'handleEnd'));
        Util::forwardEvents($this->stream, $this, [
            'error',
        ]);
    }

    /**
     * @internal
     */
    public function handleData($data)
    {
        $this->buffer .= $data;

        $lines = explode($this->eol, $this->buffer);
        for ($i = 0; $i < count($lines) - 1; $i++) {
            $this->emit('line', array($lines[$i] . $this->eol, $this));
        }

        $this->buffer = $lines[count($lines) - 1];
    }

    /**
     * @internal
     */
    public function handleEnd()
    {
        if ($this->closed) {
            return;
        }
        if ($this->buffer !== '') {
            $this->emit('line', array($this->buffer, $this));
            $this->buffer = '';
        }
        $this->emit('end');
        $this->close();
    }

    public function pause()
    {
        $this->stream->pause();
    }

    public function resume()
    {
        $this->stream->resume();
    }

    public function isReadable()
    {
        return $this->stream->isReadable();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);
        return $dest;
    }

    public function close()
    {
        $this->closed = true;
        return $this->stream->close();
    }
}
