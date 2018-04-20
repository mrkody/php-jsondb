<?php
declare(strict_types = 1);
namespace joker2620\JsonDb;

/**
 * Class JsonDb
 *
 * @package joker2620\JsonDb
 */
class JsonDb
{
    const ASC_SORT  = 1;
    const DESC_SORT = 0;
    public  $file;
    public  $content     = [];
    private $where;
    private $select;
    private $merge;
    private $update;
    private $delete      = false;
    private $lastIndexes = [];
    private $orderBy     = [];

    /**
     * select()
     *
     * @param string $args
     *
     * @return $this
     */
    public function select(string $args = '*')
    {
        $this->select = explode(',', $args);
        $this->select = array_map('trim', $this->select);
        $this->select = array_filter($this->select);

        return $this;
    }

    /**
     * delete()
     *
     * @return $this
     */
    public function delete()
    {
        $this->delete = true;
        return $this;
    }

    /**
     * update()
     *
     * @param array $columns
     *
     * @return $this
     */
    public function update(array $columns)
    {
        $this->update = $columns;
        return $this;
    }

    /**
     * insert()
     *
     * @param array $values
     *
     * @return array
     * @throws DataBaseException
     */
    public function insert(array $values)
    {
        if (!empty($this->content[0])) {
            $nulls = array_diff_key(( array )$this->content[0], $values);
            if ($nulls) {
                $nulls  = array_map(
                    function () {
                        return '';
                    }, $nulls
                );
                $values = array_merge($values, $nulls);
            }
        }

        if (!empty($this->content) && array_diff_key($values, (array )$this->content[0])) {
            throw new DataBaseException('Columns must match as of the first row');
        } else {
            $this->content[]   = ( object )$values;
            $this->lastIndexes = [(count($this->content) - 1)];
            $this->commit();
        }
        return $this->lastIndexes;
    }

    public function commit(): void
    {
        $file_resource = fopen($this->file, 'w+');
        fwrite($file_resource, (!$this->content ? '[]' : json_encode($this->content)));
        fclose($file_resource);
    }

    /**
     * trigger()
     *
     * @return $this
     */
    public function trigger()
    {
        $this->content = (!empty($this->where) ? $this->whereResult() : $this->content);
        if ($this->delete) {
            if (!empty($this->lastIndexes) && !empty($this->where)) {
                $this->content = array_map(
                    function ($index, $value) {
                        if (in_array($index, $this->lastIndexes)) {
                            return false;
                        } else {
                            return $value;
                        }
                    }, array_keys($this->content), $this->content
                );
                $this->content = array_filter($this->content);
            } elseif (empty($this->where) && empty($this->lastIndexes)) {
                $this->content = [];
            }
            $this->delete = false;
        } elseif (!empty($this->update)) {
            $this->privateUpdate();
            $this->update = [];
        }
        $this->commit();
        return $this;
    }

    /**
     * whereResult()
     *
     * @return array
     */
    private function whereResult()
    {


        if ($this->merge == 'AND') {
            return $this->whereAndResult();
        } else {
            $return = [];

            foreach ($this->content as $index => $row_data) {

                $row_data = ( array )$row_data;
                foreach ($row_data as $column => $datum) {
                    if (in_array($column, array_keys($this->where))) {
                        if ($this->where[$column] == $row_data[$column]) {
                            $return[] = $row_data;

                            $this->lastIndexes[] = $index;
                        } else {
                            continue;
                        }
                    }
                }
            }
            return $return;
        }
    }

    /**
     * whereAndResult()
     *
     * @return array
     */
    private function whereAndResult()
    {

        $return = [];

        foreach ($this->content as $index => $row_data) {

            $row_data = ( array )$row_data;
            if (!array_diff($this->where, $row_data)) {
                $return[]            = $row_data;
                $this->lastIndexes[] = $index;
            } else {
                continue;
            }
        }
        return $return;
    }

    /**
     * privateUpdate()
     *
     * @throws DataBaseException
     */
    private function privateUpdate()
    {
        if (!empty($this->lastIndexes) && !empty($this->where)) {
            foreach ($this->content as $index => $value) {
                if (in_array($index, $this->lastIndexes)) {
                    $content = ( array )$this->content[$index];
                    if (!array_diff_key($this->update, $content)) {
                        $this->content[$index] = ( object )array_merge($content, $this->update);
                    } else {
                        throw new DataBaseException('Update method has an off key');
                    }
                } else {
                    continue;
                }
            }
        } elseif (!empty($this->where) && empty($this->lastIndexes)) {
            null;
        } else {
            foreach ($this->content as $index => $value) {
                $content = ( array )$this->content[$index];
                if (!array_diff_key($this->update, $content)) {
                    $this->content[$index] = ( object )array_merge($content, $this->update);
                } else {
                    throw new DataBaseException('Update method has an off key ');
                }
            }
        }
    }


    /**
     * toXml()
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public
    function toXml(
        string $from, string $to
    ) {
        $this->from($from);
        if ($this->content) {
            $element = pathinfo($from, PATHINFO_FILENAME);
            $xml_data
                     = '
			<?xml version="1.0"?>
				<' . $element . '>
';

            foreach ($this->content as $index => $value) {
                $xml_data
                    .= '
				<DATA>';
                foreach ($value as $colum => $values) {
                    $xml_data .= sprintf(
                        '
					<%s>%s</%s>', $colum, $values, $colum
                    );
                }
                $xml_data
                    .= '
				</DATA>
				';
            }
            $xml_data .= '</' . $element . '>';

            $xml_data = trim($xml_data);
            file_put_contents($to, $xml_data);
            return true;
        }
        return false;
    }

    /**
     * from()
     *
     * @param string $file
     *
     * @return $this
     */
    public
    function from(
        string $file
    ) {
        $this->file = $file;

        $this->where([]);

        $this->orderBy = [];

        if ($this->checkFile()) {
            $this->content = ( array )json_decode(file_get_contents($this->file));
        }
        return $this;
    }

