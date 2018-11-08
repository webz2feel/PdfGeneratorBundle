<?php

namespace Lle\PdfGeneratorBundle\Generator;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Finder\Finder;
use Dompdf\Dompdf;
use Lle\PdfGeneratorBundle\ObjAccess\Accessor;

class WordToPdfGenerator extends AbstractPdfGenerator
{

    const ITERABLE = 'iterable';
    const VARS = 'vars';
    private $twig;
    private $accessor;

    public function __construct(\Twig_Environment $twig, Accessor $accessor)
    {
        $this->twig = $twig;
        $this->accessor = $accessor;
    }

    public function handleTable($params, $templateProcessor) {
        for ($i = 1; $i <= count($params[self::ITERABLE]); $i++) {
            foreach ($params[self::ITERABLE]['table' . $i][0] as $key => $content) {
                $clonekey = $key;
            }
            $templateProcessor->cloneRow($clonekey, count($params[self::ITERABLE]['table' . $i]));
            foreach ($params[self::ITERABLE] as $table) {
                $k = 0;
                foreach($table as $var) {
                    $k++;
                    foreach ($var as $key => $content) {
                        $templateProcessor->setValue($key . '#' . $k, $content);
                    }
                }
            }
        }
    }

    public function handleVars($params, $templateProcessor) {
        foreach ($params[self::VARS] as $key => $content) {
            if (is_object($content) == true) {
                $this->accessor->access($key, $content, $templateProcessor);
            } else if (is_array($content) == false) {
                $templateProcessor->setValue($key, $content);
            } else {
                foreach ($content as $k => $c) {
                    $templateProcessor->setValue($key.'.'.$k, $c);
                }
            }
        }
    }

    public function wordToPdf($source, $params, $savePath)
    {
        $templateProcessor = new TemplateProcessor($source);
        \PhpOffice\PhpWord\Settings::setPdfRenderer("TCPDF", '../vendor/tecnickcom/tcpdf');
        if (array_key_exists(self::ITERABLE, $params)  ) {
            $this->handleTable($params, $templateProcessor);
        }
        if (array_key_exists(self::VARS, $params)) {
            if (array_key_exists(self::ITERABLE, $params)) {
                $this->handleVars($params, $templateProcessor);
            }
        }
        $templateProcessor->saveAs('TemplateTest.docx');
        $phpWord = \PhpOffice\PhpWord\IOFactory::load('TemplateTest.docx');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
        $objWriter->save($savePath);
        return new BinaryFileResponse($savePath);
    }

    public function generate($source, $params, $savePath){
        return $this->wordToPdf($source, $params, $savePath);
    }

    public function getName(): string{
        return 'word_to_pdf';
    }
}