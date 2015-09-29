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
        $accounts = $this->getARMaccounts();
        $updatedattributes = [];
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]);
        }
        if(!isset($account['state'])) {
            $updatedattributes['state'] = [ array_merge($managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]) ];
        } else {
            $updatedattributes['state'] = UsfARMapi::getUpdatedStateArray($account['state'], $state, $managerattributes);
        }
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if ($status) {
            return $this->getAccountByTypeAndIdentifier($type, $identifier);
        } else {
            return new JSendResponse('error', UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']); 
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
        $accounts = $this->getARMaccounts();
        $updatedattributes = [];
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]);
        }
        if(!isset($account['roles'])) {
            return new JSendResponse('fail', [
                "role" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NO_ROLES_EXIST'] 
            ]);
        } else {
            $roles = $this->getARMroles();
            $role = $roles->findOne([ 'type' => $type, 'name' => $rolename ]); 
            if (is_null($role)) {
                return new JSendResponse('fail', [
                    "role" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS']
                ]);
            }   
            if(UsfARMapi::hasMatchingRole($account['roles'], $role['_id'])) {
                $updatedattributes['roles'] = \array_map(function($r) use($managerattributes,$state,$role) {  
                    if(isset($r['role_id'])?$r['role_id'] == $role['_id']:false) {
                        if(!isset($r['state'])) {
                            $r['state'] = [ \array_merge($managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]) ];
                        } else {
                            $r['state'] = UsfARMapi::getUpdatedStateArray($r['state'], $state, $managerattributes);
                        }
                    }
                    return $r;
                },$account['roles']);                
            } else {
                return new JSendResponse('fail', [
                    "role" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_ROLE_NOT_EXISTS'] 
                ]);
            }
        }
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if ($status) {
            return $this->getAccountByTypeAndIdentifier($type, $identifier);
        } else {
            return new JSendResponse('error', UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']);
        }        
    }
    /**
     * Checks to see if a state exists for the specified manager usfid
     * 
     * @param type $states
     * @param type $usfid
     * @return boolean
     */
    public static function hasStateForManager($states,$id) {
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
    public static function getStateForManager($states,$id) {
        $managerstate = \array_values(\array_filter($states, function($s) use(&$id) {
            return ($s['usfid'] == $id);
        }));
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
    public static function hasMatchingRole($roles,$id) {
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
    public static function hasReviewForManager($reviews,$id) {
        return !empty(\array_filter($reviews, function($rv) use($id) {
            return ($rv['usfid'] == $id);
        }));
    }
    /**
     * Returns the review string for the specified manager
     * 
     * @param type $reviews
     * @param type $id
     * @return string
     */
    public static function getReviewForManager($reviews,$id) {
        $managerreview = \array_values(\array_filter($reviews, function($r) use(&$id) {
            return ($r['usfid'] == $id);
        }));
        if(!empty($managerreview)) {
            return $managerreview[0]['review'];
        }
        return '';
    }
    /**
     * Returns an updated state array based on the new passed state and the manager attributes
     * 
     * @param type $states
     * @param type $newstate
     * @param type $managerattributes
     * @return type
     */
    public static function getUpdatedStateArray($states,$newstate,$managerattributes) {
        if(UsfARMapi::hasStateForManager($states, $managerattributes['usfid'])) {
            return \array_map(function($s) use($managerattributes,$newstate) {                    
                if($s['usfid'] == $managerattributes['usfid']) {                    
                    return \array_merge($s,$managerattributes,[ 'state' => $newstate, 'timestamp' => new \MongoDate() ]);
                } else {
                    return $s;                    
                }                    
            },$states);
        } else {
            return array_merge(
                $states,
                [ array_merge($managerattributes,[ 'state' => $newstate, 'timestamp' => new \MongoDate() ]) ]
            );
        }
    }
    /**
     * Returns an update review array based on the new passed review code and the manager attributes
     * 
     * @param type $reviews
     * @param type $reviewcode
     * @param type $managerattributes
     * @return type
     */
    public static function getUpdatedReviewArray($reviews,$reviewcode,$managerattributes) {
        if(UsfARMapi::hasReviewForManager($reviews, $managerattributes['usfid'])) {
            return \array_map(function($rv) use($managerattributes,$reviewcode) {
                if($rv['usfid'] == $managerattributes['usfid']) {
                    return \array_merge($rv,$managerattributes,[ 'review' => $reviewcode, 'timestamp' => new \MongoDate() ]);
                } else {
                    return $rv;
                }
            },((isset($reviews))?$reviews:[]));
        } else {
            return \call_user_func(function($r) use($reviewcode,$managerattributes) {
                $r[] = \array_merge($managerattributes,[ 'review' => $reviewcode, 'timestamp' => new \MongoDate() ]);
                return $r;
            },(isset($reviews))?$reviews:[]);
        }
    }
    /**
     * Returns the last confirm object with max timestamp for manager usfid
     * 
     * @param type $confirms
     * @param type $id
     * @return type
     */
    public static function getLastConfirm($confirms,$id) {
        $matchedconfirms = \array_values(\array_filter($confirms, function($c) use ($id) { return $c['usfid'] === $id; }));
        \usort($matchedconfirms, function($a,$b) {
            if (strtotime($a['timestamp']) == strtotime($b['timestamp'])) {
                return 0;
            }
            return (strtotime($a['timestamp']) > strtotime($b['timestamp'])) ? -1 : 1;
        });
        return (!empty($matchedconfirms))?$matchedconfirms[0]:[];
    }
    /**
     * Updates account to the review state
     * 
     * @param type $identifier
     * @param type $managerattributes
     * @return JSendResponse
     */
    public function setReviewByAccount($identifier,$managerattributes=[]) {
        $accounts = $this->getARMaccounts();
        $account = $accounts->findOne([ "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', [
                "account" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]);
        }
        $updatedattributes = [];
        if(!isset($account['review'])) {
            $account['review'] = [];
        }
        $updatedattributes['review'] = UsfARMapi::getUpdatedReviewArray($account['review'], 'open', $managerattributes);
        // Update the account with review changes and move on to the state changes
        $status = $accounts->update([ "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if (!$status) {
            return new JSendResponse('error', UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']);
        } else {
            // Set the empty state for the account by the manager
            $stateresp = $this->setAccountState($account['type'], $identifier, '', $managerattributes);
            if(!$stateresp->isSuccess()) {
                return $stateresp;
            }
            if(!isset($account['roles'])) {
                $account['roles'] = [];
            }
            $roles = $this->getARMroles();
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
        $accounts = $this->getARMaccounts();
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
        $accounts = $this->getARMaccounts();
        $reviewaccounts = $accounts->distinct("identity",[ "identity" => [ '$exists' => true ] ]);
        if(empty($reviewaccounts)) {
            return new JSendResponse('fail', [
                "identity" => UsfARMapi::$ARM_ERROR_MESSAGES['IDENTITIES_NONE_FOUND']
            ]);
        }
        $resp = [
            'usfids' => $reviewaccounts,
            'reviewCount' => 0
        ];
        foreach ($resp['usfids'] as $usfid) {
            $visor = $func($usfid);
            if($visor['status'] == 'success' && (isset($visor['data']['directory_info']))?!empty($visor['data']['directory_info']):false) {
                if((isset($visor['data']['directory_info']['supervisors']))?!empty($visor['data']['directory_info']['supervisors']):false) {
                    foreach($visor['data']['directory_info']['supervisors'] as $s) {
                        if($this->setReviewByIdentity($usfid, [ 'name' => $s['name'], 'usfid' => $s['usf_id'] ])->isSuccess()) {
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
        $accounts = $this->getARMaccounts();
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
        if(UsfARMapi::hasStateForManager($account['state'], $managerattributes['usfid'])) {
            $updatedattributes['confirm'] = (isset($account['confirm']))?$account['confirm']:[];
            $updatedattributes['confirm'][] = \array_merge($managerattributes,[ 
                'state' => UsfARMapi::getStateForManager($account['state'], $managerattributes['usfid']), 
                'timestamp' => new \MongoDate() 
            ]);
            if(UsfARMapi::hasReviewForManager($account['review'], $managerattributes['usfid'])) {
                // Set the account review closed
                $updatedattributes['review'] = UsfARMapi::getUpdatedReviewArray($account['review'], 'closed', $managerattributes);
                if(!isset($account['roles'])) {
                    $account['roles'] = [];
                }
                // Iterate the role confirms and set those reviews closed as well 
                $updatedattributes['roles'] = \array_map(function($r) use($managerattributes) {
                    if((isset($r['dynamic_role']))?$r['dynamic_role']:false) {
                        return $r;
                    }
                    // See if a state exists for this manager and create a confirm
                    if(UsfARMapi::hasStateForManager(((!isset($r['state']))?$r['state']:[]), $managerattributes['usfid'])) {
                        if(!isset($r['confirm'])) {
                            $r['confirm'] = [];
                        }
                        $r['confirm'][] = \array_merge($managerattributes,[ 
                            'state' => UsfARMapi::getStateForManager($r['state'],$managerattributes['usfid']), 
                            'timestamp' => new \MongoDate() ]
                        );
                    }
                    return $r;
                },$account['roles']); 
                // Update the account
                $status = $accounts->update([ "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
                if (!$status) {
                    return new JSendResponse('error', UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']);
                } else {
                    return $this->getAccountByTypeAndIdentifier($account['type'],$identifier);
                }
            } else {
                return new JSendResponse('fail', [
                    "account" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_REVIEW_UNSET_BY_MANAGER']
                ]);
            }
        } else {
            return new JSendResponse('fail', [
                "account" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_STATE_UNSET_BY_MANAGER']
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
        $accounts = $this->getARMaccounts();
        $identifiers = $accounts->distinct("identifier",[ "identity" => $identity ]);
        if(empty($identifiers)) {
            return new JSendResponse('fail', [
                "identity" => UsfARMapi::$ARM_ERROR_MESSAGES['IDENTITY_NO_ACCOUNTS_EXIST']
            ]);
        }
        foreach ($identifiers as $identifier) {
            $resp = $this->setConfirmByAccount($identifier, $managerattributes);
            if(!$resp->isSuccess()) {
                return $resp;
            }
        }
        return $this->getAccountsForIdentity($identity);
    }
    
    
}
