<?php

namespace app\controllers;

use app\models\Transactions;
use lithium\action\DispatchException;

class TransactionsController extends \lithium\action\Controller {

	public function index() {
		$transactions = Transactions::all();
		return compact('transactions');
	}

	public function view() {
		$transaction = Transactions::first($this->request->id);
		return compact('transaction');
	}

	public function add() {
		$transaction = Transactions::create();

		if (($this->request->data) && $transaction->save($this->request->data)) {
			return $this->redirect(array('Transactions::view', 'args' => array($transaction->_id)));
		}
		return compact('transaction');
	}

	public function edit() {
		$transaction = Transactions::find($this->request->id);

		if (!$transaction) {
			return $this->redirect('Transactions::index');
		}
		if (($this->request->data) && $transaction->save($this->request->data)) {
			return $this->redirect(array('Transactions::view', 'args' => array($transaction->id)));
		}
		return compact('transaction');
	}

	public function delete() {
		if (!$this->request->is('post') && !$this->request->is('delete')) {
			$msg = "Transactions::delete can only be called with http:post or http:delete.";
			throw new DispatchException($msg);
		}
		Transactions::find($this->request->id)->delete();
		return $this->redirect('Transactions::index');
	}
}

?>