<?php

namespace Classes;

class StatFromFileStorage
{
    /**
     * @var integer
     */
    private $viewsCount;

    /**
     * @var integer
     */
    private $uniqueUrlsCount;

    /**
     * @var integer
     */
    private $totalTraffic;

    /**
     * @var array
     */
    private $crawlers = [
        'Google'    => 0,
        'Bing'      => 0,
        'Baidu'     => 0,
        'Yandex'    => 0,
        'unknown'   => 0,
    ];

    /**
     * @var array
     */
    private $statusCodes = [];

    /**
     * Геттер
     *
     * @param string $param
     * @return integer|array
     */
    public function get(string $param)
    {
        return $this->$param;
    }

    /**
     * Сеттер
     *
     * @param string|array $param
     * @param integer|array $amount
     * @return void
     */
    public function set($param, $amount): void
    {
        $this->$param = $amount;
    }

    /**
     * Возвращает JSON с набором данных
     *
     * @return string
     */
    public function getJson(): string
    {
        return json_encode([
            'views' => $this->viewsCount,
            'traffic' => $this->totalTraffic,
            'urls'  => $this->uniqueUrlsCount,
            'crawlers' => $this->crawlers,
            'statusCodes' => $this->statusCodes,
        ]);
    }
}