    /**
     * where()
     *
     * @param array  $columns
     * @param string $merge
     *
     * @return $this
     */
    public
    function where(
        array $columns, $merge = 'OR'
    ) {
        $this->where = $columns;
        $this->merge = $merge;
        return $this;
    }

    /**
     * checkFile()
     *
     * @return bool
     * @throws DataBaseException
     */
    private
    function checkFile()
    {
        if (!file_exists($this->file)) {
            $this->commit();
        }

        $content = json_decode(file_get_contents($this->file));
        $this->checkJson();
        if (!is_array($content) && is_object($content)) {
            throw new DataBaseException('An array of json is required: Json data enclosed with []');
        } elseif (!is_array($content) && !is_object($content)) {
            throw new DataBaseException('json is invalid');
        } else {
            return true;
        }
    }

    /**
     * checkJson()
     */
    protected
    function checkJson()
    {
        $error = '';
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                $error = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $error = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $error = 'Unknown error';
                break;
        }
        if ($error !== '') {
            throw new DataBaseException('Invalid JSON: ' . $error);
        }
    }

    /**
     * toMysql()
     *
     * @param string $from
     * @param string $to
     * @param bool   $create_table
     *
     * @return bool
     */
    public
    function toMysql(
        string $from, string $to, bool $create_table = true
    ) {
        $this->from($from);
        if ($this->content) {
            $table = pathinfo($to, PATHINFO_FILENAME);

            $sql_data
                = "-- PHP-JSONDB JSON to MySQL Dump
--\r\n\r\n";
            if ($create_table) {
                $sql_data
                    .= "
-- Table Structure for `" . $table . "`
--

CREATE TABLE `" . $table . "`
	(
					";
                $first_row = ( array )$this->content[0];
                foreach (array_keys($first_row) as $column) {
                    $string = '`' . $column . '` ' . $this->toMysqlType(gettype($first_row[$column]));
                    $string .= (next($first_row) ? ',' : '');
                    $sql_data .= $string;
                }
                $sql_data
                    .= "
	);\r\n";
            }

            foreach ($this->content as $values) {
                $values = ( array )$values;
                $value  = array_map(
                    function ($value2) {
                        $value2 = (is_array($value2) || is_object($value2) ? serialize($value2) : $value2);
                        return "'" . addslashes($value2) . "'";
                    }, array_values($values)
                );

                $content = array_map(
                    function ($value2) {
                        return "`" . $value2 . "`";
                    }, array_keys($values)
                );
                $sql_data .= sprintf(
                /** @lang text */
                    "INSERT INTO `%s` ( %s ) VALUES ( %s );\n", $table, implode(', ', $content), implode(', ', $value)
                );
            }
            file_put_contents($to, $sql_data);
            return true;
        } else {
            return false;
        }
    }

    /**
     * toMysqlType()
     *
     * @param string $type
     *
     * @return string
     */
    private
    function toMysqlType(
        string $type
    ) {
        if ($type == 'bool') {
            $return = 'BOOLEAN';
        } elseif ($type == 'integer') {
            $return = 'INT';
        } elseif ($type == 'double') {
            $return = strtoupper($type);
        } else {
            $return = 'VARCHAR( 255 )';
        }
        return $return;
    }

    /**
     * orderBy()
     *
     * @param string $column
     * @param int    $order
     *
     * @return $this
     */
    public
    function orderBy(
        string $column, int $order = self::ASC_SORT
    ) {
        $this->orderBy = [$column, $order];
        return $this;
    }

    /**
     * getResult()
     *
     * @return array
     */
    public function get()
    {
        if ($this->where != null) {
            $content = $this->whereResult();
        } else {
            $content = $this->content;
        }

        if ($this->select && !in_array('*', $this->select)) {
            $return = [];
            foreach ($content as $index => $value) {
                $value = ( array )$value;
                foreach ($value as $index2 => $value2) {
                    if (in_array($index2, $this->select)) {
                        $return[$index][$index2] = $value2;
                    } else {
                        continue;
                    }
                }
            }
            $content = $return;
        }

        $content = $this->processOrderBy($content);

        return $content;
    }

    /**
     * processOrderBy()
     *
     * @param array $content
     *
     * @return array
     */
    private
    function processOrderBy(
        array $content
    ) {
        if ($this->orderBy && $content && in_array($this->orderBy[0], array_keys(( array )$content[0]))) {
            list($sort_column, $order_by) = $this->orderBy;
            $sort_keys = [];
            $sorted    = [];

            foreach ($content as $index => $value) {
                $value             = ( array )$value;
                $sort_keys[$index] = $value[$sort_column];
            }
            if ($order_by == self::ASC_SORT) {
                asort($sort_keys);
            } elseif ($order_by == self::DESC_SORT) {
                arsort($sort_keys);
            }
            foreach ($sort_keys as $index => $value) {
                $sorted[$index] = ( array )$content[$index];
            }

            $content = $sorted;
        }

        return $content;
    }
}
