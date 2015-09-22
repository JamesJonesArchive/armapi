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
