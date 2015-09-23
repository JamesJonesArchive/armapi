<?php

/**
 * Copyright 2015 University of South Florida
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
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
 * UsfARMapiTest tests the UsfARMapi
 * ARM service methods
 *
 * @author James Jones <james@mail.usf.edu>
 * 
 */
class UsfARMapiTest extends \PHPUnit_Framework_TestCase {

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
     * @coversNothing
     */
    public function testRead() {
        // Test a connection based read
        $result = $this->getMongoConnection()->collection('accounts')->findOne(['employeeID' => '00000012345']);
        $this->assertEquals('U12345678', $result['identity']);
        // Test the mocked method based read
        $result = $this->usfARMapi->getARMaccounts()->findOne(['employeeID' => '00000012345']);
        $this->assertEquals('U12345678', $result['identity']);
    }
    /**
     * @covers UsfARMapi::getVersion
     */
    public function testGetVersion() {
        $version = "0.0.1";
        $this->assertEquals('0.0.1', $version);
    }
    public function testGetAllAccounts() {
        $response = $this->usfARMapi->getAllAccounts();
        var_dump($response);
        $this->assertCount(2,$response->getData()['GEMS']);
    }
    /**
     * @covers UsfARMapi::getAccountsForIdentity
     */
    public function testGetAccountsForIdentity() {
        
    }
    /**
     * @covers UsfARMapi::getRolesForIdentity
     */
    public function testGetRolesForIdentity() {
        
    }
    /**
     * @covers UsfARMapi::getRolesForAccount
     */
    public function testGetRolesForAccount() {
        
    }
    /**
     * @covers UsfARMapi::getIdentityForAccount
     */
    public function testGetIdentityForAccount() {
        
    }
    /**
     * @covers UsfARMapi::getIdentitiesForRole
     */
    public function testGetIdentitiesForRole() {
        
    }
    /**
     * @covers UsfARMapi::setAccountForIdentity
     */
    public function testSetAccountForIdentity() {
        
    }
    /**
     * @covers UsfARMapi::setRoleForAccount
     */
    public function testSetRoleForAccount() {
        
    }
    
}
