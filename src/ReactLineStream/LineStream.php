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
        $this->stream->on('error',  array($this, 'handleError'));
        $this->stream->on('close',  array($this, 'close'));
    }

    /**
     * @internal
     */
    public function handleData($data)
    {
        if ($this->closed) {
            return;
        }
        $this->emit('data', array($data));

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

    public function handleError()
    {
        $this->emit('error', func_get_args());
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
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->stream->close();
        $this->emit('close');
    }
}
