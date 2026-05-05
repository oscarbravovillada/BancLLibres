<?php
// vendor/PdfGenerator.php
// Genera els tres tipus d'albarà (préstec, devolució, incidència)
// Usa FPDF manual (sense Composer)

require_once __DIR__ . '/fpdf/fpdf.php';

class PdfGenerator {

    private const CENTRE = 'Institut Tecnològic';
    private const ANY    = ANY_ESCOLAR;

    // ---------------------------------------------------------------
    // Funció per convertir UTF-8 → ISO-8859-1 (FPDF no suporta UTF-8)
    // ---------------------------------------------------------------
    private static function utf($text) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    }

    // ----------------------------------------------------------------
    // ALBARÀ DE PRÉSTEC
    // ----------------------------------------------------------------
    public static function albaraPrestec(array $dades): string {
        $pdf = self::nouDocument();

        self::capcalera($pdf, "ALBARÀ DE PRÉSTEC", $dades['data'] ?? date('d/m/Y H:i'));
        self::seccioAlumne($pdf, $dades['alumne'], $dades['classe'] ?? '', $dades['tutor'] ?? '');

        if (!empty($dades['lot_codi'])) {
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(0, 6, self::utf('Codi de lot: ') . self::utf($dades['lot_codi']), 0, 1);
            $pdf->Ln(2);
        }

        self::capcaleraExemplars($pdf);
        foreach ($dades['exemplars'] as $ex) {
            self::filaExemplar(
                $pdf,
                $ex['codi'],
                $ex['titol'],
                $ex['materia'],
                $ex['estat_inicial'],
                $ex['desperfectes_inici'] ?? ''
            );
        }

        $pdf->Ln(8);
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->MultiCell(0, 5,
            self::utf("L'alumne/a confirma haver rebut els exemplars en l'estat descrit."),
            0, 'L'
        );

        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(90, 6, self::utf('Signatura del professor/a:'), 0, 0);
        $pdf->Cell(90, 6, self::utf("Signatura de l'alumne/a:"), 0, 1);
        $pdf->Ln(10);
        $pdf->Cell(90, 0, '_________________________', 0, 0);
        $pdf->Cell(90, 0, '_________________________', 0, 1);

        return self::desar($pdf, 'prestec', $dades['alumne_id']);
    }

    // ----------------------------------------------------------------
    // ALBARÀ DE DEVOLUCIÓ
    // ----------------------------------------------------------------
    public static function albaraDevolucio(array $dades): string {
        $pdf = self::nouDocument();

        self::capcalera($pdf, "ALBARÀ DE DEVOLUCIÓ", $dades['data'] ?? date('d/m/Y H:i'));
        self::seccioAlumne($pdf, $dades['alumne'], $dades['classe'] ?? '', $dades['tutor'] ?? '');

        if (!empty($dades['retornats'])) {
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Cell(0, 7, self::utf('Exemplars retornats'), 0, 1);
            self::capcaleraExemplars($pdf, true);

            foreach ($dades['retornats'] as $ex) {
                self::filaExemplarDevolucio($pdf, $ex);
            }

            $pdf->Ln(4);
        }

        if (!empty($dades['no_retornats'])) {
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor(180, 0, 0);
            $pdf->Cell(0, 7, self::utf('Exemplars NO retornats'), 0, 1);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell(45, 6, self::utf('Codi'), 1, 0, 'L', true);
            $pdf->Cell(100, 6, self::utf('Títol'), 1, 0, 'L', true);
            $pdf->Cell(40, 6, self::utf('Motiu'), 1, 1, 'L', true);

            $pdf->SetFont('Helvetica', '', 8);
            foreach ($dades['no_retornats'] as $ex) {
                $pdf->Cell(45, 6, self::utf($ex['codi']), 1, 0);
                $pdf->Cell(100, 6, self::utf(self::truncar($ex['titol'], 60)), 1, 0);
                $pdf->Cell(40, 6, self::utf($ex['motiu']), 1, 1);
            }

            $pdf->Ln(4);
        }

        return self::desar($pdf, 'devolucio', $dades['alumne_id']);
    }

    // ----------------------------------------------------------------
    // ALBARÀ D'INCIDÈNCIA
    // ----------------------------------------------------------------
    public static function alaraIncidencia(array $dades): string {
        $pdf = self::nouDocument();

        self::capcalera($pdf, "ALBARÀ D'INCIDÈNCIA", $dades['data'] ?? date('d/m/Y H:i'));
        self::seccioAlumne($pdf, $dades['alumne'], $dades['classe'] ?? '', $dades['tutor'] ?? '');

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 7, self::utf('Exemplar afectat'), 0, 1);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(40, 6, self::utf('Codi:'), 0, 0);
        $pdf->Cell(0, 6, self::utf($dades['exemplar_codi']), 0, 1);

        $pdf->Cell(40, 6, self::utf('Títol:'), 0, 0);
        $pdf->Cell(0, 6, self::utf($dades['exemplar_titol']), 0, 1);

        $pdf->Cell(40, 6, self::utf('Matèria:'), 0, 0);
        $pdf->Cell(0, 6, self::utf($dades['materia']), 0, 1);

        $pdf->Ln(4);

        return self::desar($pdf, 'incidencia', $dades['alumne_id']);
    }

    // ---------------------------------------------------------------
    // MÈTODES PRIVATS
    // ---------------------------------------------------------------

    private static function nouDocument(): FPDF {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 20);
        return $pdf;
    }

    private static function capcalera(FPDF $pdf, string $tipus, string $data): void {
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(0, 8, self::utf(self::CENTRE . ' — ' . self::ANY), 0, 1, 'C');

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetTextColor(30, 80, 160);
        $pdf->Cell(0, 8, self::utf($tipus), 0, 1, 'C');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, self::utf('Data i hora: ' . $data), 0, 1, 'R');

        $pdf->Ln(4);
        $pdf->SetDrawColor(30, 80, 160);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(4);
    }

    private static function seccioAlumne(FPDF $pdf, string $alumne, string $classe, string $tutor): void {
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 6, self::utf("Dades de l'alumne/a"), 0, 1);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(35, 5, self::utf('Nom:'), 0, 0);
        $pdf->Cell(0, 5, self::utf($alumne), 0, 1);

        $pdf->Cell(35, 5, self::utf('Classe:'), 0, 0);
        $pdf->Cell(0, 5, self::utf($classe), 0, 1);

        $pdf->Cell(35, 5, self::utf('Tutor/a:'), 0, 0);
        $pdf->Cell(0, 5, self::utf($tutor), 0, 1);

        $pdf->Ln(4);
    }

    private static function capcaleraExemplars(FPDF $pdf, bool $ambFinal = false): void {
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(30, 80, 160);
        $pdf->SetTextColor(255, 255, 255);

        if ($ambFinal) {
            $pdf->Cell(40, 6, self::utf('Codi'), 1, 0, 'L', true);
            $pdf->Cell(75, 6, self::utf('Títol'), 1, 0, 'L', true);
            $pdf->Cell(25, 6, self::utf('Estat inicial'), 1, 0, 'C', true);
            $pdf->Cell(25, 6, self::utf('Estat final'), 1, 0, 'C', true);
            $pdf->Cell(0, 6, self::utf('Desperfectes nous'), 1, 1, 'L', true);
        } else {
            $pdf->Cell(40, 6, self::utf('Codi'), 1, 0, 'L', true);
            $pdf->Cell(75, 6, self::utf('Títol'), 1, 0, 'L', true);
            $pdf->Cell(25, 6, self::utf('Matèria'), 1, 0, 'C', true);
            $pdf->Cell(25, 6, self::utf('Estat'), 1, 0, 'C', true);
            $pdf->Cell(0, 6, self::utf('Desperfectes preexistents'), 1, 1, 'L', true);
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    private static function filaExemplar(FPDF $pdf, string $codi, string $titol,
        string $materia, string $estat, string $desperfectes): void {

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(40, 5, self::utf($codi), 1, 0);
        $pdf->Cell(75, 5, self::utf(self::truncar($titol, 45)), 1, 0);
        $pdf->Cell(25, 5, self::utf($materia), 1, 0, 'C');
        $pdf->Cell(25, 5, self::utf(ucfirst($estat)), 1, 0, 'C');
        $pdf->Cell(0, 5, self::utf(self::truncar($desperfectes, 35)), 1, 1);
    }

    private static function filaExemplarDevolucio(FPDF $pdf, array $ex): void {
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(40, 5, self::utf($ex['codi']), 1, 0);
        $pdf->Cell(75, 5, self::utf(self::truncar($ex['titol'], 45)), 1, 0);
        $pdf->Cell(25, 5, self::utf(ucfirst($ex['estat_inicial'])), 1, 0, 'C');
        $pdf->Cell(25, 5, self::utf(ucfirst($ex['estat_final'])), 1, 0, 'C');
        $pdf->Cell(0, 5, self::utf(self::truncar($ex['desperfectes_final'] ?? '', 35)), 1, 1);
    }

    private static function desar(FPDF $pdf, string $tipus, int $alumneId): string {
        if (!is_dir(PDF_DIR)) {
            mkdir(PDF_DIR, 0755, true);
        }

        $nom = $tipus . '_' . $alumneId . '_' . date('YmdHis') . '.pdf';
        $ruta = PDF_DIR . $nom;

        $pdf->Output('F', $ruta);
        return $nom;
    }

    private static function truncar(string $text, int $max): string {
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
    }
}
