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
use Venne\Forms\Form;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class RevisionFormFactory extends FormFactory
{

	protected function getControlExtensions()
	{
		return array_merge(parent::getControlExtensions(), array(
			new ControlExtension,
			new \CmsModule\Content\Forms\ControlExtensions\ControlExtension,
		));
	}


	/**
	 * @param Form $form
	 */
	public function configure(Form $form)
	{
		$form->addFileEntityInput('file', 'File');

		$form->addSaveButton('Save');
	}

}
