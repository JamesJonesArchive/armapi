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

use \JSend\JSendResponse;

/**
 * UsfARMapi is an class that performs
 * the ARM service methods
 *
 * @author James Jones <james@mail.usf.edu>
 * 
 */
class UsfARMapi extends UsfAbstractMongoConnection {
    
    private $version = "0.0.1";
    
    public function getVersion() {
        return $this->version;
    }
    
    // Get Methods
    /**
     * Retrieves an array of accounts for a specified identity object
     * 
     * @param object $identity
     * @return array of accounts
     */
    public function getAccountsForIdentity($identity) {
        return new JSendResponse('success', [ 
            "accounts" => [
                "rockybull",
                "mollymock"
            ]
        ]);
    }
    /**
     * Retrieves an array of roles for a specified identity object
     * 
     * @param object $identity
     * @return array of roles
     */
    public function getRolesForIdentity($identity) {
        return new JSendResponse('success', [ 
            "roles" => [
                "User",
                "Student"
            ]
        ]);
    }
    /**
     * Retrieves an array of roles for a specified account object
     * 
     * @param object $account
     * @return array of roles
     */
    public function getRolesForAccount($account) {
        return new JSendResponse('success', [ 
            "roles" => [
                "User",
                "Student"
            ]
        ]);
    }
    /**
     * Retrieves an identity associated with a specified account object
     * 
     * @param object $account
     * @return object as an identity associated with the account
     */
    public function getIdentityForAccount($account) {
        return new JSendResponse('success', [ 
            "name" => "Rocky Bull"
        ]);
    }
    /**
     * Retrieves an array of identities associated with a specified role object
     * 
     * @param object $role
     * @return array of identities
     */
    public function getIdentitiesForRole($role) {
        return new JSendResponse('success', [ 
            "identities" => [
                [
                    "name" => "Rocky Bull"
                ],
                [
                    "name" => "Molly Mock"
                ]
            ]
        ]);
    }
    // POST methods
    /**
     * Assigns a specified account object with an existing identity
     * 
     * @param object $identity
     * @param object $account
     * @return object with the status of the assignment
     */
    public function setAccountForIdentity($identity,$account) {
        $armdb = $this->getMongoConnection()->arm;                
        $accounts = $armdb->accounts;
        $assignaccount = $accounts->findOne([ "name" => $account.name ]);
        if(is_null($assignaccount)) {
            return new JSendResponse('fail',[
                "account" => "Specified Account does not exist"
            ]);
        } elseif ((!is_null($assignaccount.identity))?($assignaccount.identity.id == $identity.id):false) {
            return new JSendResponse('fail',[
                "account" => "Identity already set for this account"
            ]);            
        }
        $status = $accounts->update([ "name" => $account.name ],[ "identity" => $identity.id ]);
        if($status) {
            return new JSendResponse('success', [ "status" => "Update Successful!" ]);
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }
    /**
     * Assigns a specified role object with an existing account
     * 
     * @param object $account
     * @param object $role
     * @return object with the status of the assignment
     */
    public function setRoleForAccount($account,$role) {
        $armdb = $this->getMongoConnection()->arm;
        $accounts = $armdb->accounts;
        $assignaccount = $accounts->findOne([ "name" => $account["name"] ]);
        if(is_null($assignaccount)) {
            return new JSendResponse('fail',[
                "account" => "Specified Account does not exist"
            ]);
        }
        $roles = $armdb->roles;
        $assignrole = $roles->findOne([ "name" => $role["name"] ]);
        if(is_null($assignrole)) {
            return new JSendResponse('fail',[
                "account" => "Specified Role does not exist"
            ]);
        } elseif (!isset($assignaccount["roles"])) {
            $assignaccount["roles"] = [];
        }
        if (in_array($assignrole["_id"], $assignaccount["roles"])) {
            return new JSendResponse('fail',[
                "account" => "Role already set for this account"
            ]);
        }
        $assignaccount["roles"][] = $assignrole["_id"];
        $status = $accounts->update([ "name" => $account["name"] ],[ "roles" => $assignrole["roles"] ]);
        if($status) {
            return new JSendResponse('success', [ "status" => "Update Successful!" ]);
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }
}
