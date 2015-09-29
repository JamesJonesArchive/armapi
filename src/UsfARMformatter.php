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
 * Description of UsfARMformatter
 *
 * @author james
 */
trait UsfARMformatter {
    /**
     * Converts mongo dates to UTC date strings with one level of recursion
     * 
     * @param type $arr
     * @return type
     */
    public static function convertMongoDatesToUTCstrings($arr) {
        return \array_map(function($a) {
            if($a instanceof \MongoDate) {
                return $a->toDateTime()->format('Y-m-d\TH:i:s.u\Z');
            } elseif (\is_array($a)) {
                return self::convertMongoDatesToUTCstrings($a);
            }   
            return $a;
        }, $arr);
    }
    /**
     * Converts UTC date strings to mongo dates for matched keys
     * 
     * @param type $arr
     * @param type $datekeys
     * @return type
     */
    public static function convertUTCstringsToMongoDates($arr,$datekeys = []) {
        \array_walk($arr, function(&$val,$key) use(&$datekeys) {
            if(\in_array($key, $datekeys) && \is_string($val)) {
                $val = new \MongoDate(\strtotime($val));
            }
        });
        return $arr;
    }
    /**
     * Formats raw mongo account data into API compliant accounts
     * 
     * @param type $mongoaccounts an array of accounts from mongo
     * @param type $removekeys an array of keys to remove
     * @return type array of API formated accounts
     */
    public function formatMongoAccountsListToAPIListing($mongoaccounts,$removekeys = []) {        
        return \array_map(function($act) use(&$removekeys) {
            return self::formatMongoAccountToAPIaccount($act,$removekeys);
        },$mongoaccounts,[]);
    }
    /**
     * Formats a raw mongo account into API compliant account
     * 
     * @param type $mongoaccount an account from mongo
     * @param string $removekeys an array of keys to remove
     * @return type API formatted account
     */
    public function formatMongoAccountToAPIaccount($mongoaccount,$removekeys = []) {
        $roles = $this->getARMroles();
        if(!in_array('_id', $removekeys)) {
            $removekeys[] = "_id";
        }
        if((isset($mongoaccount['roles']))?  \is_array($mongoaccount['roles']):false) {
            $mongoaccount['roles'] = \array_map(function($a) use(&$roles) { 
                if(isset($a['role_id'])) {
                    $role = $roles->findOne([ "_id" => $a['role_id'] ],[ 'name' => true, 'short_description' => true, 'href' => true, '_id' => false ]);
                    if (!is_null($role)) {
                        unset($a['role_id']);
                        return self::convertMongoDatesToUTCstrings(\array_merge($a,$role));
                    }
                }
                return self::convertMongoDatesToUTCstrings($a); 
            },$mongoaccount['roles'],[]); 
        } else {
            $mongoaccount['roles'] = [];
        }
        return self::convertMongoDatesToUTCstrings(\array_diff_key($mongoaccount,array_flip($removekeys)));
    }
    /**
     * Returns a formatted string without spaces
     * 
     * @param type $name
     * @return string
     */
    public static function formatRoleName($name) {
        return str_replace(" ","+",$name);
    }
}
