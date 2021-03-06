<?php

namespace BalanceTest\Model\Persistence\Db;

use Balance\Model\AccountType;
use Balance\Model\BooleanType;
use Balance\Model\Persistence\Db\Accounts;
use BalanceTest\Mvc\Application;
use Exception as BaseException;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Db\Sql\Sql;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\Parameters;

class AccountsTest extends TestCase
{
    protected function getPersistence()
    {
        // Inicialização
        $persistence = new Accounts();

        // Conta ZZ
        $elementZZ = [
            'name'        => 'ZZ Account Test',
            'type'        => AccountType::ACTIVE,
            'description' => 'Description',
            'position'    => 1,
            'accumulate'  => 0,
        ];
        // Conta AA
        $elementAA = [
            'name'        => 'AA Account Test',
            'type'        => AccountType::ACTIVE,
            'description' => 'Description',
            'position'    => 0,
            'accumulate'  => 0,
        ];

        // Localizador de Serviços
        $serviceLocator = new ServiceManager();
        // Configurações
        $persistence->setServiceLocator($serviceLocator);

        // Banco de Dados
        $db = Application::getApplication()->getServiceManager()->get('db');
        // Configurações
        $serviceLocator->setService('db', $db);

        // Tabela de Contas
        $tbAccounts = Application::getApplication()->getServiceManager()->get('Balance\Db\TableGateway\Accounts');
        // Configurações
        $serviceLocator->setService('Balance\Db\TableGateway\Accounts', $tbAccounts);

        // Remover Todos os Lançamentos
        $delete = (new Sql($db))->delete()
            ->from('postings');
        // Execução
        $db->query($delete->getSqlString($db->getPlatform()))->execute();
        // Remover Todas as Contas
        $delete = (new Sql($db))->delete()
            ->from('accounts');
        // Execução
        $db->query($delete->getSqlString($db->getPlatform()))->execute();

        // Preparar Inserção
        $insert = (new Sql($db))->insert()
            ->into('accounts')
            ->columns(['name', 'type', 'description', 'position', 'accumulate']);

        // Adicionar Conta ZZ
        $insert->values($elementZZ);
        // Execução
        $db->query($insert->getSqlString($db->getPlatform()))->execute();

        // Adicionar Conta AA
        $insert->values($elementAA);
        // Execução
        $db->query($insert->getSqlString($db->getPlatform()))->execute();

        // Consultar as Duas Chaves Primárias
        $select = (new Sql($db))->select()
            ->from('accounts')
            ->columns(['id', 'name']);
        $rowset = $db->query($select->getSqlString($db->getPlatform()))->execute();
        // Consulta
        foreach ($rowset as $row) {
            switch ($row['name']) {
                case $elementAA['name']:
                    $elementAA['id'] = (int) $row['id'];
                    break;
                case $elementZZ['name']:
                    $elementZZ['id'] = (int) $row['id'];
                    break;
            }
        }
        // Configurar Elementos
        $this->data = [$elementAA, $elementZZ];

        // Apresentação
        return $persistence;
    }

    public function testFetch()
    {
        // Inicialização
        $persistence           = $this->getPersistence();
        $accountTypeDefinition = (new AccountType())->getDefinition();

        // Consulta
        $result = $persistence->fetch(new Parameters());

        // Verificações
        $this->assertInstanceOf('Traversable', $result);
        $this->assertCount(2, $result);
        // Capturar Primeira Posição
        $element = $result->current();
        // Verificações
        $this->assertInternalType('array', $element);
        $this->assertArrayHasKey('id', $element);
        $this->assertInternalType('int', $element['id']);
        $this->assertArrayHasKey('name', $element);
        $this->assertEquals('AA Account Test', $element['name']);
        $this->assertArrayHasKey('type', $element);
        $this->assertEquals($accountTypeDefinition[AccountType::ACTIVE], $element['type']);
        // Próximo Elemento
        $result->next();
        // Capturar Segunda Posição
        $element = $result->current();
        // Verificações
        $this->assertInternalType('array', $element);
        $this->assertArrayHasKey('id', $element);
        $this->assertInternalType('int', $element['id']);
        $this->assertArrayHasKey('name', $element);
        $this->assertEquals('ZZ Account Test', $element['name']);
        $this->assertArrayHasKey('type', $element);
        $this->assertEquals($accountTypeDefinition[AccountType::ACTIVE], $element['type']);
    }

    public function testFetchWithType()
    {
        // Inicialização
        $persistence = $this->getPersistence();

        // Consulta
        $result = $persistence->fetch(new Parameters(['type' => AccountType::ACTIVE]));

        // Verificações
        $this->assertInstanceOf('Traversable', $result);
        $this->assertCount(2, $result);

        // Consulta
        $result = $persistence->fetch(new Parameters(['type' => AccountType::PASSIVE]));

        // Verificações
        $this->assertInstanceOf('Traversable', $result);
        $this->assertCount(0, $result);
    }

