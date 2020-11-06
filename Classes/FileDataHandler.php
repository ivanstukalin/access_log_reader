<?php

namespace Classes;

use Classes\StatFromFileStorage;

class FileDataHandler
{

    const HTTP_USER_AGENT_PATTERN = "/\(([^)]*)\) (.*) \(([^)]*)\) (.*)/";

    const CRAWLERS = [
        'Google',
        'Bing',
        'Yandex',
        'Baidu',
    ];

    /**
     * @var StatFromFileStorage
     */
    private $statFromFileStorage;

    /**
     * @var integer
     */
    private $pathToFile;

    /**
     * @var array
     */
    private $fileRows = [];

    /**
     * @var array
     */
    private $parsedData = [
        [
            'ip'              => null,
            'date'            => null,
            'method'          => null,
            'url'             => null,
            'status'          => null,
            'traffic'         => null,
            'source'          => null,
            'userAgentInfo'   => null,
        ]
    ];

    /**
     * @var array
     */
    private $patterns = [
        'ip'            => '^(\S+)',
        'firstDash'     => '(\S+)',
        'secondDash'    => '(\S+)',
        'date'          => '\[([^\]]+)\]',
        'method'        => '\"(\S+) (.*?) (\S+)\"',
        'status'        => '(\S+)',
        'traffic'       => '(\S+)',
        'source'        => '(\".*?\")',
        'userAgentInfo' => '(\".*?\")',
    ];
    
    public function __construct(
        string $pathToFile, 
        StatFromFileStorage $statFromFileStorage
    ) {
        $this->pathToFile = $pathToFile;
        $this->statFromFileStorage = $statFromFileStorage;
    }

    /**
     * Получает обработанные данные из файла
     *
     * @return array
     */
    public function getFileInfo(): array
    {
        $this->dataExtract();

        $this->parseAllRows();
        
        return $this->parsedData;
    }

    /**
     * Выбирает статистические показатели и записывает их в StatFromFileSrotage
     *
     * @return void
     */
    public function selectStatInfo():void
    {
        $crawlers = [];
        $urls = [];
        $traffic = 0;
        $views = 0;
        $statusCodes = [];

        foreach ($this->parsedData as $parsedRow) {
            if(!$row) {
                continue;
            }
            foreach ($parsedRow as $key => $value) {
                switch ($key) {
                    case 'url':
                        $urls[] = $value;
                        break;
                    
                    case 'status':
                        $statusCodes[] = $value;
                        break;
                    
                    case 'traffic':
                        if ($parsedRow['status'] != '301') {
                            $traffic += $value;
                        }
                        break;

                    case 'userAgentInfo':
                        $crawler = $this->getInfoAboutCrawlers($value);
                        
                        if ($crawler) {
                            $crawlers[] = $crawler;
                        }

                        break;
                    
                    default:
                        break;
                }
            }

            $views++;
        }

        $this->statFromFileStorage->set('viewsCount', $views);
        $this->statFromFileStorage->set('crawlers',  $this->prepareCrawlersStat($crawlers));
        $this->statFromFileStorage->set('statusCodes', array_count_values($statusCodes));
        $this->statFromFileStorage->set('uniqueUrlsCount', count(array_count_values($urls)));
        $this->statFromFileStorage->set('totalTraffic', $traffic);
    }

