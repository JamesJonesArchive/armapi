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
    private $armdb = null;
    
    public function getVersion() {
        return $this->version;
    }
    
    private function getARMdb() {
        if ($this->armdb === null) {
            $this->armdb = $this->getMongoConnection()->arm;
        }

        return $this->armdb;
    }
    // New stuff
    /**
     * Returns all accounts of all types
     * 
     * @return JSendResponse
     */
    public function getAllAccounts() {
        $accounts = $this->getARMdb()->accounts;
        $accountlist = $accounts->find();
        $result = [];
        foreach($accountlist as $act) {
            if (!isset($result[$act['type']])) {
                $result[$act['type']] = [];
            }
            $result[$act['type']][] = $act["href"];
        }
        return new JSendResponse('success', $result);
    }

    /**
     * Retrieves an array of accounts for a specified identity 
     * 
     * @param object $identity
     * @return array of accounts
     */
    public function getAccountsForIdentity($identity) {
        $accounts = $this->getARMdb()->accounts;
        $accountlist = $accounts->find([ "identity" => $identity ]);
        $result = ["identity" => $identity,"accounts" => []];
        foreach($accountlist as $act) {
            unset($act['identity']);
            $result['accounts'][] = $act;
        }
        return new JSendResponse('success', $result);
    }

    /**
     * Return all accounts of a specified type
     * 
     * @param type $type
     * @return JSendResponse
     */
    public function getAccountsByType($type) {
        $accounts = $this->getARMdb()->accounts;
        $accountlist = $accounts->find([ "type" => $type ]);
        $result = [ 'account_type' => $type, 'accounts' => [] ];
        foreach($accountlist as $act) {
            $result['accounts'][] = $act;
        }
        return new JSendResponse('success', $result);
    }
    
    /**
     * Add a new account
     * 
     * @param type $type
     * @param array $account
     * @return JSendResponse
     */
    public function createAccountByType($type,$account) {
        $accounts = $this->getARMdb()->accounts;
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account info missing"
            ]);
        }
        // Check to make sure the account itself has enough valid info
        if(!isset($account["account_type"]) || !isset($account["account_identifier"]) || !isset($account["account_data"])) {
            return new JSendResponse('fail', [
                "account" => "Account info missing one of these keys: account_type,account_identifier,account_data"
            ]);
        }
        // Make sure the account_data is not empty
        if(empty($account["account_data"])) {
            return new JSendResponse('fail', [
                "account" => "Account info is empty!"
            ]);
        }
        // Check to make sure the type is set the same in the account data as indicated from the call
        if(strcasecmp($account["account_type"], $type) != 0) {
            return new JSendResponse('fail', [
                "account" => "Account type is mismatched in the request!"
            ]);
        }
        // Check to see if it exists already
        if($this->getAccountByTypeAndIdentifier($type, $account["account_identifier"])->isSuccess()) {
            return new JSendResponse('fail', [
                "account" => "Account of this type already exists!"
            ]);
        }
        // Add on the href
        $account["href"] = "/accounts/{$type}/{$account['account_identifier']}";
        $insert_status = $accounts->insert($account);
        if(!$insert_status) {
            return new JSendResponse('error', "Account creation could not be performed!");
        } else {
            return new JSendResponse('success', [
                "href" => $account["href"]
            ]);
        }
    }

    /**
     * Retrieve the account by type and identity (using the identifier)
     * 
     * @param type $type
     * @param type $identifier
     * @return JSendResponse
     */
    public function getAccountByTypeAndIdentifier($type,$identifier) {
        $accounts = $this->getARMdb()->accounts;
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account not found!"
            ]);
        }
        return new JSendResponse('success', $account);
    }
    /**
     * Modify an account by type and identity (using the identifier)
     * 
     * @param type $type
     * @param type $identifier
     * @param array $accountmods
     * @return JSendResponse
     */
    public function modifyAccountByTypeAndIdentifier($type,$identifier,$accountmods) {
        $accounts = $this->getARMdb()->accounts;
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account not found!"
            ]);
        }
        $accountmods["href"] = "/accounts/{$type}/{$identifier}";
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], $accountmods);
        if ($status) {
            return new JSendResponse('success', [ "href" => $accountmods["href"] ]);
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }
    
    /**
     * Get roles for a specific account by type and identity (using the identifier)
     * 
     * @param type $type
     * @param type $identifier
     * @return JSendResponse
     */
    public function getRolesForAccountByTypeAndIdentifier($type,$identifier) {
        $accounts = $this->getARMdb()->accounts;
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ],[ "type" => true, "identifier" => true, "roles" => true ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account not found!"
            ]);
        }
        if (!isset($account['roles'])) {
            $account['roles'] = [];
        }
        return new JSendResponse('success', $account);
    }
    
    public function modifyRolesForAccountByTypeAndIdentifier($type,$identifier,$rolechanges) {
        $accounts = $this->getARMdb()->accounts;
        $roles = $this->getARMdb()->roles;
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ],[ "type" => true, "identifier" => true, "roles" => true ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account not found!"
            ]);
        }
        if(!isset($rolechanges['role_list'])) {
            return new JSendResponse('fail', [
                "roles" => "No role list specified!"
            ]);
        }
        // See if all the roles specified are available
        $rolesupdate = [];
        $validroles = true;
        foreach($rolechanges['role_list'] as $roleref) {
            $role = $roles->findOne([ 'href' => $roleref ],['href' => true, 'name' => true,'short_description'=> true]);
            if (is_null($role)) {
                $validroles = false;
                break;
            } else {
                $rolesupdate[] = $role;
            }
        }
        if(!$validroles) {
            return new JSendResponse('fail', [
                "role_list" => "Role list contains invalid roles!"
            ]);
        }
        if(!isset($account['roles'])) {
            $account['roles'] = [];
        }
        // Remove any missing roles from the account
        $removemissing = function ($r) use($rolechanges) {
            $match = false;
            foreach($rolechanges['role_list'] as $rc) {
                if(strcasecmp($rc['href'], $r['href']) == 0) {
                    $match = true;
                    break;
                }
            }
            return $match;
        };
        $account['roles'] = array_filter($account['roles'],$removemissing);
        // Add new roles from the role change update
        foreach($rolesupdate as $ru) {
            $addrole = true;
            foreach($account['roles'] as $r) {
                if(strcasecmp($ru['href'], $r['href']) == 0) {
                    $addrole = false;
                    break;
                }
            }
            if($addrole) {
                $ru['added_date'] = new MongoDate();
                $account['roles'][] = $ru;
            }
        }
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ "roles" => $account['roles'] ]);
        if ($status) {
            return new JSendResponse('success', $account );
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }
    // End New Stuff
    // Get Methods    
    
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
    public function setAccountForIdentity($identity, $account) {
        $accounts = $this->getARMdb()->accounts;
        $assignaccount = $accounts->findOne([ "name" => $account . name]);
        if (is_null($assignaccount)) {
            return new JSendResponse('fail', [
                "account" => "Specified Account does not exist"
            ]);
        } elseif ((!is_null($assignaccount . identity)) ? ($assignaccount . identity . id == $identity . id) : false) {
            return new JSendResponse('fail', [
                "account" => "Identity already set for this account"
            ]);
        }
        $status = $accounts->update([ "name" => $account . name], [ "identity" => $identity . id]);
        if ($status) {
            return new JSendResponse('success', [ "status" => "Update Successful!"]);
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
    public function setRoleForAccount($account, $role) {
        $accounts = $this->getARMdb()->accounts;
        $assignaccount = $accounts->findOne([ "name" => $account["name"]]);
        if (is_null($assignaccount)) {
            return new JSendResponse('fail', [
                "account" => "Specified Account does not exist"
            ]);
        }
        $roles = $this->getARMdb()->roles;
        $assignrole = $roles->findOne([ "name" => $role["name"]]);
        if (is_null($assignrole)) {
            return new JSendResponse('fail', [
                "account" => "Specified Role does not exist"
            ]);
        } elseif (!isset($assignaccount["roles"])) {
            $assignaccount["roles"] = [];
        }
        if (in_array($assignrole["_id"], $assignaccount["roles"])) {
            return new JSendResponse('fail', [
                "account" => "Role already set for this account"
            ]);
        }
        $assignaccount["roles"][] = $assignrole["_id"];
        $status = $accounts->update([ "name" => $account["name"]], [ "roles" => $assignrole["roles"]]);
        if ($status) {
            return new JSendResponse('success', [ "status" => "Update Successful!"]);
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }

}
