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
/**
 * UsfARMapiTest tests the UsfARMapi
 * ARM service methods
 *
 * @author James Jones <james@mail.usf.edu>
 * 
 */
class UsfARMapiTest extends \PHPUnit_Framework_TestCase {
    use UsfARMmongomock;    
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
     * @covers \USF\IdM\UsfARMapi::getVersion
     */
    public function testGetVersion() {
        $this->assertEquals($_SERVER['ARMAPI_VERSION'], $this->usfARMapi->getVersion());
    }
    /**
     * @covers \USF\IdM\UsfARMapi::getAllAccounts
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
        // Confirming the FAST exists
        $this->assertArrayHasKey('FAST',$response->getData());
        // Confirming that the value of the FAST key is not empty
        $this->assertNotEmpty($response->getData()['FAST']);
        // Confirming the count of the values in the FAST key
        $this->assertCount(1,$response->getData()['FAST']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::getAccountTypes
     */
    public function testGetAccountTypes() {
        $response = $this->usfARMapi->getAccountTypes();
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the account_types key exists
        $this->assertArrayHasKey('account_types',$response->getData());
        // Confirming that the value of the account_types key is not empty
        $this->assertNotEmpty($response->getData()['account_types']);
        // Confirming the count of the values in the account_types key
        $this->assertCount(2,$response->getData()['account_types']);
        // Test the known types
        $this->assertContains('FAST',$response->getData()['account_types']);
        $this->assertContains('GEMS',$response->getData()['account_types']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::getAccountsForIdentity
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
     * @covers \USF\IdM\UsfARMapi::getAccountsByType
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
     * @covers \USF\IdM\UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType_NullAccount() {        
        $response = $this->usfARMapi->createAccountByType('GEMS',null);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType_ValidAccountInfo() {
        // Testing account with no key value pairs
        $response = $this->usfARMapi->createAccountByType('GEMS',[]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING_REQUIRED_KEYS'], $response->getData()['description']);

        // Testing account with 2 missing key value pairs
        $response2 = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS' ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response2->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response2->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response2->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING_REQUIRED_KEYS'], $response2->getData()['description']);        
        
        // Testing account with 1 missing key value pairs
        $response3 = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS','account_identifier' => '00000012345' ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response3->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response3->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response3->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING_REQUIRED_KEYS'], $response3->getData()['description']);                
    }
    /**
     * @covers \USF\IdM\UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType_AccountDataEmpty() {
        $response = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS','account_identifier' => '00000012345', 'account_data' => [] ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_DATA_EMPTY'], $response->getData()['description']);                        
    }
    /**
     * @covers \USF\IdM\UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType_AccountTypeMismatch() {
        $response = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS_bad','account_identifier' => '00000012345', 'account_data' => [ 'anything' => 'myvalue' ] ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_TYPE_MISMATCH'], $response->getData()['description']);                                
    }
    /**
     * @covers \USF\IdM\UsfARMapi::createAccountByType
     */
    public function testCreateAccountByType_AccountExists() {
        $response = $this->usfARMapi->createAccountByType('GEMS',[ 'account_type' => 'GEMS','account_identifier' => '00000012345', 'account_data' => [ 'anything' => 'myvalue' ] ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_EXISTS'], $response->getData()['description']);                                
    }
    /**
     * @covers \USF\IdM\UsfARMapi::createAccountByType
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
     * @covers \USF\IdM\UsfARMapi::getAccountByTypeAndIdentifier
     */
    public function testGetAccountByTypeAndIdentifier_AccountNotFound() {
        $response = $this->usfARMapi->getAccountByTypeAndIdentifier('GEMS','00000012340');
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::getAccountByTypeAndIdentifier
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
     * @covers \USF\IdM\UsfARMapi::modifyAccountByTypeAndIdentifier
     */
    public function testModifyAccountByTypeAndIdentifier_AccountNotFound() {
        $response = $this->usfARMapi->modifyAccountByTypeAndIdentifier('GEMS','00000012340',[ 'account_data' => ['anything' => 'myvalue'] ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::modifyAccountByTypeAndIdentifier
     */
    public function testModifyAccountByTypeAndIdentifier_AccountDataMissing() {
        $response = $this->usfARMapi->modifyAccountByTypeAndIdentifier('GEMS','00000012345',[ 'anything' => 'myvalue' ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_DATA_EMPTY'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::modifyAccountByTypeAndIdentifier
     */
    public function testModifyAccountByTypeAndIdentifier() {
        $response = $this->usfARMapi->modifyAccountByTypeAndIdentifier('GEMS','00000012345',[ 'account_data' => ['anything' => 'myvalue'] ]);
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
     * @covers \USF\IdM\UsfARMapi::getRolesForAccountByTypeAndIdentifier
     */
    public function testGetRolesForAccountByTypeAndIdentifier_AccountNotFound() {
        $response = $this->usfARMapi->getRolesForAccountByTypeAndIdentifier('GEMS','00000012340');
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::getRolesForAccountByTypeAndIdentifier
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
     * @covers \USF\IdM\UsfARMapi::modifyRolesForAccountByTypeAndIdentifier
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
        // Confirming the count of the values in the roles key for non deleted roles
        $this->assertCount(1,\array_filter($response->getData()['roles'], function($r) { return (isset($r['status']))?($r['status'] != "Removed"):true;  }));
        // Confirming the count of the values in the roles key for deleted roles
        $this->assertCount(5,\array_filter($response->getData()['roles'], function($r) { return (isset($r['status']))?($r['status'] == "Removed"):false;  }));
        // Matching single role href values
        $this->assertContains('/roles/GEMS/SELFSALL_ROLE',array_map(function($a) { return $a['href']; }, $response->getData()['roles']));
    }
    /**
     * @covers \USF\IdM\UsfARMapi::modifyRolesForAccountByTypeAndIdentifier
     */
    public function testModifyRolesForAccountByTypeAndIdentifier_AccountNotFound() {
        $response = $this->usfARMapi->modifyRolesForAccountByTypeAndIdentifier('GEMS','00000012340',[]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::modifyRolesForAccountByTypeAndIdentifier
     */
    public function testModifyRolesForAccountByTypeAndIdentifier_NoRoleList() {
        $response = $this->usfARMapi->modifyRolesForAccountByTypeAndIdentifier('GEMS','00000012345',[]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_LIST_MISSING'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::modifyRolesForAccountByTypeAndIdentifier
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
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role_list is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role_list key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLES_CONTAINS_INVALID'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::getAccountsByTypeAndIdentity
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
     * @covers \USF\IdM\UsfARMapi::getAllRoles
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
     * @covers \USF\IdM\UsfARMapi::createRoleByType
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
     * @covers \USF\IdM\UsfARMapi::createRoleByType
     */
    public function testCreateRoleByType_Null() {
        $response = $this->usfARMapi->createRoleByType(null);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING'], $response->getData()['description']);        
    }
    /**
     * @covers \USF\IdM\UsfARMapi::createRoleByType
     */
    public function testCreateRoleByType_MissingRequiredKeys() {
        $response = $this->usfARMapi->createRoleByType([]);
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING_REQUIRED_KEYS'], $response->getData()['description']);                
    }
    /**
     * @covers \USF\IdM\UsfARMapi::createRoleByType
     */
    public function testCreateRoleByType_MissingRoleData() {
        $response = $this->usfARMapi->createRoleByType([
            'account_type' => 'FAST',
            'name' => 'My Test Role',
            'role_data' => []
        ]);
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DATA_EMPTY'], $response->getData()['description']);                                
    }
    /**
     * @covers \USF\IdM\UsfARMapi::createRoleByType
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
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_EXISTS'], $response->getData()['description']);                                
    }
    /**
     * @covers \USF\IdM\UsfARMapi::getAllRolesByType
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
     * @covers \USF\IdM\UsfARMapi::getRoleByTypeAndName
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
     * @covers \USF\IdM\UsfARMapi::getRoleByTypeAndName
     */
    public function testGetRoleByTypeAndName_RoleNotExists() {
        $response = $this->usfARMapi->getRoleByTypeAndName('FAST','DOESNT_EXIST');
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS'], $response->getData()['description']);                                        
    }
    /**
     * @covers \USF\IdM\UsfARMapi::modifyRoleByTypeAndName
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
     * @covers \USF\IdM\UsfARMapi::modifyRoleByTypeAndName
     */
    public function testModifyRoleByTypeAndName_MissingRequiredKeys() {
        $response = $this->usfARMapi->modifyRoleByTypeAndName('FAST','USF_TR_TRAVELER',[]);
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING_REQUIRED_KEYS'], $response->getData()['description']);  
    }
    /**
     * @covers \USF\IdM\UsfARMapi::modifyRoleByTypeAndName
     */
    public function testModifyRoleByTypeAndName_RoleDataEmpty() {
        $response = $this->usfARMapi->modifyRoleByTypeAndName('FAST','USF_TR_TRAVELER',[
            'account_type' => 'FAST',
            'name' => 'USF_TR_TRAVELER',
            'role_data' => []
        ]);
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DATA_EMPTY'], $response->getData()['description']);  
    }    
    /**
     * @covers \USF\IdM\UsfARMapi::removeAccount
     */
    public function testRemoveAccount() {
        $response = $this->usfARMapi->removeAccount('/accounts/GEMS/RBULL');
        // Confirm success
        $this->assertTrue($response->isSuccess());
        // Confirming the status key exists
        $this->assertArrayHasKey('status',$response->getData());
        // Confirming the value of status is not empty
        $this->assertNotEmpty($response->getData()['status']);
        // Confirm account status removed
        $this->assertEquals("Removed",$response->getData()['status']);
        // Confirm all account roles status removed
        foreach($response->getData()['roles'] as $role) {
            $this->assertEquals("Removed",$role['status']);
        }
    } 
    /**
     * @covers \USF\IdM\UsfARMapi::removeAccount
     */
    public function testRemoveAccount_AccountNotExists() {
        $response = $this->usfARMapi->removeAccount('/accounts/GEMS/RBULL2');
        // Confirm failure
        $this->assertTrue($response->isFail());
        // Confirming the description key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of description is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::removeRole
     */
    public function testRemoveRole() {
        $response = $this->usfARMapi->removeRole('/roles/GEMS/RPT2_ROLE');
        // Confirm success
        $this->assertTrue($response->isSuccess());
        // Confirming the role_data key exists
        $this->assertArrayHasKey('role_data',$response->getData());
        // Confirming the value of role_data is not empty
        $this->assertNotEmpty($response->getData()['role_data']);
        // Confirming the status key exists
        $this->assertArrayHasKey('status',$response->getData()['role_data']);
        // Confirming the value of status is not empty
        $this->assertNotEmpty($response->getData()['role_data']['status']);
        // Confirm account status removed
        $this->assertEquals("Removed",$response->getData()['role_data']['status']);
    }
    /**
     * @covers \USF\IdM\UsfARMapi::removeRole
     */
    public function testRemoveRole_RoleNotExists() {
        $response = $this->usfARMapi->removeRole('/roles/GEMS/RPT2_ROLE2');
        // Confirm failure
        $this->assertTrue($response->isFail());
        // Confirming the description key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of description is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS'], $response->getData()['description']);        
    }
    /**
     * @covers \USF\IdM\UsfARMapi::removeAccountRole
     */
    public function testremoveAccountRole() {
        $response = $this->usfARMapi->removeAccountRole('/accounts/GEMS/RBULL','/roles/GEMS/RPT2_ROLE');
        print_r($response->getData());
        // Confirm success
        $this->assertTrue($response->isSuccess());
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$response->getData());
        // Confirming the value of roles is not empty
        $this->assertNotEmpty($response->getData()['roles']);
        foreach($response->getData()['roles'] as $role) {
            if($role['href'] == '/roles/GEMS/RPT2_ROLE') {
                // Confirming the status key exists
                $this->assertArrayHasKey('status',$role);
                // Confirm the status is removed
                $this->assertEquals("Removed",$role['status']);
            } else {
                // Confirm there is no status key
                $this->assertArrayNotHasKey('status', $role);
            }
        }
    }
    /**
     * @covers \USF\IdM\UsfARMapi::removeAccountRole
     */
    public function testRemoveAccountRole_RoleNotExists() {
        $response = $this->usfARMapi->removeAccountRole('/accounts/GEMS/RBULL','/roles/GEMS/RPT2_ROLE2');
        // Confirm failure
        $this->assertTrue($response->isFail());
        // Confirming the description key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of description is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS'], $response->getData()['description']);        
    }
    /**
     * @covers \USF\IdM\UsfARMapi::removeAccountRole
     */
    public function testRemoveAccountRole_AccountNotExists() {
        $response = $this->usfARMapi->removeAccountRole('/accounts/GEMS/RBULL2','/roles/GEMS/RPT2_ROLE');
        // Confirm failure
        $this->assertTrue($response->isFail());
        // Confirming the description key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of description is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS'], $response->getData()['description']);        
    }
    
}
