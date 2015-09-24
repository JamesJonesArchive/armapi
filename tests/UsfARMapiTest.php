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
     * @covers UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType_NullAccount() {        
        $response = $this->usfARMapi->createAccountByType('GEMS',null);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('account',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['account']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING'], $response->getData()['account']);
    }
    /**
     * @covers UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType_ValidAccountInfo() {
        // Testing account with no key value pairs
        $response = $this->usfARMapi->createAccountByType('GEMS',[]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('account',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['account']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING_REQUIRED_KEYS'], $response->getData()['account']);

        // Testing account with 2 missing key value pairs
        $response2 = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS' ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response2->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('account',$response2->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response2->getData()['account']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING_REQUIRED_KEYS'], $response2->getData()['account']);        
        
        // Testing account with 1 missing key value pairs
        $response3 = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS','account_identifier' => '00000012345' ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response3->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('account',$response3->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response3->getData()['account']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING_REQUIRED_KEYS'], $response3->getData()['account']);                
    }
    /**
     * @covers UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType_AccountDataEmpty() {
        $response = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS','account_identifier' => '00000012345', 'account_data' => [] ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('account',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['account']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_DATA_EMPTY'], $response->getData()['account']);                        
    }
    /**
     * @covers UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType_AccountTypeMismatch() {
        $response = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS_bad','account_identifier' => '00000012345', 'account_data' => [ 'anything' => 'myvalue' ] ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('account',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['account']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_TYPE_MISMATCH'], $response->getData()['account']);                                
    }
    /**
     * @covers UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType_AccountExists() {
        $response = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS','account_identifier' => '00000012345', 'account_data' => [ 'anything' => 'myvalue' ] ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('account',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['account']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_EXISTS'], $response->getData()['account']);                                
    }
    /**
     * @covers UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType() {
        $response = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS','account_identifier' => '00000012340', 'account_data' => [ 'anything' => 'myvalue' ] ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());    
        // Confirming the href key exists
        $this->assertArrayHasKey('href',$response->getData());
        // Confirming the value of href is not empty
        $this->assertNotEmpty($response->getData()['href']);
        // Confirming the value of the href key is the error message
        $this->assertEquals('/accounts/GEMS/00000012340', $response->getData()['href']); 
    }
    /**
     * @covers UsfARMapi::getAccountByTypeAndIdentifier
     */
    public function testGetAccountByTypeAndIdentifier_AccountNotFound() {
        $response = $this->usfARMapi->getAccountByTypeAndIdentifier('GEMS','00000012340');
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
     * @covers UsfARMapi::getAccountByTypeAndIdentifier
     */
    public function testGetAccountByTypeAndIdentifier() {
        $response = $this->usfARMapi->getAccountByTypeAndIdentifier('GEMS','00000012345');
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess()); 
        // Confirming the href key exists
        $this->assertArrayHasKey('href',$response->getData());
        // Confirming the value of href is not empty
        $this->assertNotEmpty($response->getData()['href']);
        // Confirming the value of the href key is the error message
        $this->assertEquals('/accounts/GEMS/00000012345', $response->getData()['href']);
        
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$response->getData());
        // Confirming the value of roles is not empty
        $this->assertNotEmpty($response->getData()['roles']);
        // Confirming the count of the values in the roles key
        $this->assertCount(6,$response->getData()['roles']);
        
        // Matching all 6 role href values
        $this->assertContains('/roles/GEMS/USF_APPLICANT',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
        $this->assertContains('/roles/GEMS/SELFSALL_ROLE',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
        $this->assertContains('/roles/GEMS/USF_EMPLOYEE',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
        $this->assertContains('/roles/GEMS/USF_WF_APPROVALS_USER',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
        $this->assertContains('/roles/GEMS/PeopleSoft+User',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
        $this->assertContains('/roles/GEMS/EFFORT_CERTIFIER_SS',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
    }
    /**
     * @covers UsfARMapi::modifyAccountByTypeAndIdentifier
     */
    public function testModifyAccountByTypeAndIdentifier_AccountNotFound() {
        $response = $this->usfARMapi->modifyAccountByTypeAndIdentifier('GEMS','00000012340',[ 'anything' => 'myvalue' ]);
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
     * @covers UsfARMapi::modifyAccountByTypeAndIdentifier
     */
    public function testModifyAccountByTypeAndIdentifier() {
        $response = $this->usfARMapi->modifyAccountByTypeAndIdentifier('GEMS','00000012345',[ 'anything' => 'myvalue' ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());    
        // Confirming the href key exists
        $this->assertArrayHasKey('href',$response->getData());
        // Confirming the value of href is not empty
        $this->assertNotEmpty($response->getData()['href']);
        // Confirming the value of the href key is the error message
        $this->assertEquals('/accounts/GEMS/00000012345', $response->getData()['href']);        
    }
    /**
     * @covers UsfARMapi::getRolesForAccountByTypeAndIdentifier
     */
    public function testGetRolesForAccountByTypeAndIdentifier_AccountNotFound() {
        $response = $this->usfARMapi->getRolesForAccountByTypeAndIdentifier('GEMS','00000012340');
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
     * @covers UsfARMapi::getRolesForAccountByTypeAndIdentifier
     */
    public function testGetRolesForAccountByTypeAndIdentifier() {
        $response = $this->usfARMapi->getRolesForAccountByTypeAndIdentifier('GEMS','00000012345');
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess()); 
        // Confirming the identifier key exists
        $this->assertArrayHasKey('identifier',$response->getData());
        // Confirming the value of identifier is not empty
        $this->assertNotEmpty($response->getData()['identifier']);
        // Confirming the value of the identifier key is U12345678
        $this->assertEquals('00000012345', $response->getData()['identifier']);
        // Confirming the type key exists
        $this->assertArrayHasKey('type',$response->getData());
        // Confirming the value of type is not empty
        $this->assertNotEmpty($response->getData()['type']);
        // Confirming the value of the type key is U12345678
        $this->assertEquals('GEMS', $response->getData()['type']);
        // Confirming the type key exists
        $this->assertArrayHasKey('roles',$response->getData());
        // Confirming the value of roles is not empty
        $this->assertNotEmpty($response->getData()['roles']);
        // Confirming the count of the values in the roles key
        $this->assertCount(6,$response->getData()['roles']);
        
        // Matching all 6 role href values
        $this->assertContains('/roles/GEMS/USF_APPLICANT',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
        $this->assertContains('/roles/GEMS/SELFSALL_ROLE',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
        $this->assertContains('/roles/GEMS/USF_EMPLOYEE',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
        $this->assertContains('/roles/GEMS/USF_WF_APPROVALS_USER',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
        $this->assertContains('/roles/GEMS/PeopleSoft+User',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
        $this->assertContains('/roles/GEMS/EFFORT_CERTIFIER_SS',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
    }
    /**
     * @covers UsfARMapi::modifyRolesForAccountByTypeAndIdentifier
     */
    public function testModifyRolesForAccountByTypeAndIdentifier() {
        $response = $this->usfARMapi->modifyRolesForAccountByTypeAndIdentifier('GEMS','00000012345',['role_list' => [
            [
                'href' => '/roles/GEMS/SELFSALL_ROLE',
                "dynamic_role" => true
            ]
        ]]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess()); 
        // Confirming the identifier key exists
        $this->assertArrayHasKey('identifier',$response->getData());
        // Confirming the value of identifier is not empty
        $this->assertNotEmpty($response->getData()['identifier']);
        // Confirming the value of the identifier key is U12345678
        $this->assertEquals('00000012345', $response->getData()['identifier']);
        // Confirming the type key exists
        $this->assertArrayHasKey('type',$response->getData());
        // Confirming the value of type is not empty
        $this->assertNotEmpty($response->getData()['type']);
        // Confirming the value of the type key is U12345678
        $this->assertEquals('GEMS', $response->getData()['type']);
        // Confirming the type key exists
        $this->assertArrayHasKey('roles',$response->getData());
        // Confirming the value of roles is not empty
        $this->assertNotEmpty($response->getData()['roles']);
        // Confirming the count of the values in the roles key
        $this->assertCount(1,$response->getData()['roles']);
        // Matching single role href values
        $this->assertContains('/roles/GEMS/SELFSALL_ROLE',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
    }
    /**
     * @covers UsfARMapi::modifyRolesForAccountByTypeAndIdentifier
     */
    public function testModifyRolesForAccountByTypeAndIdentifier_AccountNotFound() {
        $response = $this->usfARMapi->modifyRolesForAccountByTypeAndIdentifier('GEMS','00000012340',[]);
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
     * @covers UsfARMapi::modifyRolesForAccountByTypeAndIdentifier
     */
    public function testModifyRolesForAccountByTypeAndIdentifier_NoRoleList() {
        $response = $this->usfARMapi->modifyRolesForAccountByTypeAndIdentifier('GEMS','00000012345',[]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('roles',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['roles']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_LIST_MISSING'], $response->getData()['roles']);
    }
    /**
     * @covers UsfARMapi::modifyRolesForAccountByTypeAndIdentifier
     */
    public function testModifyRolesForAccountByTypeAndIdentifier_InvalidRoles() {
        $response = $this->usfARMapi->modifyRolesForAccountByTypeAndIdentifier('GEMS','00000012345',['role_list' => [
            [
                'href' => '/roles/GEMS/INVALID_ROLE_THAT_DOESNT_EXIST'
            ]
        ]]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('role_list',$response->getData());
        // Confirming the value of role_list is not empty
        $this->assertNotEmpty($response->getData()['role_list']);
        // Confirming the value of the role_list key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLES_CONTAINS_INVALID'], $response->getData()['role_list']);
    }
    /**
     * @covers UsfARMapi::getAccountsByTypeAndIdentity
     */
    public function testGetAccountsByTypeAndIdentity() {
        $response = $this->usfARMapi->getAccountsByTypeAndIdentity('GEMS','U12345678');
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
        $this->assertCount(2,$response->getData()['accounts']);

        // Matching all 3 account href values
        $this->assertContains('/accounts/GEMS/RBULL',array_map(function($a) { return $a['href']; }, $response->getData()['accounts']));
        $this->assertContains('/accounts/GEMS/00000012345',array_map(function($a) { return $a['href']; }, $response->getData()['accounts']));
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
     * @covers UsfARMapi::getAllRoles
     */
    public function testGetAllRoles() {
        $response = $this->usfARMapi->getAllRoles();
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the GEMS key exists
        $this->assertArrayHasKey('GEMS',$response->getData());
        // Confirming that the value of the GEMS key is not empty
        $this->assertNotEmpty($response->getData()['GEMS']);
        // Confirming the count of the values in the GEMS key
        $this->assertCount(8,$response->getData()['GEMS']);
        // Matching all 8 role href values
        $this->assertContains('/roles/GEMS/USF_APPLICANT',array_map(function($a) { return $a['href']; }, $response->getData()['GEMS']));
        $this->assertContains('/roles/GEMS/SELFSALL_ROLE',array_map(function($a) { return $a['href']; }, $response->getData()['GEMS']));
        $this->assertContains('/roles/GEMS/USF_EMPLOYEE',array_map(function($a) { return $a['href']; }, $response->getData()['GEMS']));
        $this->assertContains('/roles/GEMS/USF_WF_APPROVALS_USER',array_map(function($a) { return $a['href']; }, $response->getData()['GEMS']));
        $this->assertContains('/roles/GEMS/PeopleSoft+User',array_map(function($a) { return $a['href']; }, $response->getData()['GEMS']));
        $this->assertContains('/roles/GEMS/EFFORT_CERTIFIER_SS',array_map(function($a) { return $a['href']; }, $response->getData()['GEMS']));
        $this->assertContains('/roles/GEMS/RPT2_ROLE',array_map(function($a) { return $a['href']; }, $response->getData()['GEMS']));
        $this->assertContains('/roles/GEMS/INQUIRE_ROLE',array_map(function($a) { return $a['href']; }, $response->getData()['GEMS']));        
        // Confirming the FAST key exists
        $this->assertArrayHasKey('FAST',$response->getData());
        // Confirming that the value of the FAST key is not empty
        $this->assertNotEmpty($response->getData()['FAST']);
        // Confirming the count of the values in the FAST key
        $this->assertCount(1,$response->getData()['FAST']);
        // Match single role href value
        $this->assertContains('/roles/FAST/USF_TR_TRAVELER',array_map(function($a) { return $a['href']; }, $response->getData()['FAST']));
    }
    /**
     * @covers UsfARMapi::createRoleByType
     */
    public function testCreateRoleByType() {
        $response = $this->usfARMapi->createRoleByType([
            'account_type' => 'FAST',
            'name' => 'My Test Role',
            'role_data' => [
                'arbitrary_stuff' => 'Any key you need to add'
            ]
        ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the href key exists
        $this->assertArrayHasKey('href',$response->getData());
        // Confirming that the value of the href key is not empty
        $this->assertNotEmpty($response->getData()['href']);
        // Confirming the value of the identity key is U12345678
        $this->assertEquals('/roles/FAST/My+Test+Role', $response->getData()['href']);
    }
    /**
     * @covers UsfARMapi::createRoleByType
     */
    public function testCreateRoleByType_Null() {
        $response = $this->usfARMapi->createRoleByType(null);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('role',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['role']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING'], $response->getData()['role']);        
    }
    /**
     * @covers UsfARMapi::createRoleByType
     */
    public function testCreateRoleByType_MissingRequiredKeys() {
        $response = $this->usfARMapi->createRoleByType([]);
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('role',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['role']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING_REQUIRED_KEYS'], $response->getData()['role']);                
    }
    /**
     * @covers UsfARMapi::createRoleByType
     */
    public function testCreateRoleByType_MissingRoleData() {
        $response = $this->usfARMapi->createRoleByType([
            'account_type' => 'FAST',
            'name' => 'My Test Role',
            'role_data' => []
        ]);
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('role',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['role']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DATA_EMPTY'], $response->getData()['role']);                                
    }
    /**
     * @covers UsfARMapi::createRoleByType
     */
    public function testCreateRoleByType_RoleAlreadyExists() {
        $response = $this->usfARMapi->createRoleByType([
            'account_type' => 'FAST',
            'name' => 'USF_TR_TRAVELER',
            'role_data' => [
                'arbitrary_stuff' => 'Any key you need to add'
            ]
        ]);
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('role',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['role']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_EXISTS'], $response->getData()['role']);                                
    }
    /**
     * @covers UsfARMapi::getAllRolesByType
     */
    public function testGetAllRolesByType() {
        $response = $this->usfARMapi->getAllRolesByType('FAST');
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the account_type key exists
        $this->assertArrayHasKey('account_type',$response->getData());
        // Confirming that the value of the account_type key is not empty
        $this->assertNotEmpty($response->getData()['account_type']);        
        // Confirming the value of the account_type key is FAST
        $this->assertEquals('FAST', $response->getData()['account_type']);
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$response->getData());
        // Confirming that the value of the account_type key is not empty
        $this->assertNotEmpty($response->getData()['roles']);    
        // Confirming the count of the values in the GEMS key
        $this->assertCount(1,$response->getData()['roles']);
        // Match single role href value
        $this->assertContains('/roles/FAST/USF_TR_TRAVELER',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
    }
    /**
     * @covers UsfARMapi::getRoleByTypeAndName
     */
    public function testGetRoleByTypeAndName() {
        $response = $this->usfARMapi->getRoleByTypeAndName('FAST','USF_TR_TRAVELER');
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the account_type key exists
        $this->assertArrayHasKey('account_type',$response->getData());
        // Confirming that the value of the account_type key is not empty
        $this->assertNotEmpty($response->getData()['account_type']);
        // Confirming the value of the account_type key is FAST
        $this->assertEquals('FAST', $response->getData()['account_type']);

        // Confirming the role_data key exists
        $this->assertArrayHasKey('role_data',$response->getData());
        // Confirming that the value of the role_data key is not empty
        $this->assertNotEmpty($response->getData()['role_data']);

        // Confirming the href key exists
        $this->assertArrayHasKey('href',$response->getData()['role_data']);
        // Confirming that the value of the href key is not empty
        $this->assertNotEmpty($response->getData()['role_data']['href']);
        // Confirming that the value of the href key is not empty
        $this->assertEquals('/roles/FAST/USF_TR_TRAVELER', $response->getData()['role_data']['href']);
    }
    /**
     * @covers UsfARMapi::getRoleByTypeAndName
     */
    public function testGetRoleByTypeAndName_RoleNotExists() {
        $response = $this->usfARMapi->getRoleByTypeAndName('FAST','DOESNT_EXIST');
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('role',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['role']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS'], $response->getData()['role']);                                        
    }
    /**
     * @covers UsfARMapi::modifyRoleByTypeAndName
     */
    public function testModifyRoleByTypeAndName() {
        $response = $this->usfARMapi->modifyRoleByTypeAndName('FAST','USF_TR_TRAVELER',[
            'account_type' => 'FAST',
            'name' => 'USF_TR_TRAVELER',
            'role_data' => [
                'arbitrary_stuff' => 'Any key you need to add'
            ]
        ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the account_type key exists
        $this->assertArrayHasKey('account_type',$response->getData());
        // Confirming that the value of the account_type key is not empty
        $this->assertNotEmpty($response->getData()['account_type']);
        // Confirming the value of the account_type key is FAST
        $this->assertEquals('FAST', $response->getData()['account_type']);
        
        // Confirming the role_data key exists
        $this->assertArrayHasKey('role_data',$response->getData());
        // Confirming that the value of the role_data key is not empty
        $this->assertNotEmpty($response->getData()['role_data']);

        // Confirming the new arbitrary_stuff key exists
        $this->assertArrayHasKey('arbitrary_stuff',$response->getData()['role_data']);
        // Confirming that the value of the arbitrary_stuff key is not empty
        $this->assertNotEmpty($response->getData()['role_data']['arbitrary_stuff']);
        // Confirming that the value of the arbitrary_stuff key is not empty
        $this->assertEquals('Any key you need to add', $response->getData()['role_data']['arbitrary_stuff']);
        
        // NOW TEST CHANGING THE NAME (no need for role_data when there's a name change but it works as well)
        $responseNameChange = $this->usfARMapi->modifyRoleByTypeAndName('FAST','USF_TR_TRAVELER',[
            'account_type' => 'FAST',
            'name' => 'USF_TR_TRAVELER2',
            'role_data' => [
                'arbitrary_stuff' => 'Any key you need to add (changed along with name)'
            ]
        ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($responseNameChange->isSuccess());
        // Confirming the account_type key exists
        $this->assertArrayHasKey('account_type',$responseNameChange->getData());
        // Confirming that the value of the account_type key is not empty
        $this->assertNotEmpty($responseNameChange->getData()['account_type']);
        // Confirming the value of the account_type key is FAST
        $this->assertEquals('FAST', $responseNameChange->getData()['account_type']);
        
        // Confirming the role_data key exists
        $this->assertArrayHasKey('role_data',$responseNameChange->getData());
        // Confirming that the value of the role_data key is not empty
        $this->assertNotEmpty($responseNameChange->getData()['role_data']);

        // Confirming the new arbitrary_stuff key exists
        $this->assertArrayHasKey('arbitrary_stuff',$responseNameChange->getData()['role_data']);
        // Confirming that the value of the arbitrary_stuff key is not empty
        $this->assertNotEmpty($responseNameChange->getData()['role_data']['arbitrary_stuff']);
        // Confirming that the value of the arbitrary_stuff key is not empty
        $this->assertEquals('Any key you need to add (changed along with name)', $responseNameChange->getData()['role_data']['arbitrary_stuff']);
        // Confirming the changed name key exists
        $this->assertArrayHasKey('name',$responseNameChange->getData()['role_data']);
        // Confirming that the value of the name key is not empty
        $this->assertNotEmpty($responseNameChange->getData()['role_data']['name']);
        // Confirming the name change
        $this->assertEquals('USF_TR_TRAVELER2',$responseNameChange->getData()['role_data']['name']);
        // Confirming the changed href key exists
        $this->assertArrayHasKey('href',$responseNameChange->getData()['role_data']);
        // Confirming that the value of the href key is not empty
        $this->assertNotEmpty($responseNameChange->getData()['role_data']['href']);
        // Confirming the href change
        $this->assertEquals('/roles/FAST/USF_TR_TRAVELER2',$responseNameChange->getData()['role_data']['href']);        
    }
    /**
     * @covers UsfARMapi::modifyRoleByTypeAndName
     */
    public function testModifyRoleByTypeAndName_MissingRequiredKeys() {
        $response = $this->usfARMapi->modifyRoleByTypeAndName('FAST','USF_TR_TRAVELER',[]);
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('role',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['role']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING_REQUIRED_KEYS'], $response->getData()['role']);  
    }
    /**
     * @covers UsfARMapi::modifyRoleByTypeAndName
     */
    public function testModifyRoleByTypeAndName_RoleDataEmpty() {
        $response = $this->usfARMapi->modifyRoleByTypeAndName('FAST','USF_TR_TRAVELER',[
            'account_type' => 'FAST',
            'name' => 'USF_TR_TRAVELER',
            'role_data' => []
        ]);
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('role',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['role']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DATA_EMPTY'], $response->getData()['role']);  
    }
    // **************** STOPPING PLACE **********************
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
