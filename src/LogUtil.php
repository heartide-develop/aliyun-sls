<?php

declare(strict_types=1);

namespace Heartide\AliYun\Sls;

/**
 * LogUtil
 * 日志工具
 * @package Heartide\AliYun\Sls
 */
class LogUtil
{
    /**
     * getLocalIp
     * 获取本地ip
     * @static
     * @return string
     */
    public static function getLocalIp()
    {
        $ip = '127.0.0.1';
        $ips = array_values(swoole_get_local_ip());
        foreach ($ips as $v) {
            if ($v && $v != $ip) {
                $ip = $v;
                break;
            }
        }

        return $ip;
    }

    /**
     * calMD5
     * 计算大写的md5
     * @static
     * @param $value
     * @return string
     */
    public static function calMD5($value)
    {
        return strtoupper(md5($value));
    }

    /**
     * hmacSHA1
     * Calculate string $content hmacSHA1 with secret key $key.
     * @static
     * @param $content
     * @param $key
     * @return string
     */
    public static function hmacSHA1($content, $key)
    {
        $signature = hash_hmac("sha1", $content, $key, true);
        return base64_encode($signature);
    }

    /**
     * urlEncode
     * Get url encode.
     * @static
     * @param $params
     * @return string
     */
    public static function urlEncode($params)
    {
        ksort($params);
        $url = "";
        $first = true;
        foreach ($params as $key => $value) {
            $val = urlencode(strval($value));
            if ($first) {
                $first = false;
                $url = "$key=$val";
            } else {
                $url .= "&$key=$val";
            }
        }
        return $url;
    }

    /**
     * handleLOGHeaders
     * 处理请求头，规范一下
     * @static
     * @param $header
     * @return string
     */
    public static function handleLOGHeaders($header)
    {
        ksort($header);
        $content = '';
        $first = true;
        foreach ($header as $key => $value) {
            if (strpos($key, "x-log-") === 0 || strpos($key, "x-acs-") === 0) {
                if ($first) {
                    $content .= $key . ':' . $value;
                    $first = false;
                } else {
                    $content .= "\n" . $key . ':' . $value;
                }
            }
        }
        return $content;
    }

    /**
     * handleResource
     * 规范resource
     * @static
     * @param $resource
     * @param $params
     * @return string
     */
    public static function handleResource($resource, $params)
    {
        if ($params) {
            ksort($params);
            $urlString = "";
            $first = true;
            foreach ($params as $key => $value) {
                if ($first) {
                    $first = false;
                    $urlString = "$key=$value";
                } else {
                    $urlString .= "&$key=$value";
                }
            }
            return $resource . '?' . $urlString;
        }
        return $resource;
    }

    /**
     * getSignature
     * 获取签名
     * @static
     * @param $method
     * @param $resource
     * @param $accessKeySecret
     * @param $params
     * @param $headers
     * @return string
     */
    public static function getSignature($method, $resource, $accessKeySecret, $params, $headers)
    {
        if (!$accessKeySecret) {
            return '';
        }
        $content = $method . "\n";
        if (isset($headers['Content-MD5'])) {
            $content .= $headers['Content-MD5'];
        }
        $content .= "\n";
        if (isset($headers['Content-Type'])) {
            $content .= $headers['Content-Type'];
        }
        $content .= "\n";
        $content .= $headers['Date'] . "\n";
        $content .= self::handleLOGHeaders($headers) . "\n";
        $content .= self::handleResource($resource, $params);
        return self::hmacSHA1($content, $accessKeySecret);
    }

    /**
     * toBytes
     * Change $logGroup to bytes.
     * @static
     * @param $logGroup
     * @return bool|string
     */
    public static function toBytes($logGroup)
    {
        $mem = fopen("php://memory", "rwb");
        $logGroup->write($mem);
        rewind($mem);
        $bytes = "";

        if (feof($mem) === false) {
            $bytes = fread($mem, 10 * 1024 * 1024);
        }
        fclose($mem);
        return $bytes;
    }

    /**
     * @param $val
     * @return string
     * @author Junduo <caijunduo@gmail.com>
     */
    public static function encodeValue($val)
    {
        if (!$val) {
            $val = '0';
        }
        if (is_array($val)) {
            $val = json_encode($val, JSON_UNESCAPED_UNICODE);
        }
        if ($val === 0) {
            $val = '0';
        }
        if (!is_string($val)) {
            $val = strval($val);
        }

        return $val;
    }

    public static function isJson($str)
    {
        json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function decodeContent($contents)
    {
        foreach ($contents as &$content) {
            foreach ($content as $key => $val) {
                if (self::isJson($val)) {
                    $content[$key] = json_decode($val, true);
                }
            }
        }

        return $contents;
    }
}