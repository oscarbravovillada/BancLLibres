<?php
// src/helpers/Codificacio.php
// Generació automàtica de codis permanents

class Codificacio {

    /**
     * Genera el codi d'un lot nou per a un alumne.
     * Format: LOT-[CURS]-[NUM zero-padded 3 dígits]
     * Exemple: LOT-1ASIR-007
     */
    public static function generarCodiLot(string $codiCurs): string {
        $sql = "SELECT COUNT(*) AS n FROM lots l
                JOIN cursos c ON c.id = l.curs_id
                WHERE c.codi = ?";
        $row = Database::fetchOne($sql, [$codiCurs]);
        $num = (int)($row['n'] ?? 0) + 1;
        return sprintf('LOT-%s-%03d', $codiCurs, $num);
    }

    /**
     * Genera el codi d'un exemplar de matèria comuna (dins d'un lot).
     * Format: LOT-[CURS]-[NUM_LOT]-[COD_MAT]-[NUM_EX]
     * Exemple: LOT-1ASIR-007-SI-001
     */
    public static function generarCodiExemplarComuna(
        string $codiLot,
        string $codiMateria
    ): string {
        // codiLot és p.ex. LOT-1ASIR-007
        $prefix = $codiLot . '-' . $codiMateria . '-';
        $sql = "SELECT COUNT(*) AS n FROM exemplars WHERE codi LIKE ?";
        $row = Database::fetchOne($sql, [$prefix . '%']);
        $num = (int)($row['n'] ?? 0) + 1;
        return $prefix . sprintf('%03d', $num);
    }

    /**
     * Genera el codi d'un exemplar d'optativa (sense lot).
     * Format: OPT-[CURS]-[COD_MAT]-[NUM_EX]
     * Exemple: OPT-1ASIR-ANG-003
     */
    public static function generarCodiExemplarOptativa(
        string $codiCurs,
        string $codiMateria
    ): string {
        $prefix = 'OPT-' . $codiCurs . '-' . $codiMateria . '-';
        $sql = "SELECT COUNT(*) AS n FROM exemplars WHERE codi LIKE ?";
        $row = Database::fetchOne($sql, [$prefix . '%']);
        $num = (int)($row['n'] ?? 0) + 1;
        return $prefix . sprintf('%03d', $num);
    }
}