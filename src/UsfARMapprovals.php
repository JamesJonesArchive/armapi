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
     * @param string $type
     * @param string $identifier
     * @param string $state
     * @param array $managerattributes
     * @return JSendResponse
     */
    public function setAccountState($type, $identifier, $state, $managerattributes=[]) {
        $accounts = $this->getARMaccounts();
        $updatedattributes = [];
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }
        if($account['status'] === "Locked") {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_LOCKED']
            ]));
        }
        if(!isset($account['state'])) {
            $updatedattributes['state'] = [ array_merge($managerattributes,[ 'state' => $state, 'timestamp' => new \MongoDate() ]) ];
        } else {
            $updatedattributes['state'] = UsfARMapi::getUpdatedStateArray($account['state'], $state, $managerattributes);
        }
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if ($status) {
            if(!isset($account['roles'])) {
                $account['roles'] = [];
            }
            $roles = $this->getARMroles();
            foreach (\array_filter($account['roles'], function($r) { return !((isset($r['dynamic_role']))?$r['dynamic_role']:false); }) as $role) {
                $rolestateresp = $this->setAccountRoleState($type, $identifier, $roles->findOne([ "_id" => $role['role_id'] ])['href'], $state, $managerattributes);
                if(!$rolestateresp->isSuccess()) {
                    return $rolestateresp;
                }
            }
            return $this->getAccountByTypeAndIdentifier($type, $identifier);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']
            ])); 
        }
    }
    /**
     * Sets the role state on an account
     * 
     * @param string $type
     * @param string $identifier
     * @param string $rolename
     * @param string $state
     * @param array $managerattributes
     * @return JSendResponse
     */
    public function setAccountRoleState($type, $identifier, $href, $state, $managerattributes=[]) {
        $accounts = $this->getARMaccounts();
        $updatedattributes = [];
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }
        if($account['status'] === "Locked") {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_LOCKED']
            ]));
        } 
        if(!isset($account['roles'])) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NO_ROLES_EXIST'] 
            ]));
        } else {
            $roles = $this->getARMroles();
            $role = $roles->findOne(["href" => $href]);
            if (is_null($role)) {
                return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                    "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS']
                ]));
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
                return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                    "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_ROLE_NOT_EXISTS'] 
                ]));
            }
        }
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if ($status) {
            return $this->getAccountByTypeAndIdentifier($type, $identifier);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']
            ]));
        }        
    }
    /**
     * Checks to see if a state exists for the specified manager usfid
     * 
     * @param array $states
     * @param string $id
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
     * @param array $states
     * @param string $id
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
     * @param array $roles
     * @param string $id
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
     * @param array $reviews
     * @param string $id
     * @return boolean
     */
    public static function hasReviewForManager($reviews,$id) {
        return !empty(\array_filter($reviews, function($rv) use($id) {
            return ($rv['usfid'] == $id);
        }));
    }
    /**
     * Detects if list of roles has any unapproved states
     * 
     * @param array $roles
     * @param array $managerattributes
     * @return boolean Description
     */
    public static function hasUnapprovedRoleState($roles,$managerattributes=[]) {
        return !empty(\array_filter($roles, function($r) use($managerattributes) { 
            if((isset($r['dynamic_role']))?$r['dynamic_role']:false) {
                return false;
            }
            if(!UsfARMapi::hasStateForManager((isset($r['state']))?$r['state']:[], $managerattributes['usfid'])) {
                return true;
            }
            if(!preg_match('/\S/', UsfARMapi::getStateForManager((isset($r['state']))?$r['state']:[], $managerattributes['usfid']))) {
                return true;
            }
//            if(UsfARMapi::getStateForManager((isset($r['state']))?$r['state']:[], $managerattributes['usfid']) === '') {
//                return true;
//            }
            return false;
        }));
    }    
    /**
     * Returns the review string for the specified manager
     * 
     * @param array $reviews
     * @param string $id
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
     * @param array $states
     * @param string $newstate
     * @param array $managerattributes
     * @return array
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
     * @param array $reviews
     * @param string $reviewcode
     * @param array $managerattributes
     * @return array
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
     * @param array $confirms
     * @param string $id
     * @return array
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
     * Runs the external visor service for internal processing
     * 
     * @param string $id
     * @return JSendResponse
     */
    public function getVisor($id) {
        $usfVisorAPI = new \USF\IdM\USFVisorAPI((new \USF\IdM\UsfConfig())->visorConfig);
        return $usfVisorAPI->getVisor($id);
    }
    /**
     * Updates account to the review state
     * 
     * @param string $type
     * @param string $identifier
     * @param array $managerattributes
     * @return JSendResponse
     */
    public function setReviewByAccount($type,$identifier) {
        $accounts = $this->getARMaccounts();
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }
        if($account['status'] === "Locked") {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_LOCKED']
            ]));
        }            
        $supervisors = $this->getVisor($account['identity'])->getData()['directory_info']['self']['supervisors'];
        if(empty($supervisors)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_REVIEW_NO_SUPERVISORS']
            ]));
        }        
        if(!isset($account['review'])) {
            $account['review'] = [];
        }
        $updatedattributes = [ 'review' => $account['review'] ];
        $managersattributes = \array_map(function($supervisor) {
            return [
                'name' => $supervisor['name'],
                'usfid' => $supervisor['usf_id']
            ];
        }, $supervisors);
        foreach ($managersattributes as $managerattributes) {
            $updatedattributes['review'] = UsfARMapi::getUpdatedReviewArray($updatedattributes['review'], 'open', $managerattributes);
        }
        // Update the account with review changes and move on to the state changes
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if (!$status) {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']
            ]));
        } else {
            foreach ($managersattributes as $managerattributes) {
                // Set the empty state for the account by the manager
                $stateresp = $this->setAccountState($type, $identifier, '', $managerattributes);
                if(!$stateresp->isSuccess()) {
                    return $stateresp;
                }
            }
            if(!isset($account['roles'])) {
                $account['roles'] = [];
            }
            $roles = $this->getARMroles();
            foreach (\array_filter($account['roles'], function($r) { return !((isset($r['dynamic_role']))?$r['dynamic_role']:false); }) as $role) {
                foreach ($managersattributes as $managerattributes) {
                    $rolestateresp = $this->setAccountRoleState($type, $identifier, $roles->findOne([ "_id" => $role['role_id'] ])['href'], '', $managerattributes);
                    if(!$rolestateresp->isSuccess()) {
                        return $rolestateresp;
                    }
                }
            }
            return $this->getAccountByTypeAndIdentifier($type,$identifier);
        }
    }
    /**
     * Updates accounts for an identity to the review state
     * 
     * @param string $identity
     * @param array $managerattributes
     * @return JSendResponse
     */
    public function setReviewByIdentity($identity) {
        $accounts = $this->getARMaccounts();
        $reviewaccounts = $accounts->find([ "identity" => $identity, "status" => [ '$ne' => "Locked" ] ],[ "identifier" => true,'type' => true ]);
        foreach ($reviewaccounts as $account) {
            $resp = $this->setReviewByAccount($account['type'],$account['identifier']);
            if(!$resp->isSuccess()) {
                return $resp;
            }
        }
        return $this->getAccountsForIdentity($identity);
    }
    /**
     * Sets the review for ALL accounts
     * 
     * @param closure $func Anonymous function for Visor to run in to gather the managers
     * @return JSendResponse
     */
    public function setReviewAll($func) {
        $accounts = $this->getARMaccounts();
        $reviewaccounts = $accounts->distinct("identity",[ "identity" => [ '$exists' => true ], "status" => [ '$ne' => 'Locked' ] ]);
        if(empty($reviewaccounts)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['IDENTITIES_NONE_FOUND']
            ]));
        }
        $resp = [
            'usfids' => $reviewaccounts,
            'reviewCount' => 0
        ];
        foreach ($resp['usfids'] as $usfid) {
            $visor = $func($usfid);
            if($visor['status'] == 'success' && (isset($visor['data']['directory_info']))?!empty($visor['data']['directory_info']):false) {
                if((isset($visor['data']['directory_info']['self']['supervisors']))?!empty($visor['data']['directory_info']['self']['supervisors']):false) {
                    foreach($visor['data']['directory_info']['self']['supervisors'] as $s) {
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
     * Updates account role on account to the confirmed state
     * 
     * @param string $type
     * @param string $identifier
     * @param string $href
     * @param array $managerattributes
     * @return JSendResponse
     */
    public function setConfirmByAccountRole($type,$identifier,$href,$managerattributes=[]) {
        $accounts = $this->getARMaccounts();
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }
        if($account['status'] === "Locked") {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_LOCKED']
            ]));
        } 
        $updatedattributes = [];
        if(!isset($account['roles'])) {
            $updatedattributes['roles'] = [];
        } else {
            $updatedattributes['roles'] = $account['roles'];
        }
        $roles = $this->getARMroles();        
        try {
            $updatedattributes['roles'] = \array_map(function($r) use($managerattributes,$roles,$href) {
                if((isset($r['dynamic_role']))?$r['dynamic_role']:false) {
                    return $r;
                }
                $accountRole = $roles->findOne([ "_id" => $r['role_id'] ]);
                if (!is_null($accountRole)) {
                    $confirmRole = $roles->findOne(["href" => $href]);
                    if(isset($accountRole['href']) && isset($confirmRole['href'])) {
                        if($accountRole['href'] == $confirmRole['href']) {
                            // See if a state exists for this manager and create a confirm
                            if(UsfARMapi::hasStateForManager(((isset($r['state']))?$r['state']:[]), $managerattributes['usfid'])) {
                                if(!isset($r['confirm'])) {
                                    $r['confirm'] = [];
                                }
                                $r['confirm'][] = \array_merge($managerattributes,[ 
                                    'state' => UsfARMapi::getStateForManager($r['state'],$managerattributes['usfid']), 
                                    'timestamp' => new \MongoDate() ]
                                );
                            } else {
                                throw new \Exception(UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_ROLE_STATE_UNSET_BY_MANAGER']);
                            }
                        }
                    }
                }
                return $r;
            }, $updatedattributes['roles']);
        } catch (\Exception $e) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => $e->getMessage()
            ]));
        }
        // Update the account
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if (!$status) {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']
            ]));
        } else {
            return $this->getAccountByTypeAndIdentifier($type,$identifier);
        }
    }
    /**
     * Updates account to the confirmed state
     * 
     * @param string $type
     * @param string $identifier
     * @param array $managerattributes
     * @return JSendResponse
     */
    public function setConfirmByAccount($type,$identifier,$managerattributes=[]) {
        $accounts = $this->getARMaccounts();
        $account = $accounts->findOne([ "type" => $type, "identifier" => $identifier ]);
        if (is_null($account)) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_NOT_EXISTS']
            ]));
        }
        if($account['status'] === "Locked") {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_LOCKED']
            ]));
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
                if(UsfARMapi::hasUnapprovedRoleState($account['roles'], $managerattributes)) {
                    return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                        "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_HAS_UNAPPROVED_ROLE_STATES']
                    ]));
                }
                // Update the account
                $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
                if (!$status) {
                    return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                        "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']
                    ]));
                } else {  
                    $roles = $this->getARMroles(); 
                    try {
                        foreach(\array_filter($account['roles'], function($r) { return (isset($r['dynamic_role']))?!$r['dynamic_role']:true; }) as $r) {
                            $accountRole = $roles->findOne([ "_id" => $r['role_id'] ]);
                            if (!is_null($accountRole)) {
                                $resp = $this->setConfirmByAccountRole($type,$identifier, $accountRole['href'],$managerattributes);
                                if(!$resp->isSuccess()) {
                                    return $resp;
                                }
                                $rolestateresp = $this->setAccountRoleState($type, $identifier, $accountRole['href'], '', $managerattributes);
                                if(!$rolestateresp->isSuccess()) {
                                    return $rolestateresp;
                                }
                            } else {
                                throw new \Exception(UsfARMapi::$ARM_ERROR_MESSAGES['ROLE_NOT_EXISTS']);                                
                            }
                        }                        
                    } catch (\Exception $e) {
                        return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                            "description" => $e->getMessage()
                        ]));
                    }                    
                    return $this->getAccountByTypeAndIdentifier($type,$identifier);
                }
            } else {
                return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                    "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_REVIEW_UNSET_BY_MANAGER']
                ]));
            }
        } else {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_STATE_UNSET_BY_MANAGER']
            ]));
        }
    }
    /**
     * Updates accounts for an identity to the confirmed state
     * 
     * @param string $identity
     * @param array $managerattributes
     * @return JSendResponse
     */
    public function setConfirm($identity,$managerattributes=[]) {
        $accounts = $this->getARMaccounts();
        $identifiers = $accounts->aggregate([
            [ '$match' => [ 'identity' => $identity, 'status' => [ '$ne' => 'Locked' ] ] ],
            [ '$group' => [ '_id' => [ 'identifier' => '$identifier', 'type' => '$type' ] ] ]
        ]);
        if(empty($identifiers['result'])) {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['IDENTITY_NO_ACCOUNTS_EXIST']
            ]));
        }
        foreach ($identifiers['result'] as $pair) {
            $resp = $this->setConfirmByAccount($pair['_id']['type'],$pair['_id']['identifier'], $managerattributes);
            if(!$resp->isSuccess()) {
                return $resp;
            }
        }
        return $this->getAccountsForIdentity($identity);
    }
    
    
}