    public function testFetchWithKeywords()
    {
        // Inicialização
        $persistence = $this->getPersistence();

        // Consulta
        $result = $persistence->fetch(new Parameters(['keywords' => 'AA']));

        // Verificações
        $this->assertInstanceOf('Traversable', $result);
        $this->assertCount(1, $result);

        // Consulta
        $result = $persistence->fetch(new Parameters(['keywords' => 'Account Test']));

        // Verificações
        $this->assertInstanceOf('Traversable', $result);
        $this->assertCount(2, $result);

        // Consulta
        $result = $persistence->fetch(new Parameters(['keywords' => 'FOOBAR']));

        // Verificações
        $this->assertInstanceOf('Traversable', $result);
        $this->assertCount(0, $result);

        // Consulta
        $result = $persistence->fetch(new Parameters(['keywords' => 'Description']));

        // Verificações
        $this->assertInstanceOf('Traversable', $result);
        $this->assertCount(2, $result);
    }

    public function testFind()
    {
        // Inicialização
        $persistence = $this->getPersistence();

        // Primeiro Elemento
        $elementA = array_shift($this->data);
        $elementB = array_shift($this->data);

        // Processamento
        foreach ([$elementA, $elementB] as $element) {
            // Consulta
            $result = $persistence->find(new Parameters(['id' => $element['id']]));

            // Verificações
            $this->assertInstanceOf('ArrayAccess', $result);
            $this->assertArrayHasKey('id', $result);
            $this->assertEquals($element['id'], $result['id']);
            $this->assertArrayHasKey('type', $result);
            $this->assertEquals($element['type'], $result['type']);
            $this->assertArrayHasKey('name', $result);
            $this->assertEquals($element['name'], $result['name']);
            $this->assertArrayHasKey('description', $result);
            $this->assertEquals($element['description'], $result['description']);
            $this->assertArrayHasKey('accumulate', $result);
            $this->assertEquals(BooleanType::NO, $result['accumulate']);
        }
    }

    public function testFindWithoutPrimaryKey()
    {
        // Erro Esperado
        $this->setExpectedException('Balance\Model\ModelException', 'Unknown Primary Key');

        // Inicialização
        $persistence = $this->getPersistence();

        // Consulta
        $persistence->find(new Parameters());
    }

    public function testFindWithUnknownPrimaryKey()
    {
        // Erro Esperado
        $this->setExpectedException('Balance\Model\ModelException', 'Unknown Element');

        // Inicialização
        $persistence = $this->getPersistence();

        // Capturar Elementos
        $elementA = array_shift($this->data);
        $elementB = array_shift($this->data);
        // Gerar uma Chave Primária Desconhecida
        do {
            // Chave Randômica
            $id = rand();
        } while ($id === $elementA['id'] || $id === $elementB['id']);

        // Consulta
        $persistence->find(new Parameters(['id' => $id]));
    }

    public function testSaveWithInsert()
    {
        // Inicialização
        $persistence = $this->getPersistence();

        // Dados
        $data = new Parameters([
            'type'        => AccountType::PASSIVE,
            'name'        => 'FB Account Test',
            'description' => 'Description of the Account',
            'accumulate'  => BooleanType::YES,
        ]);

        // Salvar Informações
        $result = $persistence->save($data);

        // Verificação
        $this->assertSame($persistence, $result);
        $this->assertInternalType('int', $data['id']);

        // Consulta
        $result = $persistence->find(new Parameters(['id' => $data['id']]));

        // Verificação
        $this->assertNotEmpty($result);
    }

    public function testSaveWithInsertWithException()
    {
        // Erro Esperado
        $this->setExpectedException('Balance\Model\ModelException', 'Database Error');

        // Inicialização
        $persistence = $this->getPersistence();

        // Dados
        $data = new Parameters([
            'type'        => 'UNKNOWN',
            'name'        => 'FB Account Test',
            'description' => 'Description of the Account',
            'accumulate'  => BooleanType::YES,
        ]);

        $persistence->save($data);
    }

