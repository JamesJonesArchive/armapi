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

use USF\IdM\UsfConfig;
/**
 * Description of UsfARMmongomock
 *
 * @author James Jones <james@mail.usf.edu>
 */
trait UsfARMmongomock {
    use \Zumba\PHPUnit\Extensions\Mongo\TestTrait;
    
    static $DEFAULT_DATABASE = 'mongounit_test';

    protected $connection;
    protected $dataset;
    protected $usfARMapi;
    
    /**
     * Get the mongo connection for this test.
     *
     * @return Zumba\PHPUnit\Extensions\Mongo\Client\Connector
     * @coversNothing
     */
    protected function getMongoConnection() {
        // return new \MongoClient();
        if (empty($this->connection)) {
            $this->connection = new \Zumba\PHPUnit\Extensions\Mongo\Client\Connector(call_user_func(function() {
                //Access configuration values from default location (/usr/local/etc/idm_config)
                $config = new UsfConfig();

                // The DBAL connection configuration
                $mongoConfig = $config->mongoConfig;

                if(empty($mongoConfig)) {
                    return new \MongoClient();
                } elseif (!isset($mongoConfig['options'])) {
                    return new \MongoClient($mongoConfig['server']);
                } else {
                    return new \MongoClient($mongoConfig['server'],$mongoConfig['options']);
                }                
            }));
            $this->connection->setDb(self::$DEFAULT_DATABASE);
        }
        return $this->connection;
    }

    /**
     * Get the dataset to be used for this test.
     *
     * @return Zumba\PHPUnit\Extensions\Mongo\DataSet\DataSet
     * @coversNothing
     */
    protected function getMongoDataSet() {
        if (empty($this->dataSet)) {
            $this->dataSet = new \Zumba\PHPUnit\Extensions\Mongo\DataSet\DataSet($this->getMongoConnection());
            $this->dataSet->setFixture(self::getFixture());            
        }
        return $this->dataSet;
    }
    /**
     * Prepares the environment for mocking the mongo connection and the modified collection access functions
     * @coversNothing
     */
    public function setUp() {
        $this->usfARMapi = $this->getMockBuilder('\USF\IdM\UsfARMapi')
        ->setMethods(array('getARMdb','getARMaccounts','getARMroles'))
        ->getMock();
        
        $this->usfARMapi->expects($this->any())
        ->method('getARMdb')
        ->will($this->returnValue($this->getMongoConnection()));
        
        $this->usfARMapi->expects($this->any())
        ->method('getARMaccounts')
        ->will($this->returnValue($this->getMongoConnection()->collection('accounts')));

        $this->usfARMapi->expects($this->any())
        ->method('getARMroles')
        ->will($this->returnValue($this->getMongoConnection()->collection('roles')));
        
        parent::setUp();
    }
    
