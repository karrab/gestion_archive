<?php
declare(strict_types=1);

/**
 * En-tête commun pour tous les PDF : logo + établissement en haut à gauche.
 */
function pdf_header_html(array $parametres, string $titre): string
{
    $logo = BASE_PATH . '/images/' . ($parametres['logo'] ?? 'logo.png');
    $logoSrc = is_file($logo) ? $logo : '';

    return '
    <table style="width:100%; border-bottom:2px solid #0d3b66; margin-bottom:10px;">
      <tr>
        <td style="width:80px;">' . ($logoSrc ? '<img src="' . $logoSrc . '" height="60">' : '') . '</td>
        <td style="text-align:right; font-family: DejaVu Sans, sans-serif; direction:rtl;">
          <strong style="font-size:14px;">' . htmlspecialchars($parametres['nom_etablissement'] ?? '') . '</strong><br>
          <span style="font-size:10px;">' . htmlspecialchars($parametres['adresse'] ?? '') . '</span><br>
          <span style="font-size:10px;">
            ' . htmlspecialchars($parametres['tel_fixe'] ?? '') . ' |
            ' . htmlspecialchars($parametres['fax'] ?? '') . ' |
            ' . htmlspecialchars($parametres['email'] ?? '') . '
          </span>
        </td>
      </tr>
    </table>
    <h4 style="text-align:center; font-family: DejaVu Sans, sans-serif;">' . htmlspecialchars($titre) . '</h4>
    ';
}
