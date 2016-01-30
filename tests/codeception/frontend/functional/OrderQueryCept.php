<?php
use tests\codeception\frontend\FunctionalTester;
use tests\codeception\common\_pages\LoginPage;

/* @var $scenario Codeception\Scenario */

$I = new FunctionalTester($scenario);
$I->wantTo('get roon list');

$I->amGoingTo('get room list');
$I->sendGet('/order/getrooms');
$I->seeResponseIsJson();

$I->amGoingTo('get roomtables');
$I->sendPost('/order/getroomtables', [
	'start_date' => '2016-01-30',
    'end_date' => '2016-02-28',
	'rooms' => '[404]'
	]);
$I->seeResponseIsJson();

//$aa= $I->grabResponse();
//codecept_debug($aa);



// $loginPage = LoginPage::openBy($I);

// $I->amGoingTo('submit login form with no data');
// $loginPage->login('', '');
// $I->expectTo('see validations errors');
// $I->see('Username cannot be blank.', '.help-block');
// $I->see('Password cannot be blank.', '.help-block');

// $I->amGoingTo('try to login with wrong credentials');
// $I->expectTo('see validations errors');
// $loginPage->login('admin', 'wrong');
// $I->expectTo('see validations errors');
// $I->see('Incorrect username or password.', '.help-block');

// $I->amGoingTo('try to login with correct credentials');
// $loginPage->login('erau', 'password_0');
// $I->expectTo('see that user is logged');
// $I->seeLink('Logout (erau)');
// $I->dontSeeLink('Login');
// $I->dontSeeLink('Signup');
