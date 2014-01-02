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

use DoctrineModule\Forms\FormFactory;
use FormsModule\ControlExtensions\ControlExtension;
use Venne\Forms\Container;
use Venne\Forms\Form;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class AccountFormFactory extends FormFactory
{

	protected function getControlExtensions()
	{
		return array_merge(parent::getControlExtensions(), array(
			new ControlExtension,
		));
	}


	/**
	 * @param Form $form
	 */
	public function configure(Form $form)
	{
		$form->addText('name', 'Name');
		$form->addManyToOne('account', 'Account')
			->addRule($form::FILLED);

		$form->addText('identificationFormat', 'ID format')
			->addRule($form::FILLED);
		$form->addSelect('identificationInterval', 'ID interval', AccountEntity::getIntervals())
			->addRule($form::FILLED);

		$form->addText('due', 'Invoice due')
			->addRule($form::FILLED)
			->addRule($form::INTEGER);

		$form->setCurrentGroup();
		$form->addSaveButton('Save');
	}

}
