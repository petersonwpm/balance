<?php

namespace Balance\Model\Persistence\Db;

use ArrayIterator;
use Balance\Model\BooleanType;
use Balance\Model\ModelException;
use Balance\Module\ModuleInterface;
use Balance\Stdlib\Synchronizer;
use Exception;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Stdlib\Parameters;

/**
 * Camada de Persistência para Módulos
 */
class Modules implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Sincronizado?
     * @type bool
     */
    private $synchronized = false;

    /**
     * Marcar Para Sincronização
     *
     * Força que a próxima execução de sincronização seja executada, independente de outras sincronizações anteriores.
     *
     * @return Modules Próprio Objeto para Encadeamento
     */
    public function markToSynchronize()
    {
        $this->synchronized = false;
        return $this;
    }

    /**
     * Sincronizar Módulos no Banco de Dados
     *
     * Efetua a consulta de todos os módulos que estão instalados no projeto e sincroniza-os no banco de dados. Isto
     * possibilita que sejam criados outros recursos sobre os módulos, como configurações ou listas de controle de
     * acesso.
     *
     * @return Modules Próprio Objeto para Encadeamento
     */
    public function synchronize()
    {
        // Sincronizado e não forçar?
        if ($this->synchronized) {
            // Não precisamos sincronizar agora!
            return $this;
        }

        // Capturar Módulos Instalados
        // Gerenciador de Módulos
        $modules = $this->getServiceLocator()->get('ModuleManager')->getLoadedModules();
        // Captura
        $installed = [];
        foreach ($modules as $module) {
            if ($module instanceof ModuleInterface) {
                // Instalado!
                $installed[] = ['identifier' => $module->getIdentifier()];
            }
        }

        // Capturar Módulos no Banco de Dados
        // Banco de Dados
        $db = $this->getServiceLocator()->get('db');
        // Seletor
        $select = (new Select())
            ->from(['m' => 'modules'])
            ->columns(['identifier']);
        // Consulta
        $rowset = $db->query($select->getSqlString($db->getPlatform()))->execute();
        // Captura
        $persisted = [];
        foreach ($rowset as $row) {
            $persisted[] = ['identifier' => $row['identifier']];
        }

        // Sincronizar Informações
        $dataset = (new Synchronizer())
            ->setColumns(['identifier'])
            ->synchronize($persisted, $installed);

        // Tabela de Módulos
        $tbModules = $this->getServiceLocator()->get('Balance\Db\TableGateway\Modules');

        // Remover Antigos
        foreach ($dataset[Synchronizer::DELETE] as $data) {
            $tbModules->delete(function ($delete) use ($data) {
                $delete->where(function ($where) use ($data) {
                    $where->equalTo('identifier', $data['identifier']);
                });
            });
        }

        // Inserir Novos
        foreach ($dataset[Synchronizer::INSERT] as $data) {
            $tbModules->insert([
                'identifier' => $data['identifier'],
            ]);
        }

        // Sincronzizado!
        $this->synchronized = true;

        // Encadeamento
        return $this;
    }

    /**
     * Apresentação de Elementos
     *
     * Captura informações do projeto, verificando quais os módulos que estão disponíveis e apresentando informações
     * sobre o mesmo. Módulos instalados e não instalados podem ser filtrados.
     *
     * @param  Parameters  $params Parâmetros de Execução
     * @return Traversable Conjunto de Elementos Solicitados
     */
    public function fetch(Parameters $params)
    {
        // Sincronizar Módulos
        $this->synchronize();

        // Inicialização
        $result = [];
        // Gerenciador de Módulos
        $modules = $this->getServiceLocator()->get('ModuleManager')->getLoadedModules();
        // Captura
        foreach ($modules as $module) {
            // Tipagem Correta?
            if ($module instanceof ModuleInterface) {
                // Verificar Habilitado
                $enabled = $this->isEnabled($module);
                $capture = true;
                // Filtro de Habilitado?
                if ($params['enabled']) {
                    // Capturar Elemento?
                    $capture =
                        BooleanType::YES === $params['enabled'] && $enabled
                        || BooleanType::NO === $params['enabled'] && ! $enabled;
                }
                // Capturar?
                if ($capture) {
                    // Capturar Informações
                    $result[] = [
                        'identifier'  => $module->getIdentifier(),
                        'name'        => $module->getName(),
                        'description' => $module->getDescription(),
                        'enabled'     => $enabled,
                    ];
                }
            }
        }
        // Apresentação
        return new ArrayIterator($result);
    }

    /**
     * Módulo Habilitado?
     *
     * Verifica se o módulo apresentado está habilitado para execução no sistema. Captura as configurações do projeto e
     * apresenta uma confirmação de que o módulo solicitado foi habilitado pelo usuário no sistema.
     *
     * @return bool Confirmação Solicitada
     */
    public function isEnabled(ModuleInterface $module)
    {
        // Sincronizar Módulos
        $this->synchronize();
        // Limpeza PHPMD
        unset($module);

        // Todos os Módulos Habilitados
        return true;
    }

    /**
     * Habilitar Módulos
     *
     * @deprecated
     * @param      Parameters $data Dados para Salvamento
     * @return     Modules    Próprio Objeto para Encadeamento
     */
    public function save(Parameters $data)
    {
        // Sincronizar Módulos
        $this->synchronize();

        // Banco de Dados
        $db         = $this->getServiceLocator()->get('db');
        $connection = $db->getDriver()->getConnection();
        $tbModules  = $this->getServiceLocator()->get('Balance\Db\TableGateway\Modules');

        // Tratamento
        try {
            // Transação
            $connection->beginTransaction();
            // Desabilitar Todos
            $tbModules->update([
                'enabled' => 1,
            ]);
            // Processamento
            foreach ($data['modules'] as $identifier) {
                // Habilitar Módulo
                $tbModules->update([
                    'enabled' => 1,
                ], function ($where) use ($identifier) {
                    $where->equalTo('identifier', $identifier);
                });
            }
            // Finalização
            $connection->commit();
        } catch (Exception $e) {
            // Retorno
            $connection->rollback();
            // Apresentar Erro
            throw new ModelException('Database Error');
        }

        // Encadeamento
        return $this;
    }
}
