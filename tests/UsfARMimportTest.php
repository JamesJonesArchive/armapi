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
/**
 * Description of UsfARMimportTest
 *
 * @author james
 */
class UsfARMimportTest extends \PHPUnit_Framework_TestCase  {
    use UsfARMmongomock;
    /**
     * @covers \USF\IdM\UsfARMimport::importAccount
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
     * @covers \USF\IdM\UsfARMimport::importAccountRoles
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
        // Confirming the count of the values in the roles key for non deleted roles
        $this->assertCount(1,\array_filter($response->getData()['roles'], function($r) { return (isset($r['status']))?($r['status'] != "Removed"):true;  }));
        // Confirming the count of the values in the roles key for deleted roles
        $this->assertCount(5,\array_filter($response->getData()['roles'], function($r) { return (isset($r['status']))?($r['status'] == "Removed"):false;  }));
        
    }
    /**
     * @covers \USF\IdM\UsfARMimport::importAccountRoles
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
        $this->assertArrayHasKey('description',$response->getData());
        // Confirming the value of account is not empty
        $this->assertNotEmpty($response->getData()['description']);
        // Confirming the value of the identity key is the error message
        $this->assertEquals(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING'], $response->getData()['description']); 
    }
    /**
     * @covers \USF\IdM\UsfARMimport::importRole
     */
    public function testImportRole() {
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
    /**
     * @covers \USF\IdM\UsfARMimport::buildAccountComparison
     */
    public function testBuildAccountComparison() {
        $response = $this->usfARMapi->buildAccountComparison('FAST');
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the FAST key exists
        $this->assertArrayHasKey('FAST',$response->getData());
        // Confirming the value of FAST is not empty
        $this->assertNotEmpty($response->getData()['FAST']);
        // Check the FAST
        $this->assertEquals(1,$response->getData()['FAST']);
        $response = $this->usfARMapi->buildAccountComparison('GEMS');
        // Confirming the GEMS key exists
        $this->assertArrayHasKey('GEMS',$response->getData());
        // Confirming the value of GEMS is not empty
        $this->assertNotEmpty($response->getData()['GEMS']);
        // Check the GEMS
        $this->assertEquals(1,$response->getData()['GEMS']);
    }
    /**
     * @covers \USF\IdM\UsfARMimport::buildRoleComparison
     */
    public function testBuildRoleComparison() {
        $response = $this->usfARMapi->buildRoleComparison('FAST');
        // Confirming that the function executed successfully by the JSendResponse isSuccess method
        $this->assertTrue($response->isSuccess());
        // Confirming the FAST key exists
        $this->assertArrayHasKey('FAST',$response->getData());
        // Confirming the value of FAST is not empty
        $this->assertNotEmpty($response->getData()['FAST']);
        // Check the FAST
        $this->assertEquals(1,$response->getData()['FAST']);
        $response = $this->usfARMapi->buildRoleComparison('GEMS');
        // Confirming the GEMS key exists
        $this->assertArrayHasKey('GEMS',$response->getData());
        // Confirming the value of GEMS is not empty
        $this->assertNotEmpty($response->getData()['GEMS']);
        // Check the GEMS
        $this->assertEquals(8,$response->getData()['GEMS']);
    }

}
