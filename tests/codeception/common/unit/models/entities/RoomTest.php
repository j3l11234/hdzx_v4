<?php

namespace tests\codeception\common\unit\models;

use Yii;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use common\models\entities\Room;
use tests\codeception\common\fixtures\RoomFixture;

/**
 * OrderOperation test
 */
class RoomTest extends DbTestCase {

    use Specify;

    public function testRW() {
        $this->specify('can write Room', function () {
            $data = [
                'number' => '404',
                'name' => '单机琴房1',
                'type' => '1',
                'align' => '1',
                'status' => '1',
            ];
            $roomData = [
                'secure' => 1,
                'open_per_week' => 1,
                'max_before' => 30,
                'min_before' => 5,
                'max_hour' => 2,
            ];

            $room = new Room();
            $room->load($data,'');
            $room->setRoomData($roomData);

            expect('save() return true', $room->save())->true();

            $newRoom = Room::findOne($room->getPrimaryKey());

            expect('room->number equal', $newRoom->number)->equals($data['number']);
            expect('room->name equal', $newRoom->name)->equals($data['name']);
            expect('room->type equal', $newRoom->type)->equals($data['type']);
            expect('room->align equal', $newRoom->align)->equals($data['align']);
            expect('room->status equal', $newRoom->status)->equals($data['status']);
            expect('room->roomData equal', $newRoom->getRoomData())->equals($roomData);
        });
    }

    public function testCheckOpen() {
         $data = [
            'number' => '404',
            'name' => '单机琴房1',
            'type' => '1',
            'align' => '1',
            'status' => '1',
        ];
        $roomData = [
            'secure' => 1,
            'open_per_week' => 1,
            'max_before' => 10,
            'min_before' => 5,
            'max_hour' => 2,
        ];

        $room = new Room();
        $room->load($data,'');
        $room->setRoomData($roomData);

        $now = strtotime('2015-12-15');
        expect('2015-12-19 false', $room->checkOpen('2015-12-19',$now))->false();
        expect('2015-12-20 true', $room->checkOpen('2015-12-20',$now))->true();
        expect('2015-12-21 true', $room->checkOpen('2015-12-21',$now))->true();
        expect('2015-12-23 true', $room->checkOpen('2015-12-23',$now))->true();
        expect('2015-12-27 true', $room->checkOpen('2015-12-27',$now))->true();
        expect('2015-12-28 false', $room->checkOpen('2015-12-28',$now))->false();
    }

    /**
     * @inheritdoc
     */
    public function fixtures()
    {
        return [
            'user' => [
                'class' => RoomFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/entities/room.php'
            ],
        ];
    }
}
