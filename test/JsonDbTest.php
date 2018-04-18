<?php
use joker2620\JsonDb\JsonDb;

/**
 * File: JsonDbTest.php;
 * Author: Joker2620;
 * Date: 19.04.2018;
 * Time: 0:32;
 */
class JsonDbTest extends PHPUnit_Framework_TestCase
{
    public function testOrderBy()
    {
        static::assertFileExists('users.json');
        $json_db = new JsonDb();
        $json_db->from('users.json');
        $rows = $json_db->orderBy('name', JsonDb::ASC_SORT)->get();

        static::assertInternalType('array', $rows);
        static::assertNotEmpty($rows);

        foreach ($rows as $row) {
            print_r($row);
            echo "<br>";
        }
    }
}
