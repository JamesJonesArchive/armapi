<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace USF\IdM;

use \JSend\JSendResponse;

/**
 * Description of UsfARMservice
 *
 * @author james
 */
class UsfARMservice extends \USF\IdM\UsfARMapi {
    /**
     * Retrieves an array of accounts for a specified identity 
     * 
     * @param object $identity
     * @return array of accounts
     */
    public function getAccountsForIdentityARM($identity) {
        // return $this->getAccountsForIdentity($identity);
        // $usfARMapi = new UsfARMapi();
        return new JSendResponse('success', [
            'test' => $identity,
            // 'isset' => isset($usfARMapi),
            'exists' => method_exists($this,"getAccountsForIdentity"),
            // 'exists1' => method_exists($usfARMapi,"getAccountsForIdentity")
            'data' => parent::getAccountsForIdentity($identity)
        ]);
    }
}
