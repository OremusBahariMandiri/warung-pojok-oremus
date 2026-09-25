<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class CodeGenerator
{
    /**
     * Generate a unique sequential code for any model.
     *
     * Format: {PREFIX}{separator}{NUMBER}
     * Contoh: PRD-00001, RST-00042
     *
     * @param  string  $modelClass   Eloquent model class, e.g. Products::class
     * @param  string  $column       Kolom yang dicek keunikannya, e.g. 'prod_code'
     * @param  string  $prefix       Prefix statis, e.g. 'PRD', 'RST'
     * @param  int     $padLength    Panjang zero-pad angka, default 5
     * @param  string  $separator    Pemisah prefix dan angka, default '-'
     * @return string
     */
    public static function generateSequential(
        string $modelClass,
        string $column,
        string $prefix,
        int $padLength = 5,
        string $separator = '-'
    ): string {
        $prefix = strtoupper(trim($prefix));

        $latest = $modelClass::where($column, 'like', "{$prefix}{$separator}%")
            ->orderByRaw("CAST(SUBSTRING({$column}, ?) AS UNSIGNED) DESC", [
                strlen($prefix) + strlen($separator) + 1,
            ])
            ->value($column);

        if ($latest) {
            $parts   = explode($separator, $latest, 2);
            $lastNum = isset($parts[1]) ? (int) $parts[1] : 0;
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        $code = $prefix . $separator . str_pad($nextNum, $padLength, '0', STR_PAD_LEFT);

        // Guard race condition
        while ($modelClass::where($column, $code)->exists()) {
            $nextNum++;
            $code = $prefix . $separator . str_pad($nextNum, $padLength, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    /**
     * Generate unique code dari nama entitas (random suffix).
     *
     * Format: {PREFIX_DARI_NAMA}{separator}{RANDOM}
     * Contoh: NUT-A3X2, MIN-KQ9Z
     *
     * @param  string  $modelClass
     * @param  string  $column
     * @param  string  $nameValue    Nama sumber prefix, e.g. nama produk
     * @param  int     $prefixLength Berapa karakter diambil dari nama
     * @param  int     $randomLength Panjang suffix random
     * @param  string  $separator
     * @return string
     */
    public static function generateFromName(
        string $modelClass,
        string $column,
        string $nameValue,
        int $prefixLength = 3,
        int $randomLength = 4,
        string $separator = '-'
    ): string {
        $clean  = preg_replace('/[^A-Za-z0-9]/', '', $nameValue);
        $prefix = strtoupper(substr($clean, 0, $prefixLength));

        if (strlen($prefix) < $prefixLength) {
            $prefix = str_pad($prefix, $prefixLength, 'X');
        }

        do {
            $suffix = strtoupper(Str::random($randomLength));
            $code   = "{$prefix}{$separator}{$suffix}";
        } while ($modelClass::where($column, $code)->exists());

        return $code;
    }

    /**
     * Generate prod_code sequential untuk produk.
     * Format default: PRD-00001
     *
     * @param  string  $modelClass
     * @param  string  $prefix      Default 'PRD'
     * @param  string  $codeColumn  Default 'prod_code'
     * @return string
     */
    public static function generateProductCode(
        string $modelClass,
        string $prefix = 'PRD',
        string $codeColumn = 'prod_code'
    ): string {
        return self::generateSequential(
            modelClass: $modelClass,
            column: $codeColumn,
            prefix: $prefix,
            padLength: 5,
            separator: '-'
        );
    }

    /**
     * Generate invoice number untuk restock (pembelian).
     *
     * Format: INV/{YEAR}/{MONTH}/{NOMOR_URUT}
     * Contoh: INV/2025/01/00001, INV/2025/01/00002
     * Nomor urut reset setiap ganti bulan.
     *
     * @param  string  $modelClass   Model Restock::class
     * @param  string  $column       Kolom invoice, e.g. 'invoice_number'
     * @param  string  $prefix       Default 'INV'
     * @param  int     $padLength    Panjang zero-pad, default 5
     * @return string
     */
    public static function generateInvoiceNumber(
        string $modelClass,
        string $column,
        string $prefix = 'INV',
        int $padLength = 5
    ): string {
        $year  = now()->format('Y');
        $month = now()->format('m');

        // Pattern prefix bulan ini: INV/2025/01
        $monthPrefix = "{$prefix}/{$year}/{$month}";

        $latest = $modelClass::where($column, 'like', "{$monthPrefix}/%")
            ->orderByRaw("CAST(SUBSTRING_INDEX({$column}, '/', -1) AS UNSIGNED) DESC")
            ->value($column);

        if ($latest) {
            $lastNum = (int) substr($latest, strrpos($latest, '/') + 1);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        $invoiceNumber = $monthPrefix . '/' . str_pad($nextNum, $padLength, '0', STR_PAD_LEFT);

        // Guard race condition
        while ($modelClass::where($column, $invoiceNumber)->exists()) {
            $nextNum++;
            $invoiceNumber = $monthPrefix . '/' . str_pad($nextNum, $padLength, '0', STR_PAD_LEFT);
        }

        return $invoiceNumber;
    }
}