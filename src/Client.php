<?php

declare(strict_types=1);

/**
 * Created by aliyun-sls
 * Date: 2020/08/11
 * Time: 15:34
 * Author Junduo <caijunduo@gmail.com>
 */

namespace Heartide\AliYun\Sls;

use GuzzleHttp\Exception\GuzzleException;
use Heartide\AliYun\Sls\Request\GetLogsRequest;
use Heartide\AliYun\Sls\Response\GetLogsResponse;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory as GuzzleClientFactory;
use Psr\Container\ContainerInterface;
use Heartide\AliYun\Sls\Request\PutLogsRequest;
use Heartide\AliYun\Sls\Response\PutLogsResponse;
use RuntimeException;

/**
 * Class Client
 * @package Heartide\AliYun\Sls
 */
class Client implements ClientInterface
{
    /**
     * API版本
     */
    const API_VERSION = '0.6.0';

    /**
     * @var $endpoint
     */
    protected $endpoint;

    /**
     * @var $accessKeyID
     */
    protected $accessKeyID;

    /**
     * @var $accessKeySecret
     */
    protected $accessKeySecret;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Client constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->client = $container->get(GuzzleClientFactory::class)->create();
        $this->config = $container->get(ConfigInterface::class);
        $this->endpoint = $this->config->get('aliyun_sls.endpoint');
        $this->accessKeyID = $this->config->get('aliyun_sls.access_key_id', '');
        $this->accessKeySecret = $this->config->get('aliyun_sls.access_key_secret', '');
    }

    /**
     * GMT format time string.
     * @return string
     */
    protected function getGMT()
    {
        return gmdate('D, d M Y H:i:s') . ' GMT';
    }

    /**
     * Decodes a JSON string to a JSON Object.
     * Unsuccessful decode will cause an RuntimeException.
     * @param $resBody
     * @param $requestId
     * @return mixed|null
     */
    protected function parseToJson($resBody, $requestId)
    {
        if (!$resBody) {
            return null;
        }
        $result = json_decode($resBody, true);
        if ($result === null) {
            throw new RuntimeException ("Bad format,not json;requestId:{$requestId}");
        }

        return $result;
    }

    /**
     * 请求处理响应
     * @param $method
     * @param $url
     * @param $body
     * @param $headers
     * @return array
     */
    public function sendRequest($method, $url, $body, $headers)
    {
        try {
            $response = $this->client->request($method, $url, ['body' => $body, 'headers' => $headers]);
            $responseCode = $response->getStatusCode();
            $header = $response->getHeaders();
            $resBody = (string)$response->getBody();
        } catch (GuzzleException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode());
        }

        $requestId = isset($header['x-log-requestid']) ? $header ['x-log-requestid'] : '';

        if ($responseCode == 200) {
            return [$resBody, $header];
        }

        $exJson = $this->parseToJson($resBody, $requestId);

        if (isset($exJson['errorCode']) && isset($exJson['errorMessage'])) {
            throw new RuntimeException(
                "errorCode:{$exJson['errorCode']};errorMessage:{$exJson['errorMessage']};requestId:{$requestId}",
                $responseCode
            );
        }

        if ($exJson) {
            $exJson = 'The return json is ' . json_encode($exJson);
        } else {
            $exJson = '';
        }

        throw new RuntimeException("Request is failed. Http code is {$responseCode}.{$exJson};requestId:{$requestId}");
    }

    /**
     * send
     * 组合请求公共数据
     * @param $method
     * @param $project
     * @param $body
     * @param $resource
     * @param $params
     * @param $headers
     * @return array
     */
    public function send($method, $project, $body, $resource, $params, $headers)
    {
        $host = is_null($project) ? $this->endpoint : "{$project}.{$this->endpoint}";

        if ($body) {
            $headers['Content-Length'] = strlen($body);
            $headers["x-log-bodyrawsize"] = $headers["x-log-bodyrawsize"] ?? 0;
            $headers['Content-MD5'] = LogUtil::calMD5($body);
        } else {
            $headers['Content-Length'] = 0;
            $headers["x-log-bodyrawsize"] = 0;
            $headers['Content-Type'] = '';
        }

        $headers['x-log-apiversion'] = self::API_VERSION;
        $headers['x-log-signaturemethod'] = 'hmac-sha1';
        $headers['Host'] = $host;
        $headers['Date'] = $this->getGMT();
        $signature = LogUtil::getSignature($method, $resource, $this->accessKeySecret, $params, $headers);
        $headers['Authorization'] = "LOG $this->accessKeyID:$signature";
        $url = "http://{$host}{$resource}";
        if ($params) {
            $url .= '?' . LogUtil::urlEncode($params);
        }

        return $this->sendRequest($method, $url, $body, $headers);
    }

    /**
     * @inheritDoc
     */
    public function putLogs(PutLogsRequest $request)
    {
        if (count($request->getLogItems()) > 4096) {
            throw new RuntimeException('PutLogs 接口每次可以写入的日志组数据量上限为4096条!');
        }

        $logGroup = make(LogGroup::class);
        $logGroup->setTopic($request->getTopic());
        $logGroup->setSource($request->getSource() ?: LogUtil::getLocalIp());
        /** @var LogItem $logItem */
        foreach ($request->getLogItems() as $logItem) {
            $log = make(Log::class);
            $log->setTime($logItem->getTime());
            $contents = $logItem->getContents();
            foreach ($contents as $key => $value) {
                $content = make(LogContent::class);
                $content->setKey($key);
                $content->setValue(LogUtil::encodeValue($value));
                $log->addContents($content);
            }
            $logGroup->addLogs($log);
        }
        $body = LogUtil::toBytes($logGroup);
        unset($logGroup);
        $bodySize = strlen($body);
        if ($bodySize > 3 * 1024 * 1024) {
            throw new RuntimeException('PutLogs 接口每次可以写入的日志组数据量上限为3MB!');
        }

        $params = [];
        $headers = [];
        $headers["x-log-bodyrawsize"] = $bodySize;
        $headers['x-log-compresstype'] = 'deflate';
        $headers['Content-Type'] = 'application/x-protobuf';
        if ($request->getShardKey()) {
            $headers["x-log-hashkey"] = $request->getShardKey();
        }
        $body = gzcompress($body, 6);

        $resource = "/logstores/" . $request->getLogstore() . "/shards/lb";
        [$resp, $header] = $this->send("POST", $request->getProject(), $body, $resource, $params, $headers);
        $requestId = isset($header['x-log-requestid']) ? $header['x-log-requestid'] : '';
        $resp = $this->parseToJson($resp, $requestId);
        return make(PutLogsResponse::class, [$header]);
    }

    /**
     * @inheritDoc
     */
    public function getLogs(GetLogsRequest $request)
    {
        $ret = $this->getLogsJson($request);
        $resp = $ret[0];
        $header = $ret[1];
        return make(GetLogsResponse::class, [$resp, $header]);
    }

    private function getLogsJson(GetLogsRequest $request)
    {
        $headers = [];
        $params = [];
        if ($request->getTopic() !== null) {
            $params ['topic'] = $request->getTopic();
        }
        if ($request->getFrom() !== null) {
            $params ['from'] = $request->getFrom();
        }
        if ($request->getTo() !== null) {
            $params ['to'] = $request->getTo();
        }
        if ($request->getQuery() !== null) {
            $params ['query'] = $request->getQuery();
        }
        $params ['type'] = 'log';
        if ($request->getLine() !== null) {
            $params ['line'] = $request->getLine();
        }
        if ($request->getOffset() !== null) {
            $params ['offset'] = $request->getOffset();
        }
        if ($request->getOffset() !== null) {
            $params ['reverse'] = $request->getReverse() ? 'true' : 'false';
        }
        $logstore = $request->getLogstore() !== null ? $request->getLogstore() : '';
        $project = $request->getProject() !== null ? $request->getProject() : '';
        $resource = "/logstores/$logstore";
        [$resp, $header] = $this->send("GET", $project, null, $resource, $params, $headers);
        $requestId = isset ($header ['x-log-requestid']) ? $header ['x-log-requestid'] : '';
        $resp = $this->parseToJson($resp, $requestId);
        return [$resp, $header];
    }
}