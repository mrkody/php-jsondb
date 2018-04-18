<?php
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
    'users.json',
    [
        'name' => 'Вася',
        'state' => 'Россия',
        'age' => 'стока не живут!'
    ]
);

$rows = $json_db->orderBy('name', JsonDb::ASC_SORT)->get();

foreach ($rows as $row) {
    print_r($row);
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

// Defaults to Thomas OR Nigeria
$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася', 'state' => 'Россия'])
    ->get();
print_r($users);

// Now is THOMAS AND Nigeria
$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася', 'state' => 'Россия'], 'AND')
    ->get();
print_r($users);

$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася'])
    ->orderBy('age', JsonDb::ASC_SORT);
