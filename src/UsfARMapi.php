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
    /**
     * Converts mongo dates to UTC date strings with one level of recursion
     * 
     * @param type $arr
     * @return type
     */
    public static function convertMongoDatesToUTCstrings($arr) {
        return \array_map(function($a) {
            if($a instanceof \MongoDate) {
                return $a->toDateTime()->format('Y-m-d\TH:i:s.u\Z');
            } elseif (\is_array($a) && \array_diff_key($a,\array_keys(\array_keys($a)))) {
                return \array_map(function ($b) {
                    if($b instanceof \MongoDate) {
                        return $b->toDateTime()->format('Y-m-d\TH:i:s.u\Z');
                    }
                    return $b;
                }, $a);
            }
            return $a;
        }, $arr);
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
    public function getAccountsForIdentity($identity) {
        $accounts = $this->getARMdb()->accounts;
        $roles = $this->getARMdb()->roles;
        return new JSendResponse('success', [
            "identity" => $identity,
            "accounts" => \array_map(function($act) use(&$roles) {
            unset($act['_id']);
            unset($act['identity']);
            if((isset($act['roles']))?  \is_array($act['roles']):false) {
                $act['roles'] = \array_map(function($a) use(&$roles) { 
                    if(isset($a['role_id'])) {
                        $role = $roles->find([ "_id" => $a['role_id'] ],[ 'name' => true, 'short_description' => true, 'href' => true, '_id' => false ]);
                        if (!is_null($role)) {
                            unset($a['role_id']);
                            return self::convertMongoDatesToUTCstrings(\array_merge($a,$role));
                        }
                    }
                    return self::convertMongoDatesToUTCstrings($a); 
                },$act['roles']); 
            } else {
                $act['roles'] = [];
            }
            return self::convertMongoDatesToUTCstrings($act);
        },iterator_to_array($accounts->find([ "identity" => $identity ])),[])]);
    }
    /**
     * Return all accounts of a specified type
     * 
     * @param type $type
     * @return JSendResponse
     */
    public function getAccountsByType($type) {
        $accounts = $this->getARMdb()->accounts;
        $roles = $this->getARMdb()->roles;
        return new JSendResponse('success',[ 
            'account_type' => $type, 
            'accounts' => \array_map(function($act) use(&$roles) {
                unset($act['_id']);
                if((isset($act['roles']))?  \is_array($act['roles']):false) {
                    $act['roles'] = \array_map(function($a) use(&$roles) { 
                        if(isset($a['role_id'])) {
                            $role = $roles->find([ "_id" => $a['role_id'] ],[ 'name' => true, 'short_description' => true, 'href' => true, '_id' => false ]);
                            if (!is_null($role)) {
                                unset($a['role_id']);
                                return self::convertMongoDatesToUTCstrings(\array_merge($a,$role));
                            }
                        }
                        return self::convertMongoDatesToUTCstrings($a); 
                    },$act['roles']); 
                } else {
                    $act['roles'] = [];
                }
                return self::convertMongoDatesToUTCstrings($act);
            },iterator_to_array($accounts->find([ "type" => $type ])),[]) 
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
        $roles = $this->getARMdb()->roles;
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account not found!"
            ]);
        }        
        unset($account['_id']);
        if((isset($account['roles']))?  \is_array($account['roles']):false) {
            $account['roles'] = \array_map(function($a) use(&$roles) { 
                if(isset($a['role_id'])) {
                    $role = $roles->find([ "_id" => $a['role_id'] ],[ 'name' => true, 'short_description' => true, 'href' => true, '_id' => false ]);
                    if (!is_null($role)) {
                        unset($a['role_id']);
                        return self::convertMongoDatesToUTCstrings(\array_merge($a,$role));
                    }
                }
                return self::convertMongoDatesToUTCstrings($a); 
            },$account['roles']); 
        } else {
            $account['roles'] = [];
        }
        return new JSendResponse('success', self::convertMongoDatesToUTCstrings($account));
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
        $roles = $this->getARMdb()->roles;
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ],[ "type" => true, "identifier" => true, "roles" => true ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account not found!"
            ]);
        }
        unset($account['_id']);
        if((isset($account['roles']))?  \is_array($account['roles']):false) {
            $account['roles'] = \array_map(function($a) use(&$roles) { 
                if(isset($a['role_id'])) {
                    $role = $roles->find([ "_id" => $a['role_id'] ],[ 'name' => true, 'short_description' => true, 'href' => true, '_id' => false ]);
                    if (!is_null($role)) {
                        unset($a['role_id']);
                        return self::convertMongoDatesToUTCstrings(\array_merge($a,$role));
                    }
                }
                return self::convertMongoDatesToUTCstrings($a); 
            },$account['roles']); 
        } else {
            $account['roles'] = [];
        }
        return new JSendResponse('success', self::convertMongoDatesToUTCstrings($account));
    }
    // EXPERIMENT! PICK UP HERE IN THE MORNING!!!
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
        $invalidrole = false;
        $merged_roles = [];
        foreach ($rolechanges['role_list'] as $r) {
            $role_obj = $roles->findOne([ 'href' => $r['href'] ],['href' => true, 'name' => true,'short_description'=> true]);
            if(is_null($role_obj)) {
                $invalidrole = true;
                break;
            }
            $found = false;
            $existing_role = null;
            foreach($account['roles'] as $ar) {
                if(strcmp($ar['href'], $r['href']) == 0) {
                    $found=true;
                    $existing_role = $ar;
                    break;
                }
            }
            if($found) {
                $merged_roles[] = $existing_role;
            } else {
                $merged_roles[] = array_merge(
                    [
                        "name" => $role_obj['name'],
                        "short_description" => $role_obj['short_description'],
                        "added_date" => new \MongoDate()
                    ],
                    $r
                );
            }
        }
        if($invalidrole) {
            return new JSendResponse('fail', [
                "role_list" => "Role list contains invalid roles!"
            ]);
        }
        $account['roles'] = $merged_roles;
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], $account);
        if ($status) {
            return new JSendResponse('success', [ 
                'type' => $account['type'],
                'identifier' => $account['identifier'],
                'roles' => $account['roles']
            ]);
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
        $accountlist = $accounts->find([ "type" => $type,"identity" => $identity ]);
        $result = [ "identity" => $identity, 'accounts' => [] ];
        foreach($accountlist as $act) {
            $result['accounts'][] = $act;
        }
        return new JSendResponse('success', $result);
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
        $rolelist = $roles->find([ 'account_type' => $type ]);        
        return new JSendResponse('success', ['account_type' => $type,'roles' => $rolelist]);
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
        return new JSendResponse('success', ['account_type' => $type,'role_data' => array_diff_key($role,["type" => true])]);
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
            array_merge(
                (array) $updatedrole["role_data"],
                [
                    'name' => $updatedrole['name'],
                    'href' => $href,
                    'type' => $updatedrole['account_type'],
                    'modified_date' => new \MongoDate()
                ],
                (isset($role['role_data']['created_date']))?['created_date' => $role['role_data']['created_date']]:['created_date' => new \MongoDate()]
            )
        );
        if ($status) {
            return $this->getRoleByTypeAndName($type, $updatedrole['name']);
            //return new JSendResponse('success', $role );
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }
}
