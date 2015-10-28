<?php

use Balance\Controller;
use Balance\Form;
use Balance\Model;
use Zend\Db;

return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Balance\Controller\Home',
                        'action'     => 'index',
                    ),
                ),
            ),
            'accounts' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/accounts',
                    'defaults' => array(
                        'controller' => 'Balance\Controller\Accounts',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes'  => array(
                    'add' => array(
                        'type'    => 'literal',
                        'options' => array(
                            'route'    => '/add',
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                        ),
                    ),
                    'edit' => array(
                        'type'    => 'segment',
                        'options' => array(
                            'route'    => '/edit/:id',
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                            'constraints' => array(
                                'id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'remove' => array(
                        'type'    => 'segment',
                        'options' => array(
                            'route'    => '/remove/:id',
                            'defaults' => array(
                                'action' => 'remove',
                            ),
                            'constraints' => array(
                                'id' => '[0-9]+',
                            ),
                        ),
                    ),
                ),
            ),
            'postings' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/postings',
                    'defaults' => array(
                        'controller' => 'Balance\Controller\Postings',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes'  => array(
                    'add' => array(
                        'type'    => 'literal',
                        'options' => array(
                            'route'    => '/add',
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                        ),
                    ),
                    'edit' => array(
                        'type'    => 'segment',
                        'options' => array(
                            'route'    => '/edit/:id',
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                            'constraints' => array(
                                'id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'remove' => array(
                        'type'    => 'segment',
                        'options' => array(
                            'route'    => '/remove/:id',
                            'defaults' => array(
                                'action' => 'remove',
                            ),
                            'constraints' => array(
                                'id' => '[0-9]+',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),

    'navigation' => array(
        'default' => array(
            array(
                'label' => 'Balance',
                'route' => 'home',
                'pages' => array(
                    array(
                        'label' => 'Home',
                        'route' => 'home',
                    ),
                    array(
                        'label' => 'Contas',
                        'route' => 'accounts',
                        'pages' => array(
                            array(
                                'label' => 'Listar',
                                'route' => 'accounts',
                            ),
                            array(
                                'label' => 'Adicionar',
                                'route' => 'accounts/add',
                            ),
                        ),
                    ),
                    array(
                        'label' => 'Lançamentos',
                        'route' => 'postings',
                        'pages' => array(
                            array(
                                'label' => 'Listar',
                                'route' => 'postings',
                            ),
                            array(
                                'label' => 'Adicionar',
                                'route' => 'postings/add',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),

    'service_manager' => array(
        'factories' => array(
            'navigation' => 'Zend\Navigation\Service\DefaultNavigationFactory',
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'Balance\Controller\Home' => 'Balance\Controller\Home',
        ),
        'factories' => array(
            'Balance\Controller\Accounts' => function ($manager) {
                return new Controller\Controller($manager->getServiceLocator()->get('Balance\Model\Accounts'));
            },
            'Balance\Controller\Postings' => function ($manager) {
                return new Controller\Controller($manager->getServiceLocator()->get('Balance\Model\Postings'));
            },
        ),
    ),

    'service_manager' => array(
        'invokables' => array(
            'Balance\Model\Persistence\Db\Accounts' => 'Balance\Model\Persistence\Db\Accounts',
            'Balance\Model\Persistence\Db\Postings' => 'Balance\Model\Persistence\Db\Postings',
        ),
        'factories' => array(
            'Balance\Model\Accounts' => function ($manager) {
                // Dependências
                $form        = $manager->get('FormElementManager')->get('Balance\Form\Accounts');
                $filter      = $manager->get('InputFilterManager')->get('Balance\InputFilter\Accounts');
                $persistence = $manager->get('Balance\Model\Persistence\Db\Accounts');
                // Configurações
                $form->setInputFilter($filter);
                // Camada de Modelo
                return new Model\Model($form, $persistence);
            },
            'Balance\Model\Postings' => function ($manager) {
                // Dependências
                $form        = $manager->get('FormElementManager')->get('Balance\Form\Postings');
                $filter      = $manager->get('InputFilterManager')->get('Balance\InputFilter\Postings');
                $persistence = $manager->get('Balance\Model\Persistence\Db\Postings');
                // Configurações
                $form->setInputFilter($filter);
                // Camada de Modelo
                return new Model\Model($form, $persistence);
            },

            'Balance\Db\TableGateway\Accounts' => function ($manager) {
                $table = new Db\TableGateway\TableGateway('accounts', $manager->get('db'));
                $table->getFeatureSet()
                    ->addFeature(new Db\TableGateway\Feature\SequenceFeature('id', 'accounts_id_seq'));
                return $table;
            },
            'Balance\Db\TableGateway\Postings' => function ($manager) {
                $table = new Db\TableGateway\TableGateway('postings', $manager->get('db'));
                $table->getFeatureSet()
                    ->addFeature(new Db\TableGateway\Feature\SequenceFeature('id', 'postings_id_seq'));
                return $table;
            },
        ),
    ),

    'view_manager' => array(
        'doctype' => 'HTML5',

        'display_exceptions'       => true,
        'display_not_found_reason' => true,

        'not_found_template' => 'error/404',
        'exception_template' => 'error/500',

        'template_map' => array(
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'error/404'     => __DIR__ . '/../view/error/404.phtml',
            'error/500'     => __DIR__ . '/../view/error/500.phtml',
        ),

        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
