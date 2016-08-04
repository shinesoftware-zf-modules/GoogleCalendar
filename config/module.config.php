<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletongooglecalendar for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
		'bjyauthorize' => array(
				'guards' => array(
					'BjyAuthorize\Guard\Route' => array(
							
		                // Generic route guards
		                array('route' => 'google', 'roles' => array('user')),
				        array('route' => 'google/default', 'roles' => array('user')),
				        array('route' => 'google/batch', 'roles' => array('guest')),
						
					),
			  ),
		),
		
    'router' => array(
        'routes' => array(
        		'google' => array(
        		        'type'    => 'Literal',
        		        'options' => array(
        		                'route'    => '/google',
        		                'defaults' => array(
        		                        '__NAMESPACE__' => 'GoogleCalendar\Controller',
        		                        'controller'    => 'Index',
        		                        'action'        => 'index',
        		                ),
        		        ),
        		        'may_terminate' => true,
        		        'child_routes' => array(
        		                'default' => array(
        		                        'type'    => 'Segment',
        		                        'options' => array(
        		                                'route'    => '[/:action]',
        		                                'constraints' => array(
        		                                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
        		                                ),
        		                                'defaults' => array(
        		                                ),
        		                        ),
        		                ),
        		                'batch' => array(
        		                        'type'    => 'Segment',
        		                        'options' => array(
        		                                'route'    => '/batch[/:action]',
        		                                'constraints' => array(
        		                                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
        		                                ),
        		                                'defaults' => array(
            		                                'controller'    => 'Batch',
        		                                ),
        		                        ),
        		                ),
        		        ),
        		),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),

		'controllers' => array(
        'invokables' => array(
        ),
        'factories' => array(
        		'GoogleCalendar\Controller\Index' => 'GoogleCalendar\Factory\IndexControllerFactory',
        		'GoogleCalendar\Controller\Batch' => 'GoogleCalendar\Factory\BatchControllerFactory',
        )
    ),
    'view_helpers' => array (
    		'invokables' => array (
    		)
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
            ),
        ),
    ),
);
