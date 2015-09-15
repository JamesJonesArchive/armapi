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
/**
 * Description of UsfARMapprovals
 *
 * @author james
 */
trait UsfARMapprovals {
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
            if($this->hasStateForManager($account['state'], $managerattributes['usfid'])) {
                $updatedattributes['state'] = \array_map(function($s) use($managerattributes,$state) {                    
                    if($s['usfid'] == $managerattributes['usfid']) {
                        return \array_merge($s,$managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]);
                    } else {
                        return $s;                    
                    }                    
                },$account['state']);                
            } else {
                $updatedattributes['state'] = array_merge(
                    $account['state'],
                    [ array_merge($managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]) ]
                );
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
            if($this->hasMatchingRole($account['roles'], $role['_id'])) {
                $_hasStateForManager =& $this->hasStateForManager;
                $updatedattributes['roles'] = \array_map(function($r) use($managerattributes,$state,$role,&$_hasStateForManager) {  
                    if(isset($r['role_id'])?$r['role_id'] == $role['_id']:false) {
                        if(!isset($r['state'])) {
                            $r['state'] = [ \array_merge($managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]) ];
                        } else {
                            if($_hasStateForManager($r['state'], $managerattributes['usfid'])) {
                                $r['state'] = \array_map(function($s) use($managerattributes,$state) {
                                    if($s['usfid'] == $managerattributes['usfid']) {
                                        return \array_merge($s,$managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]);
                                    } else {
                                        return $s;                                        
                                    }
                                },$r['state']);
                            } else {
                                $r['state'][] = \array_merge($s,$managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]);
                            }
                        }
                    }
                    return $r;
                },$account['roles']);                
            } else {
                return new JSendResponse('fail', [
                    "role" => "Role does not exist for account specified!"
                ]);
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
     * Checks to see if a state exists for the specified manager usfid
     * 
     * @param type $states
     * @param type $usfid
     * @return boolean
     */
    private function hasStateForManager($states,$id) {
        return !empty(\array_filter($states, function($s) use(&$id) {
            return ($s['usfid'] == $id);
        }));
    }
    /**
     * Returns the state string for the specified manager
     * 
     * @param type $states
     * @param type $id
     * @return string
     */
    private function getStateForManager($states,$id) {
        $managerstate = \array_filter($states, function($s) use(&$id) {
            return ($s['usfid'] == $id);
        });
        if(!empty($managerstate)) {
            return $managerstate[0]['state'];
        }
        return '';
    }
    /**
     * Checks to see if there is a matching
     * 
     * @param type $roles
     * @param type $id
     * @return boolean
     */
    private function hasMatchingRole($roles,$id) {
        return !empty(\array_filter($roles, function($r) use($id) {
            return (isset($r['role_id'])?$r['role_id'] == $id:false);
        }));
    }
    /**
     * Checks to see if a review exists for the specified manager usfid
     * 
     * @param type $reviews
     * @param type $id
     * @return boolean
     */
    private function hasReviewForManager($reviews,$id) {
        return !empty(\array_filter($reviews, function($rv) use($id) {
            return ($rv['usfid'] == $id);
        }));
    }
    /**
     * Updates account to the review state
     * 
     * @param type $identifier
     * @param type $managerattributes
     * @return JSendResponse
     */
    public function setReviewByAccount($identifier,$managerattributes=[]) {
        $accounts = $this->getARMdb()->accounts;
        $account = $accounts->findOne([ "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account not found!"
            ]);
        }
        $updatedattributes = [];
        if(!isset($account['review'])) {
            $account['review'] = [];
        }
        if($this->hasReviewForManager($account['review'], $managerattributes['usfid'])) {
            $updatedattributes['review'] = \array_map(function($rv) use($managerattributes) {
                if($rv['usfid'] == $managerattributes['usfid']) {
                    return \array_merge($rv,$managerattributes,[ 'review' => 'open', 'timestamp' => new \MongoDate() ]);
                } else {
                    return $rv;
                }
            },((isset($account['review']))?$account['review']:[]));
        } else {
            $updatedattributes['review'] = (isset($account['review']))?$account['review']:[];
            $updatedattributes['review'][] = \array_merge($managerattributes,[ 'review' => 'open', 'timestamp' => new \MongoDate() ]);
        }
        // Update the account with review changes and move on to the state changes
        $status = $accounts->update([ "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if (!$status) {
            return new JSendResponse('error', "Update failed!");
        } else {
            // Set the empty state for the account by the manager
            $stateresp = $this->setAccountState($account['type'], $identifier, '', $managerattributes);
            if(!$stateresp->isSuccess()) {
                return $stateresp;
            }
            if(!isset($account['roles'])) {
                $account['roles'] = [];
            }
            $roles = $this->getARMdb()->roles;
            foreach (\array_filter($account['roles'], function($r) { return !((isset($r['dynamic_role']))?$r['dynamic_role']:false); }) as $role) {
                $rolestateresp = $this->setAccountRoleState($account['type'], $identifier, $roles->findOne([ "_id" => $role['role_id'] ])['name'], '', $managerattributes);
                if(!$rolestateresp->isSuccess()) {
                    return $rolestateresp;
                }
            }
            return $this->getAccountByTypeAndIdentifier($account['type'],$identifier);
        }
    }
    /**
     * Updates accounts for an identity to the review state
     * 
     * @param type $identity
     * @param type $managerattributes
     * @return type
     */
    public function setReviewByIdentity($identity,$managerattributes=[]) {
        $accounts = $this->getARMdb()->accounts;
        $reviewaccounts = $accounts->find([ "identity" => $identity ],[ "identifier" => true ]);
        foreach ($reviewaccounts as $account) {
            $resp = $this->setReviewByAccount($account['identifier'], $managerattributes);
            if(!$resp->isSuccess()) {
                return $resp;
            }
        }
        return $this->getAccountsForIdentity($identity);
    }
    /**
     * Sets the review for ALL accounts
     * 
     * @param type $func Anonymous function for Visor to run in to gather the managers
     */
    public function setReviewAll($func) {
        $accounts = $this->getARMdb()->accounts;
        $reviewaccounts = $accounts->find([ "identity" => [ '$exists' => true ] ],[ 'identity' => true, '_id' => false ]);
        if(empty($reviewaccounts)) {
            return new JSendResponse('fail', [
                "identity" => "No accounts available for review!"
            ]);
        }
        $resp = [
            'usfids' => \array_unique(\array_map(function($a) { return $a['identity']; },$reviewaccounts)),
            'reviewCount' => 0
        ];
        foreach ($resp['usfids'] as $usfid) {
            $visor = $func($usfid);
            if($visor['status'] === 'success' && (isset($svisor['data']['directory_info']))?!empty($svisor['data']['directory_info']):false) {
                if((isset($visor['data']['directory_info']['supervisors']))?!empty($visor['data']['directory_info']['supervisors']):false) {
                    foreach($visor['data']['directory_info']['supervisors'] as $s) {
                        if($this->setReviewByIdentity($usfid, [ 'name' => $s['name'], 'usfid' => $s['usf_id'] ])['status'] === 'success') {
                            $resp['reviewCount']++;
                        }
                    }
                }
            }
        }
        return new JSendResponse('success', $resp);
    }
    /**
     * Updates account to the confirmed state
     * 
     * @param type $identifier
     * @param type $managerattributes
     * @return JSendResponse
     */
    public function setConfirmByAccount($identifier,$managerattributes=[]) {
        $accounts = $this->getARMdb()->accounts;
        $account = $accounts->findOne([ "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => "Account not found!"
            ]);
        }
        $updatedattributes = [];
        if(!isset($account['confirm'])) {
            $account['confirm'] = [];
        }
        if(!isset($account['state'])) {
            $account['state'] = [];
        }
        if(!isset($account['review'])) {
            $account['review'] = [];
        }
        if($this->hasStateForManager($account['state'], $managerattributes['usfid'])) {
            $updatedattributes['confirm'] = (isset($account['confirm']))?$account['confirm']:[];
            $updatedattributes['confirm'][] = \array_merge($managerattributes,[ 
                'state' => $this->getStateForManager($account['state'], $managerattributes['usfid']), 
                'timestamp' => new \MongoDate() 
            ]);
            if($this->hasReviewForManager($account['review'], $managerattributes['usfid'])) {
                // Set the account review closed
                $updatedattributes['review'] = \array_map(function($r) use($managerattributes) {
                    if($r['usfid'] == $managerattributes['usfid']) {
                        return \array_merge($r,$managerattributes,[ 'review' => 'closed', 'timestamp' => new \MongoDate() ]);
                    } else {
                        return $r;
                    }
                },$account['review']);
                if(!isset($account['roles'])) {
                    $account['roles'] = [];
                }
                $_getStateForManager =& $this->getStateForManager($states, $id); // RETURN HERE
                $_hasStateForManager =& $this->hasStateForManager;
                // Iterate the role confirms and set those reviews closed as well 
                $updatedattributes['roles'] = \array_map(function($r) use($managerattributes,&$_hasStateForManager,&$_getStateForManager) {
                    if((isset($r['dynamic_role']))?$r['dynamic_role']:false) {
                        return $r;
                    }
                    // See if a state exists for this manager and create a confirm
                    if($_hasStateForManager(((!isset($r['state']))?$r['state']:[]), $managerattributes['usfid'])) {
                        if(!isset($r['confirm'])) {
                            $r['confirm'] = [];
                        }
                        $r['confirm'][] = \array_merge($managerattributes,[ 
                            'state' => $_getStateForManager($r['state'],$managerattributes['usfid']), 
                            'timestamp' => new \MongoDate() ]
                        );
                    }
                    return $r;
                },account['roles']); 
                // Update the account
                $status = $accounts->update([ "identity" => $identity ], [ '$set' => $updatedattributes ]);
                if (!$status) {
                    return new JSendResponse('error', "Update failed!");
                } else {
                    return $this->getAccountByTypeAndIdentifier($account['type'],$identifier);
                }
            } else {
                return new JSendResponse('fail', [
                    "account" => "Account does not have a review set by current manager!"
                ]);
            }
        } else {
            return new JSendResponse('fail', [
                "account" => "Account does not have a state set by current manager!"
            ]);
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
            $resp = $this->setConfirmByAccount($account['identifier'], $managerattributes);
            if(!$resp->isSuccess()) {
                return $resp;
            }
        }
        return $this->getAccountsForIdentity($identity);
    }
    
    
}
