<?php
namespace GoogleCalendar\Form\Element;

use GoogleCalendar\Service\GoogleCalendarService;
use Zend\Form\Element\Select;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\I18n\Translator\Translator;

class Calendars extends Select implements ServiceLocatorAwareInterface
{
    protected $serviceLocator;
    protected $calendar;
    
    public function __construct(GoogleCalendarService $calendar){
        parent::__construct();
        $this->calendar = $calendar;
    }
    
    public function init()
    {
        $data = array();
        $calendars = $this->calendar->getList();
        if(is_array($calendars)){
            foreach ($calendars as $key => $value){
                $data[$key] = $value;
            }
        }elseif(is_string($calendars)){
            $data[] = $calendars;
        }
        
        $this->setValueOptions($data);
    }
    
    public function setServiceLocator(ServiceLocatorInterface $sl)
    {
        $this->serviceLocator = $sl;
    }
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}
