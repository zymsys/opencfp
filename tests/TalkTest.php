<?php

/**
 * Set of tests for our Talk object
 */

class TalkTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test that creates a new talk
     *
     * @test
     */
    public function properlyCreateANewTalk()
    {
        // Mock our database connection
        $stmt = $this->getMockBuilder('StdClass')
            ->setMethods(array('execute'))
            ->getMock();
        $stmt->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(true));

        $db = $this->getMockBuilder('PDOMock')
            ->setMethods(array('prepare'))
            ->getMock();
        $db->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("INSERT INTO talks"))
            ->will($this->returnValue($stmt));

        $talk = new \OpenCFP\Model\Talk($db);
        $data = array(
            'title' => "The Awesome Talk of Awesomeoneess",
            'description' => "This is where a description of the talk would go, how long should it be?",
            'type' => 'tutorial',
            'user_id' => 1
        );
        $response = $talk->create($data);

        $this->assertTrue(
            $response,
            "Did now properly create a talk"
        );
    }

    /**
     * Verify that findById() returns a record as expected
     *
     * @test
     */
    public function titleFieldIsValidatedCorrectly()
    {
        $info = array(
            'id' => 2,
            'title' => "Best talk ever",
            'description' => "This is out talk description",
            'type' => 'session',
            'user_id' => 4
        );

        $stmt = $this->getMockBuilder('StdClass')
            ->setMethods(array('execute', 'fetch'))
            ->getMock();
        $stmt->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(true));
        $stmt->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($info));

        $db = $this->getMockBuilder('PDOMock')
            ->setMethods(array('prepare'))
            ->getMock();
        $db->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("SELECT * FROM talks"))
            ->will($this->returnValue($stmt));

        $talk = new \OpenCFP\Model\Talk($db);
        $record = $talk->findById($info['user_id']);

        $this->assertEquals(
            $info,
            $record,
            "Talk::findById() did not return the expected record"
        );
    }

    /**
     * Verify that findByUserId finds one or more talks by a user
     *
     * @test
     * @param integer $data
     * @dataProvider findByUserIdProvider
     */
    public function findByUserIdReturnsCorrectRecords($data)
    {
        $stmt = $this->getMockBuilder('StdClass')
            ->setMethods(array('execute', 'fetchAll'))
            ->getMock();
        $stmt->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(true));
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($data));

        $db = $this->getMockBuilder('PDOMock')
            ->setMethods(array('prepare'))
            ->getMock();
        $db->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("SELECT * FROM talks"))
            ->will($this->returnValue($stmt));

        $talk = new \OpenCFP\Model\Talk($db);
        $talks = $talk->findByUserId(1);

        $this->assertEquals(
            $data,
            $talks,
            "Did not get the expected talks"
        );
    }

    /**
     * Verify that getFullGrid returns all grid items
     * @test
     */
    public function getFullGridDataReturnsGridArray()
    {
        $speakerRows = array(
            array(
                'user_id' => 1,
                'name' => 'John Smith',
                'bio' => "John's Bio"
            ),
            array(
                'user_id' => 2,
                'name' => 'Jane Jones',
                'bio' => "Jane's Bio"
            ),
            array(
                'user_id' => 3,
                'name' => 'Mike Lee',
                'bio' => "Mike's Bio"
            ),
            array(
                'user_id' => 4,
                'name' => 'Wakeisha Murphy',
                'bio' => "Wakeisha's Bio"
            ),
        );
        $talkRows = array(
            array(
                'id' => 1,
                'user_id' => 1,
                'day' => 1,
                'slot' => 1,
                'room' => 1,
                'title' => 'Keynote',
                'description' => 'Talk description (keynote)'
            ),
            array(
                'id' => 2,
                'user_id' => 2,
                'day' => 1,
                'slot' => 2,
                'room' => 1,
                'title' => 'First talk after keynote in room 1',
                'description' => 'Talk description (Day 1; Room 1; Slot 2)'
            ),
            array(
                'id' => 3,
                'user_id' => 1,
                'day' => 1,
                'slot' => 2,
                'room' => 2,
                'title' => 'First talk after keynote in room 2',
                'description' => 'Talk description (Day 1; Room 2; Slot 2)'
            ),
            array(
                'id' => 4,
                'user_id' => 3,
                'day' => 2,
                'slot' => 1,
                'room' => 1,
                'title' => 'First talk of day 2 in room 1',
                'description' => 'Talk description (Day 2; Room 1; Slot 1)'
            ),
            array(
                'id' => 5,
                'user_id' => 4,
                'day' => 2,
                'slot' => 1,
                'room' => 2,
                'title' => 'First talk of day 2 in room 2',
                'description' => 'Talk description (Day 2; Room 2; Slot 1)'
            ),
//            array(
//                'id' => 6,
//                'user_id' => 2,
//                'day' => 2,
//                'slot' => 2,
//                'room' => 1,
//                'title' => 'Second talk of day 2 in room 1',
//                'description' => 'Talk description (Day 2; Room 1; Slot 2)'
//            ),
            array(
                'id' => 7,
                'user_id' => 4,
                'day' => 2,
                'slot' => 2,
                'room' => 2,
                'title' => 'Second talk of day 2 in room 2',
                'description' => 'Talk description (Day 2; Room 2; Slot 1&2)'
            ),
        );
        //Added days and slots; need dayspan and slotspan for spanning spots.
        $expectedGrid = array(
            'days' => 2,
            'slots' => 2,
            'rooms' => 2,
            'speakers' => array(
                1 => array(
                    'name' => 'John Smith',
                    'bio' => "John's Bio",
                ),
                2 => array(
                    'name' => 'Jane Jones',
                    'bio' => "Jane's Bio",
                ),
                3 => array(
                    'name' => 'Mike Lee',
                    'bio' => "Mike's Bio",
                ),
                4 => array(
                    'name' => 'Wakeisha Murphy',
                    'bio' => "Wakeisha's Bio",
                )
            ),
            'talks' => array(
                //Days => array of rooms
                1 => array(
                    //Slots => array of slots
                    1 => array(
                        //Rooms => associative array of talk data
                        1 => array(
                            'speakerId' => 1,
                            'talkId' => 1,
                            'talkTitle' => 'Keynote',
                            'description' => 'Talk description (keynote)',
                            'roomSpan' => 2
                        )
                        //No item in room 2 because the keynote occupies all rooms
                    ),
                    2 => array(
                        1 => array(
                            'speakerId' => 2,
                            'talkId' => 2,
                            'talkTitle' => 'First talk after keynote in room 1',
                            'description' => 'Talk description (Day 1; Room 1; Slot 2)'
                        ),
                        2 => array(
                            'speakerId' => 1,
                            'talkId' => 3,
                            'talkTitle' => 'First talk after keynote in room 2',
                            'description' => 'Talk description (Day 1; Room 2; Slot 2)'
                        )
                    )
                ),
                2 => array( //Day 2
                    1 => array( //Slot 1
                        1 => array( //Room 1
                            'speakerId' => 3,
                            'talkId' => 4,
                            'talkTitle' => 'First talk of day 2 in room 1',
                            'description' => 'Talk description (Day 2; Room 1; Slot 1)',
                            'slotSpan' => 2
                        ),
                        2 => array(
                            'speakerId' => 4,
                            'talkId' => 5,
                            'talkTitle' => 'First talk of day 2 in room 2',
                            'description' => 'Talk description (Day 2; Room 2; Slot 1)'
                        )
                    ),
                    2 => array(
//                        1 => array(
//                            'speakerId' => 2,
//                            'talkId' => 6,
//                            'talkTitle' => 'Second talk of day 2 in room 1',
//                            'description' => 'Talk description (Day 2; Room 1; Slot 2)'
//                        ),
                        2 => array(
                            'speakerId' => 4,
                            'talkId' => 7,
                            'talkTitle' => 'Second talk of day 2 in room 2',
                            'description' => 'Talk description (Day 2; Room 2; Slot 1&2)'
                        )
                    )
                )
            )
        );
        $stmtTalk = $this->getMockBuilder('StdClass')
            ->setMethods(array('execute', 'fetchAll'))
            ->getMock();
        $stmtTalk->expects($this->once())
            ->method('execute');
        $stmtTalk->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($talkRows));

        $stmtSpeaker = $this->getMockBuilder('StdClass')
            ->setMethods(array('execute', 'fetchAll'))
            ->getMock();
        $stmtSpeaker->expects($this->once())
            ->method('execute');
        $stmtSpeaker->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($speakerRows));

        $db = $this->getMockBuilder('PDOMock')
            ->setMethods(array('prepare'))
            ->getMock();
        $db->expects($this->exactly(2))
            ->method('prepare')
            ->will($this->onConsecutiveCalls($stmtTalk, $stmtSpeaker));

        $talk = new \OpenCFP\Model\Talk($db);
        $actualGrid = $talk->getGrid();

        $this->assertEquals(
            $expectedGrid,
            $actualGrid,
            '\OpenCFP\Model\Talk::getGrid() should assemble the grid correctly'
        );
    }

    /**
     * Data provider for findByUserIdReturnsCorrectRecords
     *
     * @return array
     */
    public function findByUserIdProvider()
    {
        return array(
            array(
                array(
                    'id' => 4,
                    'title' => 'Test talk',
                    'description' => 'Test description',
                    'type' => 'session',
                    'user_id' => 1
                )
            ),
            array(
                array(
                    'id' => 4,
                    'title' => 'Test talk',
                    'description' => 'Test description',
                    'type' => 'session',
                    'user_id' => 1
                ),
                array(
                    'id' => 5,
                    'title' => 'Test tutorial',
                    'description' => 'This is where the description of this tutorial goes',
                    'type' => 'tutorial',
                    'user_id' => 1
                )
            )
        );
    }

    /**
     * Test that updating an existing talk works correctly
     *
     * @test
     * @dataProvider updateProvider
     * @param boolean $updateResponse
     * @param integer $rowCount
     */
    public function updateTalkWorksCorrectly($updateResponse, $rowCount)
    {
        $stmt = $this->getMockBuilder('StdClass')
            ->setMethods(array('execute', 'rowCount'))
            ->getMock();
        $stmt->expects($this->once())
            ->method('rowCount')
            ->will($this->returnValue($rowCount));

        $db = $this->getMockBuilder('PDOMock')
            ->setMethods(array('prepare'))
            ->getMock();
        $db->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("UPDATE talks"))
            ->will($this->returnValue($stmt));

        $data = array(
            'id' => 1,
            'title' => 'Test Talk',
            'description' => 'Test description',
            'type' => 'session',
            'user_id' => 1
        );

        $talk = new \OpenCFP\Model\Talk($db);

        $this->assertEquals(
            $updateResponse,
            $talk->update($data),
            '\OpenCFP\Model\Talk::update() did not update valid data'
        );
    }

    /**
     * Data provider for verifyUpdateTalkWorksCorrectly
     *
     * @return array
     */
    public function updateProvider()
    {
        return array(
            array(true, 1),
            array(false, 3),
            array(false, 0)
        );
    }

    /**
     * Test to make sure deletion works correctly
     *
     * @test
     * @dataProvider deleteProvider
     * @param boolean $expectedResponse
     * @param boolean $rowCount
     */
    public function deleteTalkWorksCorrectly($expectedResponse, $rowCount)
    {
        // Values that don't mean anything
        $userId = 2;
        $talkId = 17;

        $stmt = $this->getMockBuilder('StdClass')
            ->setMethods(array('execute', 'rowCount'))
            ->getMock();
        $stmt->expects($this->once())
            ->method('rowCount')
            ->will($this->returnValue($rowCount));

        $db = $this->getMockBuilder('PDOMock')
            ->setMethods(array('prepare'))
            ->getMock();
        $db->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("DELETE FROM talks"))
            ->will($this->returnValue($stmt));

        $talk = new \OpenCFP\Model\Talk($db);

        $this->assertEquals(
            $expectedResponse,
            $talk->delete($talkId, $userId),
            '\OpenCFP\Model\Talk::delete() did not handle deletion correctly'
        );
    }

    /**
     * Data provider for deleteTalkWorksCorrectly
     *
     * @return array
     */
    public function deleteProvider()
    {
        return array(
            array(true, 1),
            array(false, 2),
            array(false, 0)
        );
    }
}
