<?php
/**
 *
 * pHKondo : pHKondo software for condominium property managers (http://phalkaline.eu)
 * Copyright (c) pHAlkaline . (http://phalkaline.eu)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @copyright     Copyright (c) pHAlkaline . (http://phalkaline.eu)
 * @link          http://phkondo.net pHKondo Project
 * @package       app.Controller
 * @since         pHKondo v 0.0.1
 * @license       http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 */

App::uses('AppController', 'Controller');

/**
 * Movements Controller
 *
 * @property Movement $Movement
 * @property PaginatorComponent $Paginator
 */
class MovementsController extends AppController {

    /**
     * Components
     *
     * @var array
     */
    public $components = array('Paginator');

    /**
     * index method
     *
     * @return void
     */
    public function index() {
        
        $this->Paginator->settings = $this->Paginator->settings + array(
            'contain'=>array('MovementCategory','MovementOperation','MovementType','Account'=>array('fields'=>array('title','balance'))),
            'limit' => 100,
            'conditions' => array(
                'Movement.account_id' => $this->Session->read('Condo.Account.ViewID'),
                'Movement.fiscal_year_id' => $this->Session->read('Condo.FiscalYearID'))
        );
        $this->setFilter(array('Movement.description', 'Movement.amount', 'Movement.document', 'Movement.document', 'MovementCategory.name', 'MovementType.name', 'MovementOperation.name'));
        $this->set('movements', $this->Paginator->paginate('Movement'));
        $this->Session->delete('Condo.Movement');
    }

    /**
     * view method
     *
     * @throws NotFoundException
     * @param string $id
     * @return void
     */
    public function view($id = null) {
        if (!$this->Movement->exists($id)) {
            $this->Flash->error(__('Invalid movement'));
            $this->redirect(array('action' => 'index'));
        }
        $this->Movement->contain(array('MovementCategory','MovementOperation','MovementType','Account'=>array('fields'=>array('title')),'FiscalYear'=>array('fields'=>array('title'))));
        $options = array('conditions' => array(
                'Movement.' . $this->Movement->primaryKey => $id,
                'Movement.account_id' => $this->Session->read('Condo.Account.ViewID'),
                'Movement.fiscal_year_id' => $this->Session->read('Condo.FiscalYearID')));
        $movement = $this->Movement->find('first', $options);
        $this->set('movement', $movement);
        $this->Session->write('Condo.Movement.ViewID', $id);
        $this->Session->write('Condo.Movement.ViewName', $movement['Movement']['description']);
    }

    /**
     * add method
     *
     * @return void
     */
    public function add() {
        $closeMovement = $this->Movement->find('count', array('conditions' =>
            array('Movement.fiscal_year_id' => $this->Session->read('Condo.FiscalYearID'),
                'Movement.account_id' => $this->Session->read('Condo.Account.ViewID'),
                'Movement.movement_operation_id' => '2'),
                ));
        if ($closeMovement) {
            $this->Flash->error(__('No movements allowed'));
            $this->redirect(array('action' => 'index'));
        }

        if ($this->request->is('post')) {
            $this->Movement->create();
            if ($this->Movement->save($this->request->data)) {
                $this->Flash->success(__('The movement has been saved'));
                $this->redirect(array('action' => 'view', $this->Movement->id));
            } else {
                $this->Flash->error(__('The movement could not be saved. Please, try again.'));
            }
        }
        // is first movement for this Condo / FiscalYear ? Yes => movementType = Open , movementOperation = Open/Close
        $openMovement = $this->Movement->find('count', array('conditions' =>
            array('Movement.fiscal_year_id' => $this->Session->read('Condo.FiscalYearID'),
                'Movement.account_id' => $this->Session->read('Condo.Account.ViewID'),
                'Movement.movement_operation_id' => '1'),
                ));

        $accounts = $this->Movement->Account->find('list', array('conditions' => array('id' => $this->Session->read('Condo.Account.ViewID'))));
        $fiscalYears = $this->Movement->FiscalYear->find('list', array('conditions' => array('condo_id'=>$this->Session->read('Condo.ViewID'),'id' => $this->Session->read('Condo.FiscalYearID'))));

        $movementTypes = $this->Movement->MovementType->find('list', array('conditions' => array('active' => '1')));

        $movementCategories = $this->Movement->MovementCategory->find('list', array('conditions' => array('active' => '1')));

        if ($openMovement) {
            $movementOperations = $this->Movement->MovementOperation->find('list', array('conditions' => array('MovementOperation.id <>' => '1', 'active' => '1')));
        } else {
            $movementOperations = $this->Movement->MovementOperation->find('list', array('conditions' => array('MovementOperation.id' => '1', 'active' => '1')));
        }
        $this->set(compact('accounts', 'movementCategories', 'movementOperations', 'movementTypes', 'fiscalYears'));
    }

