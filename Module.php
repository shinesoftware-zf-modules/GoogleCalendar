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


namespace GoogleCalendar;

use Base\View\Helper\Datetime;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\ResultSet\ResultSet;
use GoogleCalendar\Service\GoogleCalendarService;
use GoogleCalendar\Entity\SocialEvents;
use GoogleCalendar\Entity\GoogleCalendarProfiles;
use GoogleCalendar\Entity\GoogleCalendarEvents;
use Zend\ModuleManager\Feature\DependencyIndicatorInterface;

class Module implements DependencyIndicatorInterface{
	
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $sm = $e->getApplication()->getServiceManager();
        $headLink = $sm->get('viewhelpermanager')->get('headLink');
//         $headLink->appendStylesheet('/css/GoogleCalendar/GoogleCalendar.css');
        
        $inlineScript = $sm->get('viewhelpermanager')->get('inlineScript');
//         $inlineScript->appendFile('/js/GoogleCalendar/GoogleCalendar.js');
        
    }
    
    /**
     * Check the dependency of the module
     * (non-PHPdoc)
     * @see Zend\ModuleManager\Feature.DependencyIndicatorInterface::getModuleDependencies()
     */
    public function getModuleDependencies()
    {
    	return array('Base', 'ZfcUser', 'Events');
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    
    /**
     * Set the Services Manager items
     */
    public function getServiceConfig ()
    { 
    	return array(
    			'factories' => array(
    					'GoogleCalendarService' => function  ($sm)
    					{
    						$service = new \GoogleCalendar\Service\GoogleCalendarService($sm->get('GoogleClient'), $sm->get('GoogleCalendar'), $sm->get('MailService'), $sm->get('translator'));
    						return $service;
    					},
    					
    					'GoogleCalendarProfiles' => function  ($sm)
    					{
    						$dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
    						$translator = $sm->get('translator'); 
    						$resultSetPrototype = new ResultSet();
    						$resultSetPrototype->setArrayObjectPrototype(new GoogleCalendarProfiles());
    						$tableGateway = new TableGateway('googlecalendar_profiles', $dbAdapter, null, $resultSetPrototype);
    						$service = new \GoogleCalendar\Service\GoogleCalendarProfileService($tableGateway, $translator);
    						return $service;
    					}, 
    					
    					'GoogleCalendarForm' => function  ($sm)
    					{
    					    $form = new \GoogleCalendar\Form\GoogleCalendarForm();
    					    $form->setInputFilter($sm->get('GoogleCalendarFilter'));
    					    return $form;
    					},
    					'GoogleCalendarFilter' => function  ($sm)
    					{
    					    return new \GoogleCalendar\Form\GoogleCalendarFilter();
    					},
    						
    					'GoogleClient' => function( $sm ){
        						
        					$config = $sm->get('config');
        					if(!empty($config['GoogleClient'])){
            					$googleClientConf = $config['GoogleClient'];
            						
        					    if(!empty($googleClientConf['ClientId']) && !empty($googleClientConf['Secret']) && !empty($googleClientConf['RedirectUri']) && !empty($googleClientConf['DeveloperKey']) ){
            					    $client = new \Google_Client();
            					    $client->setAccessType('online');
            					    $client->setApplicationName('doesnotmatter');
            					    $client->setClientId( $googleClientConf['ClientId'] );
            					    $client->setClientSecret( $googleClientConf['Secret'] );
            					    $client->setRedirectUri( $googleClientConf['RedirectUri'] );
            					    $client->setDeveloperKey( $googleClientConf['DeveloperKey'] );
            					    $client->setAccessType( 'offline' );
        					    
        					        return $client;
        					    }else{
        					        throw new \Exception('Check your google.local.php configuration array!');
        					    }
        					}else{
        					    throw new \Exception('No google.local.php file has been found!');
        					}
    					},
    					'GoogleCalendar' => function( $sm ){
    					
    					    // Get the Google Client object
        					$client = $sm->get('GoogleClient');
        					
        					// Check the google client 
        					if($client){
        					    $client->setScopes( array('https://www.googleapis.com/auth/calendar'));
        					     
        					    // if there is already a google session active set the access token
        					    if( isset( $_SESSION['google_access_token'] ))
        					        $client->setAccessToken( $_SESSION['google_access_token'] );
        					    
        					    // if there is NOT any access token OR the access tokes is EXPIRED return the oAuth link
        					    if( !$client->getAccessToken() || $client->isAccessTokenExpired())
        					        return new \GoogleCalendar\Model\AuthRequest( $client->createAuthUrl() );
        					    
        					    
        					    // Now there is an ACTIVE Access Token!
        					    try{

        					        // Get the access token 
            					    if($client->getAccessToken() && !is_array($client->getAccessToken())){
            					        $token = json_decode($client->getAccessToken(), true);  // it is a JSON string
    
            					        // Get the "refresh_token" for a long time session 
                					    if(!empty($token['refresh_token'])){
            					        
            					            // Getting the User connected ID
                					        $auth = $sm->get('zfcuser_auth_service');
                					        $userId = $auth->getIdentity()->getId();

                					        // Getting the GoogleCalendar ID
                					        $GoogleCalendarProfiles = $sm->get('GoogleCalendarProfiles');

                					        // Now it will save in the GoogleCalendar Settings table
                					        $GoogleCalendarSettingEntity = new \GoogleCalendar\Entity\GoogleCalendarProfiles();

                					        // Checking if the refresh token has been already set for this GoogleCalendar
                					        $gcal_refresh_token = $GoogleCalendarProfiles->findByParameter('gcal_refresh_token', $userId);
                					        
                					        if(empty($gcal_refresh_token)){

                					            // clean up the old tokens
                					            $GoogleCalendarProfiles->deleteAllbyParameter('gcal_refresh_token', $userId); // delete the old token
                					            
                    					        // create a new token
                        					    $GoogleCalendarSettingEntity->setParameter('gcal_refresh_token');
                        					    $GoogleCalendarSettingEntity->setValue($token['refresh_token']);
                        					    $GoogleCalendarSettingEntity->setUserId($userId);
                        					    $GoogleCalendarSettingEntity->setCreatedat(date('Y-m-d H:i:s'));
                    					        $GoogleCalendarProfiles->save($GoogleCalendarSettingEntity);
                					        }                    					        
                					    }
                					}
            					}catch(\Exception $e){
            					    
            					    $userId = $auth->getIdentity()->getId();
            					    $GoogleCalendarProfiles->deleteAllbyParameter('gcal_refresh_token', $userId); // clean up the old tokens

            					    return false;
            					}
        					    
                					// Get the google calendar object 
        					    $calendar = new \Google_Service_Calendar( $client );
        					    return $calendar;
        					}else{
        					    throw new \Exception('No google.local.php file has been found!');
        					}
    					},
    					'zfcuser_user_mapper' => function ($sm) {
        					$options = $sm->get('zfcuser_module_options');
        					$mapper = new \GoogleCalendar\Mapper\User();
        					$mapper->setDbAdapter($sm->get('zfcuser_zend_db_adapter'));
        					$entityClass = $options->getUserEntityClass();
        					$mapper->setEntityPrototype(new $entityClass);
        					$mapper->setHydrator(new \ZfcUser\Mapper\UserHydrator());
        					$mapper->setTableName($options->getTableName());
        					return $mapper;
    					},
    					
    				),
    			);
    }
    
    
    /**
     * Get the form elements
     */
    public function getFormElementConfig ()
    {
    	return array (
    			'factories' => array (
    					'GoogleCalendar\Form\Element\Calendars' => function  ($sm)
		    					{
		    						$serviceLocator = $sm->getServiceLocator();
		    						$service = $serviceLocator->get('GoogleCalendarService');
		    						$element = new \GoogleCalendar\Form\Element\Calendars($service);
		    						return $element;
		    					},
    					)
    		);
    }
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    __NAMESPACE__ . "Admin" => __DIR__ . '/src/' . __NAMESPACE__ . "Admin",
                    __NAMESPACE__ . "Settings" => __DIR__ . '/src/' . __NAMESPACE__ . "Settings",
                ),
            ),
        );
    }
}
