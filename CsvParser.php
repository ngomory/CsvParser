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

    public $file;
    public $separator;
    public $header;

    public $file_tmp;
    public $file_dir;
    public $file_name;
    public $file_ext;
    public $file_fullname;

    public $datas = [];

    public function __construct(string $file, string $separator = ';', bool $header = false)
    {

        $this->file = pathinfo(realpath($file));
        $this->file_dir = $this->file['dirname'];
        $this->file_name = $this->file['filename'];
        $this->file_ext = $this->file['extension'];
        $this->file_fullname = $this->file_name . '.' . $this->file_ext;

        $this->separator = $separator;
        $this->header = $header;

        $this->setEncoding();
        $this->setDatas();
    }

    private function setEncoding()
    {

        try {

            $encoding = exec('cd ' . $this->file_dir . ' && file -i ' . $this->file_fullname);
            $encoding = explode('charset=', $encoding);
            $encoding = trim(end($encoding));
            $this->file_tmp = $this->file_name . '-' . time() . '.' . $this->file_ext;
            exec('cd ' . $this->file_dir . ' && iconv -f ' . $encoding . ' -t utf-8 ' . $this->file_fullname . ' -o ' . $this->file_tmp);
        } catch (\Throwable $th) {

            throw $th;
        }
    }

    private function setDatas()
    {

        $lines = file($this->file_dir . '/' . $this->file_tmp);
        unlink($this->file_dir . '/' . $this->file_tmp);

        foreach ($lines as $item => $line) {

            // Netoyage
            $line = trim(strip_tags(str_replace(["\r", "\n"], ['', ''], $line)));

            $values = explode($this->separator, $line);

            if ($item == 0 && $this->header == true) {
                $keys = $values;
                continue;
            } elseif ($item == 0 && $this->header == false) {
                $keys = [];
            }

            if (count($values) == count($keys)) {

                $this->datas[] = array_combine($keys, $values);
            } else {

                $this->datas[] = $values;
            }
        }

        return $this->datas;
    }

    public function header(array $keys = []): CsvParser
    {

        $first_item = current($this->datas);

        if (count($first_item) == count($keys)) {

            $datas = [];

            foreach ($this->datas as $key => $value) {

                $datas[] = array_combine($keys, $value);
            }

            $this->datas = $datas;
        }

        return $this;
    }

    public function partial(int $first = 0, int|string $end = ''): CsvParser
    {

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
    }

    public function where(string $key, string|null $operator, string $value): CsvParser
    {

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
                }
            }
        }

        $this->datas = $datas;

        return $this;
    }

    public function groupeBy(string $key): CsvParser
    {

        $datas = [];

        foreach ($this->datas as $value) {

            $datas[$value[$key]][] = $value;
        }

        $this->datas = $datas;

        return $this;
    }

    public function orderBy(string $key, string $sens = 'DESC')
    {
    }

    public function toJson(): CsvParser
    {
        $this->datas = json_encode($this->datas);
        return $this;
    }

    public function toCsv(string $separator)
    {
        # code...
    }

    public function toSql()
    {
        # code...
    }

    public function count(): int
    {
        return count($this->datas);
    }

    public function save(string $path = '.', string $name): bool
    {

        if (is_string($this->datas)) {

            if ($path == '.') {

                $filename = $this->file_dir . '/' . $name;
            } else {

                $filename = realpath($path) . '/' . $name;
            }

            file_put_contents($filename, $this->datas);

            return true;
        } else {

            return false;
        }
    }

    public function get()
    {
        return $this->datas;
    }
}
