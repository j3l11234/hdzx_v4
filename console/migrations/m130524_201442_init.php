<?php

use yii\db\Schema;
use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        //user
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull()->unique(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string()->notNull(),
            'password_reset_token' => $this->string()->unique(),
            'dept_id' => $this->integer()->notNull(),
            'email' => $this->string()->notNull()->unique(),
            'alias' => $this->string()->notNull(),  
            'approve_dept' => $this->text()->notNull(),
            'privilege' => $this->integer()->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->insert('{{%user}}', [
            'id' => 1,
            'username' => 'admin',
            'auth_key' => '3P8HQa4cb5_KDuL8tbGkSFOClcwoznx8',
            'password_hash' => '$2y$13$PzJIiAaEOJ19Ade9gsOvPupX67CZYbXWNN8BXnrzuUEgMbE6djWg2',
            'dept_id' => '1',
            'email' => 'admin@admin.com',
            'alias' => '管理员',
            'approve_dept' => '[1,2,3,4]',
            'status' => '0',
        ]);

        //user_stu
        $this->createTable('{{%user_stu}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull()->unique(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string()->notNull(),
            'password_reset_token' => $this->string()->unique(),
            'dept_id' => $this->integer()->notNull(),
            'email' => $this->string()->notNull()->unique(),
            'alias' => $this->string()->notNull(),

            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        //department
        $this->createTable('{{%dept}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'align' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        //order
        $this->createTable('{{%order}}', [
            'id' => $this->primaryKey(),
            'date' => $this->date()->notNull(),
            'room_id' => $this->integer()->notNull(),
            'hours' => $this->text()->notNull(),

            'user_id' => $this->string(10)->notNull(),
            'dept_id' => $this->integer()->notNull(),

            'type' => $this->integer()->notNull(), //申请类型 琴房申请，活动室申请
            'status' => $this->integer()->notNull(), //申请状态
            'submit_time' => $this->integer()->notNull(),
            'data' => $this->text()->notNull(),
            //data包括 姓名 学号 联系方式 活动主题 活动内容 活动人数 安全措施 等等等等、、、
            // [
            //     'name' => '李鹏翔',
            //     'student_no' => '12301119',
            //     'phone' => '15612322',
            //     'title' => '学习',
            //     'content' => '学习',
            //     'number' => '1',
            //     'secure' => '做好了',
            // ]
            'issue_time' => $this->integer(), //开门条发放时间

            'updated_at' => $this->integer()->notNull(),
            'ver' => $this->integer(),
        ], $tableOptions);
        $this->createIndex('user_id', '{{%order}}', 'user_id');

        //预约的操作
        $this->createTable('{{%order_op}}', [
            'id' => $this->primaryKey(),  
            'order_id' => $this->integer()->notNull(), //操作的预约
            'user_id' => $this->string()->notNull(), //操作用户
            'time' => $this->integer()->notNull(), //操作时间
            'type' => $this->integer()->notNull(), //操作类型 审批通过 驳回、取消、修改时间、、、
            'data' => $this->text()->notNull(), //操作数据 审批备注、
        ], $tableOptions);

        //room
        $this->createTable('{{%room}}', [
            'id' => $this->primaryKey(),
            'number' => $this->integer()->notNull(), //房间号
            'name' => $this->string()->notNull(), //房间名
            'type' => $this->integer()->notNull(), //房间类型 琴房，活动室、、、
            'data' => $this->text()->notNull(),
            // [
            //     'secure' => 1,
            //     'max_before': => 30,
            //     'min_before': => 5,
            //     'max_hour':: => 2,
            // ]     
            'align' => $this->integer()->notNull(), //排序依据
            'status' => $this->integer()->notNull(), //房间状态
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->batchInsert('{{%room}}', ['id','number', 'name', 'type', 'data', 'align', 'status'], [
            [
                'id' => 440,
                'number' => 440,
                'name' => '团队讨论室5',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 441,
                'number' => 441,
                'name' => '团体活动室2',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 439,
                'number' => 439,
                'name' => '迷你活动室18',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 435,
                'number' => 435,
                'name' => '迷你活动室14',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 434,
                'number' => 434,
                'name' => '迷你活动室13',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 438,
                'number' => 438,
                'name' => '迷你活动室17',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 437,
                'number' => 437,
                'name' => '迷你活动室16',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 436,
                'number' => 436,
                'name' => '迷你活动室15',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 433,
                'number' => 433,
                'name' => '迷你活动室12',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 432,
                'number' => 432,
                'name' => '迷你活动室11',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 431,
                'number' => 431,
                'name' => '迷你活动室10',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 430,
                'number' => 430,
                'name' => '迷你活动室9',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 429,
                'number' => 429,
                'name' => '迷你活动室8',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 428,
                'number' => 428,
                'name' => '迷你活动室7',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 427,
                'number' => 427,
                'name' => '迷你活动室6',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 426,
                'number' => 426,
                'name' => '迷你活动室5',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 425,
                'number' => 425,
                'name' => '迷你活动室4',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 424,
                'number' => 424,
                'name' => '迷你活动室3',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 423,
                'number' => 423,
                'name' => '迷你活动室2',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 422,
                'number' => 422,
                'name' => '迷你活动室1',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 421,
                'number' => 421,
                'name' => '单技琴房18',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 420,
                'number' => 420,
                'name' => '单技琴房17',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 419,
                'number' => 419,
                'name' => '单技琴房16',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 418,
                'number' => 418,
                'name' => '单技琴房15',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 417,
                'number' => 417,
                'name' => '单技琴房14',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 416,
                'number' => 416,
                'name' => '单技琴房13',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 415,
                'number' => 415,
                'name' => '单技琴房12',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 414,
                'number' => 414,
                'name' => '单技琴房11',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 413,
                'number' => 413,
                'name' => '单技琴房10',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 412,
                'number' => 412,
                'name' => '单技琴房9',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 411,
                'number' => 411,
                'name' => '单技琴房8',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 410,
                'number' => 410,
                'name' => '单技琴房7',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 409,
                'number' => 409,
                'name' => '单技琴房6',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 408,
                'number' => 408,
                'name' => '单技琴房5',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 407,
                'number' => 407,
                'name' => '单技琴房4',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 406,
                'number' => 406,
                'name' => '单技琴房3',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 405,
                'number' => 405,
                'name' => '单技琴房2',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 404,
                'number' => 404,
                'name' => '单技琴房1',
                'type' => '2',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":"2","secure":"0"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 403,
                'number' => 403,
                'name' => '团队讨论室4',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 301,
                'number' => 301,
                'name' => '多功能厅',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"5","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 302,
                'number' => 302,
                'name' => '小剧场',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"5","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 501,
                'number' => 501,
                'name' => '视听培训室1',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 502,
                'number' => 502,
                'name' => '视听培训室2',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 503,
                'number' => 503,
                'name' => '排练室3',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 504,
                'number' => 504,
                'name' => '排练室4',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 505,
                'number' => 505,
                'name' => '排练室5',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 506,
                'number' => 506,
                'name' => '排练室6',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 508,
                'number' => 508,
                'name' => '排练室8',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 507,
                'number' => 507,
                'name' => '排练室7',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 509,
                'number' => 509,
                'name' => '视听培训室3',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 603,
                'number' => 603,
                'name' => '团队讨论室3',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '1'
            ],
            [
                'id' => 604,
                'number' => 604,
                'name' => '排练室1',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 605,
                'number' => 605,
                'name' => '排练室2',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ],
            [
                'id' => 606,
                'number' => 606,
                'name' => '团体活动室1',
                'type' => '1',
                'data' => '{"by_week":0,"max_before":30,"min_before":"1","max_hour":14,"secure":"1"}',
                'align' => '1',
                'open' => '0'
            ]
        ]);
        
        //room_table
        $this->createTable('{{%room_table}}', [
            'id' => $this->primaryKey(),
            'date' => $this->date()->notNull(),
            'room_id' => $this->integer()->notNull(),
            'ordered' => $this->text()->notNull(),
            'used' => $this->text()->notNull(),
            'locked' => $this->text()->notNull(),

            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'ver' => $this->integer(),
        ], $tableOptions);


        
    }

    public function down()
    {
        $this->dropTable('{{%user}}');
    }
}
