<?php

namespace Balance\Mvc\Controller;

use Balance\Mvc\Application;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http;
use Zend\Mvc\Router;
use Zend\ServiceManager\ServiceManager;

class ConfigsTest extends TestCase
{
    public function testIndex()
    {
        // Inicialização
        $element = new Configs();
        // Localizador de Serviços
        $serviceLocator = new ServiceManager();
        $element->setServiceLocator($serviceLocator);
        // I18n
        $serviceLocator->setService('i18n', Application::getApplication()->getServiceManager()->get('i18n'));
        // Configurar Parâmetros de Despacho
        $element->getEvent()->setRouteMatch(new Router\RouteMatch(array(
            'action' => 'index',
        )));
        // Execução
        $result = $element->dispatch(new Http\PhpEnvironment\Request());
        // Verificações
        $this->assertInstanceOf('Zend\View\Model\JsonModel', $result);
        $this->assertRegexp('/^\$.application.setConfigs/', $result->serialize());
    }

    public function testIndexWithInvalidRequest()
    {
        // Exceções Esperadas
        $this->setExpectedException('Exception', 'Invalid Request');
        // Inicialização
        $element = new Configs();
        // Configurar Parâmetros de Despacho
        $element->getEvent()->setRouteMatch(new Router\RouteMatch(array(
            'action' => 'index',
        )));
        // Execução
        $element->dispatch($this->getMock('Zend\Stdlib\RequestInterface'));
    }
}
