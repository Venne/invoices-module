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
use Nette\Application\AbortException;
use Nette\Application\Application;
use Nette\Localization\ITranslator;
use Nette\Object;
use Nette\Utils\Strings;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class InvoiceManager extends Object
{

	/** @var string */
	private $dataDir;

	/** @var InvoiceRepository */
	private $invoiceRepository;

	/** @var Application */
	private $application;

	/** @var ITranslator */
	private $translator;


	/**
	 * @param $dataDir
	 * @param InvoiceRepository $invoiceRepository
	 * @param Application $application
	 * @param ITranslator $translator
	 */
	public function __construct($dataDir, InvoiceRepository $invoiceRepository, Application $application, ITranslator $translator = NULL)
	{
		$this->dataDir = $dataDir;
		$this->invoiceRepository = $invoiceRepository;
		$this->application = $application;
		$this->translator = $translator;

		if (!file_exists($this->dataDir)) {
			mkdir($this->dataDir);
		}
	}


	public function generateFile(InvoiceEntity $invoiceEntity)
	{
		$name = $invoiceEntity->getIdentification() . '_' . $invoiceEntity->date->format('Y-m-d') . '_' . count($invoiceEntity->revisions) . '.pdf';
		$file = $this->dataDir . '/' . $name;
		$html = $this->generateHtml($invoiceEntity);

		$mpdf = new \mPDF('utf-8');
		$mpdf->WriteHTML($html);
		$mpdf->Output($file, 'F');

		$fileEntity= new FileEntity;
		$fileEntity->setFile(new \SplFileInfo($file));

		$invoiceEntity->createRevision($fileEntity);
		$this->invoiceRepository->save($invoiceEntity);
		unlink($file);
	}


	public function generateHtml(InvoiceEntity $invoiceEntity)
	{
		/** @var NewsletterPresenter $presenter */
		$presenter = $this->application->getPresenterFactory()->createPresenter('Cms:Admin:Invoices');
		$presenter->autoCanonicalize = FALSE;

		$request = new \Nette\Application\Request('Cms:Admin:Invoices', 'GET', array(
			'table-table-actions-show-item' => $invoiceEntity->id,
			'do' => 'table-table-actions-show-click',
		));

		$response = $presenter->run($request);
		return (string)$response->getSource();
	}


	public function generateIdentification(InvoiceEntity $invoiceEntity)
	{
		$date = new \DateTime;
		$account = $invoiceEntity->getSupplier();
		$format = $account->getIdentificationFormat();
		$dateFrom = new \DateTime;

		if ($account->getIdentificationInterval() === AccountEntity::INTERVAL_YEAR) {
			$dateFrom->setTime(0, 0, 0);
			$dateFrom->setDate($dateFrom->format('Y'), 1, 1);

			$dateTo = \DateTime::createFromFormat('Y-m-d', (intval($dateFrom->format('Y')) + 1) . '-01-01');
			$dateTo->setTime(0, 0, 0);

		} elseif ($account->getIdentificationInterval() === AccountEntity::INTERVAL_MONTH) {
			$dateFrom->setTime(0, 0, 0);
			$dateFrom->setDate($dateFrom->format('Y'), $dateFrom->format('m'), 1);

			$year = intval($dateFrom->format('Y'));
			$month = intval($dateFrom->format('m')) + 1;
			if ($month > 12) {
				$month = $month - 12;
				$year++;
			}

			$dateTo = \DateTime::createFromFormat('Y-m-d', $year . '-' . $month . '-01');
			$dateTo->setTime(0, 0, 0);

		} elseif ($account->getIdentificationInterval() === AccountEntity::INTERVAL_QUARTER) {
			$month = intval($dateFrom->format('m'));
			$year = intval($dateFrom->format('Y'));

			$dateFrom->setTime(0, 0, 0);
			$dateFrom->setDate($year, ($month % 4) * 4, 0);

			$month = $month + 4;

			if ($month > 12) {
				$month = $month - 12;
				$year++;
			}

			$dateTo = \DateTime::createFromFormat('Y-m-d', $year . '-' . (($month % 4) * 4) . '-01');
			$dateTo->setTime(0, 0, 0);
		}

		$qb = $this->invoiceRepository->createQueryBuilder('a')
			->select('COUNT(a.id)')
			->andWhere('a.identification IS NOT NULL')
			->andWhere('a.supplier = :supplier')->setParameter('supplier', $account->id);

		if (isset($dateTo)) {
			$qb = $qb
				->andWhere('a.date >= :dateFrom')->setParameter('dateFrom', $dateFrom)
				->andWhere('a.date < :dateTo')->setParameter('dateTo', $dateTo);
		}

		$identification = intval($qb->getQuery()->getSingleScalarResult()) + 1;

		if (preg_match_all('/\?(\d+)/is', $format, $matches)) {
			$number = $matches[1][0];
			$format = str_replace('?' . $number, sprintf('%0' . $number . 'd', $identification), $format);
		}

		$exDate = clone $date;
		$exDate->modify('+' . $account->getDue() . ' days');

		$invoiceEntity->setDate($date);
		$invoiceEntity->setExpirationDate($exDate);
		$invoiceEntity->setIdentification($date->format($format));
		$this->invoiceRepository->save($invoiceEntity);
	}

}
