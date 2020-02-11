<?php

/**
 * Class permettant de parser un fichier CSV
 * PHP version 7.3
 * 
 * @category CsvParser
 * @package  CsvParser
 * @author   ngomory <ngomory14@gmail.com>
 * @license  MIT www.mit.com
 * @link     www.ngomory.ci
 */
class CsvParser
{

    private $_path;
    private $_separator;

    /**
     * [__construct description]
     *
     * @param   string  $path       [$path description]
     * @param   string  $separator  [$separator description]
     *
     * @return  [type]              [return description]
     */
    public function __construct(string $path, string $separator = ';')
    {
        $this->path = $path;
        $this->separator = $separator;
    }

    /**
     * [getDataSimple description]
     *
     * @param   int  $first  [$first description]
     * @param   int  $end    [$end description]
     *
     * @return  [type]       [return description]
     */
    public function getDataSimple(int $first = 0, int $end = null) : array
    {
        $data = [];

        foreach (file($this->path) as $key => $value) {

            // Conversion en UTF-8
            $value = utf8_decode($value);

            // Netoyage
            $value = trim(strip_tags(str_replace(["\r\n"], [null], $value)));

            if ($key >= $first && $end == null) {

                $data[] = explode($this->separator, $value);
            } elseif ($key >= $first && is_int($end) && $key <= $end) {

                $data[] = explode($this->separator, $value);
            }
        }

        return $data;
    }
}
