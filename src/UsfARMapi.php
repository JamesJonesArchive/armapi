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
    use UsfARMformatter;
    
    private $version = "0.0.1";
    private $armdb = null;
    
    public function __construct() { }
    
    public function getVersion() {
        return $this->version;
    }
    
    public function getARMdb() {
        if ($this->armdb === null) {
            $this->armdb = $this->getMongoConnection()->arm;
        }

        return $this->armdb;
    }
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
    public function getAccountsForIdentity($identity,$filter=false) {
        $accounts = $this->getARMdb()->accounts;
        if($filter) {
            return new JSendResponse('success', [
                "identity" => $identity,
                "accounts" => $this->formatMongoAccountsListToAPIListing(iterator_to_array($accounts->find([ "identity" => $identity ])), ['identity'])
            ]);
        } else {
            return new JSendResponse('success', [
                "identity" => $identity,
                "accounts" => $this->formatMongoAccountsListToAPIListing(iterator_to_array($accounts->find([ "identity" => $identity ])), ['identity'])
            ]);
        }
    }
    /**
     * Return all accounts of a specified type
     * 
     * @param type $type
     * @return JSendResponse
     */
    public function getAccountsByType($type) {
        $accounts = $this->getARMdb()->accounts;
        return new JSendResponse('success',[ 
            'account_type' => $type, 
            "accounts" => $this->formatMongoAccountsListToAPIListing(iterator_to_array($accounts->find([ "type" => $type ])))
        ]);
    }
    // ALERT: May need work    
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
        $href = "/accounts/{$type}/{$account['account_identifier']}";
        
        $insert_status = $accounts->insert(
            array_merge(
                (array) $account["account_data"],
                [
                    'href' => $href,
                    'type' => $account['account_type'],
                    'identifier' => $account['account_identifier'],
                    'created_date' => new \MongoDate(),
                    'modified_date' => new \MongoDate()
                ],
                (isset($account["account_identity"]))?["identity" => $account["account_identity"]]:[],
                (isset($account["account_data"]["password_change"]))?["password_change" => new \MongoDate(strtotime($account["account_data"]["password_change"]))]:[],
                (isset($account["account_data"]["last_used"]))?["last_used" => new \MongoDate(strtotime($account["account_data"]["last_used"]))]:[],
                (isset($account["account_data"]["last_update"]))?["last_update" => new \MongoDate(strtotime($account["account_data"]["last_update"]))]:[]
            )
        );        
        if(!$insert_status) {
            return new JSendResponse('error', "Account creation could not be performed!");
        } else {
            return new JSendResponse('success', [
                "href" => $href
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
        return new JSendResponse('success', $this->formatMongoAccountToAPIaccount($account));
    }
    // May need some revisions
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
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], ['$set' => $accountmods]);
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
        return new JSendResponse('success', $this->formatMongoAccountToAPIaccount($account));
    }
    /**
     * Modify the role list for an accounty by it's type and identity (using the identifier)
     * 
     * @param type $type
     * @param type $identifier
     * @param type $rolechanges
     * @return JSendResponse
     */
    public function modifyRolesForAccountByTypeAndIdentifier($type,$identifier,$rolechanges) {
        $accounts = $this->getARMdb()->accounts;
        $roles = $this->getARMdb()->roles;
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        // ,[ "type" => true, "identifier" => true, "roles" => true ]
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
        if(!isset($account['roles'])) {
            $account['roles'] = [];
        }
        // Look for invalid roles
        if(count(array_filter($rolechanges['role_list'],function($r) use(&$roles) {
            return is_null($roles->findOne([ 'href' => $r['href'] ]));
        })) > 0) {
            return new JSendResponse('fail', [
                "role_list" => "Role list contains invalid roles!"
            ]);
        }
        // Determine the actual new roles and add _only_ the matched existing roles
        $account['roles'] = \array_map(function($r) use(&$roles) {
            return array_merge([
                "role_id" => $roles->findOne([ 'href' => $r['href'] ])['_id'],
                "added_date" => new \MongoDate()
            ], \array_diff_key($r,array_flip([
                'href','short_description','name'
            ])));
        },\array_filter($rolechanges['role_list'],function($r) use(&$roles,&$account) { 
            return !in_array($roles->findOne([ 'href' => $r['href'] ])['_id'], \array_map(function($a) { 
                return $a['role_id'];
            }, $account['roles']));
        })) + \array_filter($account['roles'],function($ar) use(&$roles,&$rolechanges) { 
            return in_array($ar['role_id'], \array_map(function($r) use(&$roles) { 
                return $roles->findOne([ 'href' => $r['href'] ])['_id'];
            },$rolechanges['role_list']));
        });
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => ['roles' => $account['roles']]]);
        if ($status) {
            return new JSendResponse('success', $this->formatMongoAccountToAPIaccount($account,\array_keys($account,\array_flip(['type','identifier','roles']))));
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }
    /**
     * Get all accounts of a certain type for a user 
     * 
     * @param type $type
     * @param type $identity
     * @return JSendResponse
     */
    public function getAccountsByTypeAndIdentity($type,$identity) {
        $accounts = $this->getARMdb()->accounts;
        return new JSendResponse('success',[ 
            "identity" => $identity, 
            'accounts' => $this->formatMongoAccountsListToAPIListing(iterator_to_array($accounts->find([ "type" => $type,"identity" => $identity ])))
        ]);
    }
    /**
     * Get all roles
     * 
     * @return JSendResponse
     */
    public function getAllRoles() {
        $roles = $this->getARMdb()->roles;
        $rolelist = $roles->find([],[ 'href' => true,'name' => true,'account_type' => true ]);
        $result = [];
        foreach($rolelist as $role) {
            if (!isset($result[$role['account_type']])) {
                $result[$role['account_type']] = [];
            }
            $result[$role['account_type']][] = [
                'href' => $role['href'],
                'name' => $role['name']
            ];
        }
        return new JSendResponse('success', $result);
    }
    /**
     * Create a new role of a specific account type
     * 
     * @param type $newrole
     * @return JSendResponse
     */
    public function createRoleByType($newrole) {
        $roles = $this->getARMdb()->roles;
        if (is_null($newrole)) {
            return new JSendResponse('fail', [
                "role" => "Role info missing"
            ]);
        }
        // Check to make sure the account itself has enough valid info
        if(!isset($newrole["account_type"]) || !isset($newrole["name"]) || !isset($newrole["role_data"])) {
            return new JSendResponse('fail', [
                "role" => "Role info missing one of these keys: account_type,name,role_data"
            ]);
        }
        // Make sure the account_data is not empty
        if(empty($newrole["role_data"])) {
            return new JSendResponse('fail', [
                "role" => "Role info is empty!"
            ]);
        }        
        $role = $roles->findOne([ 'name' => $newrole['name'], 'account_type' => $newrole['account_type'] ]);
        if (!is_null($role)) {
            return new JSendResponse('fail', [
                "role" => "Role already exists!"
            ]);
        }
        // Add on the href
        $formattedName = str_replace(" ","+",$newrole['name']);
        $href = "/roles/{$newrole['account_type']}/{$formattedName}";
        $insert_status = $roles->insert(
            array_merge(
                (array) $newrole["role_data"],
                [
                    'name' => $newrole['name'],
                    'href' => $href,
                    'type' => $newrole['account_type'],
                    'created_date' => new \MongoDate(),
                    'modified_date' => new \MongoDate()
                ]
            )
        );
        if(!$insert_status) {
            return new JSendResponse('error', "Role creation could not be performed!");
        } else {
            return new JSendResponse('success', [
                "href" => $href
            ]);
        }
    }
    /**
     * Get all roles of a specific account type
     * 
     * @param type $type
     * @return JSendResponse
     */
    public function getAllRolesByType($type) {
        $roles = $this->getARMdb()->roles;
        return new JSendResponse('success', [
            'account_type' => $type,
            'roles' => \array_map(function($r) {                
                return self::convertMongoDatesToUTCstrings(array_diff_key($r,["_id" => true]));
            }, iterator_to_array($roles->find([ 'type' => $type ])),[])
        ]);
    }
    /**
     * Get a single role of a type by role name
     * 
     * @param type $type
     * @param type $name
     * @return JSendResponse
     */
    public function getRoleByTypeAndName($type,$name) {
        $roles = $this->getARMdb()->roles;
        $role = $roles->findOne([ 'type' => $type, 'name' => $name ]); 
        if (is_null($role)) {
            return new JSendResponse('fail', [
                "role" => "Role does not exist!"
            ]);
        }
        return new JSendResponse('success', [
            'account_type' => $type,
            'role_data' => self::convertMongoDatesToUTCstrings(array_diff_key($role,["type" => true,"_id" => true]))
        ]);
    }
    /**
     * Modify a role of a type by role name
     * 
     * @param type $type
     * @param type $name
     * @param type $updatedrole
     * @return JSendResponse
     */
    public function modifyRoleByTypeAndName($type,$name,$updatedrole) {
        $roles = $this->getARMdb()->roles;
        $roleresp = $this->getRoleByTypeAndName($type, $name);
        if(!$roleresp->isSuccess()) {
            return $roleresp;
        }
        $role = $roleresp->getData();
        // Check to make sure the account itself has enough valid info
        if(!isset($updatedrole["account_type"]) || !isset($updatedrole["name"]) || !isset($updatedrole["role_data"])) {
            return new JSendResponse('fail', [
                "role" => "Role info missing one of these keys: account_type,name,role_data"
            ]);
        }
        // Make sure the account_data is not empty when there's no name change
        if(empty($updatedrole["role_data"]) && strcmp($updatedrole["name"], $name) == 0) {
            return new JSendResponse('fail', [
                "role" => "Role info is empty!"
            ]);
        }
        // Update the href
        $formattedName = str_replace(" ","+",$updatedrole['name']);
        $href = "/roles/{$updatedrole['account_type']}/{$formattedName}";
        $status = $roles->update(
            [ 'type' => $type, 'name' => $name ],
            [ '$set' => array_merge(
                    (array) $updatedrole["role_data"],
                    [
                        'name' => $updatedrole['name'],
                        'href' => $href,
                        'type' => $updatedrole['account_type'],
                        'modified_date' => new \MongoDate()
                    ],
                    (isset($role['role_data']['created_date']))?['created_date' => new \MongoDate(strtotime($role['role_data']['created_date']))]:['created_date' => new \MongoDate()]
                )
            ]                
        );
        if ($status) {
            return $this->getRoleByTypeAndName($type, $updatedrole['name']);
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }
    /** APPROVAL FUNCTIONS **/
    
    /**
     * Sets the account state
     * 
     * @param type $type
     * @param type $identifier
     * @param type $state
     * @param type $managerattributes
     * @return JSendResponse
     */
    public function setAccountState($type, $identifier, $state, $managerattributes=[]) {
        $accounts = $this->getARMdb()->accounts;
        $updatedattributes = [];
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account not found!"
            ]);
        }        
        if(!isset($account['state'])) {
            $updatedattributes['state'] = [ array_merge($managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]) ];
        } else {
            // Find existing match for manager
            if(empty(\array_filter($account['state'], function($r) use($managerattributes) {
                return ($r['usfid'] == $managerattributes['usfid']);
            }))) {            
                $updatedattributes['state'] = array_merge(
                    $account['state'],
                    [ array_merge($managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]) ]
                );
            } else {
                $updatedattributes['state'] = \array_map(function($s) use($managerattributes,$state) {                    
                    if($s['usfid'] == $managerattributes['usfid']) {
                        return \array_merge($s,$managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]);
                    } else {
                        return $s;                    
                    }                    
                },$account['state']);
            }
        }
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if ($status) {
            return $this->getAccountByTypeAndIdentifier($type, $identifier);
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }
    /**
     * Sets the role state on an account
     * 
     * @param type $type
     * @param type $identifier
     * @param type $rolename
     * @param type $state
     * @param type $managerattributes
     * @return JSendResponse
     */
    public function setAccountRoleState($type, $identifier, $rolename, $state, $managerattributes=[]) {
        $accounts = $this->getARMdb()->accounts;
        $updatedattributes = [];
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account not found!"
            ]);
        }
        if(!isset($account['roles'])) {
            return new JSendResponse('fail', [
                "role" => "No roles exist for account specified!"
            ]);
        } else {
            $roles = $this->getARMdb()->roles;
            $role = $roles->findOne([ 'type' => $type, 'name' => $rolename ]); 
            if (is_null($role)) {
                return new JSendResponse('fail', [
                    "role" => "Role does not exist!"
                ]);
            }            
            // Find if matching role exists
            if(empty(\array_filter($account['roles'], function($r) use($role) {
                return ($r['role_id'] == $role['_id']);
            }))) {
                return new JSendResponse('fail', [
                    "role" => "Role does not exist for account specified!"
                ]);
            } else {
                $updatedattributes['roles'] = \array_map(function($r) use($managerattributes,$state,$role) {  
                    if($r['role_id'] == $role['_id']) {
                        if(!isset($r['state'])) {
                            $r['state'] = [ \array_merge($managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]) ];
                        } else {
                            // Find existing match for manager
                            if(empty(\array_filter($r['state'], function($s) use($managerattributes) {
                                return ($s['usfid'] == $managerattributes['usfid']);
                            }))) { 
                                $r['state'][] = \array_merge($s,$managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]);
                            } else {
                                $r['state'] = \array_map(function($s) use($managerattributes,$state) {
                                    if($s['usfid'] == $managerattributes['usfid']) {
                                        return \array_merge($s,$managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]);
                                    } else {
                                        return $s;                                        
                                    }
                                },$r['state']);
                            }                            
                        }
                    }
                    return $r;
                },$account['roles']);
            }            
        }
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if ($status) {
            return $this->getAccountByTypeAndIdentifier($type, $identifier);
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }
    /**
     * Updates accounts for an identity to the confirmed state
     * 
     * @param type $identity
     * @param type $managerattributes
     * @return JSendResponse
     */
    public function setConfirm($identity,$managerattributes=[]) {
        $accounts = $this->getARMdb()->accounts;
        $confirmaccounts = $accounts->find([ "identity" => $identity ]);
        if(empty($confirmaccounts)) {
            return new JSendResponse('fail', [
                "identity" => "No accounts found for identity!"
            ]);
        }
        foreach ($confirmaccounts as $account) {
            $updatedattributes = [];
            if(!isset($account['confirm'])) {
                $account['confirm'] = [];
            }
            if(!isset($account['state'])) {
                $account['state'] = [];
            }
            // Find the state indicated by the manager
            if(empty(\array_filter($account['state'], function($r) use($managerattributes) {
                return ($r['usfid'] == $managerattributes['usfid']);
            }))) { 
                return new JSendResponse('fail', [
                    "account" => "Account does not have a state set by current manager!"
                ]);
            } else {
                // Append the confirm
                $confirm_state = \array_filter($account['state'], function($r) use($managerattributes) {
                    return ($r['usfid'] == $managerattributes['usfid']);
                });
                if(empty($confirm_state)) {
                    $updatedattributes['confirm'] = (isset($account['confirm']))?$account['confirm']:[];
                    $updatedattributes['confirm'][] = \array_merge($managerattributes,[ 
                        'state' => $confirm_state[0]['state'], 
                        'timestamp' => new \MongoDate() 
                    ]);
                }
                // Set the account review closed
                $updatedattributes['review'] = \array_map(function($r) use($managerattributes) {
                    if($r['usfid'] == $managerattributes['usfid']) {
                        return \array_merge($r,$managerattributes,[ 'review' => 'closed', 'timestamp' => new \MongoDate() ]);
                    } else {
                        return $r;
                    }
                },((isset($account['review']))?$account['review']:[]));
                // Iterate the role confirms and set those reviews closed as well 
                $updatedattributes['roles'] = \array_map(function($r) use($managerattributes) {
                    // Get the current state by manager
                    $confirm_state = \array_filter(((!isset($r['state']))?$r['state']:[]), function($s) use($managerattributes) {
                        return ($s['usfid'] == $managerattributes['usfid']);
                    });
                    if(!empty($confirm_state)) {
                        if(!isset($r['confirm'])) {
                            $r['confirm'] = [];
                        }
                        $r['confirm'][] = \array_merge($managerattributes,[ 
                            'state' => $confirm_state[0]['state'], 
                            'timestamp' => new \MongoDate() ]
                        );
                    }
                    $r['review'] = \array_map(function($rv) use($managerattributes) {
                        if($rv['usfid'] == $managerattributes['usfid']) {
                            return \array_merge($rv,$managerattributes,[ 'review' => 'closed', 'timestamp' => new \MongoDate() ]);
                        } else {
                            return $rv;
                        }
                    },((isset($r['review']))?$r['review']:[]));
                    return $r;
                },((isset($account['roles']))?$account['roles']:[]));                
                // Update the account
                $status = $accounts->update([ "identity" => $identity ], [ '$set' => $updatedattributes ]);
                if (!$status) {
                    return new JSendResponse('error', "Update failed!");
                }
            }
        }
        return $this->getAccountsForIdentity($identity);
    }
    /**
     * Updates accounts for an identity to the review state
     * 
     * @param type $identity
     * @param type $managerattributes
     * @return JSendResponse
     */
    public function setReview($identity,$managerattributes=[]) {
        $accounts = $this->getARMdb()->accounts;
        $reviewaccounts = $accounts->find([ "identity" => $identity ]);
        if(empty($reviewaccounts)) {
            return new JSendResponse('fail', [
                "identity" => "No accounts found for identity!"
            ]);
        }
        foreach ($reviewaccounts as $account) {
            $updatedattributes = [];
            if(!isset($account['review'])) {
                $account['review'] = [];
            }
            // Find the review indicated by the manager
            if(empty(\array_filter($account['review'], function($rv) use($managerattributes) {
                return ($rv['usfid'] == $managerattributes['usfid']);
            }))) { 
                $updatedattributes['review'] = (isset($account['review']))?$account['review']:[];
                $updatedattributes['review'][] = \array_merge($managerattributes,[ 'review' => 'open', 'timestamp' => new \MongoDate() ]);
            } else {
                $updatedattributes['review'] = \array_map(function($rv) use($managerattributes) {
                    if($rv['usfid'] == $managerattributes['usfid']) {
                        return \array_merge($rv,$managerattributes,[ 'review' => 'open', 'timestamp' => new \MongoDate() ]);
                    } else {
                        return $rv;
                    }
                },((isset($account['review']))?$account['review']:[]));
            }
            if(!isset($account['state'])) {
                $account['state'] = [];
            }
            // Find the state indicated by the manager
            if(empty(\array_filter($account['state'], function($s) use($managerattributes) {
                return ($s['state'] == $managerattributes['state']);
            }))) { 
                $updatedattributes['state'] = (isset($account['state']))?$account['state']:[];
                $updatedattributes['state'][] = \array_merge($managerattributes,[ 'state' => '', 'timestamp' => new \MongoDate() ]);
            } else {
                $updatedattributes['state'] = \array_map(function($s) use($managerattributes) {
                    if($s['usfid'] == $managerattributes['usfid']) {
                        return \array_merge($s,$managerattributes,[ 'state' => '', 'timestamp' => new \MongoDate() ]);
                    } else {
                        return $s;
                    }
                },((isset($account['state']))?$account['state']:[]));
            }
            if(!isset($account['roles'])) {
                $account['roles'] = [];
            }
            // Iterate the role reviews and set those reviews open as well 
            $updatedattributes['roles'] = \array_map(function($r) use($managerattributes) {
                if(!isset($r['review'])) {
                    $r['review'] = [];
                }
                // Find the review indicated by the manager
                if(empty(\array_filter($r['review'], function($rv) use($managerattributes) {
                    return ($rv['usfid'] == $managerattributes['usfid']);
                }))) { 
                    $r['review'][] = \array_merge($managerattributes,[ 'review' => 'open', 'timestamp' => new \MongoDate() ]);
                } else {
                    $r['review'] = \array_map(function($rv) use($managerattributes) {
                        if($rv['usfid'] == $managerattributes['usfid']) {
                            return \array_merge($rv,$managerattributes,[ 'review' => 'open', 'timestamp' => new \MongoDate() ]);
                        } else {
                            return $rv;
                        }
                    },((isset($r['review']))?$r['review']:[]));
                }
                if(!isset($r['state'])) {
                    $r['state'] = [];
                }
                // Find the state indicated by the manager
                if(empty(\array_filter($r['state'], function($s) use($managerattributes) {
                    return ($s['state'] == $managerattributes['state']);
                }))) { 
                    $r['state'][] = \array_merge($managerattributes,[ 'state' => '', 'timestamp' => new \MongoDate() ]);
                } else {
                    $r['state'] = \array_map(function($s) use($managerattributes) {
                        if($s['usfid'] == $managerattributes['usfid']) {
                            return \array_merge($s,$managerattributes,[ 'state' => '', 'timestamp' => new \MongoDate() ]);
                        } else {
                            return $s;
                        }
                    },((isset($r['state']))?$r['state']:[]));
                }
                return $r;
            },((isset($account['roles']))?$account['roles']:[]));
            // Update the account
            $status = $accounts->update([ "identity" => $identity ], [ '$set' => $updatedattributes ]);
            if (!$status) {
                return new JSendResponse('error', "Update failed!");
            }
        }
        return $this->getAccountsForIdentity($identity);
    }
    
    /** IMPORT FUNCTIONS **/
    
    /**
     * Takes SOR account in JSON format and imports it into ARM accounts
     * 
     * @param type $account
     * @return \Api\JSendResponse
     */
    public function importAccount($account) {
        $currentaccount = $this->getAccountByTypeAndIdentifier($account['account_type'],$account['account_identifier']);
        if($currentaccount->isSuccess()) {
            // Update existing account
            return $this->modifyAccountByTypeAndIdentifier($account['account_type'],$account['account_identifier'],$account);
        } else {
            // Create new account
            return $this->createAccountByType($account['account_type'], $account); 
        }
    }
    /**
     * Takes account roles from SOR in JSON format and imports it into ARM account roles
     * 
     * @param type $accountroles
     * @return \Api\JSendResponse
     */
    public function importAccountRoles($accountroles) {
        $currentaccount = $this->getAccountByTypeAndIdentifier($accountroles['account_type'],$accountroles['account_identifier']);
        if($currentaccount->isSuccess()) {
            return $this->modifyRolesForAccountByTypeAndIdentifier($accountroles['account_type'],$accountroles['account_identifier'],[
                'account_type' => $accountroles['account_type'],
                'account_identifier' => $accountroles['account_identifier'],
                'role_list' => $accountroles['account_roles']
            ]);
        } else {
            return new JSendResponse('fail', [
                "account" => "Account info missing"
            ]);
        }
    }
    /**
     * Takes SOR role in JSON format and imports it into ARM accounts
     * 
     * @param type $role
     * @return \Api\JSendResponse
     */
    public function importRole($role) {
        $currentrole = $this->getRoleByTypeAndName($role['account_type'],$role['name']);
        if($currentrole->isSuccess()) {
            // Update existing role
            return $this->modifyRoleByTypeAndName($role['account_type'],$role['name'],$role);
        } else {
            // Create new role
            return $this->createRoleByType($role);
        }
    }
}
