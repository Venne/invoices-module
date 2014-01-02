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
use CmsModule\Content\Entities\FileEntity;
use DirectoryModule\Admin\Directory\PersonEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use DoctrineModule\Entities\IdentifiedEntity;
use Nette\Utils\Strings;
use PaymentsModule\Admin\Payments\PaymentEntity;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 * @ORM\Entity(repositoryClass="\InvoicesModule\Admin\Invoices\InvoiceRepository")
 * @ORM\Table(name="invoices_invoice")
 */
class InvoiceEntity extends IdentifiedEntity
{

	const STATE_NEW = 'new';

	const STATE_UNPAID = 'unpaid';

	const STATE_PAID = 'paid';

	const STATE_CANCELED = 'canceled';

	const TYPE_CASH = 'cash';

	const TYPE_TRANSFER = 'transfer';


	/** @var array */
	private static $states = array(
		self::STATE_NEW => 'new',
		self::STATE_UNPAID => 'unpaid',
		self::STATE_PAID => 'paid',
		self::STATE_CANCELED => 'canceled',
	);

	/** @var array */
	private static $types = array(
		self::TYPE_TRANSFER => 'transfer',
		self::TYPE_CASH => 'cash',
	);


	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	protected $state = self::STATE_NEW;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	protected $type = self::TYPE_TRANSFER;

	/**
	 * @var RevisionEntity[]
	 * @ORM\OneToMany(targetEntity="RevisionEntity", mappedBy="invoice", cascade={"persist"}, indexBy="revision")
	 * @ORM\OrderBy({"revision" = "DESC"})
	 */
	protected $revisions;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	protected $revisionCounter = 0;

	/**
	 * @var AccountEntity
	 * @ORM\ManyToOne(targetEntity="AccountEntity")
	 */
	protected $supplier;

	/**
	 * @var PersonEntity
	 * @ORM\ManyToOne(targetEntity="\DirectoryModule\Admin\Directory\PersonEntity")
	 */
	protected $customer;

	/**
	 * @var PaymentEntity
	 * @ORM\ManyToOne(targetEntity="\PaymentsModule\Admin\Payments\PaymentEntity")
	 */
	protected $payment;

	/**
	 * @var ItemEntity[]
	 * @ORM\OneToMany(targetEntity="ItemEntity", mappedBy="invoice", cascade={"persist"})
	 */
	protected $items;

	/**
	 * @var int
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $identification;

	/**
	 * @var float
	 * @ORM\Column(type="decimal", precision=20, scale=5)
	 */
	protected $amount = 0.0;

	/**
	 * @var int
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $constantSymbol;

	/**
	 * @var int
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $variableSymbol;

	/**
	 * @var int
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $specificSymbol;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $date;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $expirationDate;

	/**
	 * @var DirEntity
	 * @ORM\OneToOne(targetEntity="\CmsModule\Content\Entities\DirEntity", cascade={"all"})
	 * @ORM\JoinColumn(onDelete="SET NULL")
	 */
	protected $dir;


	public function __construct()
	{
		$this->items = new ArrayCollection;
		$this->revisions = new ArrayCollection;
		$this->items[] = new ItemEntity($this);

		$this->dir = new DirEntity;
		$this->dir->setInvisible(TRUE);
		$this->dir->setName(Strings::webalize(get_class($this)) . Strings::random());
	}


	/**
	 * @param FileEntity $fileEntity
	 * @return RevisionEntity
	 */
	public function createRevision(FileEntity $fileEntity)
	{
		$this->revisionCounter++;
		$this->revisions[$this->revisionCounter] = $revision = new RevisionEntity($this, $fileEntity, $this->revisionCounter);
		return $revision;
	}


	/**
	 * @return int
	 */
	public function getRevisionCounter()
	{
		return $this->revisionCounter;
	}


	/**
	 * @return string
	 */
	public function __toString()
	{
		if (!$this->identification) {
			return 'new';
		}

		return (string)$this->identification . ' (' . $this->getDate()->format('Y-m-d H:i:s') . ')';
	}


	/**
	 * @return \CmsModule\Content\Entities\DirEntity
	 */
	public function getDir()
	{
		return $this->dir;
	}