    /**
     * Test data for armapi testing
     * @codeCoverageIgnore
     */
    public static function getFixture() {
        return [
            'accounts' => [
                [
                    "employeeID" => "00000012345",
                    "password_change" => new \MongoDate(strtotime("2012-12-04T19:00:00-0500")),
                    "status" => "Active",
                    "last_used" => null,
                    "last_update" => new \MongoDate(strtotime("2015-06-05T22:00:08-0400")),
                    "href" => "/accounts/FAST/U12345678",
                    "type" => "FAST",
                    "identifier" => "U12345678",
                    "arm_created_date" => new \MongoDate(1442858978599),
                    "arm_modified_date" => new \MongoDate(1442858978599),
                    "identity" => "U12345678",
                    "roles" => [
                        [
                            "role_id" => new \MongoId("5600476cc8492d142e8b46d4"),
                            "arm_created_date" => new \MongoDate(1442859774809),
                            "dynamic_role" => false
                        ]
                    ]
                ],
                [
                    "employeeID" => "00000012345",
                    "password_change" => new \MongoDate(strtotime("2015-04-22T06:15:18-0400")),
                    "status" => "Active",
                    "last_used" => new \MongoDate(strtotime("2015-09-18T11:29:18-0400")),
                    "last_update" => new \MongoDate(strtotime("2015-06-05T00:30:23-0400")),
                    "href" => "/accounts/GEMS/00000012345",
                    "type" => "GEMS",
                    "identifier" => "00000012345",
                    "arm_created_date" => new \MongoDate(1442860513279),
                    "arm_modified_date" => new \MongoDate(1442860513279),
                    "identity" => "U12345678",
                    "roles" => [
                        [
                            "role_id" => new \MongoId("56004d34c8492d91308b4797"),
                            "arm_created_date" => new \MongoDate(1442866495084),
                            "dynamic_role" => true
                        ],
                        [
                            "role_id" => new \MongoId("56004d34c8492d91308b476b"),
                            "arm_created_date" => new \MongoDate(1442866495085),
                            "dynamic_role" => false
                        ],
                        [
                            "role_id" => new \MongoId("56004d34c8492d91308b46cb"),
                            "arm_created_date" => new \MongoDate(1442866495085),
                            "dynamic_role" => true
                        ],
                        [
                            "role_id" => new \MongoId("56004d34c8492d91308b46d2"),
                            "arm_created_date" => new \MongoDate(1442866495086),
                            "dynamic_role" => true
                        ],
                        [
                            "role_id" => new \MongoId("56004d34c8492d91308b4768"),
                            "arm_created_date" => new \MongoDate(1442866495086),
                            "dynamic_role" => false
                        ],
                        [
                            "role_id" => new \MongoId("56004d33c8492d91308b4590"),
                            "arm_created_date" => new \MongoDate(1442866495087),
                            "dynamic_role" => false
                        ]
                    ]
                ],
                [
                    "employeeID" => "00000012345",
                    "password_change" => new \MongoDate(strtotime("2015-04-22T06:15:18-0400")),
                    "status" => "Active",
                    "last_used" => new \MongoDate(strtotime("2011-08-22T11:43:13-0400")),
                    "last_update" => new \MongoDate(strtotime("2015-06-05T00:30:23-0400")),
                    "href" => "/accounts/GEMS/RBULL",
                    "type" => "GEMS",
                    "identifier" => "RBULL",
                    "arm_created_date" => new \MongoDate(1442866029534),
                    "arm_modified_date" => new \MongoDate(1442866029534),
                    "identity" => "U12345678",
                    "roles" => [
                        [
                            "role_id" => new \MongoId("56004d33c8492d91308b45d9"),
                            "arm_created_date" => new \MongoDate(1442870582901),
                            "dynamic_role" => false
                        ],
                        [
                            "role_id" => new \MongoId("56004d34c8492d91308b4768"),
                            "arm_created_date" => new \MongoDate(1442870582902),
                            "dynamic_role" => false
                        ],
                        [
                            "role_id" => new \MongoId("56004d33c8492d91308b466a"),
                            "arm_created_date" => new \MongoDate(1442870582902),
                            "dynamic_role" => false
                        ]
                    ]
                ]
            ],
            'roles' => [
                [
                    "_id" => new \MongoId("5600476cc8492d142e8b46d4"),
                    "short_description" => "USF Traveler",
                    "long_description" => "The role allows the access perform travel submission activities, such as view, enter, save, submit, modify, delete, and cancel travel transactions; modify profiles; reassign Pcard charges with/without  an associated travel authorization for self and specific assignees.  Intended for University employees.",
                    "name" => "USF_TR_TRAVELER",
                    "href" => "/roles/FAST/USF_TR_TRAVELER",
                    "type" => "FAST",
                    "arm_created_date" => new \MongoDate(1442858860263),
                    "arm_modified_date" => new \MongoDate(1442858860263)
                ],        
                [
                    "_id" => new \MongoId("56004d34c8492d91308b476b"),
                    "short_description" => "Self Service Role",
                    "long_description" => "",
                    "name" => "SELFSALL_ROLE",
                    "href" => "/roles/GEMS/SELFSALL_ROLE",
                    "type" => "GEMS",
                    "arm_created_date" => new \MongoDate(1442860340542),
                    "arm_modified_date" => new \MongoDate(1442860340542)
                ],
                [
                    "_id" => new \MongoId("56004d34c8492d91308b46cb"),
                    "short_description" => "Employee",
                    "long_description" => "Cloned \"Employee\" Role and modified to reduce access.  Includes required Component Interface Permissions and Web Libraries used by Candidate Gateway, Recruiting Solutions, Self-Service, Workforce Admin, etc.\r\n\r\nDelivered Role Description\r\nThis Role is shared by the HR Product and the Portal and as a Workflow Role.\r\nUSF_EMPLOYEE is a clone of the delivered Employee Role and has been customized access for your specific system.\r\n\r\nDynamic Role Establishment\r\n- Authorized to Active Employees.  Active Employees defined as Sal Admin Plans bt 00 and 24\r\n- Query Based Rule: _ROLE_DYN_ALL_ACTIVE_EE_SS",
                    "name" => "USF_EMPLOYEE",
                    "href" => "/roles/GEMS/USF_EMPLOYEE",
                    "type" => "GEMS",
                    "arm_created_date" => new \MongoDate(1442860340184),
                    "arm_modified_date" => new \MongoDate(1442860340184)
                ],
                [
                    "_id" => new \MongoId("56004d34c8492d91308b46d2"),
                    "short_description" => "WF Approvals User",
                    "long_description" => "The WF Approvals User Role permits access to approve Job Openings and Job Offers.  Provided to individuals who are added into the AD HOC approval process for these two transaction types (Job Opening and Job Offers).\r\n\r\nPermits access to make changes to Job Openings and Job Offers during the approval process.",
                    "name" => "USF_WF_APPROVALS_USER",
                    "href" => "/roles/GEMS/USF_WF_APPROVALS_USER",
                    "type" => "GEMS",
                    "arm_created_date" => new \MongoDate(1442860340206),
                    "arm_modified_date" => new \MongoDate(1442860340206)
                ],
                [
                    "_id" => new \MongoId("56004d34c8492d91308b4768"),
                    "short_description" => "PeopleSoft User",
                    "long_description" => "",
                    "name" => "PeopleSoft User",
                    "href" => "/roles/GEMS/PeopleSoft+User",
                    "type" => "GEMS",
                    "arm_created_date" => new \MongoDate(1442860340535),
                    "arm_modified_date" => new \MongoDate(1442860340535)
                ],
                [
                    "_id" => new \MongoId("56004d33c8492d91308b4590"),
                    "short_description" => "EFFORT_CERTIFIER for Self Serv",
                    "long_description" => "",
                    "name" => "EFFORT_CERTIFIER_SS",
                    "href" => "/roles/GEMS/EFFORT_CERTIFIER_SS",
                    "type" => "GEMS",
                    "arm_created_date" => new \MongoDate(1442860339550),
                    "arm_modified_date" => new \MongoDate(1442860339550)
                ],
                [
                    "_id" => new \MongoId("56004d33c8492d91308b45d9"),
                    "short_description" => "Reporting Class 2",
                    "long_description" => "",
                    "name" => "RPT2_ROLE",
                    "href" => "/roles/GEMS/RPT2_ROLE",
                    "type" => "GEMS",
                    "arm_created_date" => new \MongoDate(1442860339683),
                    "arm_modified_date" => new \MongoDate(1442860339683)
                ],
                [
                    "_id" => new \MongoId("56004d33c8492d91308b466a"),
                    "short_description" => "Inquire Role",
                    "long_description" => "",
                    "name" => "INQUIRE_ROLE",
                    "href" => "/roles/GEMS/INQUIRE_ROLE",
                    "type" => "GEMS",
                    "arm_created_date" => new \MongoDate(1442860339970),
                    "arm_modified_date" => new \MongoDate(1442860339970)
                ],
                [
                    "_id" => new \MongoId("56004d34c8492d91308b4797"),
                    "short_description" => "Internal Applicant",
                    "long_description" => "Cloned \"Applicant\" Role required for using Candidate Gateway.\r\n\r\nDynamic Role Establishment\r\n- Authorized to Active, Regular Employees.  Regular employees defined as Employees in Salary Admin Plans 21-Admin, 22-Faculty, 23-Staff, or 24-Exec\r\n- Query Based Rule: _ROLE_DYN_ALL_ACTIVE_REG_EE_SS",
                    "name" => "USF_APPLICANT",
                    "href" => "/roles/GEMS/USF_APPLICANT",
                    "type" => "GEMS",
                    "arm_created_date" => new \MongoDate(1442860340644),
                    "arm_modified_date" => new \MongoDate(1442860340644)
                ]
            ]
        ];
    }

}
