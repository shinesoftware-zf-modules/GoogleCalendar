<?php
namespace GoogleCalendar\Form;
use Zend\InputFilter\InputFilter;

class GoogleCalendarFilter extends InputFilter
{

    public function __construct ()
    {
    	$this->add(array (
    			'name' => 'googlecalendar',
    			'required' => false
    	));
    	
    }
}