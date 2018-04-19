<?php
declare(strict_types = 1);
/**
 * File: noUnitTest.php;
 * Author: Joker2620;
 * Date: 19.04.2018;
 * Time: 0:46;
 */
include_once '../vendor/autoload.php';

use joker2620\JsonDb\JsonDb;

$json_db = new JsonDb();
$json_db->from('users.json');

$json_db->insert(
    [
        'name' => 'Вася',
        'state' => 'Россия',
        'age' => 'стока не живут!'
    ]
);

$rows_data = $json_db->orderBy('name', JsonDb::ASC_SORT)->get();

foreach ($rows_data as $row_dara) {
    print_r($row_dara);
    echo "<br>";
}
$users = $json_db->select('*')
    ->from('users.json')
    ->get();
print_r($users);

$users = $json_db->select('name, state')
    ->from('users.json')
    ->get();
print_r($users);

$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася'])
    ->get();
print_r($users);

$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася', 'state' => 'Россия'])
    ->get();
print_r($users);

$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася', 'state' => 'Россия'], 'AND')
    ->get();
print_r($users);

$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася'])
    ->orderBy('age');
