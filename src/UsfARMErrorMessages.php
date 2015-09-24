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
 * Description of UsfARMErrorMessages
 *
 * @author james
 */
trait UsfARMErrorMessages {
    //put your code here
    static $ARM_ERROR_MESSAGES = array(
        'ACCOUNT_INFO_MISSING' => 'Account info missing!',
        'ACCOUNT_INFO_MISSING_REQUIRED_KEYS' => 'Account info missing required keys!',
        'ACCOUNT_DATA_EMPTY' => 'Account data is empty!',
        'ACCOUNT_TYPE_MISMATCH' => 'Account type is mismatched in the request!',
        'ACCOUNT_EXISTS' => 'Account of this type already exists!',
        'ACCOUNT_NOT_EXISTS' => 'Account not found!',
        'ACCOUNT_UPDATE_ERROR' => 'Account update failed!',
        'ACCOUNT_CREATE_ERROR' => 'Account creation could not be performed!',
        'ACCOUNT_NO_ROLES_EXIST' => 'No roles exist for account specified!',
        'ACCOUNT_ROLE_NOT_EXISTS' => 'Role does not exist for account specified!',
        'ROLE_INFO_MISSING' => 'Role info missing!',
        'ROLE_INFO_MISSING_REQUIRED_KEYS' => 'Role info missing required keys!',
        'ROLE_DATA_EMPTY' => 'Role data is empty!',
        'ROLE_EXISTS' => 'Role already exists!',
        'ROLE_NOT_EXISTS' => 'Role does not exist!',
        'ROLE_CREATE_ERROR' => 'Role creation could not be performed!',
        'ROLE_LIST_MISSING' => 'No role list specified!',
        'ROLES_CONTAINS_INVALID' => 'Role list contains invalid roles!'
    );
}
