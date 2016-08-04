<?php
/**
* Copyright (c) 2014 Shine Software.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions
* are met:
*
* * Redistributions of source code must retain the above copyright
* notice, this list of conditions and the following disclaimer.
*
* * Redistributions in binary form must reproduce the above copyright
* notice, this list of conditions and the following disclaimer in
* the documentation and/or other materials provided with the
* distribution.
*
* * Neither the names of the copyright holders nor the names of the
* contributors may be used to endorse or promote products derived
* from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
* "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
* LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
* FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
* COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
* CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
* LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
* ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* @package GoogleCalendar
* @subpackage Entity
* @author Michelangelo Turillo <mturillo@shinesoftware.com>
* @copyright 2014 Michelangelo Turillo.
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
* @link http://shinesoftware.com 
* @version @@PACKAGE_VERSION@@
*/

namespace GoogleCalendar\Entity;

class GoogleCalendarProfiles implements GoogleCalendarProfilesInterface {

    public $id;
    public $user_id;
    public $parameter;
    public $value;
    public $createdat;
    
    /**
     * This method get the array posted and assign the values to the table
     * object
     *
     * @param array $data
     */
    public function exchangeArray ($data)
    {
    	foreach ($data as $field => $value) {
    		$this->$field = (isset($value)) ? $value : null;
    	}
    
    	return true;
    }
	/**
     * @return the $id
     */
    public function getId() {
        return $this->id;
    }

	/**
     * @param field_type $id
     */
    public function setId($id) {
        $this->id = $id;
    }

	/**
     * @return the $user_id
     */
    public function getUserId() {
        return $this->user_id;
    }

	/**
     * @param field_type $user_id
     */
    public function setUserId($user_id) {
        $this->user_id = $user_id;
    }

	/**
     * @return the $parameter
     */
    public function getParameter() {
        return $this->parameter;
    }

	/**
     * @param field_type $parameter
     */
    public function setParameter($parameter) {
        $this->parameter = $parameter;
    }

	/**
     * @return the $value
     */
    public function getValue() {
        return $this->value;
    }

	/**
     * @param field_type $value
     */
    public function setValue($value) {
        $this->value = $value;
    }
	/**
     * @return the $createdat
     */
    public function getCreatedat() {
        return $this->createdat;
    }

	/**
     * @param field_type $createdat
     */
    public function setCreatedat($createdat) {
        $this->createdat = $createdat;
    }


    
}