
React Line Stream
=================

Line-by-line stream reading for React PHP.

It's `line` event gets fired only with single full line of text (EOL included).

Example
-------
```php
$loop = React\EventLoop\Factory::create();
$file = new React\Stream\Stream(fopen('foo.txt', 'r'), $loop);

$lineStream = new ReactLineStream\LineStream($file);
$lineStream->on('line', function ($line) {
    echo 'line: ' . $line;
});

$loop->run();
```

