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

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 *
 * @secured
 */
class AccountsPresenter extends AdminPresenter
{


	/** @var AccountsTableFactory */
	private $accountsTableFactory;


	/**
	 * @param AccountsTableFactory $accountsTableFactory
	 */
	public function inject(AccountsTableFactory $accountsTableFactory)
	{
		$this->accountsTableFactory = $accountsTableFactory;
	}


	protected function createComponentTable()
	{
		$control = $this->accountsTableFactory->invoke();
		return $control;
	}

}
