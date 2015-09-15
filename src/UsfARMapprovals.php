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
                $_hasStateForManager =& self::hasStateForManager;
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
    private function hasStateForManager($states,$usfid) {
        return !empty(\array_filter($states, function($s) use(&$usfid) {
            return ($s['usfid'] == $usfid);
        }));
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
}
