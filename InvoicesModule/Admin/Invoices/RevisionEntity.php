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

use CmsModule\Content\Entities\FileEntity;
use Doctrine\ORM\Mapping as ORM;
use DoctrineModule\Entities\IdentifiedEntity;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 * @ORM\Entity(repositoryClass="\InvoicesModule\Admin\Invoices\RevisionRepository")
 * @ORM\Table(name="invoices_invoice_revision")
 */
class RevisionEntity extends IdentifiedEntity
{

	/**
	 * @var InvoiceEntity
	 * @ORM\ManyToOne(targetEntity="InvoiceEntity", inversedBy="revisions")
	 * @ORM\JoinColumn(onDelete="CASCADE")
	 */
	protected $invoice;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	protected $revision = 1;

	/**
	 * @var FileEntity
	 * @ORM\OneToOne(targetEntity="\CmsModule\Content\Entities\FileEntity", cascade={"all"}, orphanRemoval=true)
	 * @ORM\JoinColumn(onDelete="SET NULL")
	 */
	protected $file;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	protected $date;


	/**
	 * @param InvoiceEntity $invoice
	 * @param FileEntity $fileEntity
	 * @param $revision
	 */
	public function __construct(InvoiceEntity $invoice, FileEntity $fileEntity, $revision)
	{
		$this->invoice = $invoice;
		$this->setFile($fileEntity);
		$this->revision = $revision;
		$this->date = new \DateTime;
	}


	/**
	 * @return string
	 */
	public function __toString()
	{
		return (string)$this->revision;
	}


	/**
	 * @return int
	 */
	public function getRevision()
	{
		return $this->revision;
	}


	/**
	 * @return \CmsModule\Content\Entities\FileEntity
	 */
	public function getFile()
	{
		return $this->file;
	}


	/**
	 * @return \DateTime
	 */
	public function getDate()
	{
		return $this->date;
	}


	/**
	 * @param \CmsModule\Content\Entities\FileEntity $file
	 */
	private function setFile($file)
	{
		$this->file = $file;

		if ($this->file) {
			$this->file->setParent($this->invoice->dir);
			$this->file->setInvisible(TRUE);
		}
	}

}
