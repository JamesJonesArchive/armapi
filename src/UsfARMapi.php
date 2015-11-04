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
    use UsfARMapprovals;
    use UsfARMimport;
    use UsfARMErrorMessages;
    use UsfARMaudit;
    
    private $version = "0.0.1";
    private $auditInfo;
    
    public function __construct($request = []) {         
        $this->auditInfo = ($request instanceof \Slim\Http\Request)?UsfARMapi::getRequestAuditInfo($request):$request;
    }
    /**
     * Returns the current API version
     * 
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }
    /**
     * Returns the arm database 
     * 
     * @return \MongoDB
     */
    public function getARMdb() {
        return parent::getMongoConnection()->arm;
    }
    /**
     * Returns the accounts mongo collection
     * 
     * @return \MongoCollection
     */
    public function getARMaccounts() {
        return $this->getARMdb()->accounts;
    }
    /**
     * Returns the roles mongo collection
     * 
     * @return \MongoCollection
     */
    public function getARMroles() {
        return $this->getARMdb()->roles;
    }
    /**
     * Returns all accounts of all types
     * 
     * @return JSendResponse
     */
    public function getAllAccounts() {
        $accounts = $this->getARMaccounts();
        $accountlist = $accounts->find();
        $result = [];
        foreach($accountlist as $act) {
            if (!isset($result[$act['type']])) {
                $result[$act['type']] = [];
            }
            $result[$act['type']][] = [ 'href' => $act["href"] ];
        }
        return new JSendResponse('success', $result);
    }
    /**
     * Retrieves an array of accounts for a specified identity 
     * 
     * @param string $identity
     * @return array of accounts
     */
    public function getAccountsForIdentity($identity) {
        $accounts = $this->getARMaccounts();
        return new JSendResponse('success', [
            "identity" => $identity,
            "accounts" => $this->formatMongoAccountsListToAPIListing(iterator_to_array($accounts->find([ "identity" => $identity ])), ['identity'])
        ]);            
    }
    /**
     * Return all accounts of a specified type
     * 
     * @param string $type
     * @return JSendResponse
     */
    public function getAccountsByType($type) {
        $accounts = $this->getARMaccounts();
        return new JSendResponse('success',[ 
            'account_type' => $type, 
            "accounts" => $this->formatMongoAccountsListToAPIListing(iterator_to_array($accounts->find([ "type" => $type ])))
        ]);
    }
    /**
     * Returns all the available account types
     * 
     * @return JSendResponse
     */
    public function getAccountTypes() {
        $accounts = $this->getARMaccounts();
        return new JSendResponse('success',[
            'account_types' => $accounts->distinct('type')
        ]);
    }
    /**
     * Add a new account
     * 
     * @param string $type
     * @param array $account
     * @return JSendResponse
     */
    public function createAccountByType($type,$account) {
        $accounts = $this->getARMaccounts();
        if (is_null($account)) {            
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING']
            ]));
        }
        // Check to make sure the account itself has enough valid info
        if(!isset($account["account_type"]) || !isset($account["account_identifier"]) || !isset($account["account_data"])) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING_REQUIRED_KEYS'],
                "required_keys" => ['account_type','account_identifier','account_data']
            ]));
        }
        // Make sure the account_data is not empty
        if(empty($account["account_data"])) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_DATA_EMPTY']
            ]));
        }
        // Check to make sure the type is set the same in the account data as indicated from the call
        if(strcasecmp($account["account_type"], $type) != 0) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_TYPE_MISMATCH']
            ]));
        }
        // Check to see if it exists already
        if($this->getAccountByTypeAndIdentifier($type, $account["account_identifier"])->isSuccess()) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_EXISTS'] 
            ]));
        }
        // Add on the href
        $href = "/accounts/{$type}/{$account['account_identifier']}";
        
        $insert_status = $accounts->insert(
            array_merge(
                UsfARMapi::convertUTCstringsToMongoDates((array) $account["account_data"],["password_change","last_used","last_update"]),
                [
                    'href' => $href,
                    'type' => $account['account_type'],
                    'identifier' => $account['account_identifier'],
                    'created_date' => new \MongoDate(),
                    'modified_date' => new \MongoDate()
                ],
                (isset($account["account_identity"]))?["identity" => $account["account_identity"]]:[]
            )
        );        
        if(!$insert_status) {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [ 
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_CREATE_ERROR'] 
            ])); 
        } else {
            return new JSendResponse('success', [
                "href" => $href
            ]);
        }
    }
    /**
     * Retrieve the account by type and identity (using the identifier)
     * 
     * @param string $type
     * @param string $identifier
     * @return JSendResponse
     */
    public function getAccountByTypeAndIdentifier($type,$identifier) {
        $accounts = $this->getARMaccounts();
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }        
        return new JSendResponse('success', $this->formatMongoAccountToAPIaccount($account));
    }
    // May need some revisions
    /**
     * Modify an account by type and identity (using the identifier)
     * 
     * @param string $type
     * @param string $identifier
     * @param array $accountmods
     * @return JSendResponse
     */
    public function modifyAccountByTypeAndIdentifier($type,$identifier,$accountmods) {
        $accounts = $this->getARMaccounts();
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }
        // Make sure the account_data is not empty
        if(empty($accountmods["account_data"])) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_DATA_EMPTY']
            ]));
        }        
        $href = "/accounts/{$type}/{$identifier}";
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], ['$set' => \array_merge(array_diff_key(UsfARMapi::convertUTCstringsToMongoDates($accountmods["account_data"],["password_change","last_used","last_update"]),array_flip(['type','identifier'])),["href" => $href ]) ]);
        if ($status) {
            return new JSendResponse('success', [ "href" => $href ]);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']
            ])); 
        }
    }
    /**
     * Get roles for a specific account by type and identity (using the identifier)
     * 
     * @param string $type
     * @param string $identifier
     * @return JSendResponse
     */
    public function getRolesForAccountByTypeAndIdentifier($type,$identifier) {
        $accounts = $this->getARMaccounts();
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ],[ "type" => true, "identifier" => true, "roles" => true ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }
        return new JSendResponse('success', $this->formatMongoAccountToAPIaccount($account));
    }
    /**
     * Modify the role list for an accounty by it's type and identity (using the identifier)
     * 
     * @param string $type
     * @param string $identifier
     * @param array $rolechanges
     * @return JSendResponse
     */
    public function modifyRolesForAccountByTypeAndIdentifier($type,$identifier,$rolechanges) {
        $accounts = $this->getARMaccounts();
        $roles = $this->getARMroles();
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        // ,[ "type" => true, "identifier" => true, "roles" => true ]
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }
        if(!isset($rolechanges['role_list'])) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_LIST_MISSING'] 
            ]));
        }
        if(!isset($account['roles'])) {
            $account['roles'] = [];
        }
        // Look for invalid roles
        if(count(array_filter($rolechanges['role_list'],function($r) use(&$roles) {
            return is_null($roles->findOne([ 'href' => $r['href'] ]));
        })) > 0) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLES_CONTAINS_INVALID']
            ]));
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
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR'] 
            ]));
        }
    }
    /**
     * Get all accounts of a certain type for a user 
     * 
     * @param string $type
     * @param string $identity
     * @return JSendResponse
     */
    public function getAccountsByTypeAndIdentity($type,$identity) {
        $accounts = $this->getARMaccounts();
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
        $roles = $this->getARMroles();
        $rolelist = $roles->find([],[ 'href' => true,'name' => true,'type' => true ]);
        $result = [];
        foreach($rolelist as $role) {
            if (!isset($result[$role['type']])) {
                $result[$role['type']] = [];
            }
            $result[$role['type']][] = [
                'href' => $role['href'],
                'name' => $role['name']
            ];
        }
        return new JSendResponse('success', $result);
    }
    /**
     * Create a new role of a specific account type
     * 
     * @param array $newrole
     * @return JSendResponse
     */
    public function createRoleByType($newrole) {
        $roles = $this->getARMroles();
        if (is_null($newrole)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING']
            ]));
        }
        // Check to make sure the account itself has enough valid info
        if(!isset($newrole["account_type"]) || !isset($newrole["name"]) || !isset($newrole["role_data"])) {            
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING_REQUIRED_KEYS'],
                "required_keys" => ['account_type','name','role_data']
            ]));
        }
        // Make sure the account_data is not empty
        if(empty($newrole["role_data"])) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DATA_EMPTY']
            ]));
        }        
        // Build the href
        $href = "/roles/{$newrole['account_type']}/" . UsfARMapi::formatRoleName($newrole['name']);
        $role = $roles->findOne([ 'href' => $href ]);
        if (!is_null($role)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_EXISTS']
            ])); 
        }
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
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_CREATE_ERROR']
            ]));
        } else {
            return new JSendResponse('success', [
                "href" => $href
            ]);
        }
    }
    /**
     * Get all roles of a specific account type
     * 
     * @param string $type
     * @return JSendResponse
     */
    public function getAllRolesByType($type) {
        $roles = $this->getARMroles();
        return new JSendResponse('success', [
            'account_type' => $type,
            'roles' => \array_map(function($r) {                
                return self::convertMongoDatesToUTCstrings(array_diff_key($r,["_id" => true]));
            }, \iterator_to_array($roles->find([ 'type' => $type ])),[])
        ]);
    }
    /**
     * Get a single role of a type by role name
     * 
     * @param string $type
     * @param string $name
     * @return JSendResponse
     */
    public function getRoleByTypeAndName($type,$name) {
        $roles = $this->getARMroles();
        // Build the href
        $href = "/roles/{$type}/" . UsfARMapi::formatRoleName($name);
        $role = $roles->findOne([ 'href' => $href ]);
        if (is_null($role)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS'] 
            ]));
        }
        return new JSendResponse('success', [
            'account_type' => $type,
            'role_data' => self::convertMongoDatesToUTCstrings(array_diff_key($role,["type" => true,"_id" => true]))
        ]);
    }
    /**
     * Modify a role of a type by role name
     * 
     * @param string $type
     * @param string $name
     * @param array $updatedrole
     * @return JSendResponse
     */
    public function modifyRoleByTypeAndName($type,$name,$updatedrole) {
        $roles = $this->getARMroles();
        $roleresp = $this->getRoleByTypeAndName($type, $name);
        if(!$roleresp->isSuccess()) {
            return $roleresp;
        }
        $role = $roleresp->getData();
        // Check to make sure the account itself has enough valid info
        if(!isset($updatedrole["account_type"]) || !isset($updatedrole["name"]) || !isset($updatedrole["role_data"])) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING_REQUIRED_KEYS'],
                "required_keys" => ['account_type','name','role_data']
            ]));
        }
        // Make sure the role_data is not empty when there's no name change
        if(empty($updatedrole["role_data"]) && strcmp($updatedrole["name"], $name) == 0) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DATA_EMPTY']
            ]));
        }
        // Update the href
        $href = "/roles/{$updatedrole['account_type']}/" . UsfARMapi::formatRoleName($updatedrole['name']);
        $status = $roles->update(
            [ 'type' => $type, 'name' => $name ],
            [ '$set' => array_merge(
                    UsfARMapi::convertUTCstringsToMongoDates((array) $updatedrole["role_data"], ['created_date']),
                    [
                        'name' => $updatedrole['name'],
                        'href' => $href,
                        'type' => $updatedrole['account_type'],
                        'modified_date' => new \MongoDate()
                    ],
                    (isset($role['role_data']['created_date']))?[]:['created_date' => new \MongoDate()]
                )
            ]                
        );
        if ($status) {
            return $this->getRoleByTypeAndName($type, $updatedrole['name']);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_UPDATE_ERROR']
            ]));
        }
    }
    /**
     * Flags a "Deleted" status for an account from the accounts
     * 
     * @param string $href
     * @return JSendResponse
     */
    public function removeAccount($href) {
        $accounts = $this->getARMaccounts();
        $account = $accounts->findOne([ 'href' => $href ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }  
        $status = $accounts->update([ 'href' => $href ], ['$set' => [ "status" => "Removed" ] ]);
        if ($status) {
            $this->auditLog([ 'href' => $href ], [ 'href' => $href ]);
            return new JSendResponse('success', [ "href" => $href ]);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_DELETE_ERROR']
            ])); 
        }
    }
    /**
     * Removes an account from the accounts
     * 
     * @param string $type
     * @param string $identifier
     * @return JSendResponse
     */
    public function removeAccountByTypeAndIdentifier($type,$identifier) {        
        return $this->removeAccount("/accounts/{$type}/{$identifier}");
    }
    /**
     * Removes an role from the roles
     * 
     * @param string $href
     * @return JSendResponse
     */
    public function removeRole($href) {
        $roles = $this->getARMroles();
        $role = $roles->findOne([ 'href' => $href ]);
        if (is_null($role)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS']
            ]));
        }  
        $status = $roles->update([ 'href' => $href ], ['$set' => [ "status" => "Removed" ] ]);
        if ($status) {
            $this->auditLog([ 'href' => $href ], [ 'href' => $href ]);
            return new JSendResponse('success', [ "href" => $href ]);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DELETE_ERROR']
            ])); 
        }
    }
    /**
     * Removes an role from the roles
     * 
     * @param string $type
     * @param string $rolename
     * @return JSendResponse
     */
    public function removeRoleByTypeAndIdentifier($type,$rolename) {  
        return $this->removeAccount(UsfARMapi::formatRoleName("/accounts/{$type}/{$rolename}"));
    }

    
}
