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
        $result1 = $this->getMongoConnection()->collection('accounts')->findOne(['employeeID' => '00000012345']);
        // Confirming the identity key exists
        $this->assertArrayHasKey('identity',$result1);
        // Confirming the value of identity is not empty
        $this->assertNotEmpty($result1['identity']);
        // Confirming the value of the identity key is U12345678
        $this->assertEquals('U12345678', $result1['identity']);
        // Test the mocked method based read
        $result2 = $this->usfARMapi->getARMaccounts()->findOne(['employeeID' => '00000012345']);
        // Confirming the identity key exists
        $this->assertArrayHasKey('identity',$result2);
        // Confirming the value of identity is not empty
        $this->assertNotEmpty($result2['identity']);
        // Confirming the value of the identity key is U12345678
        $this->assertEquals('U12345678', $result2['identity']);
    }
    /**
     * @covers UsfARMapi::getVersion
     */
    public function testGetVersion() {
        $this->assertEquals($_SERVER['ARMAPI_VERSION'], $this->usfARMapi->getVersion());
    }
    /**
     * @covers UsfARMapi:getAllAccounts
     */
    public function testGetAllAccounts() {
        $response = $this->usfARMapi->getAllAccounts();
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the GEMS key exists
        $this->assertArrayHasKey('GEMS',$response->getData());
        // Confirming that the value of the GEMS key is not empty
        $this->assertNotEmpty($response->getData()['GEMS']);
        // Confirming the count of the values in the GEMS key
        $this->assertCount(2,$response->getData()['GEMS']);
        // Confirming the href exists
        $this->assertContains('/accounts/GEMS/RBULL', $response->getData()['GEMS']);
        // Confirming the href exists
        $this->assertContains('/accounts/GEMS/00000012345', $response->getData()['GEMS']);
        // Confirming the FAST key exists
        $this->assertArrayHasKey('FAST',$response->getData());
        // Confirming that the value of the FAST key is not empty
        $this->assertNotEmpty($response->getData()['FAST']);
        // Confirming the count of the values in the FAST key
        $this->assertCount(1,$response->getData()['FAST']);
        // Confirming the href exists
        $this->assertContains('/accounts/FAST/U12345678', $response->getData()['FAST']);
    }
    /**
     * @covers UsfARMapi::getAccountsForIdentity
     */
    public function testGetAccountsForIdentity() {
        $response = $this->usfARMapi->getAccountsForIdentity('U12345678');
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());        
        // Confirming the identity key exists
        $this->assertArrayHasKey('identity',$response->getData());
        // Confirming the value of identity is not empty
        $this->assertNotEmpty($response->getData()['identity']);
        // Confirming the value of the identity key is U12345678
        $this->assertEquals('U12345678', $response->getData()['identity']);
        // Confirming the accounts key exists
        $this->assertArrayHasKey('accounts',$response->getData());        
        // Confirming that the value of the accounts key is not empty
        $this->assertNotEmpty($response->getData()['accounts']);
        // Confirming the count of the values in the accounts key
        $this->assertCount(3,$response->getData()['accounts']);
        // Matching all 3 account href values
        $this->assertContains('/accounts/GEMS/RBULL',array_map(function($a) { return $a['href']; }, $response->getData()['accounts']));
        $this->assertContains('/accounts/GEMS/00000012345',array_map(function($a) { return $a['href']; }, $response->getData()['accounts']));
        $this->assertContains('/accounts/FAST/U12345678',array_map(function($a) { return $a['href']; }, $response->getData()['accounts']));
        // Getting each account for testing
        $account1 = \array_values(\array_filter($response->getData()['accounts'], function($a) { return ($a['href'] == '/accounts/GEMS/RBULL'); }))[0];
        $account2 = \array_values(\array_filter($response->getData()['accounts'], function($a) { return ($a['href'] == '/accounts/GEMS/00000012345'); }))[0];
        $account3 = \array_values(\array_filter($response->getData()['accounts'], function($a) { return ($a['href'] == '/accounts/FAST/U12345678'); }))[0];
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$account1);
        // Confirming that the value of the roles key is not empty
        $this->assertNotEmpty($account1['roles']);
        // Confirming the count of the values in the roles key
        $this->assertCount(3,$account1['roles']);
        // Matching all 3 role href values       
        $this->assertContains('/roles/GEMS/RPT2_ROLE',array_map(function($a) { return $a['href']; }, $account1['roles']));
        $this->assertContains('/roles/GEMS/PeopleSoft+User',array_map(function($a) { return $a['href']; }, $account1['roles']));
        $this->assertContains('/roles/GEMS/INQUIRE_ROLE',array_map(function($a) { return $a['href']; }, $account1['roles']));
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$account2);
        // Confirming that the value of the roles key is not empty
        $this->assertNotEmpty($account2['roles']);
        // Confirming the count of the values in the roles key
        $this->assertCount(6,$account2['roles']);
        // Matching all 6 role href values
        $this->assertContains('/roles/GEMS/USF_APPLICANT',array_map(function($a) { return $a['href']; }, $account2['roles']));
        $this->assertContains('/roles/GEMS/SELFSALL_ROLE',array_map(function($a) { return $a['href']; }, $account2['roles']));
        $this->assertContains('/roles/GEMS/USF_EMPLOYEE',array_map(function($a) { return $a['href']; }, $account2['roles']));
        $this->assertContains('/roles/GEMS/USF_WF_APPROVALS_USER',array_map(function($a) { return $a['href']; }, $account2['roles']));
        $this->assertContains('/roles/GEMS/PeopleSoft+User',array_map(function($a) { return $a['href']; }, $account2['roles']));
        $this->assertContains('/roles/GEMS/EFFORT_CERTIFIER_SS',array_map(function($a) { return $a['href']; }, $account2['roles']));
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$account3);
        // Confirming that the value of the roles key is not empty
        $this->assertNotEmpty($account3['roles']);
        // Confirming the count of the values in the roles key
        $this->assertCount(1,$account3['roles']);
        // Matching all 1 role href values
        $this->assertContains('/roles/FAST/USF_TR_TRAVELER',array_map(function($a) { return $a['href']; }, $account3['roles']));
    }
    /**
     * @covers UsfARMapi::getAccountsByType
     */
    public function testGetAccountsByType() {
        $response = $this->usfARMapi->getAccountsByType('GEMS');
        print_r($response);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());      
        // Confirming the account_type key exists
        $this->assertArrayHasKey('account_type',$response->getData());
        // Confirming the value of account_type is not empty
        $this->assertNotEmpty($response->getData()['account_type']);
        // Confirming the value of the account_type key is GEMS
        $this->assertEquals('GEMS', $response->getData()['account_type']);
        // Confirming the accounts key exists
        $this->assertArrayHasKey('accounts',$response->getData());
        // Confirming the value of accounts is not empty
        $this->assertNotEmpty($response->getData()['accounts']);
        // Confirming the count of the values in the accounts key
        $this->assertCount(2,$response->getData()['accounts']);
        // Getting each account for testing
        $account1 = \array_values(\array_filter($response->getData()['accounts'], function($a) { return ($a['href'] == '/accounts/GEMS/RBULL'); }))[0];
        $account2 = \array_values(\array_filter($response->getData()['accounts'], function($a) { return ($a['href'] == '/accounts/GEMS/00000012345'); }))[0];
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$account1);
        // Confirming that the value of the roles key is not empty
        $this->assertNotEmpty($account1['roles']);
        // Confirming the count of the values in the roles key
        $this->assertCount(3,$account1['roles']);
        // Matching all 3 role href values       
        $this->assertContains('/roles/GEMS/RPT2_ROLE',array_map(function($a) { return $a['href']; }, $account1['roles']));
        $this->assertContains('/roles/GEMS/PeopleSoft+User',array_map(function($a) { return $a['href']; }, $account1['roles']));
        $this->assertContains('/roles/GEMS/INQUIRE_ROLE',array_map(function($a) { return $a['href']; }, $account1['roles']));
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$account2);
        // Confirming that the value of the roles key is not empty
        $this->assertNotEmpty($account2['roles']);
        // Confirming the count of the values in the roles key
        $this->assertCount(6,$account2['roles']);
        // Matching all 6 role href values
        $this->assertContains('/roles/GEMS/USF_APPLICANT',array_map(function($a) { return $a['href']; }, $account2['roles']));
        $this->assertContains('/roles/GEMS/SELFSALL_ROLE',array_map(function($a) { return $a['href']; }, $account2['roles']));
        $this->assertContains('/roles/GEMS/USF_EMPLOYEE',array_map(function($a) { return $a['href']; }, $account2['roles']));
        $this->assertContains('/roles/GEMS/USF_WF_APPROVALS_USER',array_map(function($a) { return $a['href']; }, $account2['roles']));
        $this->assertContains('/roles/GEMS/PeopleSoft+User',array_map(function($a) { return $a['href']; }, $account2['roles']));
        $this->assertContains('/roles/GEMS/EFFORT_CERTIFIER_SS',array_map(function($a) { return $a['href']; }, $account2['roles']));
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
