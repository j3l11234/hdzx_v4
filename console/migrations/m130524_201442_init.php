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

        $this->batchInsert('{{%room}}', ['number', 'name', 'type', 'data', 'align', 'status'], [
            [
            'number' => 404,
            'name' => '单技琴房1',
            'type' => '1',
            'data' => '{"secure":1,"max_before":5,"max_hour":2}',
            'align' => '1',
            'open' => '1',
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
