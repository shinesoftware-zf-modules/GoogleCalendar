<?php
namespace GoogleCalendar\Factory; 

use GoogleCalendar\Controller\BatchController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BatchControllerFactory implements FactoryInterface
{
    /** 
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $realServiceLocator = $serviceLocator->getServiceLocator();
        $countryService = $realServiceLocator->get('CountryService');
        $baseSettings = $realServiceLocator->get('SettingsService');
        $googlecalendarService = $realServiceLocator->get('GoogleCalendarService');
        $socialEventsService = $realServiceLocator->get('SocialEvents');
        $googlecalendarSettings = $realServiceLocator->get('GoogleCalendarProfiles');
        
        $form = $realServiceLocator->get('FormElementManager')->get('GoogleCalendar\Form\GoogleCalendarForm');
        $formfilter = $realServiceLocator->get('GoogleCalendarFilter');
        
        return new BatchController($countryService, $googlecalendarSettings, $googlecalendarService, $socialEventsService, $baseSettings, $form, $formfilter);
    }
}