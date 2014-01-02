<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace InvoicesModule\Admin\Invoices;

use CmsModule\Administration\AdminPresenter;
use CmsModule\Pages\Users\UserEntity;
use Doctrine\ORM\QueryBuilder;
use Grido\DataSources\Doctrine;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 *
 * @secured
 */
class InvoicesPresenter extends AdminPresenter
{

	/** @persistent */
	public $account;

	/** @persistent */
	public $invoice;

	/** @var InvoicesTableFactory */
	private $invoicesTableFactory;

	/** @var RevisionTableFactory */
	private $revisionTableFactory;

	/** @var AccountRepository */
	private $accountRepository;

	/** @var InvoiceRepository */
	private $invoiceRepository;

	/** @var RevisionRepository */
	private $revisionRepository;


	/**
	 * @param InvoicesTableFactory $invoicesTableFactory
	 * @param RevisionTableFactory $revisionTableFactory
	 * @param AccountRepository $accountRepository
	 * @param InvoiceRepository $invoiceRepository
	 * @param RevisionRepository $revisionRepository
	 */
	public function inject(
		InvoicesTableFactory $invoicesTableFactory,
		RevisionTableFactory $revisionTableFactory,
		AccountRepository $accountRepository,
		InvoiceRepository $invoiceRepository,
		RevisionRepository $revisionRepository
	)
	{
		$this->invoicesTableFactory = $invoicesTableFactory;
		$this->revisionTableFactory = $revisionTableFactory;
		$this->accountRepository = $accountRepository;
		$this->invoiceRepository = $invoiceRepository;
		$this->revisionRepository = $revisionRepository;
	}


	public function startup()
	{
		parent::startup();

		if (!$this->user->identity instanceof UserEntity) {
			$this->flashMessage($this->translator->translate('Please log in as regular user'), 'warning');
			return;
		}

		if (!$this->account) {
			$accounts = $this->getAccounts();

			if (!count($accounts)) {
				$this->flashMessage($this->translator->translate('No accounts found'), 'warning');
			} else {
				$this->account = $accounts[0]->id;
			}
		}
	}


	protected function createComponentTable()
	{
		$_this = $this;
		$control = $this->invoicesTableFactory->invoke();
		$control->getTable()->setModel(new Doctrine($this->getInvoicesQb()));

		$table = $control->getTable();
		$table->addAction('open', 'Open')->onClick[] = function ($action, $id) use ($_this) {
			$_this->redirect('this', array('invoice' => $id));
		};

		return $control;
	}


	protected function createComponentRevisionTable()
	{
		$control = $this->revisionTableFactory->invoke();
		$control->getTable()->setModel(new Doctrine($this->getRevisionsQb()));
		return $control;
	}


	/**
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getAccounts()
	{
		return $this->accountRepository->createQueryBuilder('a')
			->leftJoin('a.account', 'c')
			->leftJoin('c.person', 'p')
			->leftJoin('p.users', 'u')
			->andWhere('u.id = :id')->setParameter('id', $this->user->identity->id)
			->getQuery()->getResult();
	}


	/**
	 * @return AccountEntity
	 */
	public function getCurrentAccount()
	{
		return $this->accountRepository->find($this->account);
	}


	/**
	 * @return InvoiceEntity
	 */
	public function getCurrentInvoice()
	{
		return $this->invoiceRepository->find($this->invoice);
	}


	/**
	 * @return QueryBuilder
	 */
	public function getInvoicesQb()
	{
		return $this->invoiceRepository->createQueryBuilder('a')
			->andWhere('a.supplier = :account')->setParameter('account', $this->getCurrentAccount()->id);
	}


	/**
	 * @return QueryBuilder
	 */
	public function getRevisionsQb()
	{
		return $this->revisionRepository->createQueryBuilder('a')
			->andWhere('a.invoice = :invoice')->setParameter('invoice', $this->getCurrentInvoice()->id);
	}

}
