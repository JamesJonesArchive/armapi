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
 * Description of UsfARMfilter
 *
 * @author james
 */
trait UsfARMfilter {
    /**
     * Retrieves an array of accounts for a specified identity 
     * 
     * @param object $identity
     * @return JSendResponse
     */
    public function getAllAccountsARM() {
        return $this->getAllAccounts();
    }
//    /**
//     * Retrieves an array of accounts for a specified identity 
//     * 
//     * @param object $identity
//     * @return array of accounts
//     */
//    public function getAccountsForIdentityARM($identity) {
//        // return $this->getAccountsForIdentity($identity);
//        $something = $this->usfARMapi->getAccountsForIdentity($identity);
//        return new JSendResponse('success', [
//            'test' => $identity,
//            // 'isset' => isset($usfARMapi),
//            'exists' => method_exists($this,"getAccountsForIdentity"),
//            // 'exists1' => method_exists($usfARMapi,"getAccountsForIdentity")
//            'data' => $something
//        ]);
//    }
}
