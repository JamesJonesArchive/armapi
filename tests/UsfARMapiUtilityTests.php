<?php

/*
 * Copyright 2015 University of South Florida
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace USF\IdM;

/**
 * Description of UsfARMapiUtilityTests
 *
 * @author James Jones <james@mail.usf.edu>
 */
class UsfARMapiUtilityTests extends \PHPUnit_Framework_TestCase {
    use UsfARMmongomock;
    /**
     * @covers UsfARMapi::hasMatchingRole
     */
    public function testHasMatchingRole() {
        $this->assertTrue(UsfARMapi::hasMatchingRole([
            [ "role_id" => '56004d33c8492d91308b45d9']
        ], '56004d33c8492d91308b45d9'));
    }
    /**
     * @covers UsfARMapi::hasReviewForManager
     */
    public function testHasReviewForManager() {
        $this->assertTrue(UsfARMapi::hasReviewForManager([
            [ "usfid" => 'U99999999']
        ], 'U99999999'));
    }
    /**
     * @covers UsfARMapi::hasStateForManager
     */
    public function testHasStateForManager() {
        $this->assertTrue(UsfARMapi::hasStateForManager([
            [ "usfid" => 'U99999999']
        ], 'U99999999'));
    }
    /**
     * @covers UsfARMapi::getStateForManager
     */
    public function testGetStateForManager() {
        $this->assertEquals('pending_approval', UsfARMapi::getStateForManager([
            [ "usfid" => 'U99999999','state' => 'pending_approval']
        ], 'U99999999'));
    }
    /**
     * @covers UsfARMapi::getUpdatedReviewArray
     */
    public function testGetUpdatedReviewArray() {
        $this->assertEquals('open', UsfARMapi::getUpdatedReviewArray([
            [
                'usfid' => 'U99999999',
                'name' => 'Rocky Bull'                
            ]
        ], 'open', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])[0]['review']);
    }
    /**
     * @covers UsfARMapi::getUpdatedStateArray
     */
    public function testGetUpdatedStateArray() {
        $this->assertEquals('removal_pending', UsfARMapi::getUpdatedStateArray([
            [
                'usfid' => 'U99999999',
                'name' => 'Rocky Bull'                
            ]
        ], 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])[0]['state']);
    }
    /**
     * @covers UsfARMapi::getLastConfirm
     */
    public function testGetLastConfirm() {
        // Check the last confirm state
        $this->assertEquals('removal_pending',UsfARMapi::getLastConfirm([
            [
                'usfid' => 'U99999999',
                'name' => 'Rocky Bull',
                'timestamp' => '2011-08-22T11:43:13-0400',
                'state' => ''
            ],
            [
                'usfid' => 'U99999999',
                'name' => 'Rocky Bull',
                'timestamp' => '2015-06-05T00:30:23-0400',
                'state' => 'removal_pending'
            ]
        ], 'U99999999')['state']);
    }
    /**
     * @covers UsfARMapi::convertMongoDatesToUTCstrings
     */
    public function testConvertMongoDatesToUTCstrings() {
        $this->assertEquals('2011-08-22T15:43:13.000000Z',UsfARMapi::convertMongoDatesToUTCstrings([ 'timestamp' => new \MongoDate(strtotime('2011-08-22T15:43:13.000000Z')) ])['timestamp']);
    }
    /**
     * @covers UsfARMapi::convertUTCstringsToMongoDates
     */
    public function testConvertUTCstringsToMongoDates() {
        // Make sure the Mongo Date was created
        $this->assertTrue(UsfARMapi::convertUTCstringsToMongoDates(['password_change' => '2011-08-22T15:43:13.000000Z' ], ['password_change'])['password_change'] instanceof \MongoDate);
        // Make sure the Mongo Date matches
        $this->assertEquals(new \MongoDate(strtotime('2011-08-22T15:43:13.000000Z')), UsfARMapi::convertUTCstringsToMongoDates(['password_change' => '2011-08-22T15:43:13.000000Z' ], ['password_change'])['password_change']);
    }
    /**
     * @covers className::formatRoleName
     */
    public function testFormatRoleName() {
        $this->assertEquals('Rocky+Bull',UsfARMapi::formatRoleName('Rocky Bull'));
    }
}
