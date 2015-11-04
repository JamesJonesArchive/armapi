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
 * Description of UsfARMaudit
 *
 * @author James Jones <james@mail.usf.edu>
 */
trait UsfARMaudit {
    /**
     * Returns the audits mongo collection
     * 
     * @return \MongoCollection
     */
    public function getARMaudits() {
        return $this->getARMdb()->audits;
    }
    /**
     * 
     * @param \Slim\Http\Request $request
     * @return type
     */
    public static function getRequestAuditInfo($request) {
        return [
            'charset' => $request->getContentCharset(),
            'contentType' => $request->getContentType(),
            'host' => $request->getHost(),
            'ip' => $request->getIp(),
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'ip' => $request->getIp(),
            'port' => $request->getPort(),
            'referrer' => $request->getReferrer(),
            'scheme' => $request->getScheme(),
            'scriptName' => $request->getScriptName(),
            'url' => $request->getUrl(),
            'userAgent' => $request->getUserAgent(),
            'isAjax' => $request->isAjax(),
            'isDelete' => $request->isDelete(),
            'isFormData' => $request->isFormData(),
            'isGet' => $request->isGet()            
        ];
    }
}
