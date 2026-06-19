<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Base TCPDF avec en-tête (logo + établissement) et pied de page communs,
 * police aealarabiya pour un rendu arabe correctement formé (RTL).
 */
class TcpdfBase extends TCPDF
{
    private array $parametres;
    private string $titre;

    public function __construct(array $parametres, string $titre, string $orientation = 'P')
    {
        parent::__construct($orientation, 'mm', 'A4', true, 'UTF-8');
        $this->parametres = $parametres;
        $this->titre = $titre;

        $this->setRTL(true);
        $this->SetMargins(10, 32, 10);
        $this->SetHeaderMargin(5);
        $this->SetFooterMargin(10);
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);
        $this->SetAutoPageBreak(true, 18);
        $this->SetFont('aealarabiya', '', 10);
    }

    public function Header(): void
    {
        $logo = BASE_PATH . '/images/' . ($this->parametres['logo'] ?? 'logo.png');
        if (is_file($logo)) {
            $this->Image($logo, $this->getPageWidth() - 30, 8, 20);
        }
        $this->SetFont('aealarabiya', 'B', 13);
        $this->SetXY(10, 8);
        $this->Cell(0, 6, $this->parametres['nom_etablissement'] ?? '', 0, 1, 'R');
        $this->SetFont('aealarabiya', '', 9);
        $infos = trim(($this->parametres['adresse'] ?? '') . ' | ' .
            ($this->parametres['tel_fixe'] ?? '') . ' | ' .
            ($this->parametres['email'] ?? ''), ' |');
        $this->SetX(10);
        $this->Cell(0, 5, $infos, 0, 1, 'R');
        $this->SetFont('aealarabiya', 'B', 12);
        $this->SetX(10);
        $this->Cell(0, 8, $this->titre, 0, 1, 'C');
        $this->Line(10, $this->GetY() + 1, $this->getPageWidth() - 10, $this->GetY() + 1);
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('aealarabiya', '', 8);
        $this->Cell(0, 10, 'صفحة ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, false, 'C');
    }
}
