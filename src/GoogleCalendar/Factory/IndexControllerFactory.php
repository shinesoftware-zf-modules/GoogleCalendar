<?php
namespace GoogleCalendar\Factory; 

use GoogleCalendar\Controller\IndexController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class IndexControllerFactory implements FactoryInterface
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
        $baseSettings = $realServiceLocator->get('SettingsService');
        $googlecalendarService = $realServiceLocator->get('GoogleCalendarService');
        $socialeventsService = $realServiceLocator->get('SocialEvents');
        $googlecalendarSettings = $realServiceLocator->get('GoogleCalendarProfiles');
        
        $form = $realServiceLocator->get('FormElementManager')->get('GoogleCalendar\Form\GoogleCalendarForm');
        $formfilter = $realServiceLocator->get('GoogleCalendarFilter');
        
        return new IndexController($googlecalendarSettings, $socialeventsService, $googlecalendarService, $baseSettings, $form, $formfilter);
    }
}