    /**
     * Второй вариант
     * Менее ресурсозатратный способ чтения файла и выборки статистических 
     * показателей. 
     *
     * @return void
     */
    public function selectStatInfoEconomy()
    {
        $crawlers = [];
        $urls = [];
        $traffic = 0;
        $views = 0;
        $statusCodes = [];


        foreach ($this->readFile() as $row) {
            if(!$row) {
                continue;
            }
            $parsedRow = $this->rowParse($row);
            foreach ($parsedRow as $key => $value) {
                switch ($key) {
                    case 'url':
                        $urls[] = $value;
                        break;
                    
                    case 'status':
                        $statusCodes[] = $value;
                        break;
                    
                    case 'traffic':

                        if ($parsedRow['status'] != '301') {
                            $traffic += $value;
                        }

                        break;

                    case 'userAgentInfo':
                        $crawler = $this->getInfoAboutCrawlers($value);

                        if ($crawler) {
                            $crawlers[] = $crawler;
                        }

                        break;
                    
                    default:
                        break;
                }
            }

            $views++;
        }

        $this->statFromFileStorage->set('viewsCount', $views);
        $this->statFromFileStorage->set('crawlers', $this->prepareCrawlersStat($crawlers));
        $this->statFromFileStorage->set('statusCodes', array_count_values($statusCodes));
        $this->statFromFileStorage->set('uniqueUrlsCount', count(array_count_values($urls)));
        $this->statFromFileStorage->set('totalTraffic', $traffic);
    }

    /**
     * Подготавливает статистические показатели поисковых роботов
     *
     * @param array $crawlers
     * @return array
     */
    private function prepareCrawlersStat(array $crawlers): array
    {
        
        $crawlersStat = array_count_values($crawlers);

        foreach (self::CRAWLERS as $key) {
            if(!array_key_exists($key, $crawlersStat)) {
                $crawlersStat[$key] = 0;
            }
        }

        return $crawlersStat;
    }

    /**
     * Читает строки из файла
     *
     * @return void
     */
    private function dataExtract():void
    {
        foreach ($this->readFile() as $row) {
            $this->fileRows[] = $row;
        }
    }

    /**
     * Читает файл
     *
     * @return \Generator
     */
    private function readFile(): \Generator
    {
        $handle = fopen($this->pathToFile, "r");

        while(!feof($handle)) {
            yield trim(fgets($handle));
        }
    
        fclose($handle);
    }

    /**
     * Парсит данные из файла и записывает нужные параметры в массив
     *
     * @return void
     */
    private function parseAllRows(): void
    {
        $rowCounter = 0;

        foreach ($this->fileRows as $row) {
            $this->parsedData[$rowCounter] = $this->rowParse($row);
            $rowCounter++;
        }
    }

    /**
     * Собирает отдельные паттерны для каждого элемента строки в один паттерн
     *
     * @return string
     */
    private function prepareRowPattern(): string
    {
        $rowPattern = "/";

        foreach ($this->patterns as $pattern) {
            if($rowPattern === "/"){
                $rowPattern .= $pattern;
            } else {
                $rowPattern .= ' ' . $pattern;
            }
        }
        
        $rowPattern .= "/";

        return $rowPattern;
    }

    /**
     * Парсит строку, выбирая из нее нужные элементы
     *
     * @param string $row
     * @return array
     */
    private function rowParse(string $row): array
    {
        $rowPattern = $this->prepareRowPattern();
        preg_match($rowPattern, $row, $parsedRow);

        return [
            'ip'              => $parsedRow[1],
            'date'            => $parsedRow[4],
            'method'          => $parsedRow[5],
            'url'             => $parsedRow[6],
            'status'          => $parsedRow[8],
            'traffic'         => $parsedRow[9],
            'source'          => $parsedRow[10],
            'userAgentInfo'   => $parsedRow[11],
        ];
    }

    /**
     * Парсит HTTP_USER_AGENT, достает информацию о поисковом роботе
     *
     * @param string $userAgentInfo
     * @return string
     */
    private function getInfoAboutCrawlers(string $userAgentInfo): string
    {
        $findedRobot = null;
        
        preg_match(self::HTTP_USER_AGENT_PATTERN, $userAgentInfo, $parsedData);

        if($parsedData) {
            foreach (self::CRAWLERS as $robot) {
                $findedRobot = stristr($parsedData[2], $robot) ? $robot : null;
                if($findedRobot) {
                    break;
                }
            }
        }

        return $findedRobot ? $findedRobot : false;
    }
}