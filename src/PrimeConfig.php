<?php
declare(strict_types=1);

namespace Prime;


class PrimeConfig
{
    private $host;
    private $sourceID;
    private $writeKey;

    /**
     * PrimeConfig constructor.
     * @param string $sourceID
     * @param string $writeKey
     * @param string|null $host
     */
    public function __construct(string $sourceID, string $writeKey, string $host = null)
    {
        if ($host == null or $host == "") {
            $host = 'https://dev.primedata.ai/powehi';
        }
        $this->host = $host;
        $this->sourceID = $sourceID;
        $this->writeKey = $writeKey;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return mixed
     */
    public function getSourceID()
    {
        return $this->sourceID;
    }

    /**
     * @return mixed
     */
    public function getWriteKey()
    {
        return $this->writeKey;
    }
}