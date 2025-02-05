<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\Reservation;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class InvoiceGenerator
{
    private string $invoicesDirectory;

    public function __construct(
        private Environment $twig,
        private EntityManagerInterface $entityManager,
        private InvoiceRepository $invoiceRepository
    ) {
        $this->invoicesDirectory = __DIR__ . '/../../public/uploads/invoices';
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->invoicesDirectory);
    }

    public function generatePdf(Reservation $reservation): string
    {
        // Vérifier si une facture existe déjà
        $existingInvoice = $this->entityManager->getRepository(Invoice::class)
            ->findOneBy(['reservation' => $reservation]);

        if ($existingInvoice) {
            $pdfPath = $this->invoicesDirectory . '/' . $existingInvoice->getFilename();
            if (file_exists($pdfPath)) {
                return file_get_contents($pdfPath);
            }
        }
        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        // Instantiate Dompdf
        $dompdf = new Dompdf($options);
        
        // Generate HTML content
        $html = $this->twig->render('pdf/invoice.html.twig', [
            'reservation' => $reservation,
            'date' => new \DateTime(),
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // Set paper size
        $dompdf->setPaper('A4', 'portrait');

        // Render the PDF
        $dompdf->render();

        // Return the generated PDF as a string
        $pdfContent = $dompdf->output();

        // Créer ou mettre à jour la facture
        if (!$existingInvoice) {
            $invoice = new Invoice();
            $invoice->setReservation($reservation);
            $invoice->setNumber($this->invoiceRepository->generateInvoiceNumber());
        } else {
            $invoice = $existingInvoice;
        }

        // Générer le nom du fichier
        $filename = sprintf('facture-%s.pdf', $invoice->getNumber());
        $invoice->setFilename($filename);

        // Sauvegarder le fichier
        file_put_contents($this->invoicesDirectory . '/' . $filename, $pdfContent);

        // Persister l'entité
        if (!$existingInvoice) {
            $this->entityManager->persist($invoice);
        }
        $this->entityManager->flush();

        return $pdfContent;
    }
}
