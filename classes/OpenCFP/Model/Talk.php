<?php
/**
 * Object that represents a talk that a speaker has submitted
 */
namespace OpenCFP\Model;

class Talk
{
    protected $_db;

    /**
     * Constructor for the class
     *
     * @param PDO $db
     */
    public function __construct($db)
    {
        $this->_db = $db;
    }

    /**
     * Create a talk when you pass new data in
     *
     * @param array $data
     * @return boolean
     */
    public function create($data)
    {
        $sql = "
            INSERT INTO talks
            (title, description, type, user_id)
            VALUES (?, ?, ?, ?)
            ";
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute(
            array(
                trim($data['title']),
                trim($data['description']),
                trim($data['type']),
                $data['user_id']
            )
        );
    }

    /**
     * Return a record that matches an ID passed into it
     *
     * @param int $id
     * @return array|false
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM talks WHERE id = ?";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array($id));

        return $stmt->fetch() ?: false;
    }

    /**
     * Return one or more Talk records that match a user ID passed in
     *
     * @param integer $userId
     * @return false|array
     */
    public function findByUserId($userId)
    {
        $sql = "SELECT * FROM talks WHERE user_id = ? ORDER BY title";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array($userId));

        return $stmt->fetchAll();

    }

    /**
     * Update a record using data passed in to it
     *
     * @param array $data
     * @return boolean
     */
    public function update($data)
    {
        if (empty($data['id'])) {
            return false;
        }

        $sql = "UPDATE talks
            SET title = ?,
            description = ?,
            type = ?
            WHERE id = ?
            AND user_id = ?
        ";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array(
            trim($data['title']),
            trim($data['description']),
            trim($data['type']),
            $data['id'],
            $data['user_id']
        ));

        return ($stmt->rowCount() === 1);
    }

    /**
     * Delete a Talk record given a talk ID and a user ID
     *
     * @param integer $talkId
     * @param integer $userId
     * @return boolean
     */
    public function delete($talkId, $userId)
    {
        $sql = "DELETE FROM talks WHERE id = ? AND user_id = ?";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array($talkId, $userId));

        return ($stmt->rowCount() === 1);
    }

    /**
     * Return an array of all the talks, ordered by the title by default 
     * by default
     *
     * @param string $order default is 'title'
     * @return array
     */
    public function getAll($orderBy = 'title')
    {
        $sql = "SELECT * FROM talks ORDER BY {$orderBy}";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $talks = array();

        $stmt = $this->_db->prepare($sql);
        $stmt->execute();

        $sql = "SELECT email FROM users WHERE id = ?";
        $stmt = $this->_db->prepare($sql);

        foreach ($results as $result) {
            $stmt->execute(array($result['user_id']));
            $userDetails = $stmt->fetch();
            $talkInfo = $result;
            $talkInfo['user'] = $userDetails['email'];
            $talks[] = $talkInfo;
        }

        return $talks;
    }

    /**
     * Assemble grid data from the speakers and talks table
     * @return array
     */
    public function getGrid()
    {
        $talks = array();
        $speakers = array();
        $days = 0;
        $slots = 0;
        $rooms = 0;

        $sql = "SELECT
            id, user_id, day, slot, room, title, description
        FROM talks
        WHERE day is not null
        ORDER BY day, room, slot";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $speakers[$row['user_id']] = array();
            if (!isset($talks[$row['day']])) {
                $talks[$row['day']] = array();
            }
            if (!isset($talks[$row['day']][$row['slot']])) {
                $talks[$row['day']][$row['slot']] = array();
            }
            $talks[$row['day']][$row['slot']][$row['room']] = array(
                'speakerId' => $row['user_id'],
                'talkId' => $row['id'],
                'talkTitle' => $row['title'],
                'description' => $row['description'],
            );
            if ($row['day'] > $days) {
                $days = $row['day'];
            }
            if ($row['slot'] > $slots) {
                $slots = $row['slot'];
            }
            if ($row['room'] > $rooms) {
                $rooms = $row['room'];
            }
        }

        $sql = "SELECT user_id, name, bio FROM speakerInfo where user_id in (" .
            implode(', ', array_keys($speakers)) . ")";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $speakers[$row['user_id']] = array(
                'name' => $row['name'],
                'bio' => $row['bio'],
            );
        }

        $grid = array(
            'days' => $days,
            'slots' => $slots,
            'rooms' => $rooms,
            'speakers' => $speakers,
            'talks' => $talks,
        );
        $this->findSpans($grid);
        return $grid;
    }

    private function findSpans(&$grid)
    {
        foreach($grid['talks'] as $day=>$slots) {
            foreach ($slots as $slot => $rooms) {
                foreach ($rooms as $room => $talk) {
                    $slotSpan = 1;
                    $roomSpan = 1;
                    while (
                        !isset($grid['talks'][$day][$slot+$slotSpan][$room]) &&
                        ($slot + $slotSpan) <= $grid['slots']
                    ) {
                        $slotSpan += 1;
                    }
                    while (
                        !isset($grid['talks'][$day][$slot][$room+$roomSpan]) &&
                        ($room + $roomSpan) <= $grid['rooms']
                    ) {
                        $roomSpan += 1;
                    }
                    if ($slotSpan > 1) {
                        $grid['talks'][$day][$slot][$room]['slotSpan'] = $slotSpan;
                    }
                    if ($roomSpan > 1) {
                        $grid['talks'][$day][$slot][$room]['roomSpan'] = $roomSpan;
                    }
                }
            }
        }
    }


}
