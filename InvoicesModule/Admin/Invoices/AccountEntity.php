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

use CmsModule\Content\Entities\DirEntity;
use Doctrine\ORM\Mapping as ORM;
use DoctrineModule\Entities\NamedEntity;
use Nette\Utils\Strings;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 * @ORM\Entity(repositoryClass="\InvoicesModule\Admin\Invoices\AccountRepository")
 * @ORM\Table(name="invoices_account")
 */
class AccountEntity extends NamedEntity
{

	const INTERVAL_INFINITY = 'infinity';

	const INTERVAL_YEAR = 'year';

	const INTERVAL_MONTH = 'month';

	const INTERVAL_QUARTER = 'quarter';

	/** @var array */
	protected static $intervals = array(
		self::INTERVAL_INFINITY => self::INTERVAL_INFINITY,
		self::INTERVAL_YEAR => self::INTERVAL_YEAR,
		self::INTERVAL_MONTH => self::INTERVAL_MONTH,
		self::INTERVAL_QUARTER => self::INTERVAL_QUARTER,
	);

	/**
	 * @var \PaymentsModule\Admin\Payments\AccountEntity
	 * @ORM\ManyToOne(targetEntity="\PaymentsModule\Admin\Payments\AccountEntity")
	 */
	protected $account;

	/**
	 * @var DirEntity
	 * @ORM\OneToOne(targetEntity="\CmsModule\Content\Entities\DirEntity", cascade={"all"})
	 * @ORM\JoinColumn(onDelete="SET NULL")
	 */
	protected $dir;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	protected $identificationFormat = 'Y?4';

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	protected $identificationInterval = self::INTERVAL_YEAR;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	protected $due = 14;


	public function __construct()
	{
		$this->dir = new DirEntity;
		$this->dir->setInvisible(TRUE);
		$this->dir->setName(Strings::webalize(get_class($this)) . Strings::random());
	}


	/**
	 * @return \CmsModule\Content\Entities\DirEntity
	 */
	public function getDir()
	{
		return $this->dir;
	}


	/**
	 * @param \PaymentsModule\Admin\Payments\AccountEntity $account
	 */
	public function setAccount($account)
	{
		$this->account = $account;
	}


	/**
	 * @return \PaymentsModule\Admin\Payments\AccountEntity
	 */
	public function getAccount()
	{
		return $this->account;
	}


	/**
	 * @param string $identificationFormat
	 */
	public function setIdentificationFormat($identificationFormat)
	{
		$this->identificationFormat = $identificationFormat;
	}


	/**
	 * @return string
	 */
	public function getIdentificationFormat()
	{
		return $this->identificationFormat;
	}


	/**
	 * @param mixed $identificationInterval
	 */
	public function setIdentificationInterval($identificationInterval)
	{
		$this->identificationInterval = $identificationInterval;
	}


	/**
	 * @return mixed
	 */
	public function getIdentificationInterval()
	{
		return $this->identificationInterval;
	}


	/**
	 * @param int $due
	 */
	public function setDue($due)
	{
		$this->due = $due;
	}


	/**
	 * @return int
	 */
	public function getDue()
	{
		return $this->due;
	}


	/**
	 * @return array
	 */
	public static function getIntervals()
	{
		return self::$intervals;
	}

}
