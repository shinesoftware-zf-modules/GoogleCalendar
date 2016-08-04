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
* @subpackage Service
* @author Michelangelo Turillo <mturillo@shinesoftware.com>
* @copyright 2014 Michelangelo Turillo.
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
* @link http://shinesoftware.com
* @version @@PACKAGE_VERSION@@
*/

namespace GoogleCalendar\Service;

use Zend\EventManager\EventManager;
use Zend\Db\TableGateway\TableGateway;
use Zend\Stdlib\Hydrator\ClassMethods;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

class GoogleCalendarService implements GoogleCalendarServiceInterface, EventManagerAwareInterface
{
	protected $eventManager; 
	protected $client;
	protected $refreshToken;
	protected $syncToken;
	protected $nextPageToken;
	protected $calendar;
	protected $mailservice;
	protected $translator;
	
	public function __construct($client, $calendar, \Base\Service\MailService $mailservice, \Zend\Mvc\I18n\Translator $translator){
	    if( $calendar instanceof \GoogleCalendar\Model\AuthRequest ){
	        $this->calendar = null;
	    }else{
	        $this->calendar = $calendar;
	    }
	    
	    $this->client = $client;
	    $this->mailservice = $mailservice;
	    $this->translator = $translator;
	}
	
	/**
     * @return the $refreshToken
     */
    public function getRefreshToken() {
        return $this->refreshToken;
    }

	/**
     * @param field_type $refreshToken
     */
    public function setRefreshToken($refreshToken) {
        $this->refreshToken = $refreshToken;
    }

	/**
     * @return the $syncToken
     */
    public function getSyncToken() {
        return $this->syncToken;
    }

	/**
     * @param field_type $syncToken
     */
    public function setSyncToken($syncToken) {
        $this->syncToken = $syncToken;
    }

	/**
     * @return the $nextPageToken
     */
    public function getNextPageToken() {
        return $this->nextPageToken;
    }

	/**
     * @param field_type $nextPageToken
     */
    public function setNextPageToken($nextPageToken) {
        $this->nextPageToken = $nextPageToken;
    }

	/**
	 * Connect to google oAuth by the refresh_token
	 * @param string $refreshToken
	 */
	private function refreshClient(){
	    
	    $refreshToken = $this->getRefreshToken();
	    
	    if($refreshToken){
    	    $this->client->setAccessToken('{"access_token":"'.$refreshToken.'", "refresh_token":"'.$refreshToken.'", "token_type":"Bearer", "expires_in":3600, "id_token":"'.$refreshToken.'", "created":1320790426}');
    	    $calendar = new \Google_Service_Calendar( $this->client );
	    }else{
	        $calendar = $this->calendar;
	    }
	    return $calendar;
	}
	
	/**
	 * Get the list of the user calendars
	 * 
	 * (non-PHPdoc)
	 * @see GoogleCalendar\Service.GoogleCalendarServiceInterface::getList()
	 */
	public function getList(){
	    $calendars = array();
	    
	    if($this->calendar){
	        
	        if($this->getRefreshToken()){
	            $calendar = $this->refreshClient($this->getRefreshToken());
	        }else{
	            $calendar = $this->calendar;
	        }
	        
	        try{
    	        $calendarList = $calendar->calendarList->listCalendarList();
    	        
    	        while(true) {
    	            foreach ($calendarList->getItems() as $calendarListEntry) {
    	                $calendars[$calendarListEntry->getId()] = $calendarListEntry->getSummary();
    	            }
    	            $pageToken = $calendarList->getNextPageToken();
    	            if ($pageToken) {
    	                $optParams = array('pageToken' => $pageToken);
    	                $calendarList = $calendar->calendarList->listCalendarList($optParams);
    	            } else {
    	                break;
    	            }
    	        }
	        }catch (\Exception $e){
	            if(401 == $e->getCode()){
	                return $this->translator->translate("Invalid Google Calendar Credentials: sync error log out and log in again!");
	            }
	             
	            return false;
	        }
	    }
	    
	    return $calendars;
	}
	
	/**
	 * Get the list of the events of the calendar selected
	 * 
	 * @param string $calendarId
	 */
	public function getEvents($calendarId){
	    $events = array();
	    $optParams = array();
	    
	    $minCheck = date(DATE_RFC3339, mktime(0, 0, 0, date("m"), date("d"), date("Y")));
	    $maxCheck = date(DATE_RFC3339, mktime(0, 0, 0, 12, 31, date("Y")));
	     
	    $optParams = array("timeMin" => $minCheck, "timeMax" => $maxCheck);
	    
	    try{
    	    if($this->getRefreshToken()){
    	        $calendar = $this->refreshClient();
    	    }else{
    	        $calendar = $this->calendar;
    	    }
    	    
    	    if($this->getSyncToken()){
    	        $syncToken = $this->getSyncToken();
    	        $optParams['syncToken'] = $syncToken->getValue();
    	        unset($optParams['timeMin']);
    	        unset($optParams['timeMax']);
    	    }
    	    
    	    if($this->getNextPageToken()){
    	        $optParams['pageToken'] = $this->getNextPageToken();
    	    }
    	    
    	    if($calendar && !empty($calendarId)){
    	        $events = $calendar->events->listEvents($calendarId, $optParams);
    	        return $events;
    	    }
	    }catch (\Exception $e){
	        if(400 == $e->getCode()){ //invalid_grant
                return $e->getMessage();
	        }
	    
	        return false;
	    }
	    
	    return $events;
	}
	
	
	/* (non-PHPdoc)
     * @see \Zend\EventManager\EventManagerAwareInterface::setEventManager()
     */
     public function setEventManager (EventManagerInterface $eventManager){
         $eventManager->addIdentifiers(get_called_class());
         $this->eventManager = $eventManager;
     }

	/* (non-PHPdoc)
     * @see \Zend\EventManager\ProfileCapableInterface::getEventManager()
     */
     public function getEventManager (){
       if (null === $this->eventManager) {
            $this->setEventManager(new EventManager());
        }

        return $this->eventManager;
     }

}