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

class IndexController extends AbstractActionController
{
    protected $form;
    protected $filter;
    protected $googlecalendarprofile;
    protected $socialeventsService;
    protected $googlecalendarservice;
    protected $setting;
    protected $translator;
    
    /**
     * preDispatch of the page
     *
     * (non-PHPdoc)
     * @see Zend\Mvc\Controller.AbstractActionController::onDispatch()
     */
    public function onDispatch(\Zend\Mvc\MvcEvent $e){
        $this->translator = $e->getApplication()->getServiceManager()->get('translator');
        return parent::onDispatch( $e );
    }
    
    /**
     * this is a simple class constructor
     */
    public function __construct(\GoogleCalendar\Service\GoogleCalendarProfileService $googlecalendarProfile, 
                                $socialeventsService, 
                                \GoogleCalendar\Service\GoogleCalendarService $googlecalendarService, 
                                SettingsServiceInterface $setting, 
                                \GoogleCalendar\Form\GoogleCalendarForm $form, 
								\GoogleCalendar\Form\GoogleCalendarFilter $filter){
        
        $this->form = $form;
        $this->filter = $filter;
        $this->googlecalendarprofile = $googlecalendarProfile;
        $this->socialeventsService = $socialeventsService;
        $this->googlecalendarservice = $googlecalendarService;
        $this->setting = $setting;
    }
    
    /**
     * Here we load a simple html page in order to open up a popup page!
     * The user will show a Google Request Authentication
     * 
     * (non-PHPdoc)
     * @see Zend\Mvc\Controller.AbstractActionController::indexAction()
     */
    public function indexAction(){
        $vm = new ViewModel();
        $vm->setTemplate('googlecalendar/index/load' );
        $vm->setTerminal( true );
        return $vm;
    }
    
    /**
     * Here we load a simple html page in order to save the gooogle calendar preference!
     * 
     * (non-PHPdoc)
     * @see Zend\Mvc\Controller.AbstractActionController::indexAction()
     */
    public function calendarAction(){
        
        $form = $this->form;
        
        $userId = $this->zfcUserAuthentication()->getIdentity()->getId();
        
        // Getting the google calendar id value set in the profile setting table
        $selGoogleCalendar = $this->googlecalendarprofile->findByParameter('gcal_calendarid', $userId);
        
        if($selGoogleCalendar){
        
            // getting the value
            $strGoogleCalendarId = $selGoogleCalendar->getValue();
            
            if(!empty($strGoogleCalendarId)){
                $form->setData(array('googlecalendar' => $strGoogleCalendarId));
            }
        }
        
        $vm = new ViewModel(array('form' => $form));
        $vm->setTemplate('googlecalendar/index/form' );
        return $vm;
    }
    
    /*
     * This method is called by the Javascript 
     * located in the load.phtml file: $.getJSON( '/index/schedule', function( j ){...}
     * and a new event will be added to your own calendar
     */
    public function connectAction(){
    
        try {
            
            $calendar = $this->getServiceLocator()->get('GoogleCalendar');
            if( $calendar instanceof \GoogleCalendar\Model\AuthRequest )
                return new JsonModel( array( 'oauth' => true, 'url' => $calendar->getAuthUrl() ) );
            
            return new JsonModel( array( 'success' => true ) );
            
        }
        catch( \Exception $x )
        {
            return new JsonModel( array( 'success' => false, 'message' => $x->getMessage() ) );
        }
    }
    
    /**
     * When the user click on the authorisation button 
     * the callback will be loaded.
     * This callback save the session var and close the popup windoe
     */
    public function callbackAction(){
        $client = $this->getServiceLocator()->get('GoogleClient');
        $code = $this->params()->fromQuery('code');
        
        if( $code )
        {
            $client->authenticate( $code );
            $_SESSION['google_access_token'] = $client->getAccessToken();
        }
        
        $vm = new ViewModel();
        $vm->setTemplate('googlecalendar/index/temporary' );
        $vm->setVariable( 'output', "window.close();window.opener.location.reload();" );
        $vm->setTerminal( true );
        
        return $vm;
    }
    
    
    /**
     * Prepare the data and then save them
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function deleteAction ()
    {
        $userId = $this->zfcUserAuthentication()->getIdentity()->getId();
            
        // delete all google calendar profile 
        $this->googlecalendarprofile->deleteAllbyUserId($userId);
        
        // delete all the events
        $this->socialeventsService->deleteAllbyUserId($userId);
        
        return $this->redirect()->toRoute('zfcuser/logout');
    }
    
    /**
     * Prepare the data and then save them
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function saveAction ()
    {
        $inputFilter = $this->filter;
        $post = $this->request->getPost();
        $userId = $this->zfcUserAuthentication()->getIdentity()->getId();
         
        if (! $this->request->isPost()) {
            return $this->redirect()->toRoute(NULL, array (
                    'action' => 'index'
            ));
        }
    
        $form = $this->form;
        $form->setData($post);
        $form->setInputFilter($inputFilter);
    
        if (!$form->isValid()) {
    
            // Get the record by its id
            $viewModel = new ViewModel(array (
                    'error' => true,
                    'form' => $form,
            ));
    
            $viewModel->setTemplate('googlecalendar/index/form');
            return $viewModel;
        }
    
        // Get the posted vars
        $data = $form->getData();
        
        if(!empty($data['googlecalendar'])){
            // Checking if the preference is already set
            $gcal_calendarid = $this->googlecalendarprofile->findByParameter('gcal_calendarid', $userId);
            if($gcal_calendarid){
                $this->googlecalendarprofile->delete($gcal_calendarid->getId());
                $this->googlecalendarprofile->deleteAllbyParameter("gcal_sync_token", $userId);
                $this->socialeventsService->deleteAllbyUserIdAndSocialNetwork($userId, 'google');
            }
    
            $gSetting = new \GoogleCalendar\Entity\GoogleCalendarProfiles();
            $gSetting->setParameter('gcal_calendarid');
            $gSetting->setValue($data['googlecalendar']);
            $gSetting->setUserId($userId);
            $gSetting->setCreatedat(date('Y-m-d H:i:s'));
            $pSettingService = $this->googlecalendarprofile->save($gSetting);
        }
    
         
        $this->flashMessenger()->setNamespace('success')->addMessage($this->translator->translate('The information have been saved.'));
    
        return $this->redirect()->toRoute('profile');
    }
    
}