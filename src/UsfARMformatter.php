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
            } elseif (\is_array($a) && \array_diff_key($a,\array_keys(\array_keys($a)))) {
                return \array_map(function ($b) {
                    if($b instanceof \MongoDate) {
                        return $b->toDateTime()->format('Y-m-d\TH:i:s.u\Z');
                    }
                    return $b;
                }, $a);
            }
            return $a;
        }, $arr);
    }

}