    public function testSaveWithUpdate()
    {
        // Inicialização
        $persistence = $this->getPersistence();

        // Capturar Elemento
        $element = array_shift($this->data);

        // Dados
        $data = new Parameters([
            'id'          => $element['id'],
            'type'        => AccountType::PASSIVE,
            'name'        => 'Another Name',
            'description' => 'Another Description',
            'accumulate'  => BooleanType::NO,
        ]);

        // Salvar Informações
        $result = $persistence->save($data);

        // Verificação
        $this->assertSame($persistence, $result);

        // Consulta
        $result = $persistence->find(new Parameters(['id' => $data['id']]));

        // Verificação
        $this->assertNotEmpty($result);
        $this->assertEquals($data['type'], $result['type']);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals($data['description'], $result['description']);
        $this->assertEquals($data['accumulate'], $result['accumulate']);
    }

    public function testRemove()
    {
        // Inicialização
        $persistence = $this->getPersistence();

        // Capturar Elementos
        $elementA = array_shift($this->data);
        $elementB = array_shift($this->data);

        // Remoção
        $result = $persistence->remove(new Parameters(['id' => $elementA['id']]));

        // Verificação
        $this->assertSame($persistence, $result);

        // Consulta
        $result = $persistence->fetch(new Parameters());

        // Verificação
        $this->assertCount(1, $result);

        // Remoção
        $persistence->remove(new Parameters(['id' => $elementB['id']]));

        // Consulta
        $result = $persistence->fetch(new Parameters());

        // Verificação
        $this->assertCount(0, $result);
    }

    public function testRemoveWithoutPrimaryKey()
    {
        // Erro Esperado
        $this->setExpectedException('Balance\Model\ModelException', 'Unknown Primary Key');

        // Inicialização
        $persistence = $this->getPersistence();

        // Remoção
        $persistence->remove(new Parameters());
    }

    public function testRemoveUnknownElement()
    {
        // Erro Esperado
        $this->setExpectedException('Balance\Model\ModelException', 'Database Error');

        // Inicialização
        $persistence = $this->getPersistence();

        // Capturar Elementos
        $elementA = array_shift($this->data);
        $elementB = array_shift($this->data);
        // Gerar uma Chave Primária Desconhecida
        do {
            // Chave Randômica
            $id = rand();
        } while ($id === $elementA['id'] || $id === $elementB['id']);

        // Remoção
        $persistence->remove(new Parameters(['id' => $id]));
    }

