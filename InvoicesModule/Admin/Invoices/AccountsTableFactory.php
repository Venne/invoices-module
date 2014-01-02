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

use CmsModule\Administration\Components\AdminGrid\AdminGrid;
use Nette\Application\Responses\TextResponse;
use Nette\Localization\ITranslator;
use OndrejBrejla\Eciovni\DataBuilder;
use OndrejBrejla\Eciovni\Eciovni;
use OndrejBrejla\Eciovni\ItemImpl;
use OndrejBrejla\Eciovni\ParticipantBuilder;
use OndrejBrejla\Eciovni\TaxImpl;
use Venne\BaseFactory;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class AccountsTableFactory extends BaseFactory
{


	/** @var AccountRepository */
	private $accountRepository;

	/** @var AccountFormFactory */
	private $accountFormFactory;

	/** @var ITranslator */
	private $translator;


	/**
	 * @param AccountRepository $accountRepository
	 * @param AccountFormFactory $accountFormFactory
	 * @param ITranslator $translator
	 */
	public function __construct(AccountRepository $accountRepository, AccountFormFactory $accountFormFactory, ITranslator $translator = NULL)
	{
		$this->accountRepository = $accountRepository;
		$this->accountFormFactory = $accountFormFactory;
		$this->translator = $translator;
	}


	public function invoke()
	{
		$admin = new AdminGrid($this->accountRepository);
		$table = $admin->getTable();
		$table->setTranslator($this->translator);

		$table->addColumnText('name', 'Name');

		$form = $admin->createForm($this->accountFormFactory, 'Payment', NULL, \CmsModule\Components\Table\Form::TYPE_FULL);

		$table->addAction('edit', 'Edit');
		$admin->connectFormWithAction($form, $table->getAction('edit'), $admin::MODE_PLACE);

		// Toolbar
		$toolbar = $admin->getNavbar();
		$toolbar->addSection('new', 'Create', 'file');
		$admin->connectFormWithNavbar($form, $toolbar->getSection('new'), $admin::MODE_PLACE);

		$table->addAction('delete', 'Delete')
			->getElementPrototype()->class[] = 'ajax';
		$admin->connectActionAsDelete($table->getAction('delete'));

		return $admin;
	}

	public function pdfClick($action, $id)
	{
		$presenter = $action->grid->presenter;
		$presenter['eciovni'] = $this->getInvoice($id);

		$mpdf = new \mPDF('utf-8');

		// Exportování připravené faktury do PDF.
		// Pro uložení faktury do souboru použijte druhý a třetí parametr, stejně jak je popsáno v dokumentaci k mPDF->Output().
		$presenter['eciovni']->exportToPdf($mpdf);
	}

	public function showClick($action, $id)
	{
		$presenter = $action->grid->presenter;

		ob_start();
		$presenter['eciovni'] = $this->getInvoice($id);
		$presenter['eciovni']->render();
		$data = ob_get_clean();

		$presenter->sendResponse(new TextResponse($data));
	}


	/**
	 * @param $id
	 * @return Eciovni
	 */
	public function getInvoice($id)
	{
		/** @var InvoiceEntity $invoice */
		$invoice = $this->accountRepository->find($id);

		$account = $invoice->getSupplier();
		$person = $account->getPerson();
		$supplierBuilder = new ParticipantBuilder($person->getName(), $person->getStreet(), $person->getNumber(), $person->getCity(), $person->getZip());
		$supplierBuilder
			->setIn($person->getIc())
			->setTin($person->getDic())
			->setAccountNumber((string)$account)
			->setBankName((string)$account->getBank())
			->setRegistration($person->getRegistration())
		 	->setPayment($invoice->getType())
			->setVatPayer($person->getTaxpayer());

		if ($person->getEmail()) {
			$supplierBuilder->addContact('email', $person->getEmail());
		}

		if ($person->getPhone()) {
			$supplierBuilder->addContact('phone', $person->getPhone());
		}

		if ($person->getFax()) {
			$supplierBuilder->addContact('fax', $person->getFax());
		}

		if ($person->getWebsite()) {
			$supplierBuilder->addContact('website', $person->getWebsite());
		}

		$supplier = $supplierBuilder->build();




		$person = $invoice->getCustomer();
		$customerBuilder = new ParticipantBuilder($person->getName(), $person->getStreet(), $person->getNumber(), $person->getCity(), $person->getZip());
		$customer = $customerBuilder
			->setIn($person->getIc())
			->setTin($person->getDic())
			->setAccountNumber((string)$account)
			->setRegistration($person->getRegistration())
			->setVatPayer(FALSE)
			->build();

		$items = array();
		foreach($invoice->getItems() as $item) {
			$items[] = new ItemImpl($item->name, $item->getUnits(), $item->getQUnits(), $item->getUnitValue(), TaxImpl::fromPercent($item->getTax()), $item->getUnitValueIsTaxed());
		}

		$dataBuilder = new DataBuilder($invoice->identification, 'Daňový doklad, č.', $supplier, $customer, $invoice->getExpirationDate(), $invoice->getDate(), $items);
		$dataBuilder
			->setVariableSymbol($invoice->getVariableSymbol())
			->setConstantSymbol($invoice->getConstantSymbol())
			->setSpecificSymbol($invoice->getSpecificSymbol())
			->setDateOfVatRevenueRecognition($invoice->getDate())
			->setStamp($invoice->getSupplier()->getPerson()->getSignature());
		$data = $dataBuilder->build();

		$control = new Eciovni($data);
		$control->setTranslator($this->translator);
		return $control;
	}
}
