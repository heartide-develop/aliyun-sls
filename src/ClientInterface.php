<?php
/**
 * Created by aliyun-sls
 * Date: 2020/08/11
 * Time: 19:19
 * Author Junduo <caijunduo@gmail.com>
 */

namespace Heartide\AliYun\Sls;

use Heartide\AliYun\Sls\Response\PutLogsResponse;
use Heartide\AliYun\Sls\Request\GetLogsRequest;
use Heartide\AliYun\Sls\Response\GetLogsResponse;
use Heartide\AliYun\Sls\Request\PutLogsRequest;

/**
 * Class Client
 * @package Heartide\AliYun\Sls
 */
interface ClientInterface
{
    /**
     * 写日志
     * @param PutLogsRequest $request
     * @return PutLogsResponse
     */
    public function putLogs(PutLogsRequest $request);

    /**
     * 查日志
     * @param GetLogsRequest $request
     * @return GetLogsResponse|mixed
     * @author Junduo <caijunduo@gmail.com>
     */
    public function getLogs(GetLogsRequest $request);
}