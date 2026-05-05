<?php
// src/helpers/Codis.php
// Classe unificada per a generar codis automàtics

require_once __DIR__ . '/Database.php';

class Codis {

    /**
     * Genera el codi d'un lot:
     * LOT-[CURS]-[NUM 3 dígits]
     * Ex: LOT-1ASIR-007
     */
    public static function lot(string $codiCurs): string {
        $row = Database::fetchOne(
            "SELECT COUNT(*) AS c
             FROM lots l
             JOIN cursos cu ON l.curs_id = cu.id
             WHERE cu.codi = ?",
            [$codiCurs]
        );

        $num = str_pad(($row['c'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
        return "LOT-{$codiCurs}-{$num}";
    }


    /**
     * Genera el codi d'un exemplar dins d’un lot:
     * LOT-[CURS]-[NUM_LOT]-[COD_MAT]-[NUM_EX]
     * Ex: LOT-1ASIR-007-SI-001
     */
    public static function exemplarLot(string $codiLot, string $codiMateria): string {
        $prefix = "{$codiLot}-{$codiMateria}-";

        $row = Database::fetchOne(
            "SELECT COUNT(*) AS c
             FROM exemplars
             WHERE codi LIKE ?",
            [$prefix . '%']
        );

        $num = str_pad(($row['c'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
        return "{$prefix}{$num}";
    }


    /**
     * Genera el codi d’un exemplar d’optativa:
     * OPT-[CURS]-[COD_MAT]-[NUM_EX]
     * Ex: OPT-1ASIR-ANG-003
     */
    public static function exemplarOptativa(string $codiCurs, string $codiMateria): string {
        $prefix = "OPT-{$codiCurs}-{$codiMateria}-";

        $row = Database::fetchOne(
            "SELECT COUNT(*) AS c
             FROM exemplars
             WHERE codi LIKE ?",
            [$prefix . '%']
        );

        $num = str_pad(($row['c'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
        return "{$prefix}{$num}";
    }
}
