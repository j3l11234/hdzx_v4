<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */

$security = Yii::$app->getSecurity();

return [
    'username' => 'user'.$index,
    'auth_key' => $security->generateRandomString(),
    'password_hash' => $security->generatePasswordHash('123456'),
    'password_reset_token' => $security->generateRandomString() . '_' . time(),
    'dept_id' => 1,
    'email' => $faker->email,
    'alias' => $faker->firstName, 
    'approve_dept' => '[0,1,3]',
    'privilege' => 1,
    'status' => 2,
    'created_at' => time(),
    'updated_at' => time(),
];
