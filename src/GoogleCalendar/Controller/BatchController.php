<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace GoogleCalendar\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Base\Service\SettingsServiceInterface;
use Zend\View\Model\JsonModel;

class BatchController extends AbstractActionController
{
    protected $form;
    protected $filter;
    protected $googlecalendarprofile;
    protected $googlecalendarservice;
    protected $countryService;
    protected $socialeventsService;
    protected $settings;

    /**
     * this is a simple class constructor
     */
    public function __construct(\Base\Service\CountryService $countryService,
                                \GoogleCalendar\Service\GoogleCalendarProfileService $googlecalendarProfile,
                                \GoogleCalendar\Service\GoogleCalendarService $googlecalendarService,
                                \Events\Service\SocialeventsService $socialeventsService,
                                SettingsServiceInterface $setting,
                                \GoogleCalendar\Form\GoogleCalendarForm $form,
                                \GoogleCalendar\Form\GoogleCalendarFilter $filter){

        $this->form = $form;
        $this->filter = $filter;
        $this->countryService = $countryService;
        $this->googlecalendarprofile = $googlecalendarProfile;
        $this->googlecalendarservice = $googlecalendarService;
        $this->socialeventsService = $socialeventsService;
        $this->settings = $setting;
    }

    /**
     * Batch action to refresh the data
     * https://developers.google.com/google-apps/calendar/v3/sync
     */
    public function indexAction(){

        $arrEvents = array();
        $zfcUsers = $this->getServiceLocator()->get('zfcuser_user_service');
        $googleapikey = $this->settings->getValueByParameter("Events", "googleapikey");
        $logger = new \Zend\Log\Logger();
        $writer = new \Zend\Log\Writer\Stream(PUBLIC_PATH . '/../data/log/googlecalendar.log');
        $logger->addWriter($writer);

        $users = $zfcUsers->getUserMapper()->getAll();

        if(empty($users)){
            return false;
        }

        try{

            foreach ($users as $user){

                $logger->debug("GoogleCalendar Sync: --> Get the user: #" . $user->getId() . " " . $user->getDisplayName());

                // SyncToken 
                $sync_token_key = $this->googlecalendarprofile->findByParameter("gcal_sync_token", $user->getId());
                $token = $this->googlecalendarprofile->findByParameter("gcal_refresh_token", $user->getId());
                $calendar = $this->googlecalendarprofile->findByParameter('gcal_calendarid', $user->getId());

                // Check if there is a refresh token saved for this user
                $refreshToken =(!empty($token)) ? $token->getValue() : null;

                if(!empty($calendar) && $calendar->getValue()){
                    $logger->debug("GoogleCalendar Sync: ----> use the refreshToken: $refreshToken");

                    if($sync_token_key){
                        $logger->debug("GoogleCalendar Sync: ----> use the syncToken: " . $sync_token_key->getValue());
                    }

                    $calendarObj = $this->googlecalendarservice;
                    $calendarObj->setRefreshToken($refreshToken);
                    $calendarObj->setSyncToken($sync_token_key);

                    // get the events for this user
                    $events = $calendarObj->getEvents($calendar->getValue());
                    $logger->debug("GoogleCalendar Sync: ----> new connection to the calendar: " . $calendar->getValue());

                    if(is_string($events)){ // Error 400 invalid_grant 
                        echo $calendar->getValue() . " - " . $events . "<br/>";
                        $logger->crit("GoogleCalendar Sync: ----> error: $events");
                        continue;
                    }elseif(empty($events)){
                        $logger->debug("GoogleCalendar Sync: ----> no events to sync");
                        break;
                    }

                    $logger->debug("GoogleCalendar Sync: ----> found new " . count($events->getItems()). " events");

                    while(true) {

                        // Loop the events
                        foreach ($events->getItems() as $event) {

                            // this set the pause of the script for 2 seconds
                            sleep(2);

                            // check if there is already an event with the ID/Code in the database
                            $rsevent = $this->socialeventsService->findByCode($event->id);
                            if(empty($rsevent)){
                                $rsevent = new \Events\Entity\SocialEvents();
                                $logger->debug("GoogleCalendar Sync: ----> new event to sync - " . $event->id);
                            }

                            // set as cancelled the event
                            if($event->status == "cancelled"){
                                if($rsevent->getId()){
                                    $rsevent->setStatus('cancelled');
                                    $this->socialeventsService->save($rsevent);
                                    $logger->debug("GoogleCalendar Sync: ----> the event " . $event->id . " has been cancelled by the user");
                                }
                                continue;
                            }

                            echo $event->created . " " . $event->summary . " - " . $event->location . "<br/>";

                            if(!empty($event->location)){
                                
                                $curl     = new \Ivory\HttpAdapter\CurlHttpAdapter();
                                $geocoder = new \Geocoder\Provider\GoogleMaps($curl);

                                $request = $geocoder->geocode($event->location);

                                if(isset($request)){
                                    $rsevent->setLatitude($request->first()->getLatitude());
                                    $rsevent->setLongitude($request->first()->getLongitude());
                                    $rsevent->setCity($request->first()->getLocality());
                                    $rsevent->setLocation($request->first()->getStreetName() . " " . $request->first()->getStreetNumber());

                                    $country = $this->countryService->findByCode($request->first()->getCountry()->getCode());
                                    if($country){
                                        $rsevent->setCountryId($country->getId());
                                    }
                                }

                            }

                            // prepare the event entity
                            $rsevent->setCategoryId(1);
                            $rsevent->setCode($event->id);
                            $rsevent->setIcaluid($event->iCalUID);
                            $rsevent->setStatus($event->status);
                            $rsevent->setSummary($event->summary);

                            $photos = array();
                            preg_match_all('~https?://\\S+\\.(?:jpe?g|png|gif)~im' , $event->description , $photos);
                            if(!empty($photos[0])){
                                foreach ($photos[0] as $photo){
                                    $rsevent->setPhoto($photo);
                                }
                            }

                            // if the description is empty we will use the name of the event as description
                            if(!empty($event['description'])){
                                $rsevent->setDescription(nl2br($event['description']));
                            }else{
                                $rsevent->setDescription($event['name']);
                            }

                            $rsevent->setLocation($event->location);
                            $rsevent->setCreated($event->created);
                            $rsevent->setUpdated($event->updated);

                            $rsevent->SetEtag($event->etag);
                            $rsevent->setUserId($user->getId());
                            $rsevent->setSocialnetwork('google');

                            if(!empty($event->start->dateTime)){
                                $rsevent->setStart($event->start->dateTime);
                            }elseif(!empty($event->start->date)){
                                $rsevent->setStart($event->start->date);
                            }

                            if(!empty($event->end->dateTime)){
                                $rsevent->setEnd($event->end->dateTime);
                            }elseif(!empty($event->end->date)){
                                $rsevent->setEnd($event->end->date);
                            }

                            if($event->getRecurrence()){
                                $recurrence = $event->getRecurrence();

                                $strRecurrence = implode(";", $recurrence);
                                $rsevent->setRecurrence($strRecurrence);
                                $strRule = str_replace("RRULE:", "", $strRecurrence);
                                $rule = new \Recurr\Rule($strRule);

                                $date = $rule->getUntil();
                                if(!empty($date)){
                                    $rsevent->setEnd($date->format('Y-m-dTh:i:s'));
                                }
                            }else{
                                $rsevent->setRecurrence(null);
                            }


                            // Save the event
                            $this->socialeventsService->save($rsevent);

                            $logger->debug("GoogleCalendar Sync: ----> the event " . $event->id . " - " . $event->summary . " has been saved!");

                        }

                        // Get the NextPageToken in order to handle the Incremental request
                        $pageToken = $events->getNextPageToken();
                        if ($pageToken) {
                            $calendarObj->setNextPageToken($pageToken);
                            $events = $calendarObj->getEvents($calendar->getValue());
                        } else {
                            break;
                        }
                    }

                    if(!empty($events->nextSyncToken)){

                        // Save the SYNC Token for the next syncronization of the calendar
                        $this->googlecalendarprofile->saveParameter('gcal_sync_token', $events->nextSyncToken, $user->getId());
                        $logger->debug("GoogleCalendar Sync: ----> a new syncToken has been saved: " . $events->nextSyncToken);
                    }
                }


            }
        }catch(\Exception $e){
            echo $e->getMessage();
        }

        $logger->debug("GoogleCalendar Sync: ----> sync end.");
        die();
    }



}