<?php

namespace Lle\PdfGeneratorBundle\Generator;

use App\Service\Pdf\Pdf417;
use Lle\PdfGeneratorBundle\Lib\Pdf;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Finder\Finder;
use Dompdf\Dompdf;
use Lle\PdfGeneratorBundle\ObjAccess\Accessor;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TcpdfGenerator extends AbstractPdfGenerator
{

    protected $pdfPath;

    public function generate($source, $params, $savePath){
        $reflex = new \ReflectionClass($source);
        $pdf = $reflex->newInstance();
        if ($pdf instanceof Pdf) {
            $pdf->setData($params['vars']);
            $pdf->setRootPath($this->pdfPath);
            $pdf->initiate();
            $pdf->generate();
            $pdf->setTitle($pdf->title());
            $this->pdf = $pdf;
        } else {
            throw new \Exception('PDF GENERATOR ERROR: '.$source.' n\'est pas une class PDF');
        }
        $pdf->output($savePath, 'F');
    }

    public function getModelPath(string $pdfPath, string $modelPath): string{
        $this->pdfPath = $pdfPath;
        return $modelPath;
    }

    public function getName(): string{
        return 'tcpdf';
    }
}