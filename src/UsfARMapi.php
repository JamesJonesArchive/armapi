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

/**
 * UsfARMapi is an class that performs
 * the ARM service methods
 *
 * @author James Jones <james@mail.usf.edu>
 * 
 */
class UsfARMapi extends USF\IdM\UsfAbstractMongoConnection {
    
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
        return [
            "rockybull",
            "mollymock"
        ];
    }
    /**
     * Retrieves an array of roles for a specified identity object
     * 
     * @param object $identity
     * @return array of roles
     */
    public function getRolesForIdentity($identity) {
        return [
            "User",
            "Student"
        ];
    }
    /**
     * Retrieves an array of roles for a specified account object
     * 
     * @param object $account
     * @return array of roles
     */
    public function getRolesForAccount($account) {
        return [
            "User",
            "Student"
        ];
    }
    /**
     * Retrieves an identity associated with a specified account object
     * 
     * @param object $account
     * @return object as an identity associated with the account
     */
    public function getIdentityForAccount($account) {
        return [
            "name" => "Rocky Bull"
        ];
    }
    /**
     * Retrieves an array of identities associated with a specified role object
     * 
     * @param object $role
     * @return array of identities
     */
    public function getIdentitiesForRole($role) {
        return [
            [
                "name" => "Rocky Bull"
            ],
            [
                "name" => "Molly Mock"
            ]
        ];
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
        
    }
    /**
     * Assigns a specified role object with an existing account
     * 
     * @param object $account
     * @param object $role
     * @return object with the status of the assignment
     */
    public function setRoleForAccount($account,$role) {
        
    }
}
