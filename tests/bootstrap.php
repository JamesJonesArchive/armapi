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

error_reporting(E_ALL | E_STRICT);
require dirname(__DIR__) . '/vendor/autoload.php';

define('ARMTESTDATA',json_encode([
    'accounts' => [
        [
            "employeeID" => "00000012345",
            "password_change" => new DateTime('@' . strtotime("2012-12-04T19:00:00-0500")),
            "status" => "Active",
            "last_used" => null,
            "last_update" => new DateTime('@' . strtotime("2015-06-05T22:00:08-0400")),
            "href" => "/accounts/FAST/U12345678",
            "type" => "FAST",
            "identifier" => "U12345678",
            "arm_created_date" => new DateTime('@' . 1442858978599),
            "arm_modified_date" => new DateTime('@' . 1442858978599),
            "identity" => "U12345678",
            "roles" => [
                [
                    "role_id" => new MongoId("5600476cc8492d142e8b46d4"),
                    "arm_created_date" => new DateTime('@' . 1442859774809),
                    "dynamic_role" => false
                ]
            ]
        ],
        [
            "employeeID" => "00000012345",
            "password_change" => new DateTime('@' . strtotime("2015-04-22T06:15:18-0400")),
            "status" => "Active",
            "last_used" => new DateTime('@' . strtotime("2015-09-18T11:29:18-0400")),
            "last_update" => new DateTime('@' . strtotime("2015-06-05T00:30:23-0400")),
            "href" => "/accounts/GEMS/00000012345",
            "type" => "GEMS",
            "identifier" => "00000012345",
            "arm_created_date" => new DateTime('@' . 1442860513279),
            "arm_modified_date" => new DateTime('@' . 1442860513279),
            "identity" => "U12345678",
            "roles" => [
                [
                    "role_id" => new MongoId("56004d34c8492d91308b4797"),
                    "arm_created_date" => new DateTime('@' . 1442866495084),
                    "dynamic_role" => true
                ],
                [
                    "role_id" => new MongoId("56004d34c8492d91308b476b"),
                    "arm_created_date" => new DateTime('@' . 1442866495085),
                    "dynamic_role" => false
                ],
                [
                    "role_id" => new MongoId("56004d34c8492d91308b46cb"),
                    "arm_created_date" => new DateTime('@' . 1442866495085),
                    "dynamic_role" => true
                ],
                [
                    "role_id" => new MongoId("56004d34c8492d91308b46d2"),
                    "arm_created_date" => new DateTime('@' . 1442866495086),
                    "dynamic_role" => true
                ],
                [
                    "role_id" => new MongoId("56004d34c8492d91308b4768"),
                    "arm_created_date" => new DateTime('@' . 1442866495086),
                    "dynamic_role" => false
                ],
                [
                    "role_id" => new MongoId("56004d33c8492d91308b4590"),
                    "arm_created_date" => new DateTime('@' . 1442866495087),
                    "dynamic_role" => false
                ]
            ]
        ],
        [
            "employeeID" => "00000012345",
            "password_change" => new DateTime('@' . strtotime("2015-04-22T06:15:18-0400")),
            "status" => "Active",
            "last_used" => new DateTime('@' . strtotime("2011-08-22T11:43:13-0400")),
            "last_update" => new DateTime('@' . strtotime("2015-06-05T00:30:23-0400")),
            "href" => "/accounts/GEMS/RBULL",
            "type" => "GEMS",
            "identifier" => "RBULL",
            "arm_created_date" => new DateTime('@' . 1442866029534),
            "arm_modified_date" => new DateTime('@' . 1442866029534),
            "identity" => "U12345678",
            "roles" => [
                [
                    "role_id" => new MongoId("56004d33c8492d91308b45d9"),
                    "arm_created_date" => new DateTime('@' . 1442870582901),
                    "dynamic_role" => false
                ],
                [
                    "role_id" => new MongoId("56004d34c8492d91308b4768"),
                    "arm_created_date" => new DateTime('@' . 1442870582902),
                    "dynamic_role" => false
                ],
                [
                    "role_id" => new MongoId("56004d33c8492d91308b466a"),
                    "arm_created_date" => new DateTime('@' . 1442870582902),
                    "dynamic_role" => false
                ]
            ]
        ]
    ],
    'roles' => [

//{
//  "_id": ObjectId("5600476cc8492d142e8b46d4"),
//  "short_description": "USF Traveler",
//  "long_description": "The role allows the access perform travel submission activities, such as view, enter, save, submit, modify, delete, and cancel travel transactions; modify profiles; reassign Pcard charges with/without  an associated travel authorization for self and specific assignees.  Intended for University employees.",
//  "name": "USF_TR_TRAVELER",
//  "href": "/roles/FAST/USF_TR_TRAVELER",
//  "type": "FAST",
//  "arm_created_date": new Date(1442858860263),
//  "arm_modified_date": new Date(1442858860263)
//}
        
        
        
    ]
]));
