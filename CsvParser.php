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

    public string $separator;
    public array $options;

    public string $file_tmp;
    public string $file_dir;
    public string $file_base;
    public string $file_ext;
    public string $file_name;

    public array $keys = [];
    public $datas = [];

    /**
     * Undocumented function
     *
     * @param string $file [File path]
     * @param string $separator [Data separator]
     * @param array $options
     */
    function __construct(string $file, string $separator = ';', array $options = ['header' => true, 'header_line' => 0, 'encoding' => false])
    {

        /**
         * Check if a file is at real file
         */
        $path = realpath($file);
        if (!is_file($path)) {
            throw new Exception($path . ' Is not a file');
        }

        /**
         * Detail of file
         */
        $file = pathinfo($path);
        $this->file_dir = $file['dirname'];
        $this->file_base = $file['filename'];
        $this->file_ext = $file['extension'];
        $this->file_name = $file['basename'];

        /**
         * Options
         */
        $this->separator = $separator;
        $this->options = $options;

        /**
         * Encoding source datas
         */
        if (isset($this->options['encoding']) && $this->options['encoding'] == true) {
            $this->setEncoding();
        }

        /**
         * setting a dats in $datas
         */
        $this->initDatas();
    }

    private function setEncoding()
    {

        try {

            $encoding = exec('cd ' . $this->file_dir . ' && file -i ' . $this->file_name);
            $encoding = explode('charset=', $encoding);
            $encoding = trim(end($encoding));

            $this->file_tmp = $this->file_base . '-' . time() . '.' . $this->file_ext;
            $result = exec('cd ' . $this->file_dir . ' && iconv -f ' . $encoding . ' -t utf-8 ' . $this->file_name . ' -o ' . $this->file_tmp);
        } catch (\Throwable $th) {

            throw $th;
        }
    }

    private function initDatas()
    {

        /**
         * 
         */
        if (isset($this->options['encoding']) && $this->options['encoding'] == true) {

            $file = $this->file_dir . '/' . $this->file_tmp;
            $lines = file($file);
            unlink($file);
        } else {

            $lines = file($this->file_dir . '/' . $this->file_name);
        }

        /**
         * 
         */
        $header_data = [];
        if (isset($this->options['header']) && $this->options['header'] == true) {

            $header_line = (isset($this->options['header_line']) && is_int($this->options['header_line'])) ? $this->options['header_line'] : 0;

            if (isset($lines[$header_line])) {

                $header_data = $lines[$header_line];
                unset($lines[$header_line]);

                $line = trim(strip_tags(str_replace(["\r", "\n"], ['', ''], $header_data)));
                $values = explode($this->separator, $line);
                $this->keys = $values;
            }
        }

        foreach ($lines as $item => $line) {

            // Netoyage
            $line = trim(strip_tags(str_replace(["\r", "\n"], ['', ''], $line)));
            $values = explode($this->separator, $line);

            if (count($values) == count($this->keys)) {

                $this->datas[] = array_combine($this->keys, $values);
            } else {

                $this->datas[] = $values;
            }
        }
    }

    //----------------------- Getter et Setter -------------------//



    //----------------------- Instance -------------------//

    public function partial(int $first = 0, int|string $end = ''): CsvParser
    {

        if (is_array($this->datas)) {
            $datas = [];

            foreach ($this->datas as $key => $value) {

                if ($key >= $first && !is_int($end)) {

                    $datas[] = $value;
                } elseif ($key >= $first && is_int($end) && $key <= $end) {

                    $datas[] = $value;
                }
            }

            $this->datas = $datas;

            return $this;
        } else {

            throw new Exception('Datas is not a array');
        }
    }

    /**
     * Set header data
     *
     * @param array $keys
     * @return CsvParser
     */
    public function header(array $keys = []): CsvParser
    {

        $this->keys = $keys;

        $first_item = current($this->datas);

        if (count($first_item) == count($this->keys)) {

            $datas = [];

            if (is_array($this->datas)) {

                foreach ($this->datas as $key => $value) {

                    $datas[] = array_combine($this->keys, $value);
                }

                $this->datas = $datas;
            } else {

                throw new Exception('Datas is not a array');
            }
        }

        return $this;
    }

    public function where(string $key, string $operator, string|array $value): CsvParser
    {

        if (is_array($this->datas)) {

            $operator = in_array($operator, ['<>']) ? '!=' : $operator;

            $datas = [];

            foreach ($this->datas as $item) {

                if (array_key_exists($key, $item)) {

                    switch ($operator) {
                        case '=':
                            if ($item[$key] ==  $value) {
                                $datas[] = $item;
                            }
                            break;
                        case '!=':
                            if ($item[$key] !=  $value) {
                                $datas[] = $item;
                            }
                            break;
                        case '>':
                            if ($item[$key] >  $value) {
                                $datas[] = $item;
                            }
                            break;
                        case '>=':
                            if ($item[$key] >=  $value) {
                                $datas[] = $item;
                            }
                            break;
                        case '<':
                            if ($item[$key] < $value) {
                                $datas[] = $item;
                            }
                            break;
                        case '<=':
                            if ($item[$key] <= $value) {
                                $datas[] = $item;
                            }
                            break;
                        case 'like':
                            if (stripos($item[$key], $value) !== false) {
                                $datas[] = $item;
                            }
                            break;
                        case 'between':
                            if (
                                is_array($value) &&
                                $item[$key] >= current($value) &&
                                $item[$key] <= end($value)
                            ) {
                                $datas[] = $item;
                            }
                            break;
                        case 'in':
                            if (
                                is_array($value) &&
                                in_array($item[$key], $value)
                            ) {
                                $datas[] = $item;
                            }
                            break;
                    }
                }
            }

            $this->datas = $datas;

            return $this;
        } else {

            throw new Exception('Datas is not a array');
        }
    }

    /**
     * chunk the datas
     *
     * @param integer $length
     * @return CsvParser
     */
    public function chunk(int $length): CsvParser
    {

        if (!is_array($this->datas)) {
            throw new Exception('Datas is not a array');
        }

        $this->datas = array_chunk($this->datas, $length);

        return $this;
    }

    /**
     * Group the datas
     *
     * @param string $key
     * @return CsvParser
     */
    public function groupeBy(string $key): CsvParser
    {

        if (!is_array($this->datas)) {
            throw new Exception('Datas is not a array');
        }

        $datas = [];

        foreach ($this->datas as $value) {

            $datas[$value[$key]][] = $value;
        }

        $this->datas = $datas;

        return $this;
    }

    public function orderBy(string $key, string $sens = 'DESC'): CsvParser
    {
    }

    /**
     * Get the first item
     *
     * @return CsvParser
     */
    public function first(): CsvParser
    {

        if (!is_array($this->datas)) {
            throw new Exception('Datas is not a array');
        }

        $this->datas = current($this->datas);
        return $this;
    }

    /**
     * Get the last item
     *
     * @return CsvParser
     */
    public function last(): CsvParser
    {

        if (!is_array($this->datas)) {
            throw new Exception('Datas is not a array');
        }

        $this->datas = end($this->datas);
        return $this;
    }

    //----------------------- Convertisseur -------------------//

    /**
     * Convert data to json
     *
     * @return CsvParser
     */
    public function toJson(): CsvParser
    {

        if (!is_array($this->datas)) {
            throw new Exception('Datas is not a array');
        }

        $this->datas = json_encode($this->datas);
        return $this;
    }

    /**
     * Convert datas to csv
     *
     * @param string $separator
     * @return CsvParser
     */
    public function toCsv(string $separator): CsvParser
    {

        if (!is_array($this->datas)) {
            throw new Exception('Datas is not a array');
        }

        $datas = $this->keys ?  implode($separator, $this->keys) . "\n" : null;
        foreach ($this->datas as $data) {
            $datas .= implode($separator, $data) . "\n";
        }

        $this->datas = $datas;

        return $this;
    }

    /**
     * Convert datas to object
     *
     * @return CsvParser
     */
    public function toObject(): CsvParser
    {

        if (!is_array($this->datas)) {
            throw new Exception('Datas is not a array');
        }

        $datas = new \stdClass;
        foreach ($this->datas as $key => $value) {
            $datas->{$key} = (object) $value;
        }

        $this->datas = $datas;

        return $this;
    }

    /**
     * Convert data to sql
     *
     * @return CsvParser
     */
    public function toSql(): CsvParser
    {

        if (!is_array($this->datas)) {
            throw new Exception('Datas is not a array');
        }

        if (empty($this->keys)) {
            throw new Exception('No header available');
        }

        $columns = implode(", ", $this->keys);

        $values = null;
        foreach ($this->datas as $data) {
            $values .= ' ( ' . implode(', ', $data) . ' ),';
        }

        dd($values);

        $values = array_values($this->datas[1]);
        $values  = implode(", ", $values);
        $sql = "INSERT INTO `fbdata`($columns) VALUES ($values)";

        dd($sql);

        //return $this;
    }

    //----------------------- End of request -------------------//

    /**
     * Header keys
     *
     * @return array
     */
    public function keys(): array
    {

        if (!is_array($this->keys)) {
            throw new Exception('Keys is not a array');
        }

        return $this->keys;
    }

    /**
     * Calculates the sum of a key
     *
     * @param string $key
     * @return float
     */
    public function sum(string $key): float
    {

        if (!is_array($this->datas)) {
            throw new Exception('Datas is not a array');
        }

        $total = 0;

        foreach ($this->datas as $value) {

            if (isset($value[$key])) {

                $total += doubleval($value[$key]);
            }
        }

        return $total;
    }

    /**
     * Count items in datas
     *
     * @return integer
     */
    public function count(): int
    {

        if (!is_array($this->datas)) {
            throw new Exception('Datas is not a array');
        }

        return count($this->datas);
    }

    /**
     * Save datas in file
     *
     * @param string $path [Dir path to save file]
     * @param string $name [File name]
     * @return boolean
     */
    public function save(string $path = '.', string $name): bool
    {

        if (is_string($this->datas)) {
            throw new Exception('Datas is not a string : json | csv');
        }

        if ($path == '.') {

            $filename = $this->file_dir . '/' . $name;
        } else {

            $filename = realpath($path) . '/' . $name;
        }

        file_put_contents($filename, $this->datas);

        return true;
    }

    /**
     * Get datas
     *
     * @return array|string|object
     */
    public function get(): array|string|object
    {
        return $this->datas;
    }
}
