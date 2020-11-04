<?php

namespace Classes;

use StatFromFileStorage;

class FileDataHandler
{
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
            'ip'        => null,
            'date'      => null,
            'method'    => null,
            'url'       => null,
            'status'    => null,
            'traffic'   => null,
            'source'    => null,
            'browser'   => null,
        ]
    ];

    /**
     * @var array
     */
    private $patterns = [
        'ip' => '^(\S+)',
        'firstDash' => '(\S+)',
        'secondDash' => '(\S+)',
        'date' => '\[([^\]]+)\]',
        'method' => '\"(\S+) (.*?) (\S+)\"',
        'status' => '(\S+)',
        'traffic' => '(\S+)',
        'source' => '(\".*?\")',
        'browser' => '(\".*?\")',
    ];
    
    public function __construct($pathToFile, $statFromFileStorage)
    {
        $this->pathToFile = $pathToFile;
        $this->statFromFileStorage = $statFromFileStorage;
    }

    public function getFileInfo()
    {
        $this->dataExtract();

        $this->parse();
        
        return $this->parsedData;
    }

    public function selectStatInfo()
    {
        $crawlers = [];
        $urls = [];
        $traffic = 0;
        $statusCodes = [];

        foreach ($this->parsedData as $parsedRow) {
            foreach ($parsedRow as $key => $value) {
                switch ($key) {
                    case 'url':
                        $urls[] = $value;
                        break;
                    
                    case 'status':
                        $statusCodes[] = $value;
                        break;
                    
                    case 'traffic':
                        $traffic += $value;
                        break;
                    
                    default:
                        break;
                }
            }
        }

        $this->statFromFileStorage->set('statusCodes', array_count_values($statusCodes));
        $this->statFromFileStorage->set('uniqueUrlsCount', count(array_count_values($urls)));
        $this->statFromFileStorage->set('totalTraffic', $traffic);
    }

    private function dataParser()
    {
        if(empty($this->fileRows)){
            $this->dataExtract();
        }
    }

    private function dataExtract()
    {
        foreach ($this->readFile() as $row) {
            $this->fileRows[] = $row;
        }
    }

    private function readFile()
    {
        $handle = fopen($this->pathToFile, "r");

        while(!feof($handle)) {
            yield trim(fgets($handle));
        }
    
        fclose($handle);
    }

    private function parse()
    {
        $rowPattern = $this->prepareRowPattern();
        $rowCounter = 0;
        foreach ($this->fileRows as $row) {
            preg_match($rowPattern, $row, $parsedRow);
            var_dump($parsedRow);
            $this->parsedData[$rowCounter] = [
                'ip'        => $parsedRow[1],
                'date'      => $parsedRow[4],
                'method'    => $parsedRow[5],
                'url'       => $parsedRow[6],
                'status'    => $parsedRow[8],
                'traffic'   => $parsedRow[9],
                'source'    => $parsedRow[10],
                'browser'   => $parsedRow[11],
            ];

            $rowCounter++;
        }
    }

    private function prepareRowPattern() {
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
}