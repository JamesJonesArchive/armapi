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
        $response = $this->usfARMapi->setAccountState('FAST', 'U12345678', 'removal_pending', [
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
        $this->assertEquals('removal_pending',UsfARMapi::getStateForManager($response->getData()['state'], 'U99999999'));                
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
    public function testSetAccountRoleState_AccountHasNoRoles() {
        // Remove all roles for target account for testing
        $this->usfARMapi->getARMaccounts()->update([ "type" => 'FAST', "identifier" => 'U12345678' ],[ '$unset' => [ 'roles' => '' ]]);
        $response = $this->usfARMapi->setAccountRoleState('FAST', 'U12345678','USF_TR_TRAVELER','open',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the role key exists
        $this->assertArrayHasKey('role',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['role']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NO_ROLES_EXIST'], $response->getData()['role']);
    }
    /**
     * @covers UsfARMapi::setAccountRoleState 
     */
    public function testSetAccountRoleState_RoleNotExists() {
        $response = $this->usfARMapi->setAccountRoleState('FAST', 'U12345678','USF_TR_TRAVELER2','open',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the role key exists
        $this->assertArrayHasKey('role',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['role']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS'], $response->getData()['role']);        
    }
    /**
     * @covers UsfARMapi::setAccountRoleState
     */
    public function testSetAccountRoleState_AccountRoleNotExists() {
        $response = $this->usfARMapi->setAccountRoleState('GEMS', 'RBULL','EFFORT_CERTIFIER_SS','open',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the role key exists
        $this->assertArrayHasKey('role',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['role']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_ROLE_NOT_EXISTS'], $response->getData()['role']);                
    }
    /**
     * @covers UsfARMapi::setReviewByAccount
     */
    public function testSetReviewByAccount_AccountNotFound() {
        $response = $this->usfARMapi->setReviewByAccount('RBULL2',[
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
     * @covers UsfARMapi::setReviewByAccount
     */
    public function testSetReviewByAccount() {
        $response = $this->usfARMapi->setReviewByAccount('RBULL',[
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
        $this->assertEquals('GEMS', $response->getData()['type']);
        // Confirming the identifier key exists
        $this->assertArrayHasKey('identifier',$response->getData());
        // Confirming that the value of the identifier key is not empty
        $this->assertNotEmpty($response->getData()['identifier']);
        // Confirming the value of the identifier key is U12345678
        $this->assertEquals('RBULL', $response->getData()['identifier']);
        
        // Confirming the review key exists
        $this->assertArrayHasKey('review',$response->getData());
        // Confirming that the value of the review key is not empty
        $this->assertNotEmpty($response->getData()['review']);
        // Confirm a review is set for the specified manager identity
        $this->assertTrue(UsfARMapi::hasReviewForManager($response->getData()['review'], 'U99999999'));
        // Make sure the review is set to open
        $this->assertEquals('open', UsfARMapi::getReviewForManager($response->getData()['review'], 'U99999999'));
        
        // Confirming the state key exists
        $this->assertArrayHasKey('state',$response->getData());
        // Confirming that the value of the state key is not empty
        $this->assertNotEmpty($response->getData()['state']);
        // Confirm a review is set for the specified manager identity
        $this->assertTrue(UsfARMapi::hasStateForManager($response->getData()['state'], 'U99999999'));
        // Make sure the review is set to an empty string
        $this->assertEquals('', UsfARMapi::getStateForManager($response->getData()['state'], 'U99999999'));
        
        // Confirming the roles key exists
        $this->assertArrayHasKey('roles',$response->getData());
        // Confirming that the value of the roles key is not empty
        $this->assertNotEmpty($response->getData()['roles']);
        
        // Get the affected roles and check their states
        $role1 = \array_values(\array_filter($response->getData()['roles'], function($a) { return ($a['href'] == '/roles/GEMS/RPT2_ROLE'); }))[0];
        $role2 = \array_values(\array_filter($response->getData()['roles'], function($a) { return ($a['href'] == '/roles/GEMS/PeopleSoft+User'); }))[0];
        $role3 = \array_values(\array_filter($response->getData()['roles'], function($a) { return ($a['href'] == '/roles/GEMS/INQUIRE_ROLE'); }))[0];
        // Confirming the identifier key exists
        $this->assertArrayHasKey('state',$role1);
        // Confirming that the value of the identifier key is not empty
        $this->assertNotEmpty($role1['state']);
        // Confirming the state for the specified manager is 'open'
        $this->assertEquals('',UsfARMapi::getStateForManager($role1['state'], 'U99999999'));
        // Confirming the identifier key exists
        $this->assertArrayHasKey('state',$role2);
        // Confirming that the value of the identifier key is not empty
        $this->assertNotEmpty($role2['state']);
        // Confirming the state for the specified manager is 'open'
        $this->assertEquals('',UsfARMapi::getStateForManager($role2['state'], 'U99999999'));
        // Confirming the identifier key exists
        $this->assertArrayHasKey('state',$role3);
        // Confirming that the value of the identifier key is not empty
        $this->assertNotEmpty($role3['state']);
        // Confirming the state for the specified manager is 'open'
        $this->assertEquals('',UsfARMapi::getStateForManager($role3['state'], 'U99999999'));
    }
    /**
     * @covers UsfARMapi::setReviewByIdentity
     */
    public function testSetReviewByIdentity() {
        $response = $this->usfARMapi->setReviewByIdentity('U12345678',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirm 3 accounts had reviews set
        $this->assertCount(3, \array_filter($response->getData()['accounts'], function($a) { return isset($a['review']); }));
        // Confirm all 3 reviews are open
        $this->assertCount(3, \array_filter($response->getData()['accounts'], function($a) { return UsfARMapi::getReviewForManager($a['review'], 'U99999999') == 'open'; }));
        // Confirm all 3 states are set to an empty string
        $this->assertCount(3, \array_filter($response->getData()['accounts'], function($a) { return UsfARMapi::getStateForManager($a['state'], 'U99999999') == ''; }));        
    }
    /**
     * @covers UsfARMapi::setReviewAll
     */
    public function testSetReviewAll() {
        $response = $this->usfARMapi->setReviewAll(function ($id) {
            // Mock Visor Data
            return [
                'status' => 'success',
                'data' => [
                    'directory_info' => [
                        'supervisors' => [
                            [
                                'name' => 'Rocky Bull',
                                'usf_id' => 'U99999999'
                            ]
                        ]
                    ]
                ]
            ];
        });
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the reviewCount key exists
        $this->assertArrayHasKey('reviewCount',$response->getData());
        // Confirming that the value of the reviewCount key is not empty
        $this->assertNotEmpty($response->getData()['reviewCount']);
        // Confirming the value of the reviewCount key is 1
        $this->assertEquals(1, $response->getData()['reviewCount']);
        // Confirming the usfids key exists
        $this->assertArrayHasKey('usfids',$response->getData());
        // Confirming that the value of the usfids key is not empty
        $this->assertNotEmpty($response->getData()['usfids']);
        // Confirming that there is only 1 usfid in the test data to be processed
        $this->assertCount(1,$response->getData()['usfids']);
        // Confirming the usfids list contains U12345678
        $this->assertContains('U12345678',$response->getData()['usfids']);
    }
    /**
     * @covers UsfARMapi::setReviewAll
     */
    public function testSetReviewAll_Identities_NoneFound() {
        // Removing all accounts so the function will fail
        $this->usfARMapi->getARMaccounts()->remove([]);
        $response = $this->usfARMapi->setReviewAll(function ($id) {
            // Mock Visor Data
            return [
                'status' => 'success',
                'data' => [
                    'directory_info' => [
                        'supervisors' => [
                            [
                                'name' => 'Rocky Bull',
                                'usf_id' => 'U99999999'
                            ]
                        ]
                    ]
                ]
            ];
        });
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the identity key exists
        $this->assertArrayHasKey('identity',$response->getData());
        // Confirming that the value of the identity key is not empty
        $this->assertNotEmpty($response->getData()['identity']);
        // Confirming the value of the identity key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['IDENTITIES_NONE_FOUND'], $response->getData()['identity']);
    }
    /**
     * @covers UsfARMapi::setConfirmByAccount
     */
    public function testSetConfirmByAccount() {
        // Execute in Order
        // 
        // STEP1: Set the review first
        $this->assertTrue($this->usfARMapi->setReviewByAccount('RBULL',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        // STEP2: Set the state next
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($this->usfARMapi->setAccountState('GEMS', 'RBULL', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        // STEP 3: Then confirm last
        $response = $this->usfARMapi->setConfirmByAccount('RBULL',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming the confirm key exists
        $this->assertArrayHasKey('confirm',$response->getData());
        // Confirming the value of confirm is not empty
        $this->assertNotEmpty($response->getData()['confirm']);
        // There should be only one confirm object
        $this->assertCount(1, $response->getData()['confirm']);
        // Check the last confirm state
        $this->assertEquals('removal_pending',UsfARMapi::getLastConfirm($response->getData()['confirm'], 'U99999999')['state']);
    }
    /**
     * @covers UsfARMapi::setConfirmByAccount
     */
    public function testSetConfirmByAccount_StateUnset() {
        $response = $this->usfARMapi->setConfirmByAccount('RBULL',[
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
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_STATE_UNSET_BY_MANAGER'], $response->getData()['account']);                        
    }
    /**
     * @covers UsfARMapi::setConfirmByAccount
     */
    public function testSetConfirmByAccount_ReviewUnset() {
        // Set the state first
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($this->usfARMapi->setAccountState('GEMS', 'RBULL', '', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        // Now run the setConfirmByAccount to test the result
        $response = $this->usfARMapi->setConfirmByAccount('RBULL',[
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
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_REVIEW_UNSET_BY_MANAGER'], $response->getData()['account']);                        
    }
    /**
     * @covers UsfARMapi::setConfirm
     */
    public function testSetConfirm() {
        // STEP1: Open review
        $this->assertTrue($this->usfARMapi->setReviewByIdentity('U12345678',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());        
        // STEP2: Set the state for each account
        $this->assertTrue($this->usfARMapi->setAccountState('FAST', 'U12345678', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountState('GEMS', '00000012345', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountState('GEMS', 'RBULL', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        // STEP3: Confirm
        $response = $this->usfARMapi->setConfirm('U12345678',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the accounts key exists
        $this->assertArrayHasKey('accounts',$response->getData());
        // Confirming that the value of the accounts key is not empty
        $this->assertNotEmpty($response->getData()['accounts']);
        // Confirming the count of the accounts is 3
        $this->assertCount(3, $response->getData()['accounts']);
        foreach($response->getData()['accounts'] as $acct) {
            // Check the last confirm state
            $this->assertEquals('removal_pending',UsfARMapi::getLastConfirm($acct['confirm'], 'U99999999')['state']);
        }
    }
    /**
     * @covers UsfARMapi::setConfirm
     */
    public function testSetConfirm_NoAccounts() {        
        $response = $this->usfARMapi->setConfirm('U12345670',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the identity key exists
        $this->assertArrayHasKey('identity',$response->getData());
        // Confirming the value of identity is not empty
        $this->assertNotEmpty($response->getData()['identity']);
        // Confirming the value of the identity key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['IDENTITY_NO_ACCOUNTS_EXIST'], $response->getData()['identity']);                                
    }
}
