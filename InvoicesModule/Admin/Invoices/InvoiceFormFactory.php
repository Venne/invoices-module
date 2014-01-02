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
class InvoiceFormFactory extends FormFactory
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
		$entity = $form->data;

		$form->addGroup('Supplier');
		$form->addManyToOne('supplier', 'person')
			->addRule($form::FILLED);

		$form->addGroup('Customer');
		$form->addManyToOne('customer', 'person')
			->addRule($form::FILLED);

		$form->addGroup('Billing information');
		$form->addText('identification', 'Identification');
		$form->addText('amount', 'Amount')
			->addRule($form::FILLED)
			->addRule($form::FLOAT);
		$form->addManyToOne('payment', 'Payment');
		$form->addSelect('type', 'Type')->setItems(InvoiceEntity::getTypes());
		$form->addSelect('state', 'State')->setItems(InvoiceEntity::getStates());

		$form->addText('constantSymbol', 'Constant sb.');
		$form->addText('variableSymbol', 'Variable sb.');
		$form->addText('specificSymbol', 'Specific sb.');

		$group = $form->addGroup('Items');
		$recipients = $form->addMany('items', function (Container $container) use ($group, $form) {
			$container->setCurrentGroup($group);
			$container->addText('name', 'Name')
				->addRule($form::FILLED);
			$container->addText('unitValue', 'Unit value')
				->addRule($form::FILLED)
				->addRule($form::FLOAT);
			$container->addText('units', 'Units')
				->addRule($form::FILLED)
				->addRule($form::INTEGER);
			$container->addText('qUnit', 'Quantity unit')
				->addRule($form::FILLED);
			$container->addText('tax', 'Tax')
				->addRule($form::FLOAT);
			$container->addCheckbox('unitValueIsTaxed', 'Value is taxed');

			$container->addSubmit('remove', 'Remove')
				->addRemoveOnClick();
		}, function () use ($entity) {
			return new ItemEntity($entity);
		});
		$recipients->setCurrentGroup($group);
		$recipients->addSubmit('add', 'Add item')
			->addCreateOnClick();

		$form->setCurrentGroup();
		$form->addSaveButton('Save');
	}

}
