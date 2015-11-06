<?php

namespace Balance\Model\Persistence\Db;

use Balance\Model\ModelException;
use Balance\Model\Persistence\PersistenceInterface;
use Balance\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Stdlib\Parameters;

/**
 * Camada de Modelo de Banco de Dados para Lançamentos
 */
class Postings implements ServiceLocatorAwareInterface, PersistenceInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function fetch(Parameters $params)
    {
        // Resultado Inicial
        $result = array();
        // Adaptador de Banco de Dados
        $db = $this->getServiceLocator()->get('db');
        // Seletor
        $select = (new Select())
            ->from(array('p' => 'postings'))
            ->columns(array('id', 'datetime', 'description'));
        // Pesquisa: Palavras-Chave
        if ($params['keywords']) {
            $select->where(function ($where) use ($params) {
                $where->expression('"p"."description" ILIKE ?', '%' . $params['keywords'] . '%');
            });
        }
        // Pesquisa: Data e Hora Inicial
        if ($params['datetime_begin']) {
            // Filtrar Valor
            $datetime = date('Y-m-d H:i:s', strtotime($params['datetime_begin']));
            // Filtro
            $select->where(function ($where) use ($datetime) {
                $where->greaterThanOrEqualTo('p.datetime', $datetime);
            });
        }
        // Pesquisa: Data e Hora Final
        if ($params['datetime_end']) {
            // Filtrar Valor
            $datetime = date('Y-m-d H:i:s', strtotime($params['datetime_end']));
            // Filtro
            $select->where(function ($where) use ($datetime) {
                $where->lessThanOrEqualTo('p.datetime', $datetime);
            });
        }
        // Consulta
        $rowset = $db->query($select->getSqlString($db->getPlatform()))->execute();
        // Captura
        foreach ($rowset as $row) {
            $result[] = array(
                'id'          => (int) $row['id'],
                'datetime'    => date('d/m/Y H:i:s', strtotime($row['datetime'])),
                'description' => $row['description'],
            );
        }
        // Apresentação
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function find(Parameters $params)
    {
        // Chave Primária?
        if (! $params['id']) {
            throw new ModelException('Unknown Primary Key');
        }
        // Adaptador de Banco de Dados
        $db = $this->getServiceLocator()->get('db');
        // Seletor
        $select = (new Select())
            ->from(array('p' => 'postings'))
            ->columns(array('id', 'datetime', 'description'))
            ->where(function ($where) use ($params) {
                $where->equalTo('p.id', (int) $params['id']);
            });
        // Consulta
        $row = $db->query($select->getSqlString($db->getPlatform()))->execute()->current();
        // Encontrado?
        if (! $row) {
            throw new ModelException('Unknown Element');
        }
        // Configurações
        $element = array(
            'id'          => (int) $row['id'],
            'datetime'    => date('d/m/Y H:i:s', strtotime($row['datetime'])),
            'description' => $row['description'],
        );
        // Apresentação
        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Parameters $data)
    {
        var_dump($data);exit();
        // Inicialização
        $tbPostings = $this->getServiceLocator()->get('Balance\Db\TableGateway\Postings');
        // Conversão para Banco de Dados
        $datetime = date('Y-m-d H:i:s', strtotime($data['datetime']));
        // Chave Primária?
        if ($data['id']) {
            // Atualizar Elemento
            $tbPostings->update(array(
                'datetime'    => $datetime,
                'description' => $data['description'],
            ), function ($where) use ($data) {
                $where->equalTo('id', $data['id']);
            });
        } else {
            // Inserir Elemento
            $tbPostings->insert(array(
                'datetime'    => $datetime,
                'description' => $data['description'],
            ));
            // Chave Primária
            $data['id'] = (int) $tbPostings->getLastInsertValue();
        }
        // Encadeamento
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Parameters $params)
    {
        // Chave Primária?
        if (! $params['id']) {
            throw new ModelException('Unknwon Primary Key');
        }
        // Inicialização
        $tbPostings = $this->getServiceLocator()->get('Balance\Db\TableGateway\Postings');
        // Remover Elemento
        $count = $tbPostings->delete(function ($delete) use ($params) {
            $delete->where(function ($where) use ($params) {
                $where->equalTo('id', $params['id']);
            });
        });
        // Sucesso?
        if ($count !== 1) {
            throw new ModelException('Unknown Element');
        }
        // Encadeamento
        return $this;
    }
}
