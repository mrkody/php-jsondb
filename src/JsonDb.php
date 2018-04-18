<?php
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
    public function select($args = '*')
    {
        /**
         * Explodes the selected columns into array
         *
         */

        // Explode to array
        $this->select = explode(',', $args);
        // Remove whitespaces
        $this->select = array_map('trim', $this->select);
        // Remove empty values
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
     * @param       $file
     * @param array $values
     *
     * @return array
     * @throws \Exception
     */
    public function insert($file, array $values)
    {
        $this->from($file);

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
            throw new \Exception('Columns must match as of the first row');
        } else {
            $this->content[]   = ( object )$values;
            $this->lastIndexes = [(count($this->content) - 1)];
            $this->commit();
        }
        return $this->lastIndexes;
    }

    /**
     * from()
     *
     * @param $file
     *
     * @return $this
     */
    public function from($file)
    {
        $this->file = $file;

        // Reset where
        $this->where([]);

        // Reset order by
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
    public function where(array $columns, $merge = 'OR')
    {
        $this->where = $columns;
        $this->merge = $merge;
        return $this;
    }

    /**
     * checkFile()
     *
     * @return bool
     * @throws \Exception
     */
    private function checkFile()
    {
        /**
         * Checks and validates if JSON file exists
         *
         * @return bool
         */

        // Checks if JSON file exists, if not create
        if (!file_exists($this->file)) {
            $this->commit();
        }

        // Read content of JSON file
        $content = file_get_contents($this->file);
        $content = json_decode($content);

        // Check if its arrays of jSON
        if (!is_array($content) && is_object($content)) {
            throw new \Exception('An array of json is required: Json data enclosed with []');
        } // An invalid jSON file
        elseif (!is_array($content) && !is_object($content)) {
            throw new \Exception('json is invalid');
        } else {
            return true;
        }
    }

    public function commit()
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
        /*
            Validates the where statement values
        */

        if ($this->merge == 'AND') {
            return $this->whereAndResult();
        } else {
            $r = [];

            // Loop through the existing values. Ge the index and row
            foreach ($this->content as $index => $row) {

                // Make sure its array data type
                $row = ( array )$row;

                // Loop again through each row,  get columns and values
                foreach ($row as $column => $value) {
                    // If each of the column is provided in the where statement
                    if (in_array($column, array_keys($this->where))) {
                        // To be sure the where column value and existing row column value matches
                        if ($this->where[$column] == $row[$column]) {
                            // Append all to be modified row into a array variable
                            $r[] = $row;

                            // Append also each row array key
                            $this->lastIndexes[] = $index;
                        } else {
                            continue;
                        }
                    }
                }
            }
            return $r;
        }
    }

    /**
     * whereAndResult()
     *
     * @return array
     */
    private function whereAndResult()
    {
        /*
            Validates the where statement values
        */
        $r = [];

        // Loop through the db rows. Ge the index and row
        foreach ($this->content as $index => $row) {

            // Make sure its array data type
            $row = ( array )$row;


            //check if the row = where['col'=>'val', 'col2'=>'val2']
            if (!array_diff($this->where, $row)) {
                $r[] = $row;
                // Append also each row array key
                $this->lastIndexes[] = $index;
            } else {
                continue;
            }
        }
        return $r;
    }

    private function privateUpdate()
    {
        if (!empty($this->lastIndexes) && !empty($this->where)) {
            foreach ($this->content as $i => $v) {
                if (in_array($i, $this->lastIndexes)) {
                    $content = ( array )$this->content[$i];
                    if (!array_diff_key($this->update, $content)) {
                        $this->content[$i] = ( object )array_merge($content, $this->update);
                    } else {
                        throw new \Exception('Update method has an off key');
                    }
                } else {
                    continue;
                }
            }
        } elseif (!empty($this->where) && empty($this->lastIndexes)) {
            null;
        } else {
            foreach ($this->content as $i => $v) {
                $content = ( array )$this->content[$i];
                if (!array_diff_key($this->update, $content)) {
                    $this->content[$i] = ( object )array_merge($content, $this->update);
                } else {
                    throw new \Exception('Update method has an off key ');
                }
            }
        }
    }

    /**
     * toXml()
     *
     * @param $from
     * @param $to
     *
     * @return bool
     */
    public function toXml($from, $to)
    {
        $this->from($from);
        if ($this->content) {
            $element = pathinfo($from, PATHINFO_FILENAME);
            $xml
                     = '
			<?xml version="1.0"?>
				<' . $element . '>
';

            foreach ($this->content as $index => $value) {
                $xml
                    .= '
				<DATA>';
                foreach ($value as $col => $val) {
                    $xml .= sprintf(
                        '
					<%s>%s</%s>', $col, $val, $col
                    );
                }
                $xml
                    .= '
				</DATA>
				';
            }
            $xml .= '</' . $element . '>';

            $xml = trim($xml);
            file_put_contents($to, $xml);
            return true;
        }
        return false;
    }

    /**
     * toMysql()
     *
     * @param      $from
     * @param      $to
     * @param bool $create_table
     *
     * @return bool
     */
    public function toMysql($from, $to, $create_table = true)
    {
        $this->from($from);
        if ($this->content) {
            $table = pathinfo($to, PATHINFO_FILENAME);

            $sql
                = "-- PHP-JSONDB JSON to MySQL Dump
--\r\n\r\n";
            if ($create_table) {
                $sql
                    .= "
-- Table Structure for `" . $table . "`
--

CREATE TABLE `" . $table . "`
	(
					";
                $first_row = ( array )$this->content[0];
                foreach (array_keys($first_row) as $column) {
                    $s = '`' . $column . '` ' . $this->toMysqlType(gettype($first_row[$column]));
                    $s .= (next($first_row) ? ',' : '');
                    $sql .= $s;
                }
                $sql
                    .= "
	);\r\n";
            }

            foreach ($this->content as $values) {
                $values = ( array )$values;
                $v      = array_map(
                    function ($vv) {
                        $vv = (is_array($vv) || is_object($vv) ? serialize($vv) : $vv);
                        return "'" . addslashes($vv) . "'";
                    }, array_values($values)
                );

                $c = array_map(
                    function ($vv) {
                        return "`" . $vv . "`";
                    }, array_keys($values)
                );
                $sql .= sprintf("INSERT INTO `%s` ( %s ) VALUES ( %s );\n", $table, implode(', ', $c), implode(', ', $v));
            }
            file_put_contents($to, $sql);
            return true;
        } else {
            return false;
        }
    }

    /**
     * toMysqlType()
     *
     * @param $type
     *
     * @return string
     */
    private function toMysqlType($type)
    {
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
     * @param     $column
     * @param int $order
     *
     * @return $this
     */
    public function orderBy($column, $order = self::ASC_SORT)
    {
        $this->orderBy = [$column, $order];
        return $this;
    }

    /**
     * get()
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
            $r = [];
            foreach ($content as $id => $row) {
                $row = ( array )$row;
                foreach ($row as $key => $val) {
                    if (in_array($key, $this->select)) {
                        $r[$id][$key] = $val;
                    } else {
                        continue;
                    }
                }
            }
            $content = $r;
        }

        // Finally, lets do sorting :)
        $content = $this->processOrderBy($content);

        return $content;
    }

    /**
     * processOrderBy()
     *
     * @param $content
     *
     * @return array
     */
    private function processOrderBy($content)
    {
        if ($this->orderBy && $content && in_array($this->orderBy[0], array_keys(( array )$content[0]))) {
            /*
                * Check if order by was specified
                * Check if there's actually a result of the query
                * Makes sure the column  actually exists in the list of columns
            */

            list($sort_column, $orderBy) = $this->orderBy;
            $sort_keys = [];
            $sorted    = [];

            foreach ($content as $index => $value) {
                $value = ( array )$value;
                // Save the index and value so we can use them to sort
                $sort_keys[$index] = $value[$sort_column];
            }

            // Let's sort!
            if ($orderBy == self::ASC_SORT) {
                asort($sort_keys);
            } elseif ($orderBy == self::DESC_SORT) {
                arsort($sort_keys);
            }

            // We are done with sorting, lets use the sorted array indexes to pull back the original content and return new content
            foreach ($sort_keys as $index => $value) {
                $sorted[$index] = ( array )$content[$index];
            }

            $content = $sorted;
        }

        return $content;
    }
}
