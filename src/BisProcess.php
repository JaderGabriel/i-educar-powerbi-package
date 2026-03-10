<?php

namespace iEducar\Packages\Bis;

use App\Process;

/**
 * IDs dos processos do menu BI.
 * Usa Process quando disponível; fallback para valores numéricos em instalações antigas.
 */
final class BisProcess
{
    public const MENU_BI = 9999200;

    public const BI_DASHBOARD = 9999199;

    public const BI_MATRICULAS = 9999201;

    public const BI_TURMAS = 9999202;

    public const BI_LANCAMENTOS = 9999203;

    public const BI_INDICADORES = 9999204;

    public const BI_INCLUSAO_DIVERSIDADE = 9999205;

    public const BI_BUSCA_ATIVA = 9999206;

    public const BI_EDUCACENSO = 9999207;

    public static function menuBi(): int
    {
        return defined('App\Process::MENU_BI') ? Process::MENU_BI : self::MENU_BI;
    }

    public static function dashboard(): int
    {
        return defined('App\Process::BI_DASHBOARD') ? Process::BI_DASHBOARD : self::BI_DASHBOARD;
    }

    public static function matriculas(): int
    {
        return defined('App\Process::BI_MATRICULAS') ? Process::BI_MATRICULAS : self::BI_MATRICULAS;
    }

    public static function turmas(): int
    {
        return defined('App\Process::BI_TURMAS') ? Process::BI_TURMAS : self::BI_TURMAS;
    }

    public static function lancamentos(): int
    {
        return defined('App\Process::BI_LANCAMENTOS') ? Process::BI_LANCAMENTOS : self::BI_LANCAMENTOS;
    }

    public static function indicadores(): int
    {
        return defined('App\Process::BI_INDICADORES') ? Process::BI_INDICADORES : self::BI_INDICADORES;
    }

    public static function inclusaoDiversidade(): int
    {
        return defined('App\Process::BI_INCLUSAO_DIVERSIDADE') ? Process::BI_INCLUSAO_DIVERSIDADE : self::BI_INCLUSAO_DIVERSIDADE;
    }

    public static function buscaAtiva(): int
    {
        return defined('App\Process::BI_BUSCA_ATIVA') ? Process::BI_BUSCA_ATIVA : self::BI_BUSCA_ATIVA;
    }

    public static function educacenso(): int
    {
        return defined('App\Process::BI_EDUCACENSO') ? Process::BI_EDUCACENSO : self::BI_EDUCACENSO;
    }

    /** @return int[] */
    public static function all(): array
    {
        return [
            self::menuBi(),
            self::dashboard(),
            self::matriculas(),
            self::turmas(),
            self::lancamentos(),
            self::indicadores(),
            self::inclusaoDiversidade(),
            self::buscaAtiva(),
            self::educacenso(),
        ];
    }
}
