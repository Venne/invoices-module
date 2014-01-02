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

use Doctrine\ORM\Mapping as ORM;
use DoctrineModule\Entities\NamedEntity;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 * @ORM\Entity(repositoryClass="\InvoicesModule\Admin\Invoices\ItemRepository")
 * @ORM\Table(name="invoices_item")
 */
class ItemEntity extends NamedEntity
{

	/**
	 * @var InvoiceEntity
	 * @ORM\ManyToOne(targetEntity="InvoiceEntity", inversedBy="items")
	 * @ORM\JoinColumn(onDelete="CASCADE")
	 */
	protected $invoice;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	protected $units = 1;

	/**
	 * @var float
	 * @ORM\Column(type="decimal", precision=10, scale=2)
	 */
	protected $tax = 0.0;

	/**
	 * @var float
	 * @ORM\Column(type="decimal", precision=20, scale=5)
	 */
	protected $unitValue = 0.0;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	protected $qUnit = '';

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	protected $unitValueIsTaxed = TRUE;


	/**
	 * @param InvoiceEntity $invoice
	 */
	public function __construct(InvoiceEntity $invoice)
	{
		$this->invoice = $invoice;
	}


	/**
	 * @return InvoiceEntity
	 */
	public function getInvoice()
	{
		return $this->invoice;
	}


	/**
	 * @param string $qUnit
	 */
	public function setQUnit($qUnit)
	{
		$this->qUnit = $qUnit;
	}


	/**
	 * @return string
	 */
	public function getQUnit()
	{
		return $this->qUnit;
	}


	/**
	 * @param float $tax
	 */
	public function setTax($tax)
	{
		$this->tax = $tax;
	}


	/**
	 * @return float
	 */
	public function getTax()
	{
		return $this->tax;
	}


	/**
	 * @param float $unitValue
	 */
	public function setUnitValue($unitValue)
	{
		$this->unitValue = $unitValue;
	}


	/**
	 * @return float
	 */
	public function getUnitValue()
	{
		return $this->unitValue;
	}


	/**
	 * @param mixed $unitValueIsTaxed
	 */
	public function setUnitValueIsTaxed($unitValueIsTaxed)
	{
		$this->unitValueIsTaxed = $unitValueIsTaxed;
	}


	/**
	 * @return mixed
	 */
	public function getUnitValueIsTaxed()
	{
		return $this->unitValueIsTaxed;
	}


	/**
	 * @param int $units
	 */
	public function setUnits($units)
	{
		$this->units = $units;
	}


	/**
	 * @return int
	 */
	public function getUnits()
	{
		return $this->units;
	}

}
