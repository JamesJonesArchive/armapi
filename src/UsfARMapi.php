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
    private $smtpServer;
    private $armdbName;

    public function __construct($request = ['armuser' => [ 'usf_id' => '', 'name' => '', 'role' => 'Batch' ]],$smtpServer = '') {
        $this->auditInfo = ($request instanceof \Slim\Http\Request)?UsfARMapi::getRequestAuditInfo($request):$request;
        $this->smtpServer = $smtpServer;
        $this->armdbName = "arm";
    }
    /**
     * Overrides the default arm collection name
     * 
     * @param string $armdbName
     */
    public function setARMdbName($armdbName) {
        $this->armdbName = $armdbName;
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
        return parent::getMongoConnection()->{$this->armdbName};
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
    public function getAllAccounts($include_hashes = false) {
        $accounts = $this->getARMaccounts();
        $accountlist = $accounts->find([], [ 'href' => true, '_id' => false, 'hash' => true, 'roles_hash' => true, 'type' => true ]);
        $result = [];
        foreach($accountlist as $act) {
            if (!isset($result[$act['type']])) {
                $result[$act['type']] = [];
            }
            $acct_data = [ 'href' => $act["href"] ];
            if ($include_hashes) {
                if (isset($act['hash']))$acct_data['hash'] = $act['hash'];
                if (isset($act['roles_hash'])) $acct_data['roles_hash'] = $act['roles_hash'];
            }
            $result[$act['type']][] = $acct_data;
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
        $accountattributes = \array_merge(
            UsfARMapi::convertUTCstringsToMongoDates((array) $account["account_data"],["password_change","last_used","last_update"]),
            [
                'href' => $href,
                'type' => $account['account_type'],
                'identifier' => $account['account_identifier'],
                'created_date' => new \MongoDate(),
                'modified_date' => new \MongoDate(),
                'state' => []
            ],
            (isset($account["account_identity"]))?["identity" => $account["account_identity"]]:[],
            (isset($account["account_data"]['status']))?['status_history' => UsfARMapi::getUpdatedStatusHistoryArray([], $account["account_data"]['status']) ]:[]
        );
        $insert_status = $accounts->insert($accountattributes);
        if(!$insert_status) {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_CREATE_ERROR']
            ]),"Internal Server Error",500);
        } else {
            $this->auditLog([ "type" => $type, "account" => $account ], $accountattributes);
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
    /**
     * Returns an updated status history array based on the new passed status
     * 
     * @param array $status_histories
     * @param string $current_status
     * @return array
     */
    public static function getUpdatedStatusHistoryArray($status_histories,$current_status) {
        if((!isset($status_histories))?true:empty($status_histories)) {
            return [ [ "status" => $current_status, "modified_date" => new \MongoDate() ] ];
        } else {
            $status = "Active";
            $currentDate = "";
            foreach ($status_histories as $status_history) {
                if((empty($currentDate))?true:($currentDate->toDateTime()->getTimestamp() < $status_history['modified_date']->toDateTime()->getTimestamp())) {
                    $status = $status_history['status'];
                    $currentDate = $status_history['modified_date'];
                }
            }
            if($current_status != $status) {
                return \array_merge($status_histories,[ [ "status" => $current_status, "modified_date" => new \MongoDate() ] ]);
            } else {
                return $status_histories;
            }               
        }
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
        $updatedattributes = \array_merge(array_diff_key(UsfARMapi::convertUTCstringsToMongoDates($accountmods["account_data"],["password_change","last_used","last_update"]),array_flip(['type','identifier'])),["href" => $href ]);
        if(!isset($updatedattributes['status_history'])) {
            $updatedattributes['status_history'] = [];
        }
        if(isset($updatedattributes['status'])) {
            $updatedattributes['status_history'] = UsfARMapi::getUpdatedStatusHistoryArray($updatedattributes['status_history'], $updatedattributes['status']);
        }
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], ['$set' =>  $updatedattributes ]);
        if ($status) {
            $this->auditLog([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
            return new JSendResponse('success', [ "href" => $href ]);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']
            ]),"Internal Server Error",500);
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

        if (!isset($account['roles'])) $account['roles'] = [];
        
        // Find any orphaned account roles and add them to the roles collection
        foreach (\array_filter($rolechanges['role_list'],function($r) use(&$roles) { return is_null($roles->findOne([ 'href' => $r['href'] ])); }) as $r) {
            $elements = \explode('/', \rtrim($r['href'], '/'));
            $name = \str_replace("+"," ",\array_pop($elements));
            $type = \array_pop($elements);
            $resp = $this->createRoleByType([
                "name" => $name,
                "account_type" => $type,
                "role_data" => \array_merge(\array_diff_key($r,\array_flip(["href"])),[
                    "description" => "ORPHANED",
                    "long_description" => ""                    
                ])
            ]);
            if($resp->isSuccess()) {
                $resp = $this->orphanRole($r['href']);
                if(!$resp->isSuccess()) {
                    return $resp;
                }
            } else {
                return $resp;
            }
        }
        
        // Look for any remaining invalid roles
        if(count(\array_filter($rolechanges['role_list'],function($r) use(&$roles) {
            return is_null($roles->findOne([ 'href' => $r['href'] ]));
        })) > 0) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLES_CONTAINS_INVALID']
            ]));
        }
        // Get the existing role ids
        $existingRoleIds = \array_map(function($r) {
            return $r['role_id'];
        }, \array_values(\array_filter($account['roles'], function($r) {
            if(isset($r['status'])) {
                // Exclude deleted roles (aka: status = "Removed")
                return !($r['status'] === "Removed");
            }
            return true;
        })));
        // Get the change list of role ids
        $changeRoleIds = \array_map(function($r) use(&$roles) {
            return $roles->findOne([ 'href' => $r['href'] ])['_id'];
        }, $rolechanges['role_list']);
        // Get the list of role ids to mark as deleted
        $deleteRoleIds = \array_diff($existingRoleIds, $changeRoleIds);
        // Mark those account roles as status "Removed"
        foreach (\array_map(function($id) use(&$roles) { return $roles->findOne([ '_id' => $id ])['href']; }, $deleteRoleIds) as $rhref) {
            $resp = $this->removeAccountRole($account['href'], $rhref);
            if(!$resp->isSuccess()) {
                return $resp;
            }
        }
        // Add the new roles
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        foreach ($rolechanges['role_list'] as $roleupdate) {
            $resp = $this->addAccountRole($account['href'],$roleupdate);
            if(!$resp->isSuccess()) {
                return $resp;
            }
        }

        // Update the roles hash
        if(isset($rolechanges['role_hash'])) {
            $account = $accounts->findAndModify(
                [ "type" => $type, "identifier" => $identifier ],
                ['$set' => ['roles_hash' => $rolechanges['role_hash']]],
                null,
                ["new" => true]
            );
        }

        // Refresh the account roles after add(s)
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        return new JSendResponse('success', $this->formatMongoAccountToAPIaccount($account,\array_keys($account,\array_flip(['type','identifier','roles']))));
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
    public function getAllRoles($include_hash = false) {
        $roles = $this->getARMroles();
        $rolelist = $roles->find([],[ 'href' => true,'name' => true,'type' => true ]);
        $result = [];
        foreach($rolelist as $role) {
            if (isset($role['type'])) {
                if (!isset($result[$role['type']])) {
                    $result[$role['type']] = [];
                }
                $data = [ 'href' => $role['href'], 'name' => $role['name'] ];
                if ($include_hash) $data['hash'] = $role['hash'];
                $result[$role['type']][] = $data;
            }
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
        $roleattributes = \array_merge(
            (array) $newrole["role_data"],
            [
                'name' => $newrole['name'],
                'href' => $href,
                'type' => $newrole['account_type'],
                'status' => 'Active',
                'created_date' => new \MongoDate(),
                'modified_date' => new \MongoDate()
            ]
        );
        $insert_status = $roles->insert($roleattributes);
        if(!$insert_status) {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_CREATE_ERROR']
            ]),"Internal Server Error",500);
        } else {
            $this->auditLog([ "role" => $newrole ], $roleattributes);
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
        $updatedattributes = \array_merge(
            UsfARMapi::convertUTCstringsToMongoDates((array) $updatedrole["role_data"], ['created_date']),
            [
                'name' => $updatedrole['name'],
                'href' => $href,
                'type' => $updatedrole['account_type'],
                'status' => 'Active',
                'modified_date' => new \MongoDate()
            ],
            (isset($role['role_data']['created_date']))?[]:['created_date' => new \MongoDate()]
        );
        $status = $roles->update(
            [ 'type' => $type, 'name' => $name ],
            [ '$set' => $updatedattributes ]
        );
        if ($status) {
            $this->auditLog([ "type" => $type, 'name' => $name ], [ '$set' => $updatedattributes ]);
            return $this->getRoleByTypeAndName($type, $updatedrole['name']);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_UPDATE_ERROR']
            ]),"Internal Server Error",500);
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
        if(isset($account['roles'])) {
            $roles = $this->getARMroles();
            foreach ($account['roles'] as $r) {
                $role = $roles->findOne(['_id' => $r['role_id']]);
                if(is_null($role)) {
                    return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                        "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS']
                    ]));
                }
                $resp = $this->removeAccountRole($href,$role['href']);
                if(!$resp->isSuccess()) {
                    return $resp;
                }
            }
        }
        $status = $accounts->update([ 'href' => $href ], ['$set' => [ "status" => "Removed", 'modified_date' => new \MongoDate(), 'status_history' => UsfARMapi::getUpdatedStatusHistoryArray($account['status_history'], "Removed") ] ]);
        if ($status) {
            $this->auditLog([ 'href' => $href ], [ 'href' => $href ]);
            return $this->getAccountByTypeAndIdentifier($account['type'], $account['identifier']);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_DELETE_ERROR']
            ]),"Internal Server Error",500);
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
     * Orphan a role in the roles collection
     *
     * @param string $href
     * @return JSendResponse
     */
    public function orphanRole($href) {
        $roles = $this->getARMroles();
        $role = $roles->findOne([ 'href' => $href ]);
        if (is_null($role)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS']
            ]));
        }
        $status = $roles->update([ 'href' => $href ], ['$set' => [ "status" => "Orphaned", 'modified_date' => new \MongoDate() ] ]);
        if ($status) {
            $this->auditLog([ 'href' => $href ], [ 'href' => $href ]);
            return $this->getRoleByTypeAndName($role['type'], $role['name']);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DELETE_ERROR']
            ]),"Internal Server Error",500);
        }
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
        $status = $roles->update([ 'href' => $href ], ['$set' => [ "status" => "Removed", 'modified_date' => new \MongoDate() ] ]);
        if ($status) {
            $this->auditLog([ 'href' => $href ], [ 'href' => $href ]);
            return $this->getRoleByTypeAndName($role['type'], $role['name']);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DELETE_ERROR']
            ]),"Internal Server Error",500);
        }
    }
    /**
     * Removes an role from the roles
     *
     * @param string $type
     * @param string $rolename
     * @return JSendResponse
     */
    public function removeRoleByTypeAndName($type,$rolename) {
        return $this->removeAccount(UsfARMapi::formatRoleName("/accounts/{$type}/{$rolename}"));
    }
    /**
     * Flags the status of an account role as "Removed" to represent deleted
     *
     * @param string $ahref An account href
     * @param string $rhref The target role href for removal
     * @return JSendResponse
     */
    public function removeAccountRole($ahref,$rhref) {
        $accounts = $this->getARMaccounts();
        $roles = $this->getARMroles();
        $account = $accounts->findOne([ 'href' => $ahref ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }
        if(!isset($account['roles'])) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NO_ROLES_EXIST']
            ]));
        }
        $role = $roles->findOne([ 'href' => $rhref ]);
        if (is_null($role)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS']
            ]));
        }
        if(!\in_array($role['_id']->{'$id'}, \array_map(function($r) { return $r['role_id']->{'$id'}; }, $account['roles']))) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_ROLE_NOT_EXISTS']
            ]));
        }
        $updatedattributes = [
            'roles' => \array_map(function($r) use(&$role) {
                if($role['_id'] == $r['role_id']) {
                    $r['status'] = "Removed";
                    $r['modified_date'] = new \MongoDate();
                    $r['status_history'] = UsfARMapi::getUpdatedStatusHistoryArray($r['status_history'], "Removed");
                }
                return $r;
            }, $account['roles'])
        ];
        $status = $accounts->update([ 'href' => $ahref ], ['$set' => $updatedattributes ]);
        if ($status) {
            $this->auditLog([ 'account_href' => $ahref, 'role_href' => $rhref ], $updatedattributes);
            return $this->getAccountByTypeAndIdentifier($account['type'], $account['identifier']);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DELETE_ERROR']
            ]),"Internal Server Error",500);
        }
    }
    /**
     * Appends an existing role to an account or modifies a deleted account role to remove the status=Removed flag
     *
     * @param string $type The account type
     * @param string $identifier The account identifier
     * @param type $roleappend The array of role data
     * @return JSendResponse
     */
    public function addAccountRoleByTypeAndIdentifier($type,$identifier,$roleappend) {
        return $this->addAccountRole("/accounts/{$type}/{$identifier}",$roleappend);
    }
    /**
     * Appends an existing role to an account or modifies a deleted account role to remove the status=Removed flag
     *
     * @param string $href The account href
     * @param array $roleappend The array of role data
     * @return JSendResponse
     */
    public function addAccountRole($href,$roleappend) {
        $accounts = $this->getARMaccounts();
        $roles = $this->getARMroles();
        $account = $accounts->findOne([ 'href' => $href ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }
        if(!isset($account['roles'])) {
            $account['roles'] = [];
        }
        $role = $roles->findOne([ 'href' => $roleappend['href'] ]);
        if (is_null($role)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS']
            ]));
        }
        if(!\in_array($role['_id']->{'$id'}, \array_map(function($r) { return $r['role_id']->{'$id'}; }, $account['roles']))) {
            // Append it
            $account['roles'][] = \array_merge([
                "role_id" => $roles->findOne([ 'href' => $roleappend['href'] ])['_id'],
                "added_date" => new \MongoDate(),
                "modified_date" => new \MongoDate(),
                "status" => "Active",
                "state" => [],
                'status_history' => UsfARMapi::getUpdatedStatusHistoryArray([],"Active")
            ], \array_diff_key($roleappend,array_flip([
                'href','short_description','name','role_id','added_date','modified_date','status'
            ])));
        } else {
            // Remove status="Removed" if present
            $account['roles'] = \array_map(function($r) use(&$roles,$roleappend) {
                if($roles->findOne([ 'href' => $roleappend['href']])['_id'] == $r['role_id']) {
                    return \array_merge(\array_diff_key($r,array_flip([
                        'href','short_description','name','status'
                    ])),\array_diff_key($roleappend,array_flip([
                        'href','short_description','name','role_id','added_date','modified_date'
                    ])),[
                        'status' => 'Active',
                        'modified_date' => new \MongoDate(),
                        'status_history' => UsfARMapi::getUpdatedStatusHistoryArray($r['status_history'],"Active")
                    ]);
                }
                return $r;
            }, $account['roles']);
        }
        $updatedattributes = [ 'roles' => $account['roles'] ];
        $status = $accounts->update([ 'href' => $href ], ['$set' => $updatedattributes ]);
        if ($status) {
            $this->auditLog([ 'account_href' => $href, 'role' => $roleappend ], $updatedattributes);
            return $this->getAccountByTypeAndIdentifier($account['type'], $account['identifier']);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_DELETE_ERROR']
            ]),"Internal Server Error",500);
        }
    }
}
