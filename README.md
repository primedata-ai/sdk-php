![Build Status](https://github.com/primedata-ai/sdk-php/workflows/PHP%20Test/badge.svg)

# Usage

```php
/**
@param $buffer QueueBuffer
*/
$buffer = YourQueueBuffer();
$client = new Client('s-1', 'w-1', $buffer);
$client->track('access_report', ['in' => 'the morning'],
    Event::withSessionID("s-id"),
    Event::withSource(new Source("site", "primedata.ai", array("price" => 20))),
    Event::withTarget(new Target("report", "CDP solution", array("pages" => 100)))
);
```
