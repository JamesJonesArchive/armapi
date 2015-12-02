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
            $this->auditLog([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
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
            ]),"Internal Server Error",500); 
        }
    }
    /**
     * Sets the role state on an account
     * 
     * @param string $type
     * @param string $identifier
     * @param string $href
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
            $this->auditLog([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
            return $this->getAccountByTypeAndIdentifier($type, $identifier);
        } else {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']
            ]),"Internal Server Error",500);
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
            if((isset($r['status']))?($r['status'] == "Removed"):false) {
                return false;
            }
            if(!UsfARMapi::hasStateForManager((isset($r['state']))?$r['state']:[], $managerattributes['usfid'])) {
                return true;
            }
            if(!preg_match('/\S/', UsfARMapi::getStateForManager((isset($r['state']))?$r['state']:[], $managerattributes['usfid']))) {
                return true;
            }
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
     * @param int $days 
     * @return array
     */
    public static function getUpdatedReviewArray($reviews,$reviewcode,$managerattributes,$days = -1) {
        if(UsfARMapi::hasReviewForManager($reviews, $managerattributes['usfid'])) {
            return \array_map(function($rv) use($managerattributes,$reviewcode,$days) {
                if($rv['usfid'] == $managerattributes['usfid']) {
                    if($days < 0) {
                        return \array_merge($rv,$managerattributes,[ 'review' => $reviewcode, 'timestamp' => new \MongoDate() ]);                        
                    } else {
                        return \array_merge($rv,$managerattributes,[ 'review' => $reviewcode, 'timestamp' => new \MongoDate(), 'reviewend' => new \MongoDate(\strtotime("+{$days} day")) ]);                        
                    }
                } else {
                    return $rv;
                }
            },((isset($reviews))?$reviews:[]));
        } else {
            return \call_user_func(function($r) use($reviewcode,$managerattributes,$days) {
                if($days < 0) {
                    $r[] = \array_merge($managerattributes,[ 'review' => $reviewcode, 'timestamp' => new \MongoDate() ]);
                } else {
                    print_r($days);
                    $r[] = \array_merge($managerattributes,[ 'review' => $reviewcode, 'timestamp' => new \MongoDate(), 'reviewend' => new \MongoDate(\strtotime("+{$days} day")) ]);
                }
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
    public function getVisor($id,$proxyemplid = '') {        
        if(\in_array($this->auditInfo['armuser']['role'], ['Admin','Batch']) && empty($proxyemplid)) {
            $usfVisorAPI = new \USF\IdM\USFVisorAPI((new \USF\IdM\UsfConfig())->visorConfig);
        } elseif(empty($proxyemplid)) {
            $usfVisorAPI = new \USF\IdM\USFVisorAPI((new \USF\IdM\UsfConfig())->visorConfig,$this->auditInfo['armuser']['emplid']);
        } else {
            $usfVisorAPI = new \USF\IdM\USFVisorAPI((new \USF\IdM\UsfConfig())->visorConfig,$proxyemplid);
        }
        return $usfVisorAPI->getVisor($id);
    }
    /**
     * Updates account to the review state
     * 
     * @param string $type
     * @param string $identifier
     * @param int $days
     * @return JSendResponse
     */
    public function setReviewByAccount($type,$identifier,$days = -1) {
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
        $visor = $this->getVisor($account['identity']);
        if(!$visor->isSuccess()) {
            return $visor;
        }        
        $supervisors = $visor->getData()['directory_info']['supervisors'];
        
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
        $adminattributes = [
            'admin_role' => $this->auditInfo['armuser']['role'],
            'admin_name' => $this->auditInfo['armuser']['name'],
            'admin_usfid' => $this->auditInfo['armuser']['usf_id']
        ];
        foreach ($managersattributes as $managerattributes) {
            $updatedattributes['review'] = UsfARMapi::getUpdatedReviewArray($updatedattributes['review'], 'open', \array_merge($managerattributes, $adminattributes),$days);
        }
        // Update the account with review changes and move on to the state changes
        $status = $accounts->update([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
        if (!$status) {
            return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']
            ]),"Internal Server Error",500);
        } else {
            $this->auditLog([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);            
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
            foreach (\array_filter($account['roles'], function($r) { return (!((isset($r['dynamic_role']))?$r['dynamic_role']:false && !((isset($r['status']))?($r['status'] == "Removed"):false))); }) as $role) {
                foreach ($managersattributes as $managerattributes) {
                    $rolestateresp = $this->setAccountRoleState($type, $identifier, $roles->findOne([ "_id" => $role['role_id'] ])['href'], '', $managerattributes);
                    if(!$rolestateresp->isSuccess()) {
                        return $rolestateresp;
                    }
                }
            }
            $updatedaccount = $this->getAccountByTypeAndIdentifier($type,$identifier);
            if($updatedaccount->isSuccess()) {
                // Send email notifications
                foreach ($supervisors as $supervisor) {
                    if(isset($supervisor['email'])) {
                        // $this->sendReviewNotification($supervisor, $updatedaccount->getData(),$visor->getData()['directory_info']['self']);
                    }
                }
            }
            return $updatedaccount;
        }
    }
    /**
     * Updates accounts for an identity to the review state
     * 
     * @param string $identity
     * @param int $days
     * @return JSendResponse
     */
    public function setReviewByIdentity($identity,$days = -1) {
        $accounts = $this->getARMaccounts();
        $reviewaccounts = $accounts->find([ "identity" => $identity, "status" => [ '$ne' => "Locked" ] ],[ "identifier" => true,'type' => true ]);
        foreach ($reviewaccounts as $account) {
            $resp = $this->setReviewByAccount($account['type'],$account['identifier'],$days);
            if(!$resp->isSuccess()) {
                return $resp;
            }
        }
        return $this->getAccountsForIdentity($identity);
    }
    /**
     * Updates accounts for an identity of a specified type to the review state
     * 
     * @param string $type
     * @param string $identity
     * @param int $days
     * @return JSendResponse
     */
    public function setReviewByTypeAndIdentity($type,$identity,$days = -1) {
        $accounts = $this->getARMaccounts();
        $reviewaccounts = $accounts->find([ "identity" => $identity, "type" => $type, "status" => [ '$ne' => "Locked" ] ],[ "identifier" => true,'type' => true ]);
        foreach ($reviewaccounts as $account) {
            $resp = $this->setReviewByAccount($account['type'],$account['identifier'],$days);
            if(!$resp->isSuccess()) {
                return $resp;
            }
        }
        return $this->getAccountsByTypeAndIdentity($type,$identity);
    }
    /**
     * Sets the review for ALL accounts
     * 
     * @param int $days 
     * @return JSendResponse
     */
    public function setReviewAll($days = -1) {
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
            $response = $this->setReviewByIdentity($usfid,$days);
            if(!$response->isSuccess()) {
                return $response;
            }  
            $resp['reviewCount']++;
        }
        return new JSendResponse('success', $resp);
    }
    /**
     * Sends a notification to a supervisor that the account is under review
     * 
     * @param array $supervisor
     * @param array $account
     * @param array $userinfo
     */
    public function sendReviewNotification($supervisor,$account,$userinfo) {
        //Create a new PHPMailer instance
        $mail = new \PHPMailer;
        if(!empty($this->smtpServer)) {
            $mail->isSMTP();  // Set mailer to use SMTP
            $mail->Host = $this->smtpServer;
        }
        $mail->setFrom('noreply@arm.us', 'ARM Automated Service');
        $mail->addAddress($supervisor['email'], $supervisor['name']); 
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = 'ARM Review Pending Notification for Employee: ' . $userinfo['name'];
        
        $smarty = new \Smarty();
        $smarty->template_dir = __DIR__ . "/../templates"; 
        
        $smarty->assign('supervisor', $supervisor);
        $smarty->assign('userinfo', $userinfo);
        $smarty->assign('account', $account);

        $mail->msgHTML($smarty->fetch('reviewnotification.tpl'));
        //msgHTML also sets AltBody, but if you want a custom one, set it afterwards
        $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
        if(!$mail->send()) {
            $this->auditLog([ "supervisor" => $supervisor, "accountuser" => $userinfo ], [ 'error' => $mail->ErrorInfo ]);
        } else {
            $this->auditLog([ "supervisor" => $supervisor, "accountuser" => $userinfo ], [ 'message' => $smarty->fetch('reviewnotification.tpl') ]);
        }
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
            }, \array_filter($updatedattributes['roles'], function($r) {
                return (isset($r['status']))?($r['status'] !== "Removed"):true;
            }));
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
            ]),"Internal Server Error",500);
        } else {
            $this->auditLog([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
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
        // Test to see if review can be processed and, if so, process it
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
                    ]),"Internal Server Error",500);
                } else {  
                    $this->auditLog([ "type" => $type, "identifier" => $identifier ], [ '$set' => $updatedattributes ]);
                    $roles = $this->getARMroles(); 
                    try {
                        foreach(\array_filter($account['roles'], function($r) { return (isset($r['dynamic_role']))?!$r['dynamic_role']:true; }) as $r) {
                            $accountRole = $roles->findOne([ "_id" => $r['role_id'] ]);
                            if (!is_null($accountRole)) {
                                $resp = $this->setConfirmByAccountRole($type,$identifier, $accountRole['href'],$managerattributes);
                                if(!$resp->isSuccess()) {
                                    return $resp;
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
                    return $this->getAccountByTypeAndIdentifier($type, $identifier);
                }
            } else {
                return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                    "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_REVIEW_UNSET_BY_MANAGER']
                ]));
            }
        } else {
            // Check to see if this is a manager
            
            
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_STATE_UNSET_BY_MANAGER']
            ]));
        }
    }
    /**
     * Delegates an existing open review to another manager
     * 
     * @param string $delegateidentity
     * @param string $identity
     * @param string $type
     * @param string $identifier
     * @return JSendResponse
     */
    public function delegateReviewByTypeAndIdentifier($delegateidentity,$identity,$type,$identifier,$days = -1,$note = "") {
        $href = "/accounts/{$type}/{$identifier}";
        return $this->delegateReview($delegateidentity, $identity, $href,$days,$note);
    }
    /**
     * Delegates an existing open review to another manager
     * 
     * @param string $delegateidentity
     * @param string $identity
     * @param string $href
     * @return JSendResponse
     */
    public function delegateReview($delegateidentity,$identity,$href,$days = -1,$note = "") {
        $accounts = $this->getARMaccounts();
        $account = $accounts->findOne(["href" => $href]);
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
        if(UsfARMapi::hasReviewForManager($account['review'], $identity)) {
            // Check the delegate to see if they are allowed up the org chart
            // Get the emplid for the delegate supervisor
            $delegatevisor = $this->getVisor($delegateidentity);
            if(!$delegatevisor->isSuccess()) {
                return $delegatevisor;
            }
            $visorcheck = $this->getVisor($account['identity'],$delegatevisor->getData()['employee_id']);
            if(!$visorcheck->isSuccess()) {
                return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                    "description" => UsfARMapi::$ARM_ERROR_MESSAGES['VISOR_PROXY_LOOKUP_ERROR']
                ]));
            }
            $updatedattributes = [ 'review' => $account['review'] ];
            $managerattributes = [
                'name' => $delegatevisor->getData()['directory_info']['self']['name'],
                'usfid' => $delegatevisor->getData()['directory_info']['self']['usf_id']
            ];
            $adminattributes = [
                'admin_role' => $this->auditInfo['armuser']['role'],
                'admin_name' => $this->auditInfo['armuser']['name'],
                'admin_usfid' => $this->auditInfo['armuser']['usf_id']
            ];
            if(!empty($note)) {
                $adminattributes['admin_note'] = $note;
            }
            $updatedattributes['review'] = UsfARMapi::getUpdatedReviewArray(\array_map(function($r) use($identity) {
                if($r['usfid'] == $identity) {
                    $r['review'] = 'closed';
                }
                return $r;
            }, $updatedattributes['review']), 'open', \array_merge($managerattributes, $adminattributes),$days);
            // Update the account with review changes and move on to the state changes
            $status = $accounts->update([ "href" => $href ], [ '$set' => $updatedattributes ]);
            if (!$status) {
                return new JSendResponse('error', UsfARMapi::errorWrapper('error', [
                    "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_UPDATE_ERROR']
                ]),"Internal Server Error",500);
            } else {
                $this->auditLog([ "delegate_identity" => $delegateidentity, "target_identity" => $identity, "account_href" => $href, "days" => $days, "note" => $note ], [ '$set' => $updatedattributes ]);   
                // Set the empty state for the account by the manager
                $stateresp = $this->setAccountState($account['type'], $account['identifier'], '', $managerattributes);
                if(!$stateresp->isSuccess()) {
                    return $stateresp;
                }
                if(!isset($account['roles'])) {
                    $account['roles'] = [];
                }
                $roles = $this->getARMroles();
                foreach (\array_filter($account['roles'], function($r) { return (!((isset($r['dynamic_role']))?$r['dynamic_role']:false && !((isset($r['status']))?($r['status'] == "Removed"):false))); }) as $role) {
                    $rolestateresp = $this->setAccountRoleState($account['type'], $account['identifier'], $roles->findOne([ "_id" => $role['role_id'] ])['href'], '', $managerattributes);
                    if(!$rolestateresp->isSuccess()) {
                        return $rolestateresp;
                    }
                }
                $updatedaccount = $this->getAccountByTypeAndIdentifier($account['type'], $account['identifier']);
                if($updatedaccount->isSuccess()) {
                    // Send email notifications
                    // $this->sendReviewNotification($delegatevisor->getData()['directory_info']['self'], $updatedaccount->getData(),$visorcheck->getData()['directory_info']['self']);
                }
                return $updatedaccount;
            }            
        } else {
            return new JSendResponse('fail', UsfARMapi::errorWrapper('fail', [
                "description" => UsfARMapi::$ARM_ERROR_MESSAGES['ACCOUNT_REVIEW_UNSET_BY_MANAGER']
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
    /**
     * Gets confirmed account within the past specified minutes
     * 
     * @param int $minutes
     * @return JSendResponse
     */
    public function getConfirmedAccountsByInterval($minutes) {
        $sec = \time() - $minutes*60;        
        $accounts = $this->getARMaccounts();
        
        $confirmaccounts=$accounts->find([
            'confirm' =>  ['$elemMatch' => ['timestamp' => ['$gte' => new \MongoDate($sec) ]] ],
            'status' => 'Active'
        ],[
            '_id' => 0,'identity' => 1,'href' => 1,'identifier' => 1, 'confirm' => 1, 'type'=> 1, 'roles' => 1
        ]);
        return new JSendResponse('success',[ 
            "confirmed_since" => date('Y/m/d H:i:s', $sec), 
            'accounts' => $this->formatMongoAccountsListToAPIListing(\array_map(function($a) use($sec) {
                $filteredconfirms = \array_values(\array_filter($a['confirm'], function($c) use($sec) {
                    return ($c['timestamp']->toDateTime()->getTimestamp() >= $sec);
                }));        
                $usfids = \array_unique(\array_map(function($c) { return $c['usfid']; }, $a['confirm']));
                $confirms = [];
                foreach ($usfids as $usfid) {
                    $managerconfirms = \array_filter($filteredconfirms, function($c) use($usfid) {
                        return ($c['usfid'] == $usfid);
                    });
                    if(\count($managerconfirms) > 0) {
                        \usort($managerconfirms, function($c1,$c2) {
                            if($c1['timestamp']->toDateTime()->getTimestamp() == $c2['timestamp']->toDateTime()->getTimestamp()) {
                                return 0;
                            }
                            return ($c1['timestamp']->toDateTime()->getTimestamp() < $c2['timestamp']->toDateTime()->getTimestamp()) ? 1 : -1;
                        });
                        $confirms[] = \array_shift($managerconfirms);
                    }
                }
                $a['confirm'] = $confirms;
                if(isset($a['roles'])) {                
                    $a['roles'] = \array_map(function($r) use($usfids) {
                        if(isset($r['confirm'])) {
                            $filteredconfirms = \array_values(\array_filter($r['confirm'], function($c) use($usfids) {
                                return \in_array($c['usfid'],$usfids);
                            }));
                            $confirms = [];
                            foreach ($usfids as $usfid) {
                                $managerconfirms = \array_filter($filteredconfirms, function($c) use($usfid) {
                                    return ($c['usfid'] == $usfid);
                                });
                                if(\count($managerconfirms) > 0) {
                                    \usort($managerconfirms, function($c1,$c2) {
                                        if($c1['timestamp']->toDateTime()->getTimestamp() == $c2['timestamp']->toDateTime()->getTimestamp()) {
                                            return 0;
                                        }
                                        return ($c1['timestamp']->toDateTime()->getTimestamp() < $c2['timestamp']->toDateTime()->getTimestamp()) ? 1 : -1;
                                    });
                                    $confirms[] = \array_shift($managerconfirms);
                                }
                            }
                            $r['confirm'] = $confirms;
                        }
                        unset($r['state']);
                        return $r;
                        // return \array_diff_key($r,\array_diff($r, \array_flip(['role_id','arm_created_date','confirm'])));
                    }, \array_filter($a['roles'], function($r) use($usfids) {
                        if((isset($r['status']))?($r['status'] != 'Active'):false) {
                            return false;
                        }
                        if(!isset($r['confirm'])) {
                            return false;
                        }
                        return (\count(\array_intersect($usfids, \array_map(function($c) { return $c['usfid']; }, $r['confirm']))) > 0);
                    }));
                }
                return $a;
            }, \iterator_to_array($confirmaccounts)))
        ]);
    }
    
}
