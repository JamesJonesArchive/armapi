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
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
/**
 * Description of UsfARMapprovalsTest
 *
 * @author james
 */
class UsfARMapprovalsTest extends \PHPUnit_Framework_TestCase {
    use UsfARMmongomock;
    /**
     * @covers \USF\IdM\UsfARMapprovals::setAccountState
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
     * @covers \USF\IdM\UsfARMapprovals::setAccountState
     */
    public function testSetAccountState_AccountNotExists() {
        $response = $this->usfARMapi->setAccountState('FAST', 'U12345670', '', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
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
     * @covers \USF\IdM\UsfARMapprovals::setAccountRoleState
     */
    public function testSetAccountRoleState() {
        $response = $this->usfARMapi->setAccountRoleState('FAST', 'U12345678','/roles/FAST/USF_TR_TRAVELER','open',[
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
     * @covers \USF\IdM\UsfARMapprovals::setAccountRoleState
     */
    public function testSetAccountRoleState_AccountNotExists() {
        $response = $this->usfARMapi->setAccountRoleState('FAST', 'U12345670','/roles/FAST/USF_TR_TRAVELER','open',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
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
     * @covers \USF\IdM\UsfARMapprovals::setAccountRoleState
     */
    public function testSetAccountRoleState_AccountHasNoRoles() {
        // Remove all roles for target account for testing
        $this->usfARMapi->getARMaccounts()->update([ "type" => 'FAST', "identifier" => 'U12345678' ],[ '$unset' => [ 'roles' => '' ]]);
        $response = $this->usfARMapi->setAccountRoleState('FAST', 'U12345678','/roles/FAST/USF_TR_TRAVELER','open',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the role key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NO_ROLES_EXIST'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::setAccountRoleState 
     */
    public function testSetAccountRoleState_RoleNotExists() {
        $response = $this->usfARMapi->setAccountRoleState('FAST', 'U12345678','/roles/FAST/USF_TR_TRAVELER2','open',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the role key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS'], $response->getData()['description']);        
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::setAccountRoleState
     */
    public function testSetAccountRoleState_AccountRoleNotExists() {
        $response = $this->usfARMapi->setAccountRoleState('GEMS', 'RBULL','/roles/GEMS/EFFORT_CERTIFIER_SS','open',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the role key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of role is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the role key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_ROLE_NOT_EXISTS'], $response->getData()['description']);                
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::setReviewByAccount
     */
    public function testSetReviewByAccount_AccountNotFound() {
        $response = $this->usfARMapi->setReviewByAccount('GEMS','RBULL2');
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
     * @covers \USF\IdM\UsfARMapprovals::setReviewByAccount
     */
    public function testSetReviewByAccount() {
        $response = $this->usfARMapi->setReviewByAccount('GEMS','RBULL',10);
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
     * @covers \USF\IdM\UsfARMapprovals::setReviewByIdentity
     */
    public function testSetReviewByIdentity() {
        $response = $this->usfARMapi->setReviewByIdentity('U12345678',-1);
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
     * @covers \USF\IdM\UsfARMapprovals::delegateAllReviews
     */
    public function testDelegateAllReviews() {
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($this->usfARMapi->setReviewByAccount('GEMS','RBULL')->isSuccess());
        // Delegate open reviews for Rocky Bull to Gold Greeny
        $response = $this->usfARMapi->delegateAllReviews('U98767543','U99999999');
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the count key exists
        $this->assertArrayHasKey('count',$response->getData());
        // Confirming the value of the count key is 1
        $this->assertEquals(1, $response->getData()['count']);
        // Confirming the summary key exists
        $this->assertArrayHasKey('summary',$response->getData());
        // Confirming that there is only 1 type in the summary data to be processed
        $this->assertCount(1,$response->getData()['summary']);
        // Confirming the summary key exists
        $this->assertArrayHasKey('GEMS',$response->getData()['summary']);
        // Confirming that there is only 2 keys in the GEMS summary data to be processed
        $this->assertCount(2,$response->getData()['summary']['GEMS']);
        // Confirming the succeeded key exists
        $this->assertArrayHasKey('succeeded',$response->getData()['summary']['GEMS']);
        // Confirming the failed key exists
        $this->assertArrayHasKey('failed',$response->getData()['summary']['GEMS']);
        // Confirming the value of the succeeded key is 1
        $this->assertEquals(1, $response->getData()['summary']['GEMS']['succeeded']);
        // Confirming the value of the failed key is 1
        $this->assertEquals(0, $response->getData()['summary']['GEMS']['failed']);
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::delegateReview
     */
    public function testDelegateReview() {
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($this->usfARMapi->setReviewByAccount('GEMS','RBULL',10)->isSuccess());
        // Delegate the open review from Rocky Bull to Gold Greeny
        $response = $this->usfARMapi->delegateReview('U98767543','U99999999','/accounts/GEMS/RBULL',10);
        
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
        // Confirm a review is set for the specified manager original identity
        $this->assertTrue(UsfARMapi::hasReviewForManager($response->getData()['review'], 'U99999999'));
        // Make sure that review is set to closed now
        $this->assertEquals('delegated', UsfARMapi::getReviewForManager($response->getData()['review'], 'U99999999'));

        // Confirm a review is set for the specified manager delegate identity
        $this->assertTrue(UsfARMapi::hasReviewForManager($response->getData()['review'], 'U98767543'));
        // Make sure the delegated review is set to open
        $this->assertEquals('open', UsfARMapi::getReviewForManager($response->getData()['review'], 'U98767543'));
        
        foreach ($response->getData()['roles'] as $role) {
            // Confirming the identifier key exists
            $this->assertArrayHasKey('state',$role);
            // Confirming that the value of the identifier key is not empty
            $this->assertNotEmpty($role['state']);
            // Confirming the state for the specified original manager is 'open'
            $this->assertEquals('',UsfARMapi::getStateForManager($role['state'], 'U99999999'));
            // Confirming the state for the specified delegate manager is 'open'
            $this->assertEquals('',UsfARMapi::getStateForManager($role['state'], 'U98767543'));
        }
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::delegateReview
     */
    public function testDelegateReviewAccountNotExists() {
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($this->usfARMapi->setReviewByAccount('GEMS','RBULL')->isSuccess());
        // Delegate the open review from Rocky Bull to Gold Greeny
        $response = $this->usfARMapi->delegateReview('U98767543','U99999999','/accounts/GEMS/RBULLY');
        
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertFalse($response->isSuccess());
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS'], $response->getData()['description']); 
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::setReviewAll
     */
    public function testSetReviewAll() {
        $response = $this->usfARMapi->setReviewAll(-1);
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
     * @covers \USF\IdM\UsfARMapprovals::setReviewAll
     */
    public function testSetReviewAll_Identities_NoneFound() {
        // Removing all accounts so the function will fail
        $this->usfARMapi->getARMaccounts()->remove([]);
        $response = $this->usfARMapi->setReviewAll(-1);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the identity key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming that the value of the identity key is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the identity key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['IDENTITIES_NONE_FOUND'], $response->getData()['description']);
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::setConfirmByAccount
     * @covers \USF\IdM\UsfARMapprovals::setReviewByAccount
     * @covers \USF\IdM\UsfARMapprovals::setAccountState
     */
    public function testSetConfirmByAccount() {
        // Execute in Order
        // 
        // STEP1: Set the review first
        $this->assertTrue($this->usfARMapi->setReviewByAccount('GEMS','RBULL')->isSuccess());
        // STEP2: Set the state next
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($this->usfARMapi->setAccountState('GEMS', 'RBULL', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', 'RBULL', '/roles/GEMS/RPT2_ROLE', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', 'RBULL', '/roles/GEMS/PeopleSoft+User', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $rsresponse = $this->usfARMapi->setAccountRoleState('GEMS', 'RBULL', '/roles/GEMS/INQUIRE_ROLE', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        $this->assertTrue($rsresponse->isSuccess());
        // Create a tiny delay before confirming (.10 sec) so the dates can be compared
        \usleep(100000);
        // STEP 3: Then confirm last
        $response = $this->usfARMapi->setConfirmByAccount('GEMS','RBULL',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function succeeded by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the confirm key exists
        $this->assertArrayHasKey('confirm',$response->getData());
        // Confirming the value of confirm is not empty
        $this->assertNotEmpty($response->getData()['confirm']);
        // There should be only one confirm object
        $this->assertCount(1, $response->getData()['confirm']);
        // Check the last confirm state
        $this->assertEquals('removal_pending',UsfARMapi::getLastConfirm($response->getData()['confirm'], 'U99999999')['state']);
        // Make sure the review timestamp did not change in the confirmation process
        $this->assertEquals($rsresponse->getData()['review'][0]['timestamp'],$response->getData()['confirm'][0]['review']['timestamp']);
        // Make sure the current review value matches what was copied over to the confirm record
        $this->assertEquals($response->getData()['review'][0]['timestamp'],$response->getData()['confirm'][0]['review']['timestamp']);
        // Make sure the date of the confirm does not equal the timestamp of the review
        $this->assertNotEquals($response->getData()['confirm'][0]['review']['timestamp'], $response->getData()['confirm'][0]['timestamp']);
    }    
    /**
     * @covers \USF\IdM\UsfARMapprovals::setConfirmByAccount
     * @covers \USF\IdM\UsfARMapprovals::setReviewByAccount
     * @covers \USF\IdM\UsfARMapprovals::setAccountState
     */
    public function testSetConfirmByAccount_UnapprovedRoleState() {
        // Execute in Order
        // 
        // STEP1: Set the review first
        $this->assertTrue($this->usfARMapi->setReviewByAccount('GEMS','RBULL')->isSuccess());        
        // STEP2: Set the state next
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($this->usfARMapi->setAccountState('GEMS', 'RBULL', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        // Now change one of the role states to unset
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', 'RBULL', '/roles/GEMS/RPT2_ROLE', '', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        // STEP 3: Then confirm last
        $response = $this->usfARMapi->setConfirmByAccount('GEMS','RBULL',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_HAS_UNAPPROVED_ROLE_STATES'], $response->getData()['description']);                        
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::setConfirmByAccount
     */
    public function testSetConfirmByAccount_StateUnsetWithoutReview() {
        // STEP1: Set the review first
        $response = $this->usfARMapi->setConfirmByAccount('GEMS','RBULL',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the state key exists
        $this->assertArrayHasKey('state',$response->getData());
        // Confirm the state was set anyway since the account isn't under review
        $this->assertTrue(UsfARMapi::hasStateForManager($response->getData()['state'], 'U99999999'));
        // Confirming the review key not exists
        $this->assertArrayNotHasKey('review',$response->getData());
        // Confirming the confirm key exists
        $this->assertArrayHasKey('confirm',$response->getData());
        // Confirming the count of the accounts is 3
        $this->assertCount(1, $response->getData()['confirm']);
        // Confirming the usfid we expect in the confirm
        $this->assertEquals($response->getData()['confirm'][0]['usfid'],'U99999999');
        // Confirming the roles key not exists
        $this->assertArrayHasKey('roles',$response->getData());
        // Confirming unapproved role states were created (which is appropriate when a confirm happens outside a review where no role states existed prior for the supervisor
        $this->assertTrue(UsfARMapi::hasUnapprovedRoleState($response->getData()['roles'], [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]));
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::setConfirmByAccountRole
     */
    public function testSetConfirmByAccount_NoAccount() {
        // Now run the setConfirmByAccount to test the result
        $response = $this->usfARMapi->setConfirmByAccount('GEMS','RBULL2',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
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
     * @covers \USF\IdM\UsfARMapprovals::setConfirmByAccountRole
     */
    public function testSetConfirmByAccountRole_StateUnset() {
        // Now run the setConfirmByAccount to test the result
        $response = $this->usfARMapi->setConfirmByAccountRole('GEMS','RBULL',"/roles/GEMS/RPT2_ROLE",[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the account key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the account key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_ROLE_STATE_UNSET_BY_MANAGER'], $response->getData()['description']);                        
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::setConfirmByAccountRole
     */
    public function testSetConfirmByAccountRole_NoAccount() {
        // Now run the setConfirmByAccount to test the result
        $response = $this->usfARMapi->setConfirmByAccountRole('GEMS','RBULL2',"/roles/GEMS/RPT2_ROLE",[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
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
     * @covers \USF\IdM\UsfARMapprovals::setConfirm
     */
    public function testSetConfirm() {
        // STEP1: Open review
        $this->assertTrue($this->usfARMapi->setReviewByIdentity('U12345678',-1)->isSuccess());        
        // STEP2: Set the state for each account
        $this->assertTrue($this->usfARMapi->setAccountState('FAST', 'U12345678', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('FAST', 'U12345678', '/roles/FAST/USF_TR_TRAVELER', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountState('GEMS', '00000012345', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/USF_APPLICANT', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/SELFSALL_ROLE', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/USF_EMPLOYEE', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/USF_WF_APPROVALS_USER', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/PeopleSoft+User', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/EFFORT_CERTIFIER_SS', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());                
        $this->assertTrue($this->usfARMapi->setAccountState('GEMS', 'RBULL', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', 'RBULL', '/roles/GEMS/RPT2_ROLE', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', 'RBULL', '/roles/GEMS/PeopleSoft+User', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', 'RBULL', '/roles/GEMS/INQUIRE_ROLE', 'removal_pending', [
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
     * @covers \USF\IdM\UsfARMapprovals::setConfirm
     */
    public function testSetConfirm_NoAccounts() {        
        $response = $this->usfARMapi->setConfirm('U12345670',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ]);
        // Confirming that the function failed by the JSendResponse isFail method
        $this->assertTrue($response->isFail());
        // Confirming the identity key exists
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of identity is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the identity key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['IDENTITY_NO_ACCOUNTS_EXIST'], $response->getData()['description']);                                
    }
    /**
     * @covers \USF\IdM\UsfARMapprovals::getConfirmedAccountsByInterval
     */
    public function testgetConfirmedAccountsByInterval() {
        // STEP1: Open review
        $this->assertTrue($this->usfARMapi->setReviewByIdentity('U12345678',-1)->isSuccess());        
        // STEP2: Set the state for each account
        $this->assertTrue($this->usfARMapi->setAccountState('FAST', 'U12345678', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('FAST', 'U12345678', '/roles/FAST/USF_TR_TRAVELER', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountState('GEMS', '00000012345', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/USF_APPLICANT', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/SELFSALL_ROLE', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/USF_EMPLOYEE', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/USF_WF_APPROVALS_USER', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/PeopleSoft+User', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', '00000012345', '/roles/GEMS/EFFORT_CERTIFIER_SS', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());                
        $this->assertTrue($this->usfARMapi->setAccountState('GEMS', 'RBULL', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', 'RBULL', '/roles/GEMS/RPT2_ROLE', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', 'RBULL', '/roles/GEMS/PeopleSoft+User', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setAccountRoleState('GEMS', 'RBULL', '/roles/GEMS/INQUIRE_ROLE', 'removal_pending', [
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());        
        // STEP3: Confirm
        $this->assertTrue($this->usfARMapi->setConfirm('U12345678',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        $this->assertTrue($this->usfARMapi->setConfirm('U12345678',[
            'usfid' => 'U99999999',
            'name' => 'Rocky Bull'
        ])->isSuccess());
        // We just confirmed it twice so check the last four minutes
        $response = $this->usfARMapi->getConfirmedAccountsByInterval(4);
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the accounts key exists
        $this->assertArrayHasKey('accounts',$response->getData());
        // Confirming that the value of the accounts key is not empty
        $this->assertNotEmpty($response->getData()['accounts']);
        // Confirming the count of the accounts is 3
        $this->assertCount(3, $response->getData()['accounts']);
        foreach($response->getData()['accounts'] as $acct) {
            // Confirming the accounts key exists
            $this->assertArrayHasKey('confirm',$acct);
            // Confirming that the value of the accounts key is not empty
            $this->assertNotEmpty($acct['confirm']);
            // Confirming the count of the accounts is 3
            $this->assertCount(1, $acct['confirm']);
            // Confirming the accounts key exists
            $this->assertArrayHasKey('roles',$acct);
            // Confirming that the value of the accounts key is not empty
            $this->assertNotEmpty($acct['roles']);
            foreach($acct['roles'] as $role) {                
                // Confirming the accounts key exists
                $this->assertArrayHasKey('confirm',$role);
                // Confirming that the value of the accounts key is not empty
                $this->assertNotEmpty($role['confirm']);
                // Confirming the count of the accounts is 3
                $this->assertCount(1, $role['confirm']);                
            }
        }
        
    }
}
