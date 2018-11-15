<?php

namespace Lle\PdfGeneratorBundle\Generator;

use Doctrine\ORM\EntityManagerInterface;
use Lle\PdfGeneratorBundle\Entity\PdfModel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdfGenerator
{

    private $em;
    private $parameterBag;
    private $kerne;
    private $generators = [];

    public function __construct(EntityManagerInterface $em, KernelInterface $kernel, ParameterBagInterface $parameterBag, iterable $pdfGenerators)
    {
        $this->em = $em;
        $this->parameterBag = $parameterBag;
        $this->kernel = $kernel;
        foreach($pdfGenerators as $pdfGenerator){
            $this->generators[$pdfGenerator->getName()] = $pdfGenerator;
        }
    }

    public function generate(string $code, array $parameters = []): \PDFMerger
    {
        $model = $this->em->getRepository(PdfModel::class)->findOneBy(['code' => $code]);
        if ($model == null) {
            throw new HttpException("no model found");
        }
        $generator = $this->generators[$model->getType() ?? $this->parameterBag->get('lle.pdf.default_generator')];
        $finder = new Finder();
        $pdf = new \PDFMerger();
        foreach($parameters as $parameter){
                $tmpFile = tempnam(sys_get_temp_dir(), 'tmp');
                $generator->generate($this->getPath() . $model->getPath(), [
                    WordToPdfGenerator::ITERABLE => [],
                    WordToPdfGenerator::VARS => $parameter
            ], $tmpFile);
            $pdf->addPDF($tmpFile, "all");
        }
        return $pdf;
    }

    public function generateResponse(string $code, array $parameters = []): BinaryFileResponse{
        $tmpFile = tempnam(sys_get_temp_dir(), 'tmp');
        $pdf = $this->generate($code, $parameters);
        $pdf->merge('file', $tmpFile);
        return new BinaryFileResponse($tmpFile);
    }

    public function getPath(): string
    {
        return $this->kernel->getRootDir().'/../'.$this->parameterBag->get('lle.pdf.path').'/';
    }
}