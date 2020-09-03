<?php

declare(strict_types=1);
/**
 * Created by aliyun-sls
 * Date: 2020/08/12
 * Time: 15:14
 * Author Junduo <caijunduo@gmail.com>
 */

namespace Heartide\AliYun\Sls\Response;

use Heartide\AliYun\Sls\LogUtil;

/**
 * Class GetLogsResponse
 * @package Heartide\AliYun\Sls\Response
 */
class GetLogsResponse extends Response
{
    /**
     * @var integer log number
     */
    private $count;

    /**
     * @var string logs query status(Complete or InComplete)
     */
    private $progress;

    /**
     * @var array Aliyun_Log_Models_QueriedLog array, all log data
     */
    private $logs;

    /**
     * GetLogsResponse constructor.
     * @param $resp
     * @param $headers
     */
    public function __construct($resp, $headers)
    {
        parent::__construct($headers);
        $this->count = current($headers['x-log-count']);
        $this->progress = current($headers['x-log-progress']);
        $this->logs = LogUtil::decodeContent($resp);
    }

    /**
     * Get log number from the response
     * @return integer log number
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Check if the get logs query is completed
     * @return bool true if this logs query is completed
     */
    public function isCompleted()
    {
        return $this->progress == 'Complete';
    }

    /**
     * Get all logs from the response
     * @return array Aliyun_Log_Models_QueriedLog array, all log data
     */
    public function getLogs()
    {
        return $this->logs;
    }
}