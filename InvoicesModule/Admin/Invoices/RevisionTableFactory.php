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
class RevisionTableFactory extends BaseFactory
{


	/** @var RevisionRepository */
	private $revisionRepository;

	/** @var RevisionFormFactory */
	private $revisionFormFactory;

	/** @var ITranslator */
	private $translator;


	/**
	 * @param RevisionRepository $revisionRepository
	 * @param RevisionFormFactory $revisionFormFactory
	 * @param ITranslator $translator
	 */
	public function __construct(RevisionRepository $revisionRepository, RevisionFormFactory $revisionFormFactory, ITranslator $translator = NULL)
	{
		$this->revisionRepository = $revisionRepository;
		$this->revisionFormFactory = $revisionFormFactory;
		$this->translator = $translator;
	}


	public function invoke()
	{
		$admin = new AdminGrid($this->revisionRepository);
		$table = $admin->getTable();
		$table->setTranslator($this->translator);
		$table->setDefaultSort(array('date' => 'DESC'));

		$table->addColumnText('revision', 'Revision');
		$table->addColumnDate('date', 'Date');

		$table->addAction('edit', 'Edit')
			->getElementPrototype()->class[] = 'ajax';

		$form = $admin->createForm($this->revisionFormFactory, 'Revision');

		$admin->connectFormWithAction($form, $table->getAction('edit'));

		// Toolbar
		$toolbar = $admin->getNavbar();
		$toolbar->addSection('new', 'Create', 'file');
		$admin->connectFormWithNavbar($form, $toolbar->getSection('new'));

		$table->addAction('delete', 'Delete');
		$admin->connectActionAsDelete($table->getAction('delete'));

		return $admin;
	}


}