	/**
	 * @param \InvoicesModule\Admin\Invoices\RevisionEntity[] $revisions
	 */
	public function setRevisions($revisions)
	{
		$this->revisions = $revisions;
	}


	/**
	 * @return \InvoicesModule\Admin\Invoices\RevisionEntity[]
	 */
	public function getRevisions()
	{
		return $this->revisions;
	}


	/**
	 * @param int $identification
	 */
	public function setIdentification($identification)
	{
		$this->identification = $identification ? $identification : NULL;
	}


	/**
	 * @return int
	 */
	public function getIdentification()
	{
		return $this->identification;
	}


	/**
	 * @param \InvoicesModule\Admin\Invoices\ItemEntity[] $items
	 */
	public function setItems($items)
	{
		$this->items = $items;
	}


	/**
	 * @return \InvoicesModule\Admin\Invoices\ItemEntity[]
	 */
	public function getItems()
	{
		return $this->items;
	}


	/**
	 * @param AccountEntity $supplier
	 */
	public function setSupplier(AccountEntity $supplier)
	{
		if ($this->supplier !== $supplier) {
			$this->dir->setParent($supplier->getDir());
		}

		$this->supplier = $supplier;
	}


	/**
	 * @return AccountEntity
	 */
	public function getSupplier()
	{
		return $this->supplier;
	}


	/**
	 * @param PersonEntity $customer
	 */
	public function setCustomer(PersonEntity $customer)
	{
		$this->customer = $customer;
	}


	/**
	 * @return PersonEntity
	 */
	public function getCustomer()
	{
		return $this->customer;
	}


	/**
	 * @param PaymentEntity $payment
	 */
	public function setPayment(PaymentEntity $payment = NULL)
	{
		$this->payment = $payment;
	}


	/**
	 * @return PaymentEntity
	 */
	public function getPayment()
	{
		return $this->payment;
	}


	/**
	 * @param float $amount
	 */
	public function setAmount($amount)
	{
		$this->amount = $amount;
	}


	/**
	 * @return float
	 */
	public function getAmount()
	{
		return $this->amount;
	}


	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}


	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}


	/**
	 * @param string $state
	 */
	public function setState($state)
	{
		$this->state = $state;
	}


	/**
	 * @return string
	 */
	public function getState()
	{
		return $this->state;
	}


	/**
	 * @param int $constantSymbol
	 */
	public function setConstantSymbol($constantSymbol)
	{
		$this->constantSymbol = $constantSymbol == '' ? NULL : $constantSymbol;
	}


	/**
	 * @return int
	 */
	public function getConstantSymbol()
	{
		return $this->constantSymbol;
	}


	/**
	 * @param int $specificSymbol
	 */
	public function setSpecificSymbol($specificSymbol)
	{
		$this->specificSymbol = $specificSymbol == '' ? NULL : $specificSymbol;
	}


	/**
	 * @return int
	 */
	public function getSpecificSymbol()
	{
		return $this->specificSymbol;
	}


	/**
	 * @param int $variableSymbol
	 */
	public function setVariableSymbol($variableSymbol)
	{
		$this->variableSymbol = $variableSymbol == '' ? NULL : $variableSymbol;
	}


	/**
	 * @return int
	 */
	public function getVariableSymbol()
	{
		return $this->variableSymbol;
	}


	/**
	 * @param \DateTime $date
	 */
	public function setDate(\DateTime $date)
	{
		$this->date = $date;
	}


	/**
	 * @return \DateTime
	 */
	public function getDate()
	{
		return $this->date;
	}


	/**
	 * @param \DateTime $expirationDate
	 */
	public function setExpirationDate(\DateTime $expirationDate)
	{
		$this->expirationDate = $expirationDate;
	}


	/**
	 * @return \DateTime
	 */
	public function getExpirationDate()
	{
		return $this->expirationDate;
	}


	/**
	 * @return array
	 */
	public static function getStates()
	{
		return self::$states;
	}


	/**
	 * @return array
	 */
	public static function getTypes()
	{
		return self::$types;
	}

}