    /**
     * edit method
     *
     * @throws NotFoundException
     * @param string $id
     * @return void
     */
    public function edit($id = null) {

        if (!$this->Movement->exists($id)) {
            $this->Flash->error(__('Invalid movement'));
            $this->redirect(array('action' => 'index'));
        }
        if ($this->request->is('post') || $this->request->is('put')) {
            if ($this->Movement->save($this->request->data)) {
                $this->Flash->success(__('The movement has been saved'));
                //$this->redirect(array('action' => 'view', $id));
            } else {
                $this->Flash->error(__('The movement could not be saved. Please, try again.'));
            }
        } else {
            $options = array('conditions' => array(
                    'Movement.' . $this->Movement->primaryKey => $id,
                    'Movement.account_id' => $this->Session->read('Condo.Account.ViewID'),
                    'Movement.fiscal_year_id' => $this->Session->read('Condo.FiscalYearID')));
            $this->request->data = $this->Movement->find('first', $options);
        }
        $accounts = $this->Movement->Account->find('list', array('conditions' => array('id' => $this->Session->read('Condo.Account.ViewID'))));
        $fiscalYears = $this->Movement->FiscalYear->find('list', array('conditions' => array('condo_id'=>$this->Session->read('Condo.ViewID'),'id' => $this->Session->read('Condo.FiscalYearID'))));
        $movementTypes = $this->Movement->MovementType->find('list', array('conditions' => array('active' => '1')));
        $movementCategories = $this->Movement->MovementCategory->find('list', array('conditions' => array('active' => '1')));

        $openMovement = $this->request->data['Movement']['movement_operation_id'];
        if ($openMovement != 1) {
            $movementOperations = $this->Movement->MovementOperation->find('list', array('conditions' => array('MovementOperation.id <>' => '1', 'active' => '1')));
        } else {
            $movementOperations = $this->Movement->MovementOperation->find('list', array('conditions' => array('MovementOperation.id' => '1', 'active' => '1')));
        }

        $this->set(compact('accounts', 'movementCategories', 'movementOperations', 'movementTypes', 'fiscalYears'));
        $this->Session->write('Condo.Movement.ViewID', $id);
        $this->Session->write('Condo.Movement.ViewName', $this->request->data['Movement']['description']);
    }

    /**
     * delete method
     *
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     * @param string $id
     * @return void
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }
        $this->Movement->id = $id;
        if (!$this->Movement->exists()) {
            $this->Flash->error(__('Invalid movement'));
            $this->redirect(array('action' => 'index'));
        }

        $this->Movement->read();
        if ($this->Movement->data['Movement']['movement_operation_id'] != 1 && $this->Movement->delete()) {
            $this->Flash->success(__('Movement deleted'));
            $this->redirect(array('action' => 'index'));
        }
        $this->Flash->error(__('Movement can not be deleted'));
        $this->redirect(array('action' => 'view', $id));
    }

    public function beforeFilter() {
        parent::beforeFilter();
        if (!$this->Session->check('Condo.Account.ViewID') || !$this->Session->read('Condo.FiscalYearID')) {
            $this->Flash->error(__('Invalid account or fiscal year'));
            $this->redirect(array('controller'=>'accounts','action' => 'index'));
        }
    }

    public function beforeRender() {



        $breadcrumbs = array(
            array('link' => Router::url(array('controller' => 'pages', 'action' => 'index')), 'text' => __('Home'), 'active' => ''),
            array('link' => Router::url(array('controller' => 'condos', 'action' => 'index')), 'text' => __n('Condo','Condos',2), 'active' => ''),
            array('link' => Router::url(array('controller' => 'condos', 'action' => 'view', $this->Session->read('Condo.ViewID'))), 'text' => $this->Session->read('Condo.ViewName'), 'active' => ''),
            array('link' => Router::url(array('controller' => 'accounts', 'action' => 'index')), 'text' => __n('Account','Accounts',2), 'active' => ''),
            array('link' => Router::url(array('controller' => 'accounts', 'action' => 'view', $this->Session->read('Condo.Account.ViewID'))), 'text' => $this->Session->read('Condo.Account.ViewName'), 'active' => ''),
            array('link' => '', 'text' => __n('Movement','Movements',2), 'active' => 'active')
        );

        switch ($this->action) {
            case 'view':
                $breadcrumbs[5] = array('link' => Router::url(array('controller' => 'movements', 'action' => 'index')), 'text' => __n('Movement','Movements',2), 'active' => '');
                $breadcrumbs[6] = array('link' => '', 'text' => $this->Session->read('Condo.Movement.ViewName'), 'active' => 'active');
                break;
            case 'edit':
                $breadcrumbs[5] = array('link' => Router::url(array('controller' => 'movements', 'action' => 'index')), 'text' => __n('Movement','Movements',2), 'active' => '');
                $breadcrumbs[6] = array('link' => '', 'text' => $this->Session->read('Condo.Movement.ViewName'), 'active' => 'active');
                break;
        }
        $this->set(compact('breadcrumbs'));
    }

}
