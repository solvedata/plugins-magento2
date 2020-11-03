<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter;

use Magento\Framework\HTTP\Adapter\Curl as CurlAdapter;
use SolveData\Events\Api\Event\Transport\AdapterInterface;

abstract class CurlAbstract extends CurlAdapter implements AdapterInterface
{
    /**
     * Get default options
     *
     * @return array
     */
    private function getDefaultConfig(): array
    {
        $config = [];
        foreach (array_keys($this->_config) as $param) {
            if (array_key_exists($param, $this->_allowedParams)) {
                $config[$this->_allowedParams[$param]] = $this->_config[$param];
            }
        }

        return $config;
    }

    /**
     * Send events by bulk
     *
     * @param array $events
     *
     * @return int[]
     *
     * @throws \Exception
     */
    abstract public function sendBulk(array $events): array;

    /**
     * Send event
     *
     * @param array $event
     *
     * @return int|false
     */
    abstract public function send(array $event);

    /**
     * Send request
     *
     * @param array $event
     *
     * @return int|null
     */
    abstract protected function request(array $event);

    /**
     * Send mass request
     *
     * @param array $events
     *
     * @return int[]
     */
    abstract protected function massRequest(array $events): array;

    /**
     * Curl_multi_* requests support
     *
     * @param array $urls
     * @param array $options
     *
     * @return array
     */
    public function multiRequest($urls, $options = []): array
    {
        $handles = [];
        $result = [];

        $multihandle = curl_multi_init();

        // add default parameters
        foreach ($this->getDefaultConfig() as $defaultOption => $defaultValue) {
            if (!isset($options[$defaultOption])) {
                $options[$defaultOption] = $defaultValue;
            }
        }

        foreach ($urls as $key => $url) {
            $handles[$key] = curl_init();
            curl_setopt($handles[$key], CURLOPT_URL, $url);
            curl_setopt($handles[$key], CURLOPT_HEADER, 0);
            curl_setopt($handles[$key], CURLOPT_RETURNTRANSFER, 1);
            /** @customization START */
            if (!empty($options[$key])) {
                curl_setopt_array($handles[$key], $options[$key]);
            }
            /** @customization END */
            curl_multi_add_handle($multihandle, $handles[$key]);
        }
        $process = null;
        do {
            curl_multi_exec($multihandle, $process);
            usleep(100);
        } while ($process > 0);

        foreach ($handles as $key => $handle) {
            /** @customization START */
            $result[$key] = [
                'request' => [
                    'url'        => $urls[$key],
                    'parameters' => urldecode($options[$key][CURLOPT_POSTFIELDS])
                ],
                'response' => [
                    'body' => curl_multi_getcontent($handle),
                    'code' => curl_getinfo($handle, CURLINFO_HTTP_CODE),
                ],
            ];
            /** @customization END */
            curl_multi_remove_handle($multihandle, $handle);
        }
        curl_multi_close($multihandle);

        return $result;
    }
}
