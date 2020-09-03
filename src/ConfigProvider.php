<?php

declare(strict_types=1);

namespace Heartide\AliYun\Sls;

/**
 * ConfigProvider
 * 类的介绍
 * @package Heartide\AliYun\Sls
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ClientInterface::class => Client::class,
            ],
            'processes' => [
            ],
            'listeners' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'collectors' => [
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for aliyun sls.',
                    'source' => __DIR__ . '/../publish/aliyun_sls.php',
                    'destination' => BASE_PATH . '/config/autoload/aliyun_sls.php',
                ],
            ],
        ];
    }
}