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
 * Description of UsfARMimportTest
 *
 * @author james
 */
class UsfARMimportTest extends \PHPUnit_Framework_TestCase  {
    use \Zumba\PHPUnit\Extensions\Mongo\TestTrait;
    use UsfARMtestdata;
    
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
            $this->dataSet->setFixture(self::getFixture());            
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
     * @covers UsfARMapi::importAccount
     */
    public function testImportAccount() {
        $response = $this->usfARMapi->importAccount([
            "account_type" => "FAST",
            "account_identifier" => "ROCKYBULL",
            "account_identity" => "U12345678",
            "account_data" => [
                "employeeID" => "00000012345",
                "status" => "Locked"
            ]
        ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the href key exists
        $this->assertArrayHasKey('href',$response->getData());
        // Confirming that the value of the accounts key is not empty
        $this->assertNotEmpty($response->getData()['href']);
        // Check the last confirm state
        $this->assertEquals("/accounts/FAST/ROCKYBULL",$response->getData()['href']);
        // Confirm the account was created
        $this->assertTrue($this->usfARMapi->getAccountByTypeAndIdentifier("FAST","ROCKYBULL")->isSuccess());
    }
    /**
     * @covers UsfARMapi::importAccountRoles
     */
    public function testImportAccountRoles() {
        // The specified account has many roles. This should reduce that to one role
        $response = $this->usfARMapi->importAccountRoles([
            "account_type" => "GEMS",
            "account_identifier" => "00000012345",
            "account_roles" => [
                [
                    "href" => "/roles/GEMS/USF_APPLICANT",
                    "dynamic_role" => false
                ]
            ]
        ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$response->getData());
        // Confirming that the value of the roles key is not empty
        $this->assertNotEmpty($response->getData()['roles']);
        // Confirming the count of the roles is 1
        $this->assertCount(1, $response->getData()['roles']);
    }
    /**
     * @covers UsfARMapi::importAccountRoles
     */
    public function testImportAccountRoles_MissingAccountData() {
        $response = $this->usfARMapi->importAccountRoles([
            "account_type" => "GEMS",
            "account_identifier" => "00000012340",
            "account_roles" => [
                [
                    "href" => "/roles/GEMS/USF_APPLICANT",
                    "dynamic_role" => false
                ]
            ]
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('account',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['account']);
        // Confirming the value of the identity key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING'], $response->getData()['account']); 
    }
    /**
     * @covers UsfARMapi::importRole
     */
    public function testimportRole() {
        $response = $this->usfARMapi->importRole([
            "name" => "Test Role",
            "account_type" => "FAST",
            "role_data" => [
                "short_description" => "This is a test role",
                "long_description" => ""
            ]
        ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the href key exists
        $this->assertArrayHasKey('href',$response->getData());
        // Confirming that the value of the accounts key is not empty
        $this->assertNotEmpty($response->getData()['href']);
        // Check the last confirm state
        $this->assertEquals("/roles/FAST/Test+Role",$response->getData()['href']);
        // Confirm the account was created
        $this->assertTrue($this->usfARMapi->getRoleByTypeAndName("FAST","Test Role")->isSuccess());
    }
}
