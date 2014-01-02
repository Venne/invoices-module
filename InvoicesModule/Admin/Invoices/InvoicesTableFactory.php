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
use Nette\Application\Responses\FileResponse;
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
class InvoicesTableFactory extends BaseFactory
{


	/** @var InvoiceRepository */
	private $invoiceRepository;

	/** @var InvoiceFormFactory */
	private $invoiceFormFactory;

	/** @var InvoiceManager */
	private $invoiceManager;

	/** @var ITranslator */
	private $translator;


	/**
	 * @param InvoiceRepository $invoiceRepository
	 * @param InvoiceFormFactory $invoiceFormFactory
	 * @param InvoiceManager $invoiceManager
	 * @param ITranslator $translator
	 */
	public function __construct(InvoiceRepository $invoiceRepository, InvoiceFormFactory $invoiceFormFactory, InvoiceManager $invoiceManager, ITranslator $translator = NULL)
	{
		$this->invoiceRepository = $invoiceRepository;
		$this->invoiceFormFactory = $invoiceFormFactory;
		$this->invoiceManager = $invoiceManager;
		$this->translator = $translator;
	}


	public function invoke()
	{
		$translator = $this->translator;
		$admin = new AdminGrid($this->invoiceRepository);
		$table = $admin->getTable();
		$table->setTranslator($this->translator);
		$table->setDefaultSort(array('date' => 'DESC'));

		$table->addColumnText('identification', 'ID');
		$table->addColumnText('revisionCounter', 'Revision');
		$table->addColumnDate('date', 'Date');
		$table->addColumnText('supplier', 'Supplier')
			->setCustomRender(function (InvoiceEntity $invoiceEntity) {
				return $invoiceEntity->getSupplier()->getAccount()->getPerson();
			});
		$table->addColumnText('customer', 'Customer')
			->setCustomRender(function (InvoiceEntity $invoiceEntity) {
				return $invoiceEntity->getCustomer();
			});
		$table->addColumnText('amount', 'Amount');
		$table->addColumnText('state', 'State')
			->setCustomRender(function (InvoiceEntity $invoiceEntity) use ($translator) {
				$states = InvoiceEntity::getStates();
				return $translator->translate($states[$invoiceEntity->getState()]);
			});

		$table->addAction('generate', 'Generate ID')
			->setCustomRender(function (InvoiceEntity $invoiceEntity, $element) {
				if ($invoiceEntity->getIdentification()) {
					$element->class[] = 'disabled';
				}
				return $element;
			})
			->onClick[] = $this->generateClick;

		$table->addAction('generatePdf', 'Generate PDF')
			->onClick[] = $this->generatePdfClick;

		$table->addAction('downloadPdf', 'Download PDF')
			->setCustomRender(function (InvoiceEntity $invoiceEntity, $element) {
				if (!count($invoiceEntity->revisions)) {
					$element->class[] = 'disabled';
				}
				return $element;
			})
			->onClick[] = $this->downloadPdfClick;

		$table->addAction('show', 'Preview')
			->onClick[] = $this->showClick;

		$table->addAction('pdf', 'PDF')
			->onClick[] = $this->pdfClick;

		$table->addAction('edit', 'Edit')
			->getElementPrototype()->class[] = 'ajax';

		$form = $admin->createForm($this->invoiceFormFactory, 'Payment', NULL, \CmsModule\Components\Table\Form::TYPE_FULL);

		$admin->connectFormWithAction($form, $table->getAction('edit'), $admin::MODE_PLACE);

		// Toolbar
		$toolbar = $admin->getNavbar();
		$toolbar->addSection('new', 'Create', 'file');
		$admin->connectFormWithNavbar($form, $toolbar->getSection('new'), $admin::MODE_PLACE);

		$table->addAction('delete', 'Delete')
			->setCustomRender(function (InvoiceEntity $invoiceEntity, $element) {
				$element->class[] = 'ajax';
				if ($invoiceEntity->getState() !== InvoiceEntity::STATE_NEW) {
					$element->class[] = 'disabled';
				}
				return $element;
			});
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
		$invoice = $this->invoiceRepository->find($id);

		$account = $invoice->getSupplier()->getAccount();
		$person = $account->getPerson();
		$supplierBuilder = new ParticipantBuilder($person->getName(), $person->getStreet(), $person->getNumber(), $person->getCity(), $person->getZip());
		$supplierBuilder
			->setIn($person->getIdentificationNumber())
			->setTin($person->getTaxIdentificationNumber())
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
			->setIn($person->getIdentificationNumber())
			->setTin($person->getTaxIdentificationNumber())
			->setAccountNumber((string)$account)
			->setRegistration($person->getRegistration())
			->setVatPayer(FALSE)
			->build();

		$items = array();
		foreach ($invoice->getItems() as $item) {
			$items[] = new ItemImpl($item->name, $item->getUnits(), $item->getQUnit(), $item->getUnitValue(), TaxImpl::fromPercent($item->getTax()), $item->getUnitValueIsTaxed());
		}

		$dataBuilder = new DataBuilder($invoice->identification, 'Daňový doklad, č.', $supplier, $customer, $invoice->getExpirationDate(), $invoice->getDate(), $items);
		$dataBuilder
			->setVariableSymbol($invoice->getVariableSymbol())
			->setConstantSymbol($invoice->getConstantSymbol())
			->setSpecificSymbol($invoice->getSpecificSymbol())
			->setDateOfVatRevenueRecognition($invoice->getDate())
			->setStamp($invoice->getSupplier()->getAccount()->getPerson()->getSignature());
		$data = $dataBuilder->build();

		$control = new Eciovni($data);
		$control->setTranslator($this->translator);
		return $control;
	}


	public function generateClick($action, $id)
	{
		/** @var InvoiceEntity $invoice */
		$invoice = $this->invoiceRepository->find($id);
		$this->invoiceManager->generateIdentification($invoice);

		$action->grid->presenter->redirect('this');
	}


	public function generatePdfClick($action, $id)
	{
		/** @var InvoiceEntity $invoice */
		$invoice = $this->invoiceRepository->find($id);
		$this->invoiceManager->generateFile($invoice);

		$action->grid->presenter->redirect('this');
	}

	public function downloadPdfClick($action, $id)
	{
		/** @var InvoiceEntity $invoice */
		$invoice = $this->invoiceRepository->find($id);

		$action->grid->presenter->sendResponse(new FileResponse($invoice->revisions->first()->getFile()->getFilePath()));
	}
}
