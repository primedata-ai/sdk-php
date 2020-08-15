![Build Status](https://github.com/primedata-ai/sdk-php/workflows/PHP%20Test/badge.svg)

# Usage

```php
/**
@var $buffer QueueBuffer
*/
$buffer = YourQueueBuffer();
$client = new Client('s-1', 'w-1', $buffer);
$client->track('access_report', ['in' => 'the morning'],
    Event::withSessionID("s-id"),
    Event::withSource(new Source("site", "primedata.ai", array("price" => 20))),
    Event::withTarget(new Target("report", "CDP solution", array("pages" => 100)))
);


public function testSend()
{
    $client = new Client("web-1fcrwsKgV0Zk2EdpCFYIvYbNRgs", "1fcrwstLt8g0ggTL5K87a6O6umy");
    $client->track("purchase_product", ['total_value' => 2000, 'currency' => "USD"],
        Event::withSessionID("1e85YTciGhH6vLfLpmqhJfhFhpq"));
}
```
