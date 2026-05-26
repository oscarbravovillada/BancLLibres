<?php
// vendor/PdfGenerator.php
// Genera els tres tipus d'albarà (préstec, devolució, incidència)
// Usa FPDF manual (sense Composer) — FPDF no suporta UTF-8, usem iconv

require_once __DIR__ . '/fpdf/fpdf.php';

class PdfGenerator {

    // Colors corporatius (RGB)
    private const C_BLAU_FOSC  = [26,  35, 126];   // #1a237e
    private const C_BLAU       = [21, 101, 192];   // #1565c0
    private const C_BLAU_CLAR  = [232,234,246];   // #e8eaf6
    private const C_GRIS_CLAR  = [248,249,250];   // #f8f9fa
    private const C_GRIS_LINIA = [220,220,220];   // #dcdcdc
    private const C_TEXT       = [ 33, 33,  33];   // #212121
    private const C_BLANC      = [255,255,255];
    private const C_VERD       = [ 27,124, 76];   // #1b7c4c
    private const C_VERMELL    = [198, 40, 40];   // #c62828

    // Text → ISO-8859-1 (requerit per FPDF)
    private static function u(string $t): string {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $t) ?: $t;
    }

    private static function truncar(string $t, int $max): string {
        return mb_strlen($t) > $max ? mb_substr($t, 0, $max - 1) . '...' : $t;
    }

    // ----------------------------------------------------------------
    // ALBARÀ DE PRÉSTEC
    // ----------------------------------------------------------------
    public static function albaraPrestec(array $d): string {
        $pdf = self::iniciar();

        self::capcalera($pdf, 'ALBARÀ DE PRÉSTEC', $d['data'] ?? date('d/m/Y H:i'), 'prestec');
        self::blocAlumne($pdf, $d['alumne'], $d['classe'] ?? '', $d['tutor'] ?? '');

        if (!empty($d['lot_codi'])) {
            self::blocLot($pdf, $d['lot_codi']);
        }

        // Taula d'exemplars
        self::titolSeccio($pdf, 'Exemplars prestats');
        self::capTaulaExemplars($pdf, false);

        $fila = 0;
        foreach ($d['exemplars'] as $ex) {
            self::filaTaulaExemplars($pdf, $ex['codi'], $ex['titol'], $ex['materia'],
                ucfirst($ex['estat_inicial']), $ex['desperfectes_inici'] ?? '', $fila++);
        }

        $pdf->Ln(10);
        self::notaConformitat($pdf, 'prestec');
        self::zonaFirmes($pdf, $d['responsable'] ?? '');

        return self::desar($pdf, 'prestec', $d['alumne_id']);
    }

    // ----------------------------------------------------------------
    // ALBARÀ DE DEVOLUCIÓ
    // ----------------------------------------------------------------
    public static function albaraDevolucio(array $d): string {
        $pdf = self::iniciar();

        self::capcalera($pdf, 'ALBARÀ DE DEVOLUCIÓ', $d['data'] ?? date('d/m/Y H:i'), 'devolucio');
        self::blocAlumne($pdf, $d['alumne'], $d['classe'] ?? '', $d['tutor'] ?? '');

        if (!empty($d['retornats'])) {
            self::titolSeccio($pdf, 'Exemplars retornats');
            self::capTaulaExemplars($pdf, true);
            $fila = 0;
            foreach ($d['retornats'] as $ex) {
                self::filaTaulaDevolucio($pdf, $ex, $fila++);
            }
            $pdf->Ln(6);
        }

        if (!empty($d['no_retornats'])) {
            self::titolSeccio($pdf, 'Exemplars NO retornats', self::C_VERMELL);
            self::capTaulaNoRetornats($pdf);
            $fila = 0;
            foreach ($d['no_retornats'] as $ex) {
                self::filaTaulaNoRetornats($pdf, $ex, $fila++);
            }
            $pdf->Ln(6);
        }

        if (!empty($d['pendents'])) {
            self::titolSeccio($pdf, 'Exemplars pendents de retorn', [180, 100, 0]);
            self::capTaulaPendents($pdf);
            $fila = 0;
            foreach ($d['pendents'] as $ex) {
                self::filaTaulaPendents($pdf, $ex, $fila++);
            }
            $pdf->Ln(6);
        }

        self::notaConformitat($pdf, 'devolucio');
        self::zonaFirmes($pdf, $d['responsable'] ?? '');

        return self::desar($pdf, 'devolucio', $d['alumne_id']);
    }

    // ----------------------------------------------------------------
    // ALBARÀ D'INCIDÈNCIA
    // ----------------------------------------------------------------
    public static function alaraIncidencia(array $d): string {
        $pdf = self::iniciar();

        self::capcalera($pdf, "ALBARÀ D'INCIDÈNCIA", $d['data'] ?? date('d/m/Y H:i'), 'incidencia');
        self::blocAlumne($pdf, $d['alumne'], $d['classe'] ?? '', $d['tutor'] ?? '');

        // Bloc exemplar afectat
        self::titolSeccio($pdf, 'Exemplar afectat');

        $y = $pdf->GetY();
        [$r,$g,$b] = self::C_GRIS_CLAR;
        $pdf->SetFillColor($r,$g,$b);
        [$r,$g,$b] = self::C_GRIS_LINIA;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->Rect(15, $y, 180, 22, 'DF');

        $pdf->SetFont('Helvetica', 'B', 9);
        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);

        $pdf->SetXY(18, $y + 3);
        $pdf->Cell(30, 5, self::u('Codi:'), 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(55, 5, self::u($d['exemplar_codi']), 0, 0);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(20, 5, self::u('Matèria:'), 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, self::u($d['materia']), 0, 1);

        $pdf->SetXY(18, $y + 10);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(30, 5, self::u('Títol:'), 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, self::u(self::truncar($d['exemplar_titol'], 70)), 0, 1);

        $pdf->SetY($y + 25);
        $pdf->Ln(4);

        // Nota incidència
        $pdf->SetFont('Helvetica', 'I', 8);
        [$r,$g,$b] = self::C_VERMELL;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->MultiCell(0, 5, self::u(
            'Nota: Aquest albarà acredita la incidència registrada sobre l\'exemplar indicat. ' .
            'Conserveu una còpia signada com a justificant.'
        ), 0, 'L');

        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->Ln(6);

        self::zonaFirmes($pdf, $d['responsable'] ?? '');

        return self::desar($pdf, 'incidencia', $d['alumne_id']);
    }

    // ================================================================
    // MÈTODES PRIVATS DE CONSTRUCCIÓ
    // ================================================================

    private static function iniciar(): FPDF {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();
        return $pdf;
    }

    // ---------------------------------------------------------------
    // Capçalera del document
    // ---------------------------------------------------------------
    private static function capcalera(FPDF $pdf, string $titolDoc, string $data, string $tipus): void {
        // Banda superior blava
        [$r,$g,$b] = self::C_BLAU_FOSC;
        $pdf->SetFillColor($r,$g,$b);
        $pdf->Rect(0, 0, 210, 22, 'F');

        // Nom de l'aplicació
        $pdf->SetFont('Helvetica', 'B', 15);
        [$r,$g,$b] = self::C_BLANC;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetXY(15, 5);
        $pdf->Cell(100, 7, self::u(APP_NAME), 0, 0, 'L');

        // Any escolar (dreta)
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(110, 5);
        $pdf->Cell(85, 7, self::u('Curs ' . ANY_ESCOLAR), 0, 0, 'R');

        // Centre
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetXY(15, 13);
        $pdf->Cell(100, 5, self::u('IES PORÇONS'), 0, 0, 'L');

        $pdf->SetY(26);

        // Títol del document
        $pdf->SetFont('Helvetica', 'B', 13);
        [$r,$g,$b] = self::C_BLAU;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->Cell(0, 8, self::u($titolDoc), 0, 1, 'C');

        // Data a la dreta
        $pdf->SetFont('Helvetica', '', 8);
        [$r,$g,$b] = [120,120,120];
        $pdf->SetTextColor($r,$g,$b);
        $pdf->Cell(0, 5, self::u('Generat el: ' . $data), 0, 1, 'R');

        // Línia separadora
        [$r,$g,$b] = self::C_BLAU;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);

        // Restaurar
        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
        [$r,$g,$b] = self::C_GRIS_LINIA;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->SetLineWidth(0.2);
    }

    // ---------------------------------------------------------------
    // Bloc dades de l'alumne
    // ---------------------------------------------------------------
    private static function blocAlumne(FPDF $pdf, string $alumne, string $classe, string $tutor): void {
        $y = $pdf->GetY();

        // Fons
        [$r,$g,$b] = self::C_BLAU_CLAR;
        $pdf->SetFillColor($r,$g,$b);
        [$r,$g,$b] = self::C_BLAU;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect(15, $y, 180, 20, 'DF');

        // Etiqueta "Alumne/a"
        [$r,$g,$b] = self::C_BLAU_FOSC;
        $pdf->SetFillColor($r,$g,$b);
        $pdf->Rect(15, $y, 30, 20, 'F');

        $pdf->SetFont('Helvetica', 'B', 9);
        [$r,$g,$b] = self::C_BLANC;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetXY(15, $y + 6);
        $pdf->Cell(30, 6, self::u("ALUMNE/A"), 0, 0, 'C');

        // Dades
        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY(48, $y + 3);
        $pdf->Cell(34, 5, self::u('Nom i cognoms:'), 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(68, 5, self::u($alumne), 0, 0);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(14, 5, self::u('Classe:'), 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, self::u($classe), 0, 1);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY(48, $y + 11);
        $pdf->Cell(22, 5, self::u('Tutor/a:'), 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, self::u($tutor ?: '—'), 0, 1);

        $pdf->SetY($y + 24);

        // Restaurar
        [$r,$g,$b] = self::C_GRIS_LINIA;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->SetLineWidth(0.2);
    }

    // ---------------------------------------------------------------
    // Bloc del codi de lot
    // ---------------------------------------------------------------
    private static function blocLot(FPDF $pdf, string $codiLot): void {
        $pdf->Ln(2);
        $y = $pdf->GetY();

        [$r,$g,$b] = self::C_VERD;
        $pdf->SetFillColor($r,$g,$b);
        $pdf->Rect(15, $y, 180, 10, 'F');

        $pdf->SetFont('Helvetica', 'B', 10);
        [$r,$g,$b] = self::C_BLANC;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetXY(15, $y + 2);
        $pdf->Cell(50, 6, self::u('Codi de lot:'), 0, 0, 'R');
        $pdf->Cell(0, 6, '  ' . self::u($codiLot), 0, 1, 'L');

        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetY($y + 14);
    }

    // ---------------------------------------------------------------
    // Títol de secció
    // ---------------------------------------------------------------
    private static function titolSeccio(FPDF $pdf, string $text, array $color = self::C_BLAU_FOSC): void {
        $pdf->Ln(2);
        [$r,$g,$b] = $color;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 7, self::u($text), 0, 1, 'L');

        $pdf->SetDrawColor($r,$g,$b);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(3);

        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
        [$r,$g,$b] = self::C_GRIS_LINIA;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->SetLineWidth(0.2);
    }

    // ---------------------------------------------------------------
    // Capçalera taula exemplars (préstec o devolució)
    // ---------------------------------------------------------------
    private static function capTaulaExemplars(FPDF $pdf, bool $ambEstatFinal): void {
        [$r,$g,$b] = self::C_BLAU;
        $pdf->SetFillColor($r,$g,$b);
        [$r,$g,$b] = self::C_BLANC;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetFont('Helvetica', 'B', 8);

        if ($ambEstatFinal) {
            $pdf->Cell(32, 7, self::u('Codi'), 1, 0, 'C', true);
            $pdf->Cell(60, 7, self::u('Títol'), 1, 0, 'C', true);
            $pdf->Cell(24, 7, self::u('Estat inicial'), 1, 0, 'C', true);
            $pdf->Cell(24, 7, self::u('Estat final'), 1, 0, 'C', true);
            $pdf->Cell(40, 7, self::u('Observacions'), 1, 1, 'C', true);
        } else {
            $pdf->Cell(32, 7, self::u('Codi'), 1, 0, 'C', true);
            $pdf->Cell(60, 7, self::u('Títol'), 1, 0, 'C', true);
            $pdf->Cell(24, 7, self::u('Matèria'), 1, 0, 'C', true);
            $pdf->Cell(24, 7, self::u('Estat'), 1, 0, 'C', true);
            $pdf->Cell(40, 7, self::u('Desperfectes previs'), 1, 1, 'C', true);
        }

        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
    }

    // ---------------------------------------------------------------
    // Fila taula préstec
    // ---------------------------------------------------------------
    private static function filaTaulaExemplars(FPDF $pdf, string $codi, string $titol,
        string $materia, string $estat, string $desperfectes, int $fila): void {

        $fill = ($fila % 2 === 0);
        [$r,$g,$b] = $fill ? self::C_GRIS_CLAR : self::C_BLANC;
        $pdf->SetFillColor($r,$g,$b);

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(32, 6, self::u($codi), 1, 0, 'L', $fill);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(60, 6, self::u(self::truncar($titol, 38)), 1, 0, 'L', $fill);
        $pdf->Cell(24, 6, self::u($materia), 1, 0, 'C', $fill);
        $pdf->Cell(24, 6, self::u($estat), 1, 0, 'C', $fill);
        $pdf->Cell(40, 6, self::u(self::truncar($desperfectes, 26)), 1, 1, 'L', $fill);
    }

    // ---------------------------------------------------------------
    // Capçalera taula devolució
    // ---------------------------------------------------------------
    private static function filaTaulaDevolucio(FPDF $pdf, array $ex, int $fila): void {
        self::filaTaulaExemplars(
            $pdf,
            $ex['codi'],
            $ex['titol'],
            ucfirst($ex['estat_inicial'] ?? ''),
            ucfirst($ex['estat_final']   ?? ''),
            $ex['desperfectes_final']    ?? '',
            $fila
        );
    }

    // ---------------------------------------------------------------
    // Capçalera + fila taula no retornats
    // ---------------------------------------------------------------
    private static function capTaulaNoRetornats(FPDF $pdf): void {
        [$r,$g,$b] = self::C_VERMELL;
        $pdf->SetFillColor($r,$g,$b);
        [$r,$g,$b] = self::C_BLANC;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(45, 7, self::u('Codi'), 1, 0, 'C', true);
        $pdf->Cell(100, 7, self::u('Títol'), 1, 0, 'C', true);
        $pdf->Cell(0,  7, self::u('Motiu'), 1, 1, 'C', true);
        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
    }

    private static function filaTaulaNoRetornats(FPDF $pdf, array $ex, int $fila): void {
        $fill = ($fila % 2 === 0);
        [$r,$g,$b] = $fill ? [255, 235, 238] : self::C_BLANC; // rosa clar / blanc
        $pdf->SetFillColor($r,$g,$b);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(45, 6, self::u($ex['codi']), 1, 0, 'L', $fill);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(100, 6, self::u(self::truncar($ex['titol'], 60)), 1, 0, 'L', $fill);
        $pdf->Cell(0,   6, self::u($ex['motiu']), 1, 1, 'C', $fill);
    }

    // ---------------------------------------------------------------
    // Capçalera + fila taula pendents de retorn
    // ---------------------------------------------------------------
    private static function capTaulaPendents(FPDF $pdf): void {
        [$r,$g,$b] = [180, 100, 0];
        $pdf->SetFillColor($r,$g,$b);
        [$r,$g,$b] = self::C_BLANC;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(45, 7, self::u('Codi'), 1, 0, 'C', true);
        $pdf->Cell(135, 7, self::u('Títol'), 1, 1, 'C', true);
        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
    }

    private static function filaTaulaPendents(FPDF $pdf, array $ex, int $fila): void {
        $fill = ($fila % 2 === 0);
        [$r,$g,$b] = $fill ? [255, 243, 224] : self::C_BLANC;
        $pdf->SetFillColor($r,$g,$b);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(45, 6, self::u($ex['codi']), 1, 0, 'L', $fill);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(135, 6, self::u(self::truncar($ex['titol'], 80)), 1, 1, 'L', $fill);
    }

    // ---------------------------------------------------------------
    // Nota de conformitat
    // ---------------------------------------------------------------
    private static function notaConformitat(FPDF $pdf, string $tipus): void {
        $texts = [
            'prestec'    => "L'alumne/a i el/la professor/a confirmen amb la seva signatura que els exemplars detallats han estat lliurats en l'estat indicat. L'alumne/a es compromet a retornar-los en el mateix estat al final del curs.",
            'devolucio'  => "La firma d'ambdues parts confirma que s'han revisat els exemplars i el seu estat de conservació en el moment de la devolució. Qualsevol discrepància s'ha de comunicar en el termini de 48 hores.",
            'incidencia' => "Les signatures acrediten que la incidència descrita ha estat comunicada i acceptada per ambdues parts. Una còpia d'aquest document serà lliurada a la família.",
        ];
        $text = $texts[$tipus] ?? '';

        $pdf->SetFont('Helvetica', 'I', 7);
        [,$g,$b] = [100,100,100];
        $pdf->SetTextColor(100,100,100);
        [$r,$g,$b] = [240,240,245];
        $pdf->SetFillColor($r,$g,$b);
        $pdf->MultiCell(180, 4.5, self::u($text), 0, 'L');
        $pdf->Ln(4);

        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
    }

    // ---------------------------------------------------------------
    // Zona de firmes (dos quadres costat a costat)
    // ---------------------------------------------------------------
    private static function zonaFirmes(FPDF $pdf, string $responsable = ''): void {
        // Si queda poc espai, nova pàgina
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
        }

        $y = $pdf->GetY();

        // Línia separadora
        [$r,$g,$b] = self::C_BLAU;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(15, $y, 195, $y);
        $y += 4;

        $pdf->SetFont('Helvetica', 'B', 9);
        [$r,$g,$b] = self::C_BLAU_FOSC;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetXY(15, $y);
        $pdf->Cell(0, 5, self::u('Signatures'), 0, 1, 'L');
        $y += 7;

        [$r,$g,$b] = self::C_GRIS_LINIA;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->SetLineWidth(0.2);

        // Caixa esquerra: professor/a
        $pdf->Rect(15, $y, 84, 42);
        $pdf->SetFont('Helvetica', 'B', 8);
        [$r,$g,$b] = self::C_BLAU_FOSC;
        $pdf->SetFillColor($r,$g,$b);
        $pdf->Rect(15, $y, 84, 8, 'F');
        [$r,$g,$b] = self::C_BLANC;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetXY(15, $y + 1);
        $pdf->Cell(84, 6, self::u('PROFESSOR/A RESPONSABLE'), 0, 1, 'C');

        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetFont('Helvetica', '', 8);

        $pdf->SetXY(18, $y + 10);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(30, 4, self::u('Nom i cognoms:'), 0, 0);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(0, 4, self::u($responsable), 0, 1);
        [$r,$g,$b] = self::C_GRIS_LINIA;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->Line(18, $y + 22, 96, $y + 22);

        $pdf->SetXY(18, $y + 28);
        $pdf->Cell(30, 4, self::u('Data:'), 0, 0);
        $pdf->Line(35, $y + 34, 96, $y + 34);

        // Caixa dreta: alumne/a o familiar
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->Rect(111, $y, 84, 42);
        $pdf->SetFont('Helvetica', 'B', 8);
        [$r,$g,$b] = self::C_BLAU_FOSC;
        $pdf->SetFillColor($r,$g,$b);
        $pdf->Rect(111, $y, 84, 8, 'F');
        [$r,$g,$b] = self::C_BLANC;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetXY(111, $y + 1);
        $pdf->Cell(84, 6, self::u("ALUMNE/A O FAMILIAR"), 0, 1, 'C');

        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
        $pdf->SetFont('Helvetica', '', 8);

        $pdf->SetXY(114, $y + 12);
        $pdf->Cell(30, 4, self::u('Nom i cognoms:'), 0, 1);
        [$r,$g,$b] = self::C_GRIS_LINIA;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->Line(114, $y + 22, 192, $y + 22);

        $pdf->SetXY(114, $y + 28);
        $pdf->Cell(30, 4, self::u('Data:'), 0, 0);
        $pdf->Line(131, $y + 34, 192, $y + 34);

        // Peu de pàgina
        $pdf->SetY($y + 48);
        $pdf->SetFont('Helvetica', 'I', 7);
        $pdf->SetTextColor(150,150,150);
        $pdf->Cell(0, 4,
            self::u('Document generat automaticament per ' . APP_NAME . ' — ' . date('d/m/Y H:i') . ' — ' . ANY_ESCOLAR),
            0, 1, 'C');

        // Restaurar colors
        [$r,$g,$b] = self::C_TEXT;
        $pdf->SetTextColor($r,$g,$b);
        [$r,$g,$b] = self::C_GRIS_LINIA;
        $pdf->SetDrawColor($r,$g,$b);
        $pdf->SetLineWidth(0.2);
    }

    // ---------------------------------------------------------------
    // Desar PDF a disc
    // ---------------------------------------------------------------
    private static function desar(FPDF $pdf, string $tipus, int $alumneId): string {
        if (!is_dir(PDF_DIR)) {
            mkdir(PDF_DIR, 0755, true);
        }
        $nom  = $tipus . '_' . $alumneId . '_' . date('YmdHis') . '.pdf';
        $pdf->Output('F', PDF_DIR . $nom);
        return $nom;
    }
}
