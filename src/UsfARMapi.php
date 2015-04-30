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

    private $version = "0.0.1";

    public function getVersion() {
        return $this->version;
    }

    // New stuff
    public function getAllAccounts() {
        return new JSendResponse('success', [
            "GEMS" => [
                [ "href" => "/accounts/GEMS/000000123456"],
                [ "href" => "/accounts/GEMS/000000123457"],
                [ "href" => "/accounts/GEMS/000000123457"],
                [ "href" => "/accounts/GEMS/000000123458"],
                [ "href" => "/accounts/GEMS/mmock"],
                [ "href" => "/accounts/GEMS/jsmith"]
            ],
            "FAST" => [
                [ "href" => "/accounts/FAST/U12345678"],
                [ "href" => "/accounts/FAST/U23456789"],
                [ "href" => "/accounts/FAST/U34567890"]
            ],
            "Active Directory" => [
                [ "href" => "/accounts/AD/mmock@usf.edu"],
                [ "href" => "/accounts/AD/jsmith@usf.edu"],
                [ "href" => "/accounts/AD/jsmith12@usf.edu"]
            ]
        ]);
    }

    // End New Stuff
    // Get Methods
    /**
     * Retrieves an array of accounts for a specified identity object
     * 
     * @param object $identity
     * @return array of accounts
     */
    public function getAccountsForIdentity($identity) {
        return new JSendResponse('success', [
            "identity" => "U12345678",
            "accounts" => [
                [
                    "type" => "GEMS",
                    "identifier" => "00000012345",
                    "employeeID" => "00000012345",
                    "created_date" => "2015-05-08T00:00:00.000Z",
                    "passwd_change" => "2015-01-08T13:20:11.000Z",
                    "href" => "/accounts/GEMS/00000012345",
                    "roles" => [
                        [
                            "name" => "Self-Service",
                            "description" => "This role allows the user to access GEMS self-service",
                            "added_date" => "2015-05-08T00:00:00.000Z",
                            "href" => "/roles/1"
                        ]
                    ]
                ],
                [
                    "type" => "GEMS",
                    "identifier" => "jsmith",
                    "employeeID" => "00000012345",
                    "created_date" => "2015-05-08T00:00:00.000Z",
                    "passwd_change" => "2015-01-08T13:20:11.000Z",
                    "href" => "/accounts/GEMS/jsmith",
                    "roles" => [
                        [
                            "name" => "Self-Service",
                            "description" => "This role allows the user to access GEMS",
                            "added_date" => "2015-05-08T00:00:00.000Z",
                            "href" => "/roles/1"
                        ]
                    ]
                ],
                [
                    "type" => "FAST",
                    "identifier" => "U12345678",
                    "employeeID" => "00000012345",
                    "created_date" => "2014-01-09T10:15:52.000Z",
                    "passwd_change" => "2015-01-08T13:20:11.000Z",
                    "href" => "/accounts/FAST/U12345678",
                    "roles" => [
                        [
                            "name" => "Traveler",
                            "description" => "This role allows the user to acces the travel application",
                            "added_date" => "2014-01-09T10:15:52.000Z",
                            "href" => "/roles/3"
                        ]
                    ]
                ],
                [
                    "type" => "Active Directory",
                    "identifier" => "mmock@usf.edu",
                    "dn" => "CN=Mock\\, Molly [mmock],OU=affiliated,DC=forest,DC=usf,DC=edu",
                    "created_date" => "2012-04-23T18:25:43.000Z",
                    "modified_date" => "2015-01-08T13:20:11.000Z",
                    "passwd_change" => "2015-01-08T13:20:11.000Z",
                    "href" => "/accounts/AD/mmock@usf.edu",
                    "primary_address" => "mmock@usf.edu",
                    "email_addresses" => [
                        "mmock@usf.edu",
                        "mmock@admin.usf.edu",
                        "molly.m.mock@honors.usf.edu",
                        "mmock@usfedu.onmicrosoft.com"
                    ],
                    "roles" => [
                        [
                            "name" => "AD-Affiliated",
                            "description" => "Standard AD access (Can login to desktops, can mount drives, etc)",
                            "added_date" => "2012-04-23T18:25:43.000Z",
                            "href" => "/roles/4"
                        ],
                        [
                            "name" => "Office 365",
                            "description" => "Cloud-based Exchange account.",
                            "added_date" => "2012-04-23T18:25:43.000Z",
                            "href" => "/roles/5"
                        ],
                        [
                            "name" => "Lync",
                            "description" => "On-prem Lync account.",
                            "added_date" => "2012-04-23T18:25:43.000Z",
                            "href" => "/roles/6"
                        ]
                    ]
                ],
                [
                    "type" => "Active Directory",
                    "identifier" => "it-example@usf.edu",
                    "dn" => "CN=Service Account\\, IT Example [it-example],OU=service accounts,DC=forest,DC=usf,DC=edu",
                    "created_date" => "2012-04-23T18:25:43.000Z",
                    "modified_date" => "2015-01-08T13:20:11.000Z",
                    "passwd_change" => "2015-01-08T13:20:11.000Z",
                    "href" => "/accounts/AD/it-example@usf.edu",
                    "owner" => "mmock",
                    "notify_list" => [
                        "mmock@usf.edu",
                        "somebody@usf.edu"
                    ],
                    "roles" => [
                        [
                            "name" => "AD-Service Account",
                            "description" => "Ative Directory Service account.",
                            "added_date" => "2012-04-23T18:25:43.000Z",
                            "href" => "/roles/7"
                        ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * Retrieves an array of roles for a specified identity object
     * 
     * @param object $identity
     * @return array of roles
     */
    public function getRolesForIdentity($identity) {
        return new JSendResponse('success', [
            "roles" => [
                "User",
                "Student"
            ]
        ]);
    }

    /**
     * Retrieves an array of roles for a specified account object
     * 
     * @param object $account
     * @return array of roles
     */
    public function getRolesForAccount($account) {
        return new JSendResponse('success', [
            "roles" => [
                "User",
                "Student"
            ]
        ]);
    }

    /**
     * Retrieves an identity associated with a specified account object
     * 
     * @param object $account
     * @return object as an identity associated with the account
     */
    public function getIdentityForAccount($account) {
        return new JSendResponse('success', [
            "name" => "Rocky Bull"
        ]);
    }

    /**
     * Retrieves an array of identities associated with a specified role object
     * 
     * @param object $role
     * @return array of identities
     */
    public function getIdentitiesForRole($role) {
        return new JSendResponse('success', [
            "identities" => [
                [
                    "name" => "Rocky Bull"
                ],
                [
                    "name" => "Molly Mock"
                ]
            ]
        ]);
    }

    // POST methods
    /**
     * Assigns a specified account object with an existing identity
     * 
     * @param object $identity
     * @param object $account
     * @return object with the status of the assignment
     */
    public function setAccountForIdentity($identity, $account) {
        $armdb = $this->getMongoConnection()->arm;
        $accounts = $armdb->accounts;
        $assignaccount = $accounts->findOne([ "name" => $account . name]);
        if (is_null($assignaccount)) {
            return new JSendResponse('fail', [
                "account" => "Specified Account does not exist"
            ]);
        } elseif ((!is_null($assignaccount . identity)) ? ($assignaccount . identity . id == $identity . id) : false) {
            return new JSendResponse('fail', [
                "account" => "Identity already set for this account"
            ]);
        }
        $status = $accounts->update([ "name" => $account . name], [ "identity" => $identity . id]);
        if ($status) {
            return new JSendResponse('success', [ "status" => "Update Successful!"]);
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }

    /**
     * Assigns a specified role object with an existing account
     * 
     * @param object $account
     * @param object $role
     * @return object with the status of the assignment
     */
    public function setRoleForAccount($account, $role) {
        $armdb = $this->getMongoConnection()->arm;
        $accounts = $armdb->accounts;
        $assignaccount = $accounts->findOne([ "name" => $account["name"]]);
        if (is_null($assignaccount)) {
            return new JSendResponse('fail', [
                "account" => "Specified Account does not exist"
            ]);
        }
        $roles = $armdb->roles;
        $assignrole = $roles->findOne([ "name" => $role["name"]]);
        if (is_null($assignrole)) {
            return new JSendResponse('fail', [
                "account" => "Specified Role does not exist"
            ]);
        } elseif (!isset($assignaccount["roles"])) {
            $assignaccount["roles"] = [];
        }
        if (in_array($assignrole["_id"], $assignaccount["roles"])) {
            return new JSendResponse('fail', [
                "account" => "Role already set for this account"
            ]);
        }
        $assignaccount["roles"][] = $assignrole["_id"];
        $status = $accounts->update([ "name" => $account["name"]], [ "roles" => $assignrole["roles"]]);
        if ($status) {
            return new JSendResponse('success', [ "status" => "Update Successful!"]);
        } else {
            return new JSendResponse('error', "Update failed!");
        }
    }

}
