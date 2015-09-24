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

use \USF\IdM\UsfARMapi;
use USF\IdM\UsfConfig;
/**
 * Description of UsfARMapprovalsTest
 *
 * @author james
 */
class UsfARMapprovalsTest extends \PHPUnit_Framework_TestCase {
    
    use \Zumba\PHPUnit\Extensions\Mongo\TestTrait;

    const DEFAULT_DATABASE = 'mongounit_test';

    protected $connection;
    protected $dataset;
    protected $usfARMapi;
    
    /**
     * Get the mongo connection for this test.
     *
     * @return Zumba\PHPUnit\Extensions\Mongo\Client\Connector
     */
    protected function getMongoConnection() {
        // return new \MongoClient();
        if (empty($this->connection)) {
            $this->connection = new \Zumba\PHPUnit\Extensions\Mongo\Client\Connector(call_user_func(function() {
                //Access configuration values from default location (/usr/local/etc/idm_config)
                $config = new UsfConfig();

                // The DBAL connection configuration
                $mongoConfig = $config->mongoConfig;

                if(empty($mongoConfig)) {
                    return new \MongoClient();
                } elseif (!isset($mongoConfig['options'])) {
                    return new \MongoClient($mongoConfig['server']);
                } else {
                    return new \MongoClient($mongoConfig['server'],$mongoConfig['options']);
                }                
            }));
            $this->connection->setDb(static::DEFAULT_DATABASE);
        }
        return $this->connection;
    }

    /**
     * Get the dataset to be used for this test.
     *
     * @return Zumba\PHPUnit\Extensions\Mongo\DataSet\DataSet
     */
    protected function getMongoDataSet() {
        if (empty($this->dataSet)) {
            $this->dataSet = new \Zumba\PHPUnit\Extensions\Mongo\DataSet\DataSet($this->getMongoConnection());
            global $fixture;
            $this->dataSet->setFixture($fixture);
        }
        return $this->dataSet;
    }
    /**
     * Prepares the environment for mocking the mongo connection and the modified collection access functions
     * 
     */
    public function setUp() {
        $this->usfARMapi = $this->getMockBuilder('\USF\IdM\UsfARMapi')
        ->setMethods(array('getARMdb','getARMaccounts','getARMroles'))
        ->getMock();
        
        $this->usfARMapi->expects($this->any())
        ->method('getARMdb')
        ->will($this->returnValue($this->getMongoConnection()));
        
        $this->usfARMapi->expects($this->any())
        ->method('getARMaccounts')
        ->will($this->returnValue($this->getMongoConnection()->collection('accounts')));

        $this->usfARMapi->expects($this->any())
        ->method('getARMroles')
        ->will($this->returnValue($this->getMongoConnection()->collection('roles')));
        
        parent::setUp();
    }
    /**
     * @covers UsfARMapi::setAccountState
     */
    public function testSetAccountState() {
        $response = $this->usfARMapi->setAccountState('FAST', 'U12345678', '', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the type key exists
        $this->assertArrayHasKey('type',$response->getData());
        // Confirming that the value of the type key is not empty
        $this->assertNotEmpty($response->getData()['type']);
        // Confirming the value of the type key is FAST
        $this->assertEquals('FAST', $response->getData()['type']);
        // Confirming the identifier key exists
        $this->assertArrayHasKey('identifier',$response->getData());
        // Confirming that the value of the identifier key is not empty
        $this->assertNotEmpty($response->getData()['identifier']);
        // Confirming the value of the identifier key is U12345678
        $this->assertEquals('U12345678', $response->getData()['identifier']);
        
        // Confirming the state key exists
        $this->assertArrayHasKey('state',$response->getData());
        // Confirming that the value of the state key is not empty
        $this->assertNotEmpty($response->getData()['state']);
        // Make sure the state is matched for the specified manager (aka: USFID)
        $this->assertEquals('',UsfARMapi::getStateForManager($response->getData()['state'], 'U99999999'));                
    }
    /**
     * @covers UsfARMapi::setAccountState
     */
    public function testSetAccountState_AccountNotExists() {
        $response = $this->usfARMapi->setAccountState('FAST', 'U12345670', '', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('account',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['account']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS'], $response->getData()['account']);
    }
    /**
     * @covers UsfARMapi::setAccountRoleState
     */
    public function testSetAccountRoleState() {
        $response = $this->usfARMapi->setAccountRoleState('FAST', 'U12345678','USF_TR_TRAVELER','open',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the type key exists
        $this->assertArrayHasKey('type',$response->getData());
        // Confirming that the value of the type key is not empty
        $this->assertNotEmpty($response->getData()['type']);
        // Confirming the value of the type key is FAST
        $this->assertEquals('FAST', $response->getData()['type']);
        // Confirming the identifier key exists
        $this->assertArrayHasKey('identifier',$response->getData());
        // Confirming that the value of the identifier key is not empty
        $this->assertNotEmpty($response->getData()['identifier']);
        // Confirming the value of the identifier key is U12345678
        $this->assertEquals('U12345678', $response->getData()['identifier']);
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$response->getData());
        // Confirming that the value of the roles key is not empty
        $this->assertNotEmpty($response->getData()['roles']);
        // Get the affected role
        $role = \array_values(\array_filter($response->getData()['roles'], function($a) { return ($a['href'] == '/roles/FAST/USF_TR_TRAVELER'); }))[0];
        // Confirming the identifier key exists
        $this->assertArrayHasKey('state',$role);
        // Confirming that the value of the identifier key is not empty
        $this->assertNotEmpty($role['state']);
        // Confirming the state for the specified manager is 'open'
        $this->assertEquals('open',UsfARMapi::getStateForManager($role['state'], 'U99999999'));
    }
    /**
     * @covers UsfARMapi::setAccountRoleState
     */
    public function testSetAccountRoleState_AccountNotExists() {
        $response = $this->usfARMapi->setAccountRoleState('FAST', 'U12345670','USF_TR_TRAVELER','open',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        print_r($response);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('account',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['account']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS'], $response->getData()['account']);
    }
}