    public function testGetValueOptions()
    {
        // Inicialização
        $persistence = $this->getPersistence();

        // Verificação de Tipagem
        $this->assertInstanceOf('Balance\Model\Persistence\ValueOptionsInterface', $persistence);

        // Consulta
        $result = $persistence->getValueOptions();

        // Consultar Resultados
        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);
        // Capturar Primeiro Conjunto
        $result = array_shift($result);
        // Capturar Primeira Posição
        $element = array_shift($result['options']);
        // Verificação
        $this->assertEquals('AA Account Test', $element);
        // Capturar Segunda Posição
        $element = array_shift($result['options']);
        // Verificação
        $this->assertEquals('ZZ Account Test', $element);
    }

    public function testGetValueOptionsByPosition()
    {
        // Inicialização
        $persistence = $this->getPersistence();

        // Conta NN Passiva
        $data = new Parameters([
            'type'        => AccountType::PASSIVE,
            'name'        => 'MM Account Test',
            'description' => '',
            'accumulate'  => BooleanType::NO,
        ]);
        // Salvar
        $persistence->save($data);

        // Conta MM Ativa
        $data = new Parameters([
            'type'        => AccountType::ACTIVE,
            'name'        => 'NN Account Test',
            'description' => '',
            'accumulate'  => BooleanType::NO,
        ]);
        // Salvar
        $persistence->save($data);

        // Consulta
        $result = $persistence->getValueOptions();

        // Contabilização
        $this->assertCount(2, $result);

        // Definições
        $definition = (new AccountType())->getDefinition();

        // Capturar Primeiro Elemento
        $element = current($result);
        // Verificações
        $this->assertEquals($definition[AccountType::ACTIVE], $element['label']);
        $this->assertEquals([
            'AA Account Test',
            'NN Account Test',
            'ZZ Account Test',
        ], array_values($element['options']));

        // Capturar Segundo Elemento
        $element = next($result);
        // Verificações
        $this->assertEquals($definition[AccountType::PASSIVE], $element['label']);
        $this->assertEquals(['MM Account Test'], array_values($element['options']));
    }

    public function testOrderToBegin()
    {
        // Camada de Persistência
        $persistence = $this->getPersistence();

        // Capturar Elementos
        $elementA = array_shift($this->data);
        $elementB = array_shift($this->data);

        // Colocar na Primeira Posição
        $result = $persistence->order(new Parameters([
            'id' => $elementB['id'],
        ]));

        // Verificações
        $this->assertSame($persistence, $result);

        // Consulta
        $element = $persistence->find(new Parameters(['id' => $elementB['id']]));

        // Verificações
        $this->assertEquals($elementB['id'], $element['id']);

        // Colocar na Primeira Posição
        $result = $persistence->order(new Parameters([
            'id' => $elementA['id'],
        ]));

        // Consulta
        $element = $persistence->find(new Parameters(['id' => $elementA['id']]));

        // Verificações
        $this->assertEquals($elementA['id'], $element['id']);
    }

    public function testOrderWithSamePosition()
    {
        // Camada de Persistência
        $persistence = $this->getPersistence();

        // Capturar Elementos
        $elementA = array_shift($this->data);

        // Colocar no Início (já está)
        $persistence->order(new Parameters([
            'id' => $elementA['id'],
        ]));

        // Consulta
        $result = $persistence->fetch(new Parameters());

        // Capturar Primeiro Elemento
        $element = $result->current();

        // Verificações
        $this->assertEquals($elementA['id'], $element['id']);

        // Colocar o A no mesmo Lugar
        $persistence->order(new Parameters([
            'id'       => $elementA['id'],
            'previous' => $elementA['id'],
        ]));

        // Consulta
        $result = $persistence->fetch(new Parameters());

        // Capturar Primeiro Elemento
        $element = $result->current();

        // Verificações
        $this->assertEquals($elementA['id'], $element['id']);
    }

    public function testOrderWithUnknownElement()
    {
        // Erro Esperado
        $this->setExpectedException('Balance\Model\ModelException', 'Unknown Element');

        // Camada de Persistência
        $persistence = $this->getPersistence();

        // Capturar Elementos
        $elementA = array_shift($this->data);
        $elementB = array_shift($this->data);
        // Gerar uma Chave Primária Desconhecida
        do {
            // Chave Randômica
            $id = rand();
        } while ($id === $elementA['id'] || $id === $elementB['id']);

        // Colocar no Início
        $persistence->order(new Parameters(['id' => $id]));
    }

    public function testOrderWithDatabaseError()
    {
        // Erro Esperado
        $this->setExpectedException('Balance\Model\ModelException', 'Database Error');

        // Camada de Persistência
        $persistence = $this->getPersistence();

        // Criar um Mock para Tabela de Contas
        $tbAccounts = $this->getMockBuilder('Zend\Db\TableGateway\TableGateway')
            ->disableOriginalConstructor()
            ->getMock();

        // Configurações
        $persistence->getServiceLocator()
            ->setAllowOverride(true)
            ->setService('Balance\Db\TableGateway\Accounts', $tbAccounts)
            ->setAllowOverride(false);

        // Gerar o Erro
        $tbAccounts
            ->method('update')
            ->will($this->throwException(new BaseException('Internal Error')));

        // Capturar Elementos
        $elementA = array_shift($this->data);
        $elementB = array_shift($this->data);

        // Ordenar Elementos
        $persistence->order(new Parameters([
            'id'       => $elementA['id'],
            'previous' => $elementB['id'],
        ]));
    }

    public function testOrderWithThreeElements()
    {
        // Camada de Persistência
        $persistence = $this->getPersistence();

        // Novo Elemento
        $data = new Parameters([
            'type'        => AccountType::PASSIVE,
            'name'        => 'Another Name',
            'description' => 'Another Description',
            'accumulate'  => BooleanType::NO,
        ]);

        // Salvar
        $persistence->save($data);

        // Capturar Elementos
        $elementA = array_shift($this->data);
        $elementB = array_shift($this->data);

        // Trocar Posições
        $persistence->order(new Parameters([
            'id'       => $elementA['id'],
            'previous' => $data['id'],
        ]));

        // Consulta
        $result = $persistence->fetch(new Parameters());

        // Capturar Elemento
        $element = $result->current();
        // Verificações
        $this->assertEquals($elementB['name'], $element['name']);

        // Próximo Elemento
        $result->next();
        // Capturar Elemento
        $element = $result->current();
        // Verificações
        $this->assertEquals($data['name'], $element['name']);

        // Próximo Elemento
        $result->next();
        // Capturar Elemento
        $element = $result->current();
        // Verificações
        $this->assertEquals($elementA['name'], $element['name']);
    }

    public function testFindWithAfterFindEvent()
    {
        // Inicialização
        $persistence = $this->getPersistence();
        $container   = new Parameters();

        // Evento: Carregar Elemento com Dados Adicionais
        $persistence->getEventManager()
            ->attach('afterFind', function () use ($container) {
                // Adicionar Parâmetros
                $container['foo'] = 'bar';
            });

        // Consulta de Elemento
        $persistence->find(new Parameters(['id' => $this->data[0]['id']]));

        // Verificações
        $this->assertEquals('bar', $container['foo']);
    }
}
