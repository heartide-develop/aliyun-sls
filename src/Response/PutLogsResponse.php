<?php

declare(strict_types=1);

namespace Heartide\AliYun\Sls\Response;

/**
 * PutLogsResponse
 * The response of the PutLogs API from log service.
 * @package Heartide\AliYun\Sls\Response
 */
class PutLogsResponse extends Response
{
    /**
     * PutLogsResponse constructor.
     * @param array $headers PutLogs HTTP response header
     */
    public function __construct($headers)
    {
        parent::__construct($headers);
    }
}