<?php
/**
 * Selects random talks for testing purposes by populating day, room and
 * slot columns in the talks database.
 */
require '../classes/OpenCFP/Bootstrap.php';

$bootstrap = new \OpenCFP\Bootstrap();
$app = $bootstrap->getApp();

$max_day = 2;
$max_room = 4;
$max_slot = 6;

$sql = "SELECT id FROM talks ORDER BY RAND() LIMIT " . $max_day * $max_room * $max_slot;
$talkIds = array();
$stmt = $app['db']->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    $talkIds[] = $row['id'];
}

$sql = "UPDATE talks SET day=:day, room=:room, slot=:slot WHERE id=:id";
$stmt = $app['db']->prepare($sql);
for ($day = 1; $day <= $max_day; $day += 1) {
    for ($room = 1; $room <= $max_room; $room += 1) {
        for ($slot = 1; $slot <= $max_slot; $slot += 1) {
            $stmt->execute(array(
                ':day' => $day,
                ':room' => $room,
                ':slot' => $slot,
                ':id' => array_pop($talkIds),
            ));
        }
    }
}