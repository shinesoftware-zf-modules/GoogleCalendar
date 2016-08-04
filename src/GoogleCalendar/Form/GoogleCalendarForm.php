<?php
namespace GoogleCalendar\Form;
use Zend\Form\Form;
use Zend\Stdlib\Hydrator\ClassMethods;

class GoogleCalendarForm extends Form
{

    public function init ()
    {

        $this->setAttribute('method', 'post');
        
        $this->add(array (
        		'type' => 'GoogleCalendar\Form\Element\Calendars',
        		'name' => 'googlecalendar',
        		'attributes' => array (
        				'class' => 'form-control'
        		),
        		'options' => array (
        				'label' => _('Google Calendar'),
        		        'disable_inarray_validator' => true,
        		)
        ));
        
        $this->add(array ( 
                'name' => 'submit', 
                'attributes' => array ( 
                        'type' => 'submit', 
                        'class' => 'btn btn-success', 
                        'value' => _('Save your preference')
                )
        ));
        $this->add(array (
                'name' => 'id',
                'attributes' => array (
                        'type' => 'hidden'
                )
        ));
    }
}