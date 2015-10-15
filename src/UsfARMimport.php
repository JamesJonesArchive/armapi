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
    /**
     * Returns the arm database 
     * 
     * @return \MongoDB
     */
    public function getARMlogdb() {
        return parent::getMongoConnection()->armLog;
    }
    /**
     * Takes SOR account in JSON format and imports it into ARM accounts
     * 
     * @param array $account
     * @return JSendResponse
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
     * @param array $accountroles
     * @return JSendResponse
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
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_INFO_MISSING']
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
        $currentrole = $this->getRoleByTypeAndName($role['account_type'],$role['name']);
        if($currentrole->isSuccess()) {
            // Update existing role
            return $this->modifyRoleByTypeAndName($role['account_type'],$role['name'],$role);
        } else {
            // Create new role
            return $this->createRoleByType($role);
        }
    }
    /**
     * Finds all existing accounts and primes a compares collection for later processing
     */
    public function buildAccountComparison() {
        $compares = $this->getARMtracking();
        $compares->drop();
        foreach ($this->getAllAccounts()->getData() as $type => $accounts) {
            $compares->batchInsert($accounts);
        }
    }
    /**
     * Finds all existing roles and primes a compares collection for later processing
     */
    public function buildRoleComparison() {
        $compares = $this->getARMtracking();
        $compares->drop();
        foreach ($this->getAllRoles()->getData() as $type => $roles) {
            $compares->batchInsert($roles);
        }
    }
    /**
     * Returns the compares mongo collection (tracking changes)
     * 
     * @return \MongoCollection
     */
    public function getARMtracking() {
        return $this->getARMlogdb()->tracking;
    }
    /**
     * Returns the logs mongo collection (tracking changes)
     * 
     * @return \MongoCollection
     */
    public function getARMlogs() {
        return $this->getARMlogdb()->logs;
    }


}
