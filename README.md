# 项目概述
- 基于Hyperf2.0中的阿里云SLS服务扩展

# 运行环境

确保操作环境满足以下要求：

- PHP >= 7.2
- Swoole PHP extension >= 4.4, and Disabled `Short Name`
- OpenSSL PHP extension
- JSON PHP extension

# 安装

    $ composer require "heartide/aliyun-sls"

# 配置

默认情况下，配置文件为 `config/autoload/aliyun_sls.php` , 如文件不存在，则在项目根目录下执行 `php bin/hyperf.php vendor:publish heartide/aliyun-sls`

```php
<?php

return [
    'endpoint' => env('ALIYUN_SLS_ENDPOINT', 'cn-beijing.log.aliyuncs.com'),
    'access_key_id' => env('ALIYUN_SLS_ACCESS_KEY_ID', ''),
    'access_key_secret' => env('ALIYUN_SLS_ACCESS_KEY_SECRET', ''),
];
```

# 使用

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Heartide\AliYun\Sls\Request\GetLogsRequest;
use Heartide\AliYun\Sls\Request\PutLogsRequest;
use Hyperf\HttpServer\Request;
use Heartide\AliYun\Sls\ClientInterface;
use Hyperf\Di\Annotation\Inject;

class LoggerService
{
    /**
     * @Inject()
     * @var ClientInterface
     */
    protected $sls;

    public function getLogs(Request $request)
    {
        $getLogsRequest = make(GetLogsRequest::class);
        $getLogsRequest->setProject($request->input('project'));
        $getLogsRequest->setLogstore($request->input('logStore'));
        $getLogsRequest->setFrom($request->input('from'));
        $getLogsRequest->setTo($request->input('to'));
        $getLogsRequest->setTopic($request->input('topic'));
        $getLogsRequest->setQuery($request->input('query'));
        $getLogsRequest->setLine($request->input('line'));
        $getLogsRequest->setOffset($request->input('offset'));
        $getLogsRequest->setReverse($request->input('reverse'));

        return $this->sls->getLogs($getLogsRequest);
    }

    public function putLogs(Request $request)
    {
        $content = collect($request->input('content'))
            ->map(function ($item) {
                return make(LogItem::class, [time(), $item]);
            });

        $putLogsRequest = make(PutLogsRequest::class);
        $putLogsRequest->setProject($request->input('project'));
        $putLogsRequest->setLogstore($request->input('logStore'));
        $putLogsRequest->setTopic($request->input('topic'));
        $putLogsRequest->setShardKey($request->input('shardKey'));
        $putLogsRequest->setLogItems($content->toArray());

        $putLogsResponse = $this->sls->putLogs($putLogsRequest);

        return $putLogsResponse->getRequestId();
    }
}
```

# 注入

```php
use Hyperf\Di\Annotation\Inject;
use Heartide\AliYun\Sls\ClientInterface;

/**
 * @Inject()
 * @var ClientInterface
 */
protected $sls;
```

# 接口列表

- [x] `getLogs` 获取日志库中的日志数据

        Heartide\AliYun\Sls\Request\GetLogsRequest   // 参数类
        Heartide\AliYun\Sls\Response\GetLogsResponse // 响应类

- [x] `putLogs` 将日志数据写入日志库中

        Heartide\AliYun\Sls\Request\PutLogsRequest     // 参数类
        Heartide\AliYun\Sls\Response\PutLogsResponse   // 响应类
