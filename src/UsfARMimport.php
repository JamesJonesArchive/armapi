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

use \JSend\JSendResponse;
/**
 * Description of UsfARMimport
 *
 * @author james
 */
trait UsfARMimport {

    protected $current_accounts = [];
    protected $current_roles = [];
    protected $current_roles_hash = [];
    protected $current_accounts_hash = [];
    protected $current_mapping_hash = [];

    /**
     * Returns the tracking mongo collection (tracking changes)
     *
     * @return \MongoCollection
     */
    public function getARMtracking($uniquesuffix = "") {
        if(!empty($uniquesuffix)) {
            $this->getARMdb()->selectCollection("tracking".$uniquesuffix);
        } else {
            return $this->getARMdb()->tracking;
        }
    }
    /**
     * Returns the logs mongo collection (logging changes)
     *
     * @return \MongoCollection
     */
    public function getARMlogs() {
        return $this->getARMdb()->logs;
    }
    /**
     * Takes SOR account in JSON format and imports it into ARM accounts
     *
     * @param array $account
     * @return JSendResponse
     */
    public function importAccount($account) {
        if (!empty($account)) {
            $href = '/accounts/'.$account['account_type'].'/'.str_replace(' ', '+', $account['account_identifier']);
            $hash = md5(serialize($account));
            $account['account_data']['hash'] = $hash;

            if (isset($this->current_accounts_hash[$account['account_type']])) $href_key = array_search($href, $this->current_accounts[$account['account_type']]);

            if (isset($href_key) && $href_key !== false) {
                unset($this->current_accounts[$account['account_type']][$href_key]);

                $hash_key = array_search($hash, $this->current_accounts_hash[$account['account_type']]);
                if ($hash_key === false) {
                    // Update existing account
                    return $this->modifyAccountByTypeAndIdentifier($account['account_type'], $account['account_identifier'], $account);
                } else {
                    unset($this->current_accounts_hash[$account['account_type']][$hash_key]);

                    return new JSendResponse('success', ['message' => 'No update necessary']);
                }
            } else {
                // Create new account
                return $this->createAccountByType($account['account_type'], $account);
            }
        } else {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING']
            ]));
        }
    }
    /**
     * Takes account roles from SOR in JSON format and imports it into ARM account roles
     *
     * @param array $accountroles
     * @return JSendResponse
     */
    public function importAccountRoles($accountroles) {
        if (!empty($accountroles)){
            $hash = md5(serialize($accountroles));
            $key = array_search($hash, $this->current_mapping_hash);
            if ($key === false) {
                $currentaccount = $this->getAccountByTypeAndIdentifier($accountroles['account_type'],$accountroles['account_identifier']);
                if ($currentaccount->isSuccess()) {
                    return $this->modifyRolesForAccountByTypeAndIdentifier($accountroles['account_type'],$accountroles['account_identifier'],[
                        'account_type' => $accountroles['account_type'],
                        'account_identifier' => $accountroles['account_identifier'],
                        'role_list' => $accountroles['account_roles'],
                        'role_hash' => $hash
                    ]);

                } else {
                    return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                        "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING']
                    ]));
                }
            } else {
                unset($this->current_mapping_hash[$key]);
                return new JSendResponse('success', ['message' => 'No update necessary']);
            }
        } else {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING']
            ]));
        }
    }
    /**
     * Takes SOR role in JSON format and imports it into ARM accounts
     *
     * @param array $role
     * @return JSendResponse
     */
    public function importRole($role) {
        if (!empty($role)) {
            $href = '/roles/'.$role['account_type'].'/'.str_replace(' ', '+', $role['name']);
            $hash = md5(serialize($role));
            $role['role_data']['hash'] = $hash;
            if (isset($this->current_roles[$role['account_type']]) && in_array($href, $this->current_roles[$role['account_type']])) {
                if (! in_array($hash, $this->current_roles_hash[$role['account_type']])) {
                    // Update existing role
                    return $this->modifyRoleByTypeAndName($role['account_type'], $role['name'], $role);
                }
            } else {
                // Create new role
                return $this->createRoleByType($role);
            }
         } else {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_INFO_MISSING']
            ]));
        }
    }

    /**
     * Finds all existing accounts and primes a compares collection for later processing
     *
     * @return JSendResponse
     */
    public function buildAccountComparison($type) {
        $result = [];
        if(is_null($type)) {
            foreach ($this->getAllAccounts(true)->getData() as $type => $accounts) {
              $this->current_accounts[$type] = [];
              $this->current_accounts_hash[$type] = [];
              foreach ($accounts as $account) {
                if (isset($account['href'])) $this->current_accounts[$type][] = $account['href'];
                if (isset($account['hash'])) $this->current_accounts_hash[$type][] = $account['hash'];
              }
            }
        } else {
            $accounts = $this->getARMaccounts()->find(['type' => $type ],[ 'href' => true, '_id' => false ]);
            $this->current_accounts[$type] = [];
            $this->current_accounts_hash[$type] = [];
            foreach ($accounts as $account) {
                if (isset($account['href'])) $this->current_accounts[$type][] = $account['href'];
                if (isset($account['hash'])) $this->current_accounts_hash[$type][] = $account['hash'];
            }
            $result[$type] = count($accounts);
        }
        return new JSendResponse('success', $result);
    }
    /**
     * Finds all existing roles and primes a compares collection for later processing
     *
     * @return JSendResponse
     */
    public function buildRoleComparison($type) {
        $result = [];
        if(is_null($type)) {
            foreach ($this->getAllRoles()->getData() as $type => $roles) {
              $this->current_roles[$type] = [];
              $this->current_roles_hash[$type] = [];
              foreach ($roles as $role) {
                if (isset($role['href'])) $this->current_roles[$type][] = $role['href'];
                if (isset($role['hash'])) $this->current_roles_hash[$type][] = $role['hash'];
              }
              $result[$type] = count($roles);
            }
        } else {
          $this->current_roles[$type] = [];
          $this->current_roles_hash[$type] = [];
            $roles = \array_map(function($a) {
                return ['href' => $a['href']];
            }, $this->getAllRolesByType($type)->getData()['roles']);
            $result[$type] = count($roles);
            foreach ($roles as $role) {
              if (isset($role['href'])) $this->current_roles[$type][] = $role['href'];
              if (isset($role['hash'])) $this->current_roles_hash[$type][] = $role['hash'];
            }
        }
        return new JSendResponse('success', $result);
    }
    /**
     * Finds all existing roles<=>account mappings and primes a compares collection for later processing
     *
     * @return JSendResponse
     */
    public function buildMappingComparison($type) {
        $result = [];
        $this->current_mapping_hash = [];
        foreach ($this->getAllAccounts(true)->getData() as $type => $accounts) {
            foreach ($accounts as $account) {
                if (isset($account['href']) && isset($account['roles_hash'])) $this->current_mapping_hash[] = $account['roles_hash'];
            }
            $result[$type] = count($accounts);
        }
        return new JSendResponse('success', $result);
    }

    /**
     * Removes an account from the tracking list
     *
     * @param string $href
     * @return JSendResponse
     */
    public function removeHrefFromTracking($href) {
        $compares = $this->getARMtracking();
        $delete_status = $compares->remove(['href' => $href], ["justOne" => true]);
        if($delete_status['n'] < 1) {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['TRACKING_ACCOUNT_DELETE_FAILED']
            ]),"Internal Server Error",500);
        } else {
            return new JSendResponse('success', [
                "href" => $href
            ]);
        }
    }
    /**
     * Gets the hrefs in the tracking collection
     *
     * @return JSendResponse
     */
    public function getTrackingHrefList() {
        $compares = $this->getARMtracking();
        return new JSendResponse('success', [
            'hrefs' => $compares->distinct("href",[ "href" => [ '$exists' => true ] ])
        ]);
    }
    /**
     * Logs the import error and offending object
     *
     * @param string $importType
     * @param array $importObject
     * @param array $error
     * @return JSendResponse
     */
    public function logImportErrors($importType,$importObject,$error) {
        $logs = $this->getARMlogs();
        $insert_status = $logs->insert(\array_merge([
            'importType' => $importType,
            'importObject' => $importObject,
            'error' => $error
        ],[
            'timestamp' => new \MongoDate()
        ]));
        if(!$insert_status) {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['LOG_CREATE_ERROR']
            ]),"Internal Server Error",500);
        } else {
            return new JSendResponse('success', [
                "error" => $error
            ]);
        }
    }
    /**
     * Ensures the indexes on the collections are setup
     */
    public function ensureIndexes() {
        // Indexes for accounts (1 is ascending)
        $this->getARMdb()->accounts->createIndex(['href' => 1]);
        $this->getARMdb()->accounts->createIndex(['identity' => 1]);
        $this->getARMdb()->accounts->createIndex(['type' => 1,'identifier' => 1]);
        // Indexes for roles (1 is ascending)
        $this->getARMdb()->roles->createIndex(['href' => 1]);
    }

}
