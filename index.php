<?php

if (!extension_loaded('redis')) {
    die("Расширение phpredis не установлено. Установите его для работы с Redis.\n");
}

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

echo "Подключение к Redis успешно установлено.\n";

function cacheData($redis, $key, $value, $expiration = 3600) {
    $redis->set($key, $value, ['ex' => $expiration]);
    echo "Данные закэшированы: $key => $value\n";
}

function getCachedData($redis, $key) {
    $value = $redis->get($key);
    echo "Получены закэшированные данные: $key => $value\n";
    return $value;
}

function setSession($redis, $sessionId, $data) {
    $redis->set("session:$sessionId", json_encode($data));
    echo "Сессия установлена: session:$sessionId\n";
}

function getSession($redis, $sessionId) {
    $data = json_decode($redis->get("session:$sessionId"), true);
    echo "Получены данные сессии: session:$sessionId => " . print_r($data, true) . "\n";
    return $data;
}

function pushTask($redis, $queueName, $task) {
    $redis->rPush($queueName, json_encode($task));
    echo "Задача добавлена в очередь: $queueName => " . print_r($task, true) . "\n";
}

function popTask($redis, $queueName) {
    $task = json_decode($redis->lPop($queueName), true);
    echo "Задача извлечена из очереди: $queueName => " . print_r($task, true) . "\n";
    return $task;
}

function publishMessage($redis, $channel, $message) {
    $redis->publish($channel, json_encode($message));
    echo "Сообщение опубликовано в канале: $channel => " . print_r($message, true) . "\n";
}

function subscribeToChannel($redis, $channel) {
    echo "Ожидание сообщений в канале: $channel\n";
    $redis->subscribe([$channel], function ($redis, $channel, $message) {
        echo "Получено сообщение в канале $channel: " . print_r(json_decode($message, true), true) . "\n";
    });
}

function setHashData($redis, $key, $field, $value) {
    $redis->hSet($key, $field, $value);
    echo "Данные добавлены в хэш: $key => $field => $value\n";
}

function getHashData($redis, $key, $field) {
    $value = $redis->hGet($key, $field);
    echo "Получены данные из хэша: $key => $field => $value\n";
    return $value;
}

function addToSet($redis, $key, $value) {
    $redis->sAdd($key, $value);
    echo "Элемент добавлен в множество: $key => $value\n";
}

function getSetMembers($redis, $key) {
    $members = $redis->sMembers($key);
    echo "Элементы множества $key: " . print_r($members, true) . "\n";
    return $members;
}

function addToSortedSet($redis, $key, $score, $value) {
    $redis->zAdd($key, $score, $value);
    echo "Элемент добавлен в сортированное множество: $key => $value (score: $score)\n";
}

function getSortedSetMembers($redis, $key) {
    $members = $redis->zRange($key, 0, -1, true);
    echo "Элементы сортированного множества $key: " . print_r($members, true) . "\n";
    return $members;
}

function executeTransaction($redis) {
    $redis->multi();
    $redis->set('transaction:key1', 'value1');
    $redis->set('transaction:key2', 'value2');
    $redis->exec();
    echo "Транзакция выполнена: два ключа установлены.\n";
}

function deleteKey($redis, $key) {
    $redis->del($key);
    echo "Ключ удален: $key\n";
}

cacheData($redis, 'user:1', 'John Doe');
getCachedData($redis, 'user:1');

setSession($redis, 'session:1', ['user_id' => 1, 'username' => 'johndoe']);
getSession($redis, 'session:1');

pushTask($redis, 'tasks', ['task_id' => 1, 'task_name' => 'Send email']);
popTask($redis, 'tasks');

if (pcntl_fork() == 0) {
    subscribeToChannel($redis, 'notifications');
    exit;
}

sleep(1);

publishMessage($redis, 'notifications', ['message' => 'Hello, Redis!']);sleep(1);

setHashData($redis, 'user:1:profile', 'name', 'John Doe');
getHashData($redis, 'user:1:profile', 'name');

addToSet($redis, 'user:1:friends', 'Alice');
addToSet($redis, 'user:1:friends', 'Bob');
getSetMembers($redis, 'user:1:friends');

addToSortedSet($redis, 'leaderboard', 100, 'Alice');
addToSortedSet($redis, 'leaderboard', 200, 'Bob');
getSortedSetMembers($redis, 'leaderboard');

executeTransaction($redis);

deleteKey($redis, 'user:1');

echo "Демонстрация завершена.\